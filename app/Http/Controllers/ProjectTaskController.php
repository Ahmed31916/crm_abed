<?php

namespace App\Http\Controllers;

use App\Models\ProjectTask;
use App\Models\Project;
use App\Models\User;
use App\Models\TaskStatus;
use App\Exports\ProjectTaskExport;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ProjectTaskController extends Controller
{
    public function index(Request $request)
    {
        $query = ProjectTask::query()
            ->with(['project', 'assignedUser', 'creator', 'parent', 'taskStatus'])
            ->where(function ($q) {
                if (auth()->user()->type === 'company') {
                    $q->where('created_by', createdBy());
                } else {
                    $q->where('assigned_to', auth()->id());
                }
            });

        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
            $query->where('task_status_id', $request->status);
        }

        if ($request->has('priority') && !empty($request->priority) && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        if ($request->has('project_id') && !empty($request->project_id) && $request->project_id !== 'all') {
            $query->where('project_id', $request->project_id);
        }

        if ($request->has('assigned_to') && !empty($request->assigned_to) && $request->assigned_to !== 'all') {
            $query->where('assigned_to', $request->assigned_to);
        }

        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts=['id', 'title'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $perPage = $request->get('per_page', 10);
        $tasks = $query->paginate((int)$perPage);

        $projects = Project::where('created_by', createdBy())->where('status', 'active')
            ->when(auth()->user()->type !== 'company', function ($q) {
                $q->where('assigned_to', auth()->id());
            })->get(['id', 'name']);

        $users = [];
        if (auth()->user()->type === 'company') {
            $users = User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        $parentTasks = [];

        $taskStatuses = TaskStatus::where('created_by', createdBy())
            ->where('status', 'active')
            ->select('id', 'name', 'color')
            ->get();

        return Inertia::render('project-tasks/index', [
            'tasks' => $tasks,
            'projects' => $projects,
            'users' => $users,
            'parentTasks' => $parentTasks,
            'taskStatuses' => $taskStatuses,
            'filters' => $request->all(['search', 'status', 'priority', 'project_id', 'assigned_to', 'sort_field', 'sort_direction', 'per_page','view']),
        ]);
    }

    public function show($id)
    {
        $task = ProjectTask::with(['project', 'assignedUser', 'creator', 'parent', 'subtasks.assignedUser', 'taskStatus'])
            ->where('created_by', createdBy())
            ->findOrFail($id);

        $taskStatuses = TaskStatus::where('created_by', createdBy())
            ->where('status', 'active')
            ->select('id', 'name', 'color')
            ->get();

        return Inertia::render('project-tasks/show', [
            'task' => $task,
            'taskStatuses' => $taskStatuses,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'required|exists:projects,id',
            'parent_id' => 'nullable|exists:project_tasks,id',
            'assigned_to' => auth()->user()->type == 'company' ? 'required|exists:users,id' : 'nullable|exists:users,id',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'task_status_id' => 'required|integer|exists:task_statuses,id',
            'estimated_hours' => 'nullable|numeric|min:0',
            'actual_hours' => 'nullable|numeric|min:0',
            'progress' => 'nullable|integer|min:0|max:100',
        ]);

        $validated['created_by'] = createdBy();

        if (auth()->user()->type != 'company') {
            $validated['assigned_to'] = auth()->id();
        } else {
            // Convert empty string or 'unassigned' to null
            if (empty($validated['assigned_to']) || $validated['assigned_to'] === 'unassigned') {
                $validated['assigned_to'] = null;
            }
        }

        // Set default task status if not provided
        if (!isset($validated['task_status_id'])) {
            $defaultStatus = TaskStatus::where('created_by', createdBy())
                ->where('name', 'To Do')
                ->first();
            if ($defaultStatus) {
                $validated['task_status_id'] = $defaultStatus->id;
            }
        }

        $task = ProjectTask::create($validated);
        if (isEmailTemplateEnabled('Task Assigned', createdBy()) && $task && $task->assigned_to && !IsDemo()) {
            event(new \App\Events\TaskAssigned($task));
        }

        return redirect()->back()->with('success', __('Task created successfully.'));
    }

    public function update(Request $request, $taskId)
    {
        $task = ProjectTask::where('id', $taskId)
            ->where('created_by', createdBy())
            ->first();

        if ($task) {
            try {
                $validated = $request->validate([
                    'title' => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'project_id' => 'required|exists:projects,id',
                    'parent_id' => 'nullable|exists:project_tasks,id',
                    'assigned_to' => auth()->user()->type == 'company' ? 'required|exists:users,id' : 'nullable|exists:users,id',
                    'start_date' => 'nullable|date',
                    'due_date' => 'nullable|date|after_or_equal:start_date',
                    'priority' => 'nullable|in:low,medium,high,urgent',
                    'task_status_id' => 'required|integer',
                    'estimated_hours' => 'nullable|numeric|min:0',
                    'actual_hours' => 'nullable|numeric|min:0',
                    'progress' => 'nullable|integer|min:0|max:100',
                ]);

                if (auth()->user()->type != 'company') {
                    $validated['assigned_to'] = auth()->id();
                } else {
                    // Convert empty string or 'unassigned' to null
                    if (empty($validated['assigned_to']) || $validated['assigned_to'] === 'unassigned') {
                        $validated['assigned_to'] = null;
                    }
                }

                // Validate task_status_id belongs to current user
                if (!empty($validated['task_status_id'])) {
                    $statusExists = TaskStatus::where('id', $validated['task_status_id'])
                        ->where('created_by', createdBy())
                        ->where('status', 'active')
                        ->exists();

                    if (!$statusExists) {
                        return redirect()->back()->with('error', __('Invalid task status.'));
                    }
                }

                $task->update($validated);

                return redirect()->back()->with('success', __('Task updated successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update task.'));
            }
        } else {
            return redirect()->back()->with('error', __('Task not found.'));
        }
    }

    public function destroy($taskId)
    {
        $task = ProjectTask::with('taskStatus')
            ->where('id', $taskId)
            ->where('created_by', createdBy())
            ->first();

        if ($task) {
            // Prevent deletion if task is in specific status
            if ($task->taskStatus && in_array($task->taskStatus->name, ['In Progress', 'Review'])) {
                return redirect()->back()->with('error', __('Cannot delete task in ' . $task->taskStatus->name . ' status.'));
            }

            try {
                $task->delete();
                return redirect()->back()->with('success', __('Task deleted successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete task.'));
            }
        } else {
            return redirect()->back()->with('error', __('Task not found.'));
        }
    }

    public function toggleStatus($taskId)
    {
        $task = ProjectTask::where('id', $taskId)
            ->where('created_by', createdBy())
            ->first();

        if ($task) {
            try {
                // Get To Do and Done status IDs
                $toDoStatus = TaskStatus::where('created_by', createdBy())->where('name', 'To Do')->first();
                $doneStatus = TaskStatus::where('created_by', createdBy())->where('name', 'Done')->first();

                if (!$toDoStatus || !$doneStatus) {
                    return redirect()->back()->with('error', __('Required task statuses not found.'));
                }

                $newStatusId = $task->task_status_id === $toDoStatus->id ? $doneStatus->id : $toDoStatus->id;
                $task->update(['task_status_id' => $newStatusId]);

                return redirect()->back()->with('success', __('Task status updated successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update task status.'));
            }
        } else {
            return redirect()->back()->with('error', __('Task not found.'));
        }
    }

    public function kanban($projectId)
    {
        $project = Project::where('id', $projectId)
            ->where('created_by', createdBy())
            ->firstOrFail();

        $statuses = TaskStatus::where('created_by', createdBy())
            ->where('status', 'active')
            ->select('id', 'name', 'color')
            ->get()
            ->map(function ($status) {
                return [
                    'id' => $status->id,
                    'name' => $status->name,
                    'color' => $status->color
                ];
            })
            ->toArray();

        $tasks = ProjectTask::with(['assignedUser', 'creator', 'taskStatus'])
            ->where('project_id', $projectId)
            ->where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function ($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'priority' => $task->priority,
                    'progress' => $task->progress,
                    'start_date' => $task->start_date,
                    'due_date' => $task->due_date,
                    'task_status_id' => $task->task_status_id,
                    'assigned_user' => $task->assignedUser,
                    'created_at' => $task->created_at,
                ];
            })
            ->groupBy('task_status_id');

        $kanbanData = [];
        foreach ($statuses as $status) {
            $kanbanData[$status['id']] = [
                'status' => $status,
                'tasks' => $tasks->get($status['id'], collect())->values()->toArray()
            ];
        }

        $users = [];
        if (auth()->user()->type === 'company') {
            $users = User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        $allTaskStatuses = TaskStatus::where('created_by', createdBy())
            ->where('status', 'active')
            ->select('id', 'name', 'color')
            ->get();

        return Inertia::render('projects/kanban', [
            'project' => $project,
            'kanbanData' => $kanbanData,
            'statuses' => $statuses,
            'users' => $users,
            'taskStatuses' => $allTaskStatuses,
        ]);
    }

    public function gantt($projectId)
    {
        $project = Project::where('id', $projectId)
            ->where('created_by', createdBy())
            ->firstOrFail();

        if (IsDemo()) {
            $tasks = [
                [
                    'id' => 1,
                    'title' => 'Project Planning',
                    'description' => 'Initial project planning and requirements gathering',
                    'start_date' => now()->subDays(10)->format('Y-m-d'),
                    'due_date' => now()->subDays(5)->format('Y-m-d'),
                    'priority' => 'high',
                    'progress' => 100,
                    'task_status' => ['id' => 1, 'name' => 'Done', 'color' => '#10b981'],
                    'assigned_user' => ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']
                ],
                [
                    'id' => 2,
                    'title' => 'Design Phase',
                    'description' => 'UI/UX design and mockups',
                    'start_date' => now()->subDays(5)->format('Y-m-d'),
                    'due_date' => now()->addDays(2)->format('Y-m-d'),
                    'priority' => 'high',
                    'progress' => 75,
                    'task_status' => ['id' => 2, 'name' => 'In Progress', 'color' => '#3b82f6'],
                    'assigned_user' => ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']
                ],
                [
                    'id' => 3,
                    'title' => 'Backend Development',
                    'description' => 'API development and database setup',
                    'start_date' => now()->format('Y-m-d'),
                    'due_date' => now()->addDays(15)->format('Y-m-d'),
                    'priority' => 'urgent',
                    'progress' => 30,
                    'task_status' => ['id' => 2, 'name' => 'In Progress', 'color' => '#3b82f6'],
                    'assigned_user' => ['id' => 3, 'name' => 'Mike Johnson', 'email' => 'mike@example.com']
                ],
                [
                    'id' => 4,
                    'title' => 'Frontend Development',
                    'description' => 'React components and pages',
                    'start_date' => now()->addDays(3)->format('Y-m-d'),
                    'due_date' => now()->addDays(20)->format('Y-m-d'),
                    'priority' => 'high',
                    'progress' => 0,
                    'task_status' => ['id' => 3, 'name' => 'To Do', 'color' => '#6b7280'],
                    'assigned_user' => ['id' => 4, 'name' => 'Sarah Williams', 'email' => 'sarah@example.com']
                ],
                [
                    'id' => 5,
                    'title' => 'Testing & QA',
                    'description' => 'Quality assurance and bug fixes',
                    'start_date' => now()->addDays(18)->format('Y-m-d'),
                    'due_date' => now()->addDays(25)->format('Y-m-d'),
                    'priority' => 'medium',
                    'progress' => 0,
                    'task_status' => ['id' => 3, 'name' => 'To Do', 'color' => '#6b7280'],
                    'assigned_user' => ['id' => 5, 'name' => 'Tom Brown', 'email' => 'tom@example.com']
                ],
                [
                    'id' => 6,
                    'title' => 'Deployment',
                    'description' => 'Production deployment and monitoring',
                    'start_date' => now()->addDays(25)->format('Y-m-d'),
                    'due_date' => now()->addDays(30)->format('Y-m-d'),
                    'priority' => 'high',
                    'progress' => 0,
                    'task_status' => ['id' => 3, 'name' => 'To Do', 'color' => '#6b7280'],
                    'assigned_user' => ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']
                ]
            ];
        } else {
            $tasks = ProjectTask::with(['assignedUser', 'creator', 'taskStatus'])
                ->where('project_id', $projectId)
                ->where('created_by', createdBy())
                ->when(auth()->user()->type !== 'company', function ($q) {
                    $q->where('assigned_to', auth()->id());
                })
                ->orderBy('start_date')
                ->get();
        }

        $users = [];
        if (auth()->user()->type === 'company') {
            $users = User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        $taskStatuses = TaskStatus::where('created_by', createdBy())
            ->where('status', 'active')
            ->select('id', 'name', 'color')
            ->get();

        return Inertia::render('projects/gantt', [
            'project' => $project,
            'tasks' => $tasks,
            'users' => $users,
            'taskStatuses' => $taskStatuses,
        ]);
    }

    public function updateStatus($taskId)
    {
        $task = ProjectTask::where('id', $taskId)
            ->where('created_by', createdBy())
            ->first();

        if (!$task) {
            return back()->with('error', __('Task not found.'));
        }

        $validated = request()->validate([
            'task_status_id' => 'required|exists:task_statuses,id'
        ]);

        $task->update(['task_status_id' => $validated['task_status_id']]);

        return back()->with('success', __('Task status updated successfully.'));
    }

    public function getParentTasks($projectId)
    {
        $parentTasks = ProjectTask::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function ($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->where('project_id', $projectId)
            ->whereNull('parent_id')
            ->select('id', 'title')
            ->get();

        return response()->json($parentTasks);
    }

    public function fileExport()
    {
        if (!auth()->user()->can('export-project-tasks')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $name = 'project_tasks_' . date('Y-m-d_H-i-s');
        return Excel::download(new ProjectTaskExport(), $name . '.xlsx');
    }

    public function getProjectDetails($projectId)
    {
        $parentTasks = ProjectTask::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function ($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->where('project_id', $projectId)
            ->whereNull('parent_id')
            ->select('id', 'title')
            ->get();

        return response()->json([
            'parent_tasks' => $parentTasks
        ]);
    }
}
