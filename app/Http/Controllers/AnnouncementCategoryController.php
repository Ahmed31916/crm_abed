<?php

namespace App\Http\Controllers;

use App\Models\AnnouncementCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnnouncementCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = AnnouncementCategory::query()->with('creator')->where('created_by', createdBy());

        if ($request->has('search') && !empty($request->search)) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts=['id', 'name'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $perPage = $request->get('per_page', 10);
        $categories = $query->paginate((int)$perPage);

        return Inertia::render('announcement-categories/index', [
            'categories' => $categories,
            'filters' => $request->all(['search', 'status', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:announcement_categories,name,NULL,id,created_by,' . createdBy(),
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['created_by'] = createdBy();

        AnnouncementCategory::create($validated);

        return redirect()->back()->with('success', __('Announcement category created successfully.'));
    }

    public function update(Request $request, $id)
    {
        $category = AnnouncementCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:announcement_categories,name,' . $id . ',id,created_by,' . createdBy(),
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $category->update($validated);

        return redirect()->back()->with('success', __('Announcement category updated successfully.'));
    }

    public function destroy($id)
    {
        $category = AnnouncementCategory::findOrFail($id);
        $category->delete();

        return redirect()->back()->with('success', __('Announcement category deleted successfully.'));
    }

    public function toggleStatus($id)
    {
        $category = AnnouncementCategory::findOrFail($id);
        $category->update(['status' => $category->status === 'active' ? 'inactive' : 'active']);

        return redirect()->back()->with('success', __('Announcement category status updated successfully.'));
    }
}
