<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\Lead;
use App\Models\Contact;
use App\Models\CallAttendee;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CallController extends Controller
{
    public function index(Request $request)
    {
        $query = Call::query()
            ->with(['creator', 'assignedUser', 'attendees'])
            ->where(function ($q) {
                if (auth()->user()->type === 'company') {
                    $q->where('created_by', createdBy());
                } else {
                    $userId = auth()->id();

                    $q->where('assigned_to', $userId)
                        ->orWhereHas('attendees', function ($attendeeQuery) use ($userId) {

                            $attendeeQuery->where(function ($query) use ($userId) {

                                // User attendee
                                $query->where(function ($q1) use ($userId) {
                                    $q1->where('attendee_type', 'user')
                                        ->where('attendee_id', $userId);
                                })

                                    // Lead attendee
                                    ->orWhere(function ($q2) use ($userId) {
                                        $q2->where('attendee_type', 'lead')
                                            ->whereIn('attendee_id', function ($leadQuery) use ($userId) {
                                                $leadQuery->select('id')
                                                    ->from('leads')
                                                    ->where('assigned_to', $userId);
                                            });
                                    })

                                    // Contact attendee
                                    ->orWhere(function ($q3) use ($userId) {
                                        $q3->where('attendee_type', 'contact')
                                            ->whereIn('attendee_id', function ($contactQuery) use ($userId) {
                                                $contactQuery->select('id')
                                                    ->from('contacts')
                                                    ->where('assigned_to', $userId);
                                            });
                                    });
                            });
                        });
                }
            });

        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('assigned_to') && !empty($request->assigned_to) && $request->assigned_to !== 'all') {
            if ($request->assigned_to === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->assigned_to);
            }
        }

        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts=['id', 'title', 'start_date', 'created_at'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $perPage = $request->get('per_page', 10);
        $calls = $query->paginate((int)$perPage);

        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        return Inertia::render('calls/index', [
            'calls' => $calls,
            'users' => $users,
            'filters' => $request->all(['search', 'status', 'assigned_to', 'sort_field', 'sort_direction', 'per_page']),
            'settings' => settings(createdBy()),
        ]);
    }

    public function show($id)
    {
        $call = Call::with(['creator', 'assignedUser'])
            ->where('created_by', createdBy())
            ->findOrFail($id);

        $call->load(['attendees' => function ($query) {
            $query->with(['call']);
        }]);

        foreach ($call->attendees as $attendee) {
            switch ($attendee->attendee_type) {
                case 'user':
                    $attendee->attendee = \App\Models\User::find($attendee->attendee_id);
                    break;
                case 'contact':
                    $attendee->attendee = \App\Models\Contact::find($attendee->attendee_id);
                    break;
                case 'lead':
                    $attendee->attendee = \App\Models\Lead::find($attendee->attendee_id);
                    break;
            }
        }

        if ($call->parent_module && $call->parent_id) {
            switch ($call->parent_module) {
                case 'lead':
                    $call->parent_record = \App\Models\Lead::find($call->parent_id);
                    break;
                case 'account':
                    $call->parent_record = \App\Models\Account::find($call->parent_id);
                    break;
                case 'contact':
                    $call->parent_record = \App\Models\Contact::find($call->parent_id);
                    break;
                case 'opportunity':
                    $call->parent_record = \App\Models\Opportunity::find($call->parent_id);
                    break;
                case 'case':
                    $call->parent_record = \App\Models\CaseModel::find($call->parent_id);
                    break;
                case 'project':
                    $call->parent_record = \App\Models\Project::find($call->parent_id);
                    break;
            }
        }

        return Inertia::render('calls/show', [
            'call' => $call,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:65535',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'parent_module' => 'required|in:none,lead,account,contact,opportunity,case,project',
            'parent_id' => 'required|integer|min:1',
            'status' => 'nullable|in:planned,held,not_held',
            'assigned_to' => auth()->user()->type == 'company' ? 'required|exists:users,id' : 'nullable|exists:users,id',
            'attendees' => 'required|array|min:1',
            'attendees.*.type' => 'required_with:attendees|in:user,contact,lead',
            'attendees.*.id' => 'required_with:attendees|integer|min:1',
            'sync_with_google_calendar' => 'nullable|boolean',
        ]);

        // Validate end datetime is after start datetime
        $startDate = \Carbon\Carbon::parse($validated['start_date'])->format('Y-m-d');
        $endDate = \Carbon\Carbon::parse($validated['end_date'])->format('Y-m-d');
        $startDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $startDate . ' ' . $validated['start_time']);
        $endDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $endDate . ' ' . $validated['end_time']);

        if ($endDateTime->lte($startDateTime)) {
            return redirect()->back()->withErrors(['end_time' => __('End date and time must be after start date and time.')])->withInput();
        }

        // Check attendee availability
        if (isset($validated['attendees'])) {
            foreach ($validated['attendees'] as $attendee) {
                $callConflict = CallAttendee::where('attendee_type', $attendee['type'])
                    ->where('attendee_id', $attendee['id'])
                    ->whereHas('call', function ($q) use ($startDateTime, $endDateTime) {
                        $q->where('created_by', createdBy())
                            ->where(function ($query) use ($startDateTime, $endDateTime) {
                                $query->whereRaw("CONCAT(start_date, ' ', start_time) < ?", [$endDateTime->format('Y-m-d H:i')])
                                    ->whereRaw("CONCAT(end_date, ' ', end_time) > ?", [$startDateTime->format('Y-m-d H:i')]);
                            });
                    })
                    ->first();

                $meetingConflict = \App\Models\MeetingAttendee::where('attendee_type', $attendee['type'])
                    ->where('attendee_id', $attendee['id'])
                    ->whereHas('meeting', function ($q) use ($startDateTime, $endDateTime) {
                        $q->where('created_by', createdBy())
                            ->where(function ($query) use ($startDateTime, $endDateTime) {
                                $query->whereRaw("CONCAT(start_date, ' ', start_time) < ?", [$endDateTime->format('Y-m-d H:i')])
                                    ->whereRaw("CONCAT(end_date, ' ', end_time) > ?", [$startDateTime->format('Y-m-d H:i')]);
                            });
                    })
                    ->first();

                if ($callConflict || $meetingConflict) {
                    $attendeeName = $this->getAttendeeName($attendee['type'], $attendee['id']);
                    return redirect()->back()->withErrors(['attendees' => __(':name is already scheduled for another call or meeting during this time.', ['name' => $attendeeName])])->withInput();
                }
            }
        }

        $validated['created_by'] = createdBy();
        $validated['status'] = $validated['status'] ?? 'planned';

        // Clean up nullable fields
        $validated['parent_module'] = ($validated['parent_module'] ?? null) === 'none' ? null : ($validated['parent_module'] ?? null);
        $validated['parent_id'] = empty($validated['parent_id']) || $validated['parent_id'] === 'select' ? null : (int)$validated['parent_id'];
        $validated['assigned_to'] = empty($validated['assigned_to']) || $validated['assigned_to'] === 'unassigned' ? null : (int)$validated['assigned_to'];

        // Validate parent_id exists if parent_module is set
        if ($validated['parent_module'] && $validated['parent_id']) {
            $this->validateParentRecord($validated['parent_module'], $validated['parent_id']);
        }

        if (auth()->user()->type !== 'company') {
            $validated['assigned_to'] = auth()->id();
        }
        $call = Call::create($validated);

        if ($call && $request->sync_with_google_calendar) {
            $calendarService = new GoogleCalendarService();
            $eventId = $calendarService->createEvent($call, createdBy(), 'call');
            if ($eventId) {
                $call->update(['google_calendar_event_id' => $eventId]);
            }
        }

        if (isset($validated['attendees'])) {
            foreach ($validated['attendees'] as $attendee) {
                CallAttendee::create([
                    'call_id' => $call->id,
                    'attendee_type' => $attendee['type'],
                    'attendee_id' => $attendee['id'],
                ]);
            }
        }

        // Create activity for parent modules
        if ($call->parent_module && $call->parent_id) {
            switch ($call->parent_module) {
                case 'account':
                    \App\Models\AccountActivity::create([
                        'account_id' => $call->parent_id,
                        'user_id' => auth()->id(),
                        'activity_type' => 'Call Created',
                        'title' => auth()->user()->name . ' created a call: ' . $call->title,
                        'description' => 'Call scheduled for ' . date('M j, Y', strtotime($call->start_date)) . ' at ' . date('g:i A', strtotime($call->start_time)),
                        'created_by' => createdBy()
                    ]);
                    break;
                case 'lead':
                    \App\Models\LeadActivity::create([
                        'lead_id' => $call->parent_id,
                        'user_id' => auth()->id(),
                        'activity_type' => 'Call Created',
                        'title' => auth()->user()->name . ' created a call: ' . $call->title,
                        'description' => 'Call scheduled for ' . date('M j, Y', strtotime($call->start_date)) . ' at ' . date('g:i A', strtotime($call->start_time)),
                        'created_by' => createdBy()
                    ]);
                    break;
                case 'opportunity':
                    \App\Models\OpportunityActivity::create([
                        'opportunity_id' => $call->parent_id,
                        'user_id' => auth()->id(),
                        'activity_type' => 'Call Created',
                        'title' => auth()->user()->name . ' created a call: ' . $call->title,
                        'description' => 'Call scheduled for ' . date('M j, Y', strtotime($call->start_date)) . ' at ' . date('g:i A', strtotime($call->start_time)),
                        'created_by' => createdBy()
                    ]);
                    break;
            }
        }

        // Create activity for attendees
        if (isset($validated['attendees'])) {
            foreach ($validated['attendees'] as $attendee) {
                switch ($attendee['type']) {
                    case 'contact':
                        $contact = \App\Models\Contact::find($attendee['id']);
                        if ($contact && $contact->account_id) {
                            \App\Models\AccountActivity::create([
                                'account_id' => $contact->account_id,
                                'user_id' => auth()->id(),
                                'activity_type' => 'Call Attendee',
                                'title' => auth()->user()->name . ' added ' . $contact->name . ' to call: ' . $call->title,
                                'description' => 'Contact added as attendee to call scheduled for ' . date('M j, Y', strtotime($call->start_date)),
                                'created_by' => createdBy()
                            ]);
                        }
                        break;
                    case 'lead':
                        $lead = \App\Models\Lead::find($attendee['id']);
                        if ($lead) {
                            \App\Models\LeadActivity::create([
                                'lead_id' => $lead->id,
                                'user_id' => auth()->id(),
                                'activity_type' => 'Call Attendee',
                                'title' => auth()->user()->name . ' added ' . $lead->name . ' to call: ' . $call->title,
                                'description' => 'Lead added as attendee to call scheduled for ' . date('M j, Y', strtotime($call->start_date)),
                                'created_by' => createdBy()
                            ]);
                        }
                        break;
                }
            }
        }

        return redirect()->back()->with('success', __('Call created successfully.'));
    }

    public function update(Request $request, $callId)
    {
        $call = Call::where('id', $callId)
            ->where('created_by', createdBy())
            ->firstOrFail();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:65535',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'parent_module' => 'required|in:none,lead,account,contact,opportunity,case,project',
            'parent_id' => 'required|integer|min:1',
            'status' => 'nullable|in:planned,held,not_held',
            'assigned_to' => auth()->user()->type == 'company' ? 'required|exists:users,id' : 'nullable|exists:users,id',
            'attendees' => 'required|array|min:1',
            'attendees.*.type' => 'required_with:attendees|in:user,contact,lead',
            'attendees.*.id' => 'required_with:attendees|integer|min:1',
            'sync_with_google_calendar' => 'nullable|boolean',
        ]);

        // Validate end datetime is after start datetime
        $startDate = \Carbon\Carbon::parse($validated['start_date'])->format('Y-m-d');
        $endDate = \Carbon\Carbon::parse($validated['end_date'])->format('Y-m-d');
        $startDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $startDate . ' ' . $validated['start_time']);
        $endDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $endDate . ' ' . $validated['end_time']);

        if ($endDateTime->lte($startDateTime)) {
            return redirect()->back()->withErrors(['end_time' => __('End date and time must be after start date and time.')])->withInput();
        }

        // Check attendee availability
        if (isset($validated['attendees'])) {
            foreach ($validated['attendees'] as $attendee) {
                $callConflict = CallAttendee::where('attendee_type', $attendee['type'])
                    ->where('attendee_id', $attendee['id'])
                    ->whereHas('call', function ($q) use ($startDateTime, $endDateTime, $callId) {
                        $q->where('created_by', createdBy())
                            ->where('id', '!=', $callId)
                            ->where(function ($query) use ($startDateTime, $endDateTime) {
                                $query->whereRaw("CONCAT(start_date, ' ', start_time) < ?", [$endDateTime->format('Y-m-d H:i')])
                                    ->whereRaw("CONCAT(end_date, ' ', end_time) > ?", [$startDateTime->format('Y-m-d H:i')]);
                            });
                    })
                    ->first();

                $meetingConflict = \App\Models\MeetingAttendee::where('attendee_type', $attendee['type'])
                    ->where('attendee_id', $attendee['id'])
                    ->whereHas('meeting', function ($q) use ($startDateTime, $endDateTime) {
                        $q->where('created_by', createdBy())
                            ->where(function ($query) use ($startDateTime, $endDateTime) {
                                $query->whereRaw("CONCAT(start_date, ' ', start_time) < ?", [$endDateTime->format('Y-m-d H:i')])
                                    ->whereRaw("CONCAT(end_date, ' ', end_time) > ?", [$startDateTime->format('Y-m-d H:i')]);
                            });
                    })
                    ->first();

                if ($callConflict || $meetingConflict) {
                    $attendeeName = $this->getAttendeeName($attendee['type'], $attendee['id']);
                    return redirect()->back()->withErrors(['attendees' => __(':name is already scheduled for another call or meeting during this time.', ['name' => $attendeeName])])->withInput();
                }
            }
        }

        // Clean up nullable fields
        $validated['parent_module'] = ($validated['parent_module'] ?? null) === 'none' ? null : ($validated['parent_module'] ?? null);
        $validated['parent_id'] = empty($validated['parent_id']) || $validated['parent_id'] === 'select' ? null : (int)$validated['parent_id'];
        $validated['assigned_to'] = empty($validated['assigned_to']) || $validated['assigned_to'] === 'unassigned' ? null : (int)$validated['assigned_to'];

        // Validate parent_id exists if parent_module is set
        if ($validated['parent_module'] && $validated['parent_id']) {
            $this->validateParentRecord($validated['parent_module'], $validated['parent_id']);
        }

        if (auth()->user()->type !== 'company') {
            $validated['assigned_to'] = auth()->id();
        }

        $call->update($validated);

        if ($call->google_calendar_event_id) {
            $calendarService = new GoogleCalendarService();
            $calendarService->updateEvent($call->google_calendar_event_id, $call, createdBy(), 'call');
        }

        $call->attendees()->delete();
        if (isset($validated['attendees'])) {
            foreach ($validated['attendees'] as $attendee) {
                CallAttendee::create([
                    'call_id' => $call->id,
                    'attendee_type' => $attendee['type'],
                    'attendee_id' => $attendee['id'],
                ]);
            }
        }

        return redirect()->back()->with('success', __('Call updated successfully'));
    }

    public function destroy($callId)
    {
        $call = Call::where('id', $callId)
            ->where('created_by', createdBy())
            ->firstOrFail();

        // Delete from Google Calendar if synced
        if ($call->google_calendar_event_id) {
            $calendarService = new GoogleCalendarService();
            $calendarService->deleteEvent($call->google_calendar_event_id, createdBy());
        }

        $call->delete();
        return redirect()->back()->with('success', __('Call deleted successfully'));
    }

    public function toggleStatus($callId)
    {
        $call = Call::where('id', $callId)
            ->where('created_by', createdBy())
            ->firstOrFail();

        $call->status = $call->status === 'planned' ? 'held' : 'planned';
        $call->save();

        return redirect()->back()->with('success', __('Call status updated successfully'));
    }

    public function getParentModuleRecords($module)
    {
        $records = [];

        switch ($module) {
            case 'lead':
                $records = \App\Models\Lead::where('created_by', createdBy())
                    ->where('status', 'active')
                    ->select('id', 'name')
                    ->get();
                break;
            case 'account':
                $records = \App\Models\Account::where('created_by', createdBy())
                    ->where('status', 'active')
                    ->select('id', 'name')
                    ->get();
                break;
            case 'contact':
                $records = \App\Models\Contact::where('created_by', createdBy())
                    ->where('status', 'active')
                    ->select('id', 'name')
                    ->get();
                break;
            case 'opportunity':
                $records = \App\Models\Opportunity::where('created_by', createdBy())
                    ->where('status', 'active')
                    ->select('id', 'name')
                    ->get();
                break;
            case 'case':
                $records = \App\Models\CaseModel::where('created_by', createdBy())
                    ->whereNotIn('status', ['closed'])
                    ->select('id', 'subject as name')
                    ->get();
                break;
            case 'project':
                $records = \App\Models\Project::where('created_by', createdBy())
                    ->where('status', 'active')
                    ->select('id', 'name')
                    ->get();
                break;
        }

        return response()->json($records);
    }

    public function getAttendeeRecords($type)
    {
        $records = [];

        switch ($type) {
            case 'user':
                $records = \App\Models\User::where('created_by', createdBy())
                    ->select('id', 'name', 'email')
                    ->get()
                    ->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                        ];
                    });
                break;
            case 'contact':
                $records = \App\Models\Contact::where('created_by', createdBy())
                    ->where('status', 'active')
                    ->select('id', 'name', 'email')
                    ->get()
                    ->map(function ($contact) {
                        return [
                            'id' => $contact->id,
                            'name' => $contact->name,
                        ];
                    });
                break;
            case 'lead':
                $records = \App\Models\Lead::where('created_by', createdBy())
                    ->where('status', 'active')
                    ->select('id', 'name', 'email')
                    ->get()
                    ->map(function ($lead) {
                        return [
                            'id' => $lead->id,
                            'name' => $lead->name,
                        ];
                    });
                break;
        }

        return response()->json($records);
    }

    /**
     * Validate that parent record exists
     */
    private function validateParentRecord($module, $id)
    {
        $exists = false;

        switch ($module) {
            case 'lead':
                $exists = \App\Models\Lead::where('id', $id)->where('created_by', createdBy())->exists();
                break;
            case 'account':
                $exists = \App\Models\Account::where('id', $id)->where('created_by', createdBy())->exists();
                break;
            case 'contact':
                $exists = \App\Models\Contact::where('id', $id)->where('created_by', createdBy())->exists();
                break;
            case 'opportunity':
                $exists = \App\Models\Opportunity::where('id', $id)->where('created_by', createdBy())->exists();
                break;
            case 'case':
                $exists = \App\Models\CaseModel::where('id', $id)->where('created_by', createdBy())->exists();
                break;
            case 'project':
                $exists = \App\Models\Project::where('id', $id)->where('created_by', createdBy())->exists();
                break;
        }

        if (!$exists) {
            throw new \Illuminate\Validation\ValidationException(
                \Illuminate\Validation\Validator::make([], [])
                    ->errors()
                    ->add('parent_id', 'The selected parent record does not exist.')
            );
        }
    }

    private function getAttendeeName($type, $id)
    {
        switch ($type) {
            case 'user':
                $user = \App\Models\User::find($id);
                return $user ? $user->name : 'Unknown';
            case 'contact':
                $contact = \App\Models\Contact::find($id);
                return $contact ? $contact->name : 'Unknown';
            case 'lead':
                $lead = \App\Models\Lead::find($id);
                return $lead ? $lead->name : 'Unknown';
            default:
                return 'Unknown';
        }
    }
}
