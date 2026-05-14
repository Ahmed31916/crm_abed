<?php

namespace App\Http\Controllers;

use App\Models\TargetList;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TargetListController extends Controller
{
    public function index(Request $request)
    {
        $query = TargetList::query()
            ->where('created_by', createdBy());

        // Handle search
        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
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
        $targetLists = $query->paginate((int)$perPage);

        return Inertia::render('target-lists/index', [
            'targetLists' => $targetLists,
            'filters' => $request->all(['search', 'status', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:target_lists,name,NULL,id,created_by,' . createdBy(),
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $validated['created_by'] = createdBy();
        $validated['status'] = $validated['status'] ?? 'active';

        TargetList::create($validated);

        return redirect()->back()->with('success', __('Target list created successfully.'));
    }

    public function update(Request $request, $targetListId)
    {
        $targetList = TargetList::where('id', $targetListId)
            ->where('created_by', createdBy())
            ->first();

        if ($targetList) {
            try {
                $validated = $request->validate([
                    'name' => 'required|string|max:255|unique:target_lists,name,' . $targetListId . ',id,created_by,' . createdBy(),
                    'description' => 'nullable|string',
                    'status' => 'nullable|in:active,inactive',
                ]);

                $targetList->update($validated);

                return redirect()->back()->with('success', __('Target list updated successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update target list.'));
            }
        } else {
            return redirect()->back()->with('error', __('Target list not found.'));
        }
    }

    public function destroy($targetListId)
    {
        $targetList = TargetList::where('id', $targetListId)
            ->where('created_by', createdBy())
            ->first();

        if ($targetList) {
            try {
                $targetList->delete();
                return redirect()->back()->with('success', __('Target list deleted successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete target list.'));
            }
        } else {
            return redirect()->back()->with('error', __('Target list not found.'));
        }
    }

    public function toggleStatus($targetListId)
    {
        $targetList = TargetList::where('id', $targetListId)
            ->where('created_by', createdBy())
            ->first();

        if ($targetList) {
            try {
                $targetList->status = $targetList->status === 'active' ? 'inactive' : 'active';
                $targetList->save();

                return redirect()->back()->with('success', __('Target list status updated successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update target list status.'));
            }
        } else {
            return redirect()->back()->with('error', __('Target list not found.'));
        }
    }
}
