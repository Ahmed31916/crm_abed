<?php

namespace App\Http\Controllers;

use App\Models\LeadStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeadStatusController extends Controller
{
    public function index(Request $request)
    {
        $query = LeadStatus::query()
            ->where('created_by', createdBy());

        // Handle search
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Handle status filter
        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
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
        $leadStatuses = $query->paginate((int)$perPage);

        return Inertia::render('lead-statuses/index', [
            'leadStatuses' => $leadStatuses,
            'filters' => $request->all(['search', 'status', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:lead_statuses,name,NULL,id,created_by,' . createdBy(),
            'color' => 'required|string|max:7',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $validated['created_by'] = createdBy();
        $validated['status'] = $validated['status'] ?? 'active';

        LeadStatus::create($validated);

        return redirect()->back()->with('success', __('Lead status created successfully.'));
    }

    public function update(Request $request, $leadStatusId)
    {
        $leadStatus = LeadStatus::where('id', $leadStatusId)
            ->where('created_by', createdBy())
            ->first();

        if ($leadStatus) {
            try {
                $validated = $request->validate([
                    'name' => 'required|string|max:255|unique:lead_statuses,name,' . $leadStatusId . ',id,created_by,' . createdBy(),
                    'color' => 'required|string|max:7',
                    'description' => 'nullable|string',
                    'status' => 'nullable|in:active,inactive',
                ]);

                $leadStatus->update($validated);

                return redirect()->back()->with('success', __('Lead status updated successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update lead status.'));
            }
        } else {
            return redirect()->back()->with('error', __('Lead status not found.'));
        }
    }

    public function destroy($leadStatusId)
    {
        $leadStatus = LeadStatus::where('id', $leadStatusId)
            ->where('created_by', createdBy())
            ->first();

        if ($leadStatus) {
            try {
                $leadStatus->delete();
                return redirect()->back()->with('success', __('Lead status deleted successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete lead status.'));
            }
        } else {
            return redirect()->back()->with('error', __('Lead status not found.'));
        }
    }

    public function toggleStatus($leadStatusId)
    {
        $leadStatus = LeadStatus::where('id', $leadStatusId)
            ->where('created_by', createdBy())
            ->first();

        if ($leadStatus) {
            try {
                $leadStatus->status = $leadStatus->status === 'active' ? 'inactive' : 'active';
                $leadStatus->save();

                return redirect()->back()->with('success', __('Lead status status updated successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update lead status status.'));
            }
        } else {
            return redirect()->back()->with('error', __('Lead status not found.'));
        }
    }
}
