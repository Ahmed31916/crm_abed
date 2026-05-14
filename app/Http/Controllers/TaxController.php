<?php

namespace App\Http\Controllers;

use App\Models\Tax;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaxController extends Controller
{
    public function index(Request $request)
    {
        $query = Tax::query()
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
        $allowedSorts=['id', 'name', 'rate', 'created_at'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $perPage = $request->get('per_page', 10);
        $taxes = $query->paginate((int)$perPage);

        return Inertia::render('taxes/index', [
            'taxes' => $taxes,
            'filters' => $request->all(['search', 'status', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:taxes,name,NULL,id,created_by,' . createdBy(),
            'rate' => 'required|numeric|min:0|max:999.9999',
            'type' => 'nullable|in:percentage,fixed',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $validated['created_by'] = createdBy();
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['type'] = $validated['type'] ?? 'percentage';
        $validated['type'] = $validated['type'] ?? 'percentage';

        Tax::create($validated);

        return redirect()->back()->with('success', __('Tax created successfully.'));
    }

    public function update(Request $request, $taxId)
    {
        $tax = Tax::where('id', $taxId)
            ->where('created_by', createdBy())
            ->first();

        if ($tax) {
            try {
                $validated = $request->validate([
                    'name' => 'required|string|max:255|unique:taxes,name,' . $taxId . ',id,created_by,' . createdBy(),
                    'rate' => 'required|numeric|min:0|max:999.9999',
                    'type' => 'nullable|in:percentage,fixed',
                    'description' => 'nullable|string',
                    'status' => 'nullable|in:active,inactive',
                ]);

                $tax->update($validated);

                return redirect()->back()->with('success', __('Tax updated successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update tax.'));
            }
        } else {
            return redirect()->back()->with('error', __('Tax not found.'));
        }
    }

    public function destroy($taxId)
    {
        $tax = Tax::where('id', $taxId)
            ->where('created_by', createdBy())
            ->first();

        if ($tax) {
            try {
                $tax->delete();
                return redirect()->back()->with('success', __('Tax deleted successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete tax.'));
            }
        } else {
            return redirect()->back()->with('error', __('Tax not found.'));
        }
    }

    public function toggleStatus($taxId)
    {
        $tax = Tax::where('id', $taxId)
            ->where('created_by', createdBy())
            ->first();

        if ($tax) {
            try {
                $tax->status = $tax->status === 'active' ? 'inactive' : 'active';
                $tax->save();

                return redirect()->back()->with('success', __('Tax status updated successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update tax status.'));
            }
        } else {
            return redirect()->back()->with('error', __('Tax not found.'));
        }
    }
}
