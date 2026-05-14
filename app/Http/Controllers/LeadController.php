<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\LeadSource;
use App\Models\User;
use App\Exports\LeadExport;
use App\Imports\LeadImport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class LeadController extends Controller
{
    public function show($id)
    {
        $lead = Lead::with(['leadStatus', 'leadSource', 'assignedUser', 'creator', 'campaign.campaignType', 'accountIndustry', 'activities.user', 'comments.user'])
            ->where('created_by', createdBy())
            ->findOrFail($id);

        // Get related accounts if lead was converted
        $relatedAccounts = [];
        if ($lead->is_converted) {
            $relatedAccounts = \App\Models\Account::where('created_by', createdBy())
                ->where(function ($q) use ($lead) {
                    $q->where('name', 'like', '%' . $lead->company . '%')
                        ->orWhere('email', $lead->email);
                })
                ->with(['accountType', 'accountIndustry'])
                ->get();
        }

        // Get related contacts if lead was converted
        $relatedContacts = [];
        if ($lead->is_converted) {
            $relatedContacts = \App\Models\Contact::where('created_by', createdBy())
                ->where(function ($q) use ($lead) {
                    $q->where('name', $lead->name)
                        ->orWhere('email', $lead->email);
                })
                ->with(['account'])
                ->get();
        }

        // Get related meetings (both as parent and as attendee)
        $parentMeetings = \App\Models\Meeting::where('created_by', createdBy())
            ->where('parent_module', 'lead')
            ->where('parent_id', $id)
            ->with(['creator', 'assignedUser'])
            ->get();

        $attendeeMeetings = \App\Models\Meeting::where('created_by', createdBy())
            ->whereHas('attendees', function ($q) use ($id) {
                $q->where('attendee_type', 'lead')
                    ->where('attendee_id', $id);
            })
            ->with(['creator', 'assignedUser'])
            ->get();

        // Get related calls (both as parent and as attendee)
        $parentCalls = \App\Models\Call::where('created_by', createdBy())
            ->where('parent_module', 'lead')
            ->where('parent_id', $id)
            ->with(['creator', 'assignedUser'])
            ->get()
            ->map(function ($call) {
                $call->type = 'call';
                return $call;
            });

        $attendeeCalls = \App\Models\Call::where('created_by', createdBy())
            ->whereHas('attendees', function ($q) use ($id) {
                $q->where('attendee_type', 'lead')
                    ->where('attendee_id', $id);
            })
            ->with(['creator', 'assignedUser'])
            ->get()
            ->map(function ($call) {
                $call->type = 'call';
                return $call;
            });

        $meetings = $parentMeetings->merge($attendeeMeetings)->merge($parentCalls)->merge($attendeeCalls)->unique('id')->sortByDesc('start_date')->values();

        return Inertia::render('leads/show', [
            'lead' => $lead,
            'streamItems' => $lead->activities()->orderBy('created_at', 'asc')->get(),
            'comments' => $lead->comments,
            'relatedAccounts' => $relatedAccounts,
            'relatedContacts' => $relatedContacts,
            'meetings' => $meetings
        ]);
    }

    public function index(Request $request)
    {
        $query = Lead::query()
            ->with(['leadStatus', 'leadSource', 'assignedUser', 'campaign', 'accountIndustry'])
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
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('phone', 'like', '%' . $request->search . '%')
                    ->orWhere('company', 'like', '%' . $request->search . '%');
            });
        }



        // Handle filters
        if ($request->has('lead_status_id') && !empty($request->lead_status_id) && $request->lead_status_id !== 'all') {
            $query->where('lead_status_id', $request->lead_status_id);
        }

        if ($request->has('lead_source_id') && !empty($request->lead_source_id) && $request->lead_source_id !== 'all') {
            $query->where('lead_source_id', $request->lead_source_id);
        }

        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('is_converted') && $request->is_converted !== 'all') {
            $query->where('is_converted', $request->is_converted === '1');
        }

        if ($request->has('assigned_to') && !empty($request->assigned_to) && $request->assigned_to !== 'all') {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Handle sorting
        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts=['id', 'name', 'value', 'created_at'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        if ($request->view === 'kanban' || empty($request->view)) {
            $leads = collect(['data' => $query->get()]);
        } else {
            $perPage = $request->input('per_page', 10);
            $leads = $query->paginate((int)$perPage)->withQueryString();
        }

        // Get data for dropdowns
        $isCompany = auth()->user()->hasRole('company');

        $leadStatuses = LeadStatus::where('created_by', createdBy())->where('status', 'active')
            ->get(['id', 'name', 'color']);

        $leadSources = LeadSource::where('created_by', createdBy())->where('status', 'active')
            ->get(['id', 'name']);

        $accounts = \App\Models\Account::where('created_by', createdBy())->where('status', 'active')
            ->when(!$isCompany, function ($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->get(['id', 'name']);

        $campaigns = \App\Models\Campaign::where('created_by', createdBy())->where('status', 'active')
            ->when(!$isCompany, function ($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->get(['id', 'name']);

        $accountIndustries = \App\Models\AccountIndustry::where('created_by', createdBy())->where('status', 'active')->get(['id', 'name']);

        $accountTypes = \App\Models\AccountType::where('created_by', createdBy())->where('status', 'active')->get(['id', 'name']);

        $users = [];
        if ($isCompany) {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        return Inertia::render('leads/index', [
            'leads' => $leads,
            'leadStatuses' => $leadStatuses,
            'leadSources' => $leadSources,
            'accounts' => $accounts,
            'campaigns' => $campaigns,
            'accountIndustries' => $accountIndustries,
            'accountTypes' => $accountTypes,
            'users' => $users,
            'samplePath' => file_exists(storage_path('uploads/sample/sample-lead.xlsx')) ? route('lead.download.template') : null,
            'filters' => $request->all(['view', 'search', 'lead_status_id', 'lead_source_id', 'status', 'is_converted', 'assigned_to', 'sort_field', 'sort_direction', 'per_page', 'page']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:leads,email,NULL,id,created_by,' . createdBy(),
            'phone' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_industry_id' => 'required|exists:account_industries,id',
            'website' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'value' => 'nullable|numeric|min:0',
            'lead_status_id' => 'required|exists:lead_statuses,id',
            'lead_source_id' => 'required|exists:lead_sources,id',
            'campaign_id' => 'required|exists:campaigns,id',
            'status' => 'nullable|in:active,inactive',
            'assigned_to' => auth()->user()->type == 'company' ? 'required|exists:users,id' : 'nullable|exists:users,id',
        ]);

        $validated['created_by'] = createdBy();
        $validated['status'] = $validated['status'] ?? 'active';

        // Auto-assign to current user if staff user
        if (auth()->user()->type !== 'company') {
            $validated['assigned_to'] = auth()->id();
        }

        $lead = Lead::create($validated);
        if ($lead && !IsDemo()) {
            event(new \App\Events\LeadAssigned($lead));
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
            $message = __('Lead created successfully, but ') . implode(', ', $errors);
            return redirect()->back()->with('warning', $message);
        }

        return redirect()->back()->with('success', __('Lead created successfully.'));
    }

    public function update(Request $request, $leadId)
    {
        $lead = Lead::where('id', $leadId)
            ->where('created_by', createdBy())
            ->first();

        if ($lead) {
            try {
                $validated = $request->validate([
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|max:255|unique:leads,email,' . $leadId . ',id,created_by,' . createdBy(),
                    'phone' => 'nullable|string|max:255',
                    'company' => 'nullable|string|max:255',
                    'account_name' => 'nullable|string|max:255',
                    'account_industry_id' => 'required|exists:account_industries,id',
                    'website' => 'nullable|string|max:255',
                    'position' => 'nullable|string|max:255',
                    'address' => 'nullable|string',
                    'notes' => 'nullable|string',
                    'value' => 'nullable|numeric|min:0',
                    'lead_status_id' => 'required|exists:lead_statuses,id',
                    'lead_source_id' => 'required|exists:lead_sources,id',
                    'campaign_id' => 'required|exists:campaigns,id',
                    'status' => 'nullable|in:active,inactive',
                    'assigned_to' => auth()->user()->type == 'company' ? 'required|exists:users,id' : 'nullable|exists:users,id',
                ]);

                // Auto-assign to current user if staff user
                if (auth()->user()->type !== 'company') {
                    $validated['assigned_to'] = auth()->id();
                }

                $lead->fill($validated);

                if (isEmailTemplateEnabled('Lead Moved', createdBy()) && $lead->isDirty('lead_status_id')) {
                    $old = $lead->getOriginal('lead_status_id');
                    $new = $lead->lead_status_id;
                    $oldStatusName = \App\Models\LeadStatus::find($old)?->name ?? 'N/A';
                    $newStatusName = \App\Models\LeadStatus::find($new)?->name ?? 'N/A';
                    if (!IsDemo()) {
                        event(new \App\Events\LeadStatusChanged($lead, $oldStatusName, $newStatusName));
                    }
                }
                $lead->update($validated);

                return redirect()->back()->with('success', __('Lead updated successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update lead.'));
            }
        } else {
            return redirect()->back()->with('error', __('Lead not found.'));
        }
    }

    public function destroy($leadId)
    {
        $lead = Lead::where('id', $leadId)
            ->where('created_by', createdBy())
            ->first();

        if ($lead) {
            try {
                $lead->delete();
                return redirect()->back()->with('success', __('Lead deleted successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete lead.'));
            }
        } else {
            return redirect()->back()->with('error', __('Lead not found.'));
        }
    }

    public function toggleStatus($leadId)
    {
        $lead = Lead::where('id', $leadId)
            ->where('created_by', createdBy())
            ->first();

        if ($lead) {
            try {
                $lead->status = $lead->status === 'active' ? 'inactive' : 'active';
                $lead->save();

                return redirect()->back()->with('success', __('Lead status updated successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update lead status.'));
            }
        } else {
            return redirect()->back()->with('error', __('Lead not found.'));
        }
    }

    public function deleteActivities($id)
    {
        $lead = Lead::where('id', $id)
            ->where('created_by', createdBy())
            ->firstOrFail();

        \App\Models\LeadActivity::where('lead_id', $lead->id)->delete();

        return redirect()->back()->with('success', __('All activities deleted successfully.'));
    }

    public function deleteActivity($leadId, $activityId)
    {
        $lead = Lead::where('id', $leadId)
            ->where('created_by', createdBy())
            ->firstOrFail();

        \App\Models\LeadActivity::where('id', $activityId)
            ->where('lead_id', $lead->id)
            ->delete();

        return redirect()->back()->with('success', __('Activity deleted successfully.'));
    }

    public function updateStatus(Request $request, $leadId)
    {
        $validated = $request->validate([
            'lead_status_id' => 'required|exists:lead_statuses,id',
        ]);

        $lead = Lead::where('id', $leadId)
            ->where('created_by', createdBy())
            ->firstOrFail();

        $lead->update([
            'lead_status_id' => $validated['lead_status_id']
        ]);

        return redirect()->back()->with('success', __('Lead status updated successfully.'));
    }

    public function kanban(Request $request)
    {
        $leadStatuses = LeadStatus::where('created_by', createdBy())
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        $leadsQuery = Lead::with(['leadStatus', 'leadSource', 'assignedUser'])
            ->where(function ($q) {
                if (auth()->user()->type === 'company') {
                    $q->where('created_by', createdBy());
                } else {
                    $q->where('assigned_to', auth()->id());
                }
            });

        // Handle search
        if ($request->has('search') && !empty($request->search)) {
            $leadsQuery->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('phone', 'like', '%' . $request->search . '%')
                    ->orWhere('company', 'like', '%' . $request->search . '%');
            });
        }

        // Handle filters
        if ($request->has('lead_source_id') && !empty($request->lead_source_id) && $request->lead_source_id !== 'all') {
            $leadsQuery->where('lead_source_id', $request->lead_source_id);
        }

        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
            $leadsQuery->where('status', $request->status);
        }

        if ($request->has('is_converted') && $request->is_converted !== 'all') {
            $leadsQuery->where('is_converted', $request->is_converted === '1');
        }

        $leads = $leadsQuery->get();

        // Group leads by status
        $kanbanData = [];
        foreach ($leadStatuses as $status) {
            $kanbanData[$status->id] = [
                'status' => $status,
                'leads' => $leads->where('lead_status_id', $status->id)->values()->toArray()
            ];
        }

        return response()->json([
            'kanbanData' => $kanbanData,
            'leadStatuses' => $leadStatuses->toArray()
        ]);
    }

    public function convertToAccount(Request $request, $leadId)
    {
        $lead = Lead::where('id', $leadId)
            ->where('created_by', createdBy())
            ->firstOrFail();

        $validated = $request->validate([
            'account_type_id' => 'required|exists:account_types,id',
            'website' => 'nullable|string|max:255',
            'address' => 'nullable|string',
        ]);

        // Create account from lead
        $account = \App\Models\Account::create([
            'name' => $lead->company ?: $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'website' => $validated['website'] ?? $lead->website,
            'account_type_id' => $validated['account_type_id'],
            'account_industry_id' => $lead->account_industry_id,
            'billing_address' => $validated['address'] ?? $lead->address,
            'assigned_to' => $lead->assigned_to,
            'status' => 'active',
            'created_by' => createdBy(),
        ]);

        // Mark lead as converted
        $lead->update(['is_converted' => true]);

        return redirect()->back()->with('success', __('Lead converted to account successfully.'));
    }

    public function convertToContact(Request $request, $leadId)
    {
        $lead = Lead::where('id', $leadId)
            ->where('created_by', createdBy())
            ->firstOrFail();

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'position' => 'nullable|string|max:255',
            'address' => 'nullable|string',
        ]);

        // Create contact from lead
        $contact = \App\Models\Contact::create([
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'position' => $validated['position'] ?? $lead->position,
            'address' => $validated['address'] ?? $lead->address,
            'account_id' => $validated['account_id'],
            'assigned_to' => $lead->assigned_to,
            'status' => 'active',
            'created_by' => createdBy(),
        ]);

        // Mark lead as converted
        $lead->update(['is_converted' => true]);

        return redirect()->back()->with('success', __('Lead converted to contact successfully.'));
    }

    public function fileExport()
    {
        if (!auth()->user()->can('export-leads')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $name = 'lead_' . date('Y-m-d i:h:s');
        return Excel::download(new LeadExport(), $name . '.xlsx');
    }
    public function downloadTemplate()
    {
        if (!auth()->user()->can('import-leads')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        $filePath = storage_path('uploads/sample/sample-lead.xlsx');

        if (!file_exists($filePath)) {
            return response()->json(['error' => __('Template file not available')], 404);
        }

        return response()->download($filePath, 'sample-lead.xlsx');
    }

    public function parseFile(Request $request)
    {
        if (!auth()->user()->can('import-leads')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $rules = [
            'file' => 'required|mimes:csv,txt,xlsx',
        ];

        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        try {
            $file = $request->file('file');

            // Read headers and preview data
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $highestColumn = $worksheet->getHighestColumn();
            $highestRow = $worksheet->getHighestRow();
            $headers = [];

            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $value = $worksheet->getCell($col . '1')->getValue();
                if ($value) {
                    $headers[] = $value;
                }
            }

            // Get preview data (first 2 rows after header)
            $previewData = [];
            for ($row = 2; $row <= $highestRow; $row++) {
                $rowData = [];
                $colIndex = 0;
                for ($col = 'A'; $col <= $highestColumn; $col++) {
                    if ($colIndex < count($headers)) {
                        $rowData[$headers[$colIndex]] = (string)$worksheet->getCell($col . $row)->getValue();
                    }
                    $colIndex++;
                }
                $previewData[] = $rowData;
            }

            return response()->json([
                'excelColumns' => $headers,
                'previewData' => $previewData
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to parse file: :error', ['error' => $e->getMessage()]));
        }
    }

    public function fileImport(Request $request)
    {
        if (!auth()->user()->can('import-leads')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $rules = [
            'data' => 'required|array',
        ];

        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        try {
            $data = $request->data;

            // Create temporary CSV file from data
            $tempFile = storage_path('tmp/import_' . time() . '.csv');

            // Ensure tmp directory exists
            if (!file_exists(dirname($tempFile))) {
                mkdir(dirname($tempFile), 0755, true);
            }

            $handle = fopen($tempFile, 'w');

            // Write headers
            if (!empty($data)) {
                fputcsv($handle, array_keys($data[0]));

                // Write data rows
                foreach ($data as $row) {
                    fputcsv($handle, $row);
                }
            }
            fclose($handle);

            $import = new LeadImport();
            Excel::import($import, $tempFile);

            // Clean up temp file
            unlink($tempFile);

            $message = __('Import completed: :added leads added, :skipped leads skipped', [
                'added' => $import->getAddedCount(),
                'skipped' => $import->getSkippedCount()
            ]);

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to import: :error', ['error' => $e->getMessage()]));
        }
    }
}
