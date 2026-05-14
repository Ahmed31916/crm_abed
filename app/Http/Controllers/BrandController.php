<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $query = Brand::query()
            ->where('created_by', createdBy());

        // Handle search
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%')
                    ->orWhere('website', 'like', '%' . $request->search . '%');
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
        $brands = $query->paginate((int)$perPage);

        return Inertia::render('brands/index', [
            'brands' => $brands,
            'filters' => $request->all(['search', 'status', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:brands,name,NULL,id,created_by,' . createdBy(),
            'description' => 'nullable|string',
            'website' => 'nullable|url|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        $validated['created_by'] = createdBy();
        $validated['status'] = $validated['status'] ?? 'active';

        Brand::create($validated);

        return redirect()->back()->with('success', __('Brand created successfully.'));
    }

    public function update(Request $request, $brandId)
    {
        $brand = Brand::where('id', $brandId)
            ->where('created_by', createdBy())
            ->first();

        if ($brand) {
            try {
                $validated = $request->validate([
                    'name' => 'required|string|max:255|unique:brands,name,' . $brandId . ',id,created_by,' . createdBy(),
                    'description' => 'nullable|string',
                    'website' => 'nullable|url|max:255',
                    'status' => 'nullable|in:active,inactive',
                ]);

                $brand->update($validated);

                return redirect()->back()->with('success', __('Brand updated successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update brand.'));
            }
        } else {
            return redirect()->back()->with('error', __('Brand not found.'));
        }
    }

    public function destroy($brandId)
    {
        $brand = Brand::where('id', $brandId)
            ->where('created_by', createdBy())
            ->first();

        if ($brand) {
            try {
                $brand->delete();
                return redirect()->back()->with('success', __('Brand deleted successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete brand.'));
            }
        } else {
            return redirect()->back()->with('error', __('Brand not found.'));
        }
    }

    public function toggleStatus($brandId)
    {
        $brand = Brand::where('id', $brandId)
            ->where('created_by', createdBy())
            ->first();

        if ($brand) {
            try {
                $brand->status = $brand->status === 'active' ? 'inactive' : 'active';
                $brand->save();

                return redirect()->back()->with('success', __('Brand status updated successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update brand status.'));
            }
        } else {
            return redirect()->back()->with('error', __('Brand not found.'));
        }
    }
}
