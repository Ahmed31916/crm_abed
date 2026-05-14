<?php

namespace App\Http\Controllers;

use App\Models\OpportunitySource;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OpportunitySourceController extends Controller
{
    public function index(Request $request)
    {
        $query = OpportunitySource::query()
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
        $opportunitySources = $query->paginate((int)$perPage);

        return Inertia::render('opportunity-sources/index', [
            'opportunitySources' => $opportunitySources,
            'filters' => $request->all(['search', 'status', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:opportunity_sources,name,NULL,id,created_by,' . createdBy(),
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $validated['created_by'] = createdBy();
        $validated['status'] = $validated['status'] ?? 'active';

        OpportunitySource::create($validated);

        return redirect()->back()->with('success', __('Opportunity source created successfully.'));
    }

    public function update(Request $request, $opportunitySourceId)
    {
        $opportunitySource = OpportunitySource::where('id', $opportunitySourceId)
            ->where('created_by', createdBy())
            ->first();

        if ($opportunitySource) {
            try {
                $validated = $request->validate([
                    'name' => 'required|string|max:255|unique:opportunity_sources,name,' . $opportunitySourceId . ',id,created_by,' . createdBy(),
                    'description' => 'nullable|string',
                    'status' => 'nullable|in:active,inactive',
                ]);

                $opportunitySource->update($validated);

                return redirect()->back()->with('success', __('Opportunity source updated successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update opportunity source.'));
            }
        } else {
            return redirect()->back()->with('error', __('Opportunity source not found.'));
        }
    }

    public function destroy($opportunitySourceId)
    {
        $opportunitySource = OpportunitySource::where('id', $opportunitySourceId)
            ->where('created_by', createdBy())
            ->first();

        if ($opportunitySource) {
            try {
                $opportunitySource->delete();
                return redirect()->back()->with('success', __('Opportunity source deleted successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete opportunity source.'));
            }
        } else {
            return redirect()->back()->with('error', __('Opportunity source not found.'));
        }
    }

    public function toggleStatus($opportunitySourceId)
    {
        $opportunitySource = OpportunitySource::where('id', $opportunitySourceId)
            ->where('created_by', createdBy())
            ->first();

        if ($opportunitySource) {
            try {
                $opportunitySource->status = $opportunitySource->status === 'active' ? 'inactive' : 'active';
                $opportunitySource->save();

                return redirect()->back()->with('success', __('Opportunity source status updated successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update opportunity source status.'));
            }
        } else {
            return redirect()->back()->with('error', __('Opportunity source not found.'));
        }
    }
}
