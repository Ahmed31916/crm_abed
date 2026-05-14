<?php

namespace App\Http\Controllers;

use App\Models\TaskStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaskStatusController extends Controller
{
    public function index(Request $request)
    {
        $query = TaskStatus::query()->where('created_by', createdBy());

        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Handle sorting
        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts=['id', 'name', 'created_at'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $perPage = $request->get('per_page', 10);
        $taskStatuses = $query->paginate((int)$perPage);

        return Inertia::render('task-statuses/index', [
            'taskStatuses' => $taskStatuses,
            'filters' => $request->only(['search', 'status', 'per_page', 'sort_field', 'sort_direction'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:task_statuses,name,NULL,id,created_by,' . createdBy(),
            'color' => 'required|string|max:7',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['created_by'] = createdBy();

        TaskStatus::create($validated);

        return redirect()->back()->with('success', __('Task status created successfully'));
    }

    public function update(Request $request, TaskStatus $taskStatus)
    {
        if ($taskStatus->created_by !== createdBy()) {
            return redirect()->back()->with('error', __('Unauthorized'));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:task_statuses,name,' . $taskStatus->id . ',id,created_by,' . createdBy(),
            'color' => 'required|string|max:7',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $taskStatus->update($validated);

        return redirect()->back()->with('success', __('Task status updated successfully'));
    }

    public function destroy(TaskStatus $taskStatus)
    {
        if ($taskStatus->created_by !== createdBy()) {
            return redirect()->back()->with('error', __('Unauthorized'));
        }

        // Check if any tasks are using this status
        $taskCount = \App\Models\ProjectTask::where('task_status_id', $taskStatus->id)->count();

        if ($taskCount > 0) {
            return redirect()->back()->with('error', __('Cannot delete task status. ' . $taskCount . ' task(s) are using this status.'));
        }

        $taskStatus->delete();

        return redirect()->back()->with('success', __('Task status deleted successfully'));
    }

    public function toggleStatus($taskStatusId)
    {
        $taskStatus = TaskStatus::where('id', $taskStatusId)
            ->where('created_by', createdBy())
            ->first();

        if ($taskStatus) {
            try {
                $taskStatus->status = $taskStatus->status === 'active' ? 'inactive' : 'active';
                $taskStatus->save();

                return redirect()->back()->with('success', __('Task-Status status updated successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update Task-Status status'));
            }
        } else {
            return redirect()->back()->with('error', __('Task status not found.'));
        }

        $taskStatus->status = $taskStatus->status === 'active' ? 'inactive' : 'active';
        $taskStatus->save();

        return redirect()->back()->with('success', __('Task-Status updated successfully'));
    }
}
