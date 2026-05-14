<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        // Check if demo mode is enabled
        if (IsDemo()) {
            return Inertia::render('calendar/index', [
                'events' => getDemoCalendarData(),
                'settings' => settings(createdBy()),
            ]);
        }

        $events = collect();

        // Get meetings
        try {
            $meetings = \App\Models\Meeting::where('created_by', createdBy())
                ->when(auth()->user()->type !== 'company', function($q) {
                    $q->where(function($query) {
                        $query->where('assigned_to', auth()->id())
                              ->orWhereHas('attendees', function($attendeeQuery) {
                                  $attendeeQuery->where('attendee_id', auth()->id());
                              });
                    });
                })
                ->get()
                ->map(function($meeting) {
                    $startDateTime = $meeting->start_date->format('Y-m-d') . ' ' . $meeting->start_time->format('H:i:s');
                    $endDateTime = $meeting->end_date->format('Y-m-d') . ' ' . $meeting->end_time->format('H:i:s');
                    return [
                        'id' => 'meeting-' . $meeting->id,
                        'title' => $meeting->title,
                        'start' => $startDateTime,
                        'end' => $endDateTime,
                        'type' => 'meeting',
                        'backgroundColor' => '#3b82f6',
                        'borderColor' => '#2563eb',
                        'meeting_id' => $meeting->id,
                        'description' => $meeting->description,
                        'location' => $meeting->location,
                        'status' => $meeting->status,
                        'parent_name' => $meeting->parent_name ?? ($meeting->parent_type ? ucfirst(str_replace('_', ' ', $meeting->parent_type)) : null),
                        'startDateTime' => $meeting->start_time->format('H:i:s'),
                        'endDateTime' => $meeting->end_time->format('H:i:s')
                    ];
                });
            $events = $events->merge($meetings);
        } catch (\Exception $e) {
            // Skip if Meeting model doesn't exist
        }

        // Get calls
        try {
            $calls = \App\Models\Call::where('created_by', createdBy())
                ->when(auth()->user()->type !== 'company', function($q) {
                    $q->where(function($query) {
                        $query->where('assigned_to', auth()->id())
                              ->orWhereHas('attendees', function($attendeeQuery) {
                                  $attendeeQuery->where('attendee_id', auth()->id());
                              });
                    });
                })
                ->get()
                ->map(function($call) {
                    $startDateTime = $call->start_date->format('Y-m-d') . ' ' . $call->start_time->format('H:i:s');
                    $endDateTime = $call->end_date->format('Y-m-d') . ' ' . $call->end_time->format('H:i:s');
                    return [
                        'id' => 'call-' . $call->id,
                        'title' => $call->title,
                        'start' => $startDateTime,
                        'end' => $endDateTime,
                        'type' => 'call',
                        'backgroundColor' => '#10b77f',
                        'borderColor' => '#059669',
                        'call_id' => $call->id,
                        'description' => $call->description,
                        'status' => $call->status,
                        'parent_name' => $call->parent_name ?? ($call->parent_type ? ucfirst(str_replace('_', ' ', $call->parent_type)) : null),
                        'startDateTime' => $call->start_time->format('H:i:s'),
                        'endDateTime' => $call->end_time->format('H:i:s')
                    ];
                });
            $events = $events->merge($calls);
        } catch (\Exception $e) {
            // Skip if Call model doesn't exist
        }

        // Get project tasks
        try {
            $tasks = \App\Models\ProjectTask::where('created_by', createdBy())
                ->with('project')
                ->whereNotNull('due_date')
                ->when(auth()->user()->type !== 'company', function($q) {
                    $q->where('assigned_to', auth()->id());
                })
                ->get()
                ->map(function($task) {
                    $taskName = $task->name ?? $task->title ?? 'Task #' . $task->id;
                    $projectName = $task->project ? $task->project->name : 'Unknown Project';
                    return [
                        'id' => 'task-' . $task->id,
                        'title' => $taskName,
                        'start' => $task->due_date,
                        'type' => 'task',
                        'backgroundColor' => '#f59e0b',
                        'borderColor' => '#d97706',
                        'task_id' => $task->id,
                        'project_id' => $task->project_id,
                        'description' => $task->description,
                        'status' => $task->status,
                        'parent_name' => $projectName
                    ];
                });
            $events = $events->merge($tasks);
        } catch (\Exception $e) {
            // Skip if ProjectTask model doesn't exist
        }

        return Inertia::render('calendar/index', [
            'events' => $events->values()->toArray(),
            'settings' => settings(createdBy()),
        ]);
    }
}
