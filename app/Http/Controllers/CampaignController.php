<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $query = Campaign::query()
            ->with(['assignedUser', 'campaignType', 'targetList'])
            ->where(function($q) {
                if (auth()->user()->type === 'company') {
                    $q->where('created_by', createdBy());
                } else {
                    $q->where('assigned_to', auth()->id());
                }
            });

        // Handle search
        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Handle campaign type filter
        if ($request->has('campaign_type_id') && !empty($request->campaign_type_id) && $request->campaign_type_id !== 'all') {
            $query->where('campaign_type_id', $request->campaign_type_id);
        }

        // Handle target list filter
        if ($request->has('target_list_id') && !empty($request->target_list_id) && $request->target_list_id !== 'all') {
            $query->where('target_list_id', $request->target_list_id);
        }

        // Handle status filter
        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Handle assigned_to filter
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
        $allowedSorts=['id', 'name', 'start_date', 'end_date', 'budget', 'actual_cost', 'created_at'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }
        $perPage = $request->get('per_page', 10);
        $campaigns = $query->paginate((int)$perPage);

        // Get users for assignment dropdown (only for company users)
        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        // Get campaign types and target lists
        $campaignTypes = \App\Models\CampaignType::where('created_by', createdBy())
            ->where('status', 'active')
            ->select('id', 'name')
            ->get();

        $targetLists = \App\Models\TargetList::where('created_by', createdBy())
            ->where('status', 'active')
            ->select('id', 'name')
            ->get();

        return Inertia::render('campaigns/index', [
            'campaigns' => $campaigns,
            'users' => $users,
            'campaignTypes' => $campaignTypes,
            'targetLists' => $targetLists,
            'filters' => $request->all(['search', 'campaign_type_id', 'target_list_id', 'status', 'assigned_to', 'sort_field', 'sort_direction', 'per_page', 'view', 'page']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0',
            'actual_cost' => 'nullable|numeric|min:0',
            'expected_response' => 'nullable|integer|min:0',
            'actual_response' => 'nullable|integer|min:0',
            'campaign_type_id' => 'required|exists:campaign_types,id',
            'target_list_id' => 'required|exists:target_lists,id',
            'status' => 'nullable|in:active,inactive',
            'assigned_to' => auth()->user()->type == 'company' ? 'required|exists:users,id' : 'nullable|exists:users,id',
        ]);

        // Validate unique name with campaign_type_id and created_by
        $exists = Campaign::where('name', $validated['name'])
            ->where('campaign_type_id', $validated['campaign_type_id'])
            ->where('created_by', createdBy())
            ->exists();

        if ($exists) {
            return redirect()->back()->withErrors(['name' => __('A campaign with this name and type already exists.')])->withInput();
        }

        $validated['created_by'] = createdBy();
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['actual_cost'] = $validated['actual_cost'] ?? 0;
        $validated['expected_response'] = $validated['expected_response'] ?? 0;
        $validated['actual_response'] = $validated['actual_response'] ?? 0;

        // Handle 'none' values
        if (isset($validated['target_list_id']) && $validated['target_list_id'] === 'none') {
            $validated['target_list_id'] = null;
        }

        // Auto-assign to current user if staff user
        if (auth()->user()->type !== 'company') {
            $validated['assigned_to'] = auth()->id();
        }

        Campaign::create($validated);

        return redirect()->back()->with('success', __('Campaign created successfully.'));
    }

    public function update(Request $request, $campaignId)
    {
        $campaign = Campaign::where('id', $campaignId)
            ->where('created_by', createdBy())
            ->first();

        if ($campaign) {
            try {
                $validated = $request->validate([
                    'name' => 'required|string|max:255',
                    'description' => 'nullable|string',
                    'start_date' => 'required|date',
                    'end_date' => 'required|date|after_or_equal:start_date',
                    'budget' => 'nullable|numeric|min:0',
                    'actual_cost' => 'nullable|numeric|min:0',
                    'expected_response' => 'nullable|integer|min:0',
                    'actual_response' => 'nullable|integer|min:0',
                    'campaign_type_id' => 'required|exists:campaign_types,id',
                    'target_list_id' => 'required|exists:target_lists,id',
                    'status' => 'nullable|in:active,inactive',
                    'assigned_to' => auth()->user()->type == 'company' ? 'required|exists:users,id' : 'nullable|exists:users,id',
                ]);

                // Validate unique name with campaign_type_id and created_by
                $exists = Campaign::where('name', $validated['name'])
                    ->where('campaign_type_id', $validated['campaign_type_id'])
                    ->where('created_by', createdBy())
                    ->where('id', '!=', $campaignId)
                    ->exists();

                if ($exists) {
                    return redirect()->back()->withErrors(['name' => __('A campaign with this name and type already exists.')])->withInput();
                }

                // Handle 'none' values
                if (isset($validated['target_list_id']) && $validated['target_list_id'] === 'none') {
                    $validated['target_list_id'] = null;
                }

                // Auto-assign to current user if staff user
                if (auth()->user()->type !== 'company') {
                    $validated['assigned_to'] = auth()->id();
                }

                $campaign->update($validated);

                return redirect()->back()->with('success', __('Campaign updated successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update campaign.'));
            }
        } else {
            return redirect()->back()->with('error', __('Campaign not found.'));
        }
    }

    public function destroy($campaignId)
    {
        $campaign = Campaign::where('id', $campaignId)
            ->where('created_by', createdBy())
            ->first();

        if ($campaign) {
            try {
                $campaign->delete();
                return redirect()->back()->with('success', __('Campaign deleted successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete campaign.'));
            }
        } else {
            return redirect()->back()->with('error', __('Campaign not found.'));
        }
    }

    public function show($campaignId)
    {
        $campaign = Campaign::where('id', $campaignId)
            ->where('created_by', createdBy())
            ->with(['assignedUser', 'creator', 'campaignType', 'targetList'])
            ->first();

        // Get all leads created using this campaign
        $campaignLeads = \App\Models\Lead::where('campaign_id', $campaignId)
            ->where('created_by', createdBy())
            ->with(['leadStatus', 'assignedUser'])
            ->get();

        $campaign->leads = $campaignLeads;

        if (!$campaign) {
            return redirect()->route('campaigns.index')->with('error', __('Campaign not found.'));
        }

        return Inertia::render('campaigns/show', [
            'campaign' => $campaign,
            'campaignLeads' => $campaignLeads
        ]);
    }

    public function toggleStatus($campaignId)
    {
        $campaign = Campaign::where('id', $campaignId)
            ->where('created_by', createdBy())
            ->first();

        if ($campaign) {
            try {
                $campaign->status = $campaign->status === 'active' ? 'inactive' : 'active';
                $campaign->save();

                return redirect()->back()->with('success', __('Campaign status updated successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update campaign status.'));
            }
        } else {
            return redirect()->back()->with('error', __('Campaign not found.'));
        }
    }
}
