<?php

namespace App\Http\Controllers;

use App\Models\CaseModel;
use App\Models\Account;
use App\Models\Contact;
use App\Exports\CaseExport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class CaseController extends Controller
{
    public function index(Request $request)
    {
        $query = CaseModel::query()
            ->with(['account', 'contact', 'assignedUser'])
            ->where(function ($q) {
                if (auth()->user()->type === 'company') {
                    $q->where('created_by', createdBy());
                } else {
                    $q->where('assigned_to', auth()->id());
                }
            });

        // Handle search
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('subject', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Handle account filter
        if ($request->has('account_id') && !empty($request->account_id) && $request->account_id !== 'all') {
            $query->where('account_id', $request->account_id);
        }

        // Handle priority filter
        if ($request->has('priority') && !empty($request->priority) && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        // Handle status filter
        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Handle case type filter
        if ($request->has('case_type') && !empty($request->case_type) && $request->case_type !== 'all') {
            $query->where('case_type', $request->case_type);
        }

        // Handle assigned to filter
        if ($request->has('assigned_to') && !empty($request->assigned_to) && $request->assigned_to !== 'all') {
            if ($request->assigned_to === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->assigned_to);
            }
        }

        // Handle sorting
        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts=['id', 'subject', 'created_at'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $perPage = $request->get('per_page', 10);
        $cases = $query->paginate((int)$perPage);

        // Get accounts for filter dropdown
        $accounts = Account::where('created_by', createdBy())
            ->where('status', 'active')
            ->when(auth()->user()->type !== 'company', function ($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->get(['id', 'name']);

        // Get contacts for dropdown
        $contacts = Contact::where('created_by', createdBy())
            ->where('status', 'active')
            ->when(auth()->user()->type !== 'company', function ($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->with('account:id,name')
            ->get(['id', 'name', 'account_id']);

        // Get users for assignment dropdown (only for company users)
        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        return Inertia::render('cases/index', [
            'cases' => $cases,
            'accounts' => $accounts,
            'contacts' => $contacts,
            'users' => $users,
            'filters' => $request->all(['search', 'account_id', 'priority', 'status', 'case_type', 'assigned_to', 'sort_field', 'sort_direction', 'per_page', 'view', 'page']),
        ]);
    }

    public function create()
    {
        $accounts = Account::where('created_by', createdBy())
            ->where('status', 'active')
            ->when(auth()->user()->type !== 'company', function ($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->get(['id', 'name']);

        $contacts = Contact::where('created_by', createdBy())
            ->where('status', 'active')
            ->when(auth()->user()->type !== 'company', function ($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->with('account:id,name')
            ->get(['id', 'name', 'account_id']);

        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        return Inertia::render('cases/create', [
            'accounts' => $accounts,
            'contacts' => $contacts,
            'users' => $users
        ]);
    }

    public function show($id)
    {
        $case = CaseModel::with(['account', 'contact', 'assignedUser', 'creator'])
            ->where('created_by', createdBy())
            ->findOrFail($id);

        $parentMeetings = \App\Models\Meeting::with(['assignedUser'])
            ->where('parent_module', 'case')
            ->where('parent_id', $id)
            ->where('created_by', createdBy())
            ->get();

        // Get related calls
        $parentCalls = \App\Models\Call::with(['assignedUser'])
            ->where('parent_module', 'case')
            ->where('parent_id', $id)
            ->where('created_by', createdBy())
            ->get()
            ->map(function ($call) {
                $call->type = 'call';
                return $call;
            });

        $meetings = $parentMeetings->merge($parentCalls)->sortByDesc('start_date')->values();

        return Inertia::render('cases/show', [
            'case' => $case,
            'meetings' => $meetings
        ]);
    }

    public function edit($id)
    {
        $case = CaseModel::with(['account', 'contact', 'assignedUser', 'creator'])
            ->where('created_by', createdBy())
            ->findOrFail($id);

        $accounts = Account::where('created_by', createdBy())
            ->where('status', 'active')
            ->when(auth()->user()->type !== 'company', function ($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->get(['id', 'name']);

        $contacts = Contact::where('created_by', createdBy())
            ->where('status', 'active')
            ->when(auth()->user()->type !== 'company', function ($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->with('account:id,name')
            ->get(['id', 'name', 'account_id']);

        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        return Inertia::render('cases/edit', [
            'case' => $case,
            'accounts' => $accounts,
            'contacts' => $contacts,
            'users' => $users
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'status' => 'nullable|in:new,in_progress,pending,resolved,closed',
            'case_type' => 'nullable|in:support,bug,feature_request,complaint,inquiry',
            'account_id' => 'required|exists:accounts,id',
            'contact_id' => 'required|exists:contacts,id',
            'assigned_to' => auth()->user()->type == 'company' ? 'required|exists:users,id' : 'nullable|exists:users,id',
        ]);

        $validated['created_by'] = createdBy();
        $validated['priority'] = $validated['priority'] ?? 'medium';
        $validated['status'] = $validated['status'] ?? 'new';
        $validated['case_type'] = $validated['case_type'] ?? 'support';

        // Auto-assign to current user if staff user
        if (auth()->user()->type !== 'company') {
            $validated['assigned_to'] = auth()->id();
        }

        $case = CaseModel::create($validated);
        if ($case && !IsDemo()) {
            event(new \App\Events\CaseCreated($case));
        }

        // Check for errors and combine them
        $emailError = session()->pull('email_error');
        $twilioError = session()->pull('twilio_error');

        $errors = [];
        if ($emailError) {
            $errors[] = __('Email send failed: ') . $emailError;
        }
        if ($twilioError) {
            $errors[] = __('SMS send failed: ') . $twilioError;
        }

        if (!empty($errors)) {
            $message = __('Case created successfully, but ') . implode(', ', $errors);
            return redirect()->back()->with('warning', $message);
        }

        return redirect()->back()->with('success', __('Case created successfully.'));
    }

    public function update(Request $request, $caseId)
    {
        $case = CaseModel::where('id', $caseId)
            ->where('created_by', createdBy())
            ->first();

        if ($case) {
            try {
                $validated = $request->validate([
                    'subject' => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'priority' => 'nullable|in:low,medium,high,urgent',
                    'status' => 'nullable|in:new,in_progress,pending,resolved,closed',
                    'case_type' => 'nullable|in:support,bug,feature_request,complaint,inquiry',
                    'account_id' => 'required|exists:accounts,id',
                    'contact_id' => 'required|exists:contacts,id',
                    'assigned_to' => auth()->user()->type == 'company' ? 'required|exists:users,id' : 'nullable|exists:users,id',
                ]);

                $validated['priority'] = $validated['priority'] ?? $case->priority;
                $validated['status'] = $validated['status'] ?? $case->status;
                $validated['case_type'] = $validated['case_type'] ?? $case->case_type;

                // Auto-assign to current user if staff user
                if (auth()->user()->type !== 'company') {
                    $validated['assigned_to'] = auth()->id();
                }

                $case->update($validated);

                return redirect()->back()->with('success', __('Case updated successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update case.'));
            }
        } else {
            return redirect()->back()->with('error', __('Case not found.'));
        }
    }

    public function destroy($caseId)
    {
        $case = CaseModel::where('id', $caseId)
            ->where('created_by', createdBy())
            ->first();

        if ($case) {
            try {
                $case->delete();
                return redirect()->back()->with('success', __('Case deleted successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete case.'));
            }
        } else {
            return redirect()->back()->with('error', __('Case not found.'));
        }
    }

    public function toggleStatus($caseId)
    {
        $case = CaseModel::where('id', $caseId)
            ->where('created_by', createdBy())
            ->first();

        if ($case) {
            try {
                $case->status = $case->status === 'new' ? 'closed' : 'new';
                $case->save();

                return redirect()->back()->with('success', __('Case status updated successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update case status.'));
            }
        } else {
            return redirect()->back()->with('error', __('Case not found.'));
        }
    }

    public function fileExport()
    {
        if (!auth()->user()->can('export-cases')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $name = 'case_' . date('Y-m-d i:h:s');
        return Excel::download(new CaseExport(), $name . '.xlsx');
    }
}
