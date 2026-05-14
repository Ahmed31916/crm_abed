<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnnouncementController extends Controller
{

    private function updateStatus()
    {
        if (!IsDemo()) {
            Announcement::where('created_by', createdBy())
                ->where('status', 'active')
                ->whereDate('end_date', '<', now())
                ->update(['status' => 'expired']);
        }
    }

    public function dashboard()
    {
        $this->updateStatus();
        $announcements = Announcement::with(['creator', 'category'])
            ->where('created_by', createdBy())
            ->whereIn('status', ['active','expired'])
            ->orderBy('is_featured', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('announcements/dashboard', [
            'announcements' => $announcements,
        ]);
    }

    public function index(Request $request)
    {
        $this->updateStatus();
        $query = Announcement::query()->with(['creator', 'category'])
            ->where('created_by', createdBy());

        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('content', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('category') && $request->category !== 'all') {
            $query->where('announcement_category_id', $request->category);
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts=['id', 'title', 'created_at'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $perPage = $request->get('per_page', 10);
        $announcements = $query->paginate((int)$perPage);

        $categories = AnnouncementCategory::where('status', 'active')->where('created_by', createdBy())->get();

        return Inertia::render('announcements/index', [
            'announcements' => $announcements,
            'categories' => $categories,
            'filters' => $request->all(['search', 'category', 'status', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }

    public function show($id)
    {
        $announcement = Announcement::with(['creator', 'category'])
            ->where('created_by', createdBy())
            ->findOrFail($id);

        return Inertia::render('announcements/show', [
            'announcement' => $announcement,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255|unique:announcements,title,NULL,id,created_by,' . createdBy(),
            'content' => 'required|string',
            'announcement_category_id' => 'required|exists:announcement_categories,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:active,inactive,expired',
            'is_featured' => 'boolean',
        ]);

        $validated['created_by'] = createdBy();

        Announcement::create($validated);

        return redirect()->back()->with('success', __('Announcement created successfully.'));
    }

    public function update(Request $request, $id)
    {
        $announcement = Announcement::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255|unique:announcements,title,' . $id . ',id,created_by,' . createdBy(),
            'content' => 'required|string',
            'announcement_category_id' => 'required|exists:announcement_categories,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:active,inactive,expired',
            'is_featured' => 'boolean',
        ]);

        $announcement->update($validated);

        return redirect()->back()->with('success', __('Announcement updated successfully.'));
    }

    public function destroy($id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->delete();

        return redirect()->back()->with('success', __('Announcement deleted successfully.'));
    }

    public function toggleStatus($id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->update(['status' => $announcement->status === 'active' ? 'inactive' : 'active']);

        return redirect()->back()->with('success', __('Announcement status updated successfully.'));
    }
}
