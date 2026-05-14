<?php

namespace App\Http\Controllers;

use App\Models\ShippingProviderType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShippingProviderTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = ShippingProviderType::query()
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
        $shippingProviderTypes = $query->paginate((int)$perPage);

        return Inertia::render('shipping-provider-types/index', [
            'shippingProviderTypes' => $shippingProviderTypes,
            'filters' => $request->all(['search', 'status', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:shipping_provider_types,name,NULL,id,created_by,' . createdBy(),
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
            'status' => 'nullable|in:active,inactive',
        ]);

        $validated['created_by'] = createdBy();
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['color'] = $validated['color'] ?? '#3B82F6';

        ShippingProviderType::create($validated);

        return redirect()->back()->with('success', __('Shipping provider type created successfully.'));
    }

    public function update(Request $request, $shippingProviderTypeId)
    {
        $shippingProviderType = ShippingProviderType::where('id', $shippingProviderTypeId)
            ->where('created_by', createdBy())
            ->first();

        if ($shippingProviderType) {
            try {
                $validated = $request->validate([
                    'name' => 'required|string|max:255|unique:shipping_provider_types,name,' . $shippingProviderTypeId . ',id,created_by,' . createdBy(),
                    'description' => 'nullable|string',
                    'color' => 'nullable|string|max:7',
                    'status' => 'nullable|in:active,inactive',
                ]);

                $validated['color'] = $validated['color'] ?? '#3B82F6';

                $shippingProviderType->update($validated);

                return redirect()->back()->with('success', __('Shipping provider type updated successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update shipping provider type.'));
            }
        } else {
            return redirect()->back()->with('error', __('Shipping provider type not found.'));
        }
    }

    public function destroy($shippingProviderTypeId)
    {
        $shippingProviderType = ShippingProviderType::where('id', $shippingProviderTypeId)
            ->where('created_by', createdBy())
            ->first();

        if ($shippingProviderType) {
            try {
                $shippingProviderType->delete();
                return redirect()->back()->with('success', __('Shipping provider type deleted successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete shipping provider type.'));
            }
        } else {
            return redirect()->back()->with('error', __('Shipping provider type not found.'));
        }
    }

    public function show($shippingProviderTypeId)
    {
        $shippingProviderType = ShippingProviderType::where('id', $shippingProviderTypeId)
            ->where('created_by', createdBy())
            ->with(['creator'])
            ->first();

        if (!$shippingProviderType) {
            return redirect()->route('shipping-provider-types.index')->with('error', __('Shipping provider type not found.'));
        }

        return Inertia::render('shipping-provider-types/show', [
            'shippingProviderType' => $shippingProviderType
        ]);
    }

    public function toggleStatus($shippingProviderTypeId)
    {
        $shippingProviderType = ShippingProviderType::where('id', $shippingProviderTypeId)
            ->where('created_by', createdBy())
            ->first();

        if ($shippingProviderType) {
            try {
                $shippingProviderType->status = $shippingProviderType->status === 'active' ? 'inactive' : 'active';
                $shippingProviderType->save();

                return redirect()->back()->with('success', __('Shipping provider type status updated successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update shipping provider type status.'));
            }
        } else {
            return redirect()->back()->with('error', __('Shipping provider type not found.'));
        }
    }
}
