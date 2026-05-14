<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NoteController extends Controller
{
    public function index(Request $request)
    {
        $query = Note::query()
            ->with(['creator', 'sharedUsers'])
            ->where(function ($q) {
                $q->where('created_by', auth()->id())
                    ->orWhereHas('sharedUsers', function ($sq) {
                        $sq->where('user_id', auth()->id());
                    });
            });

        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('content', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('created_by') && $request->created_by !== 'all') {
            $query->where('created_by', $request->created_by);
        }

        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts=['id'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $viewType = $request->view ?? 'grid';
        $perPage = $request->get('per_page', 10);
        $allNotes = $viewType === 'kanban' ? $query->get() : $query->paginate((int)$perPage);

        $isKanban = $viewType === 'kanban';
        $items = $isKanban ? $allNotes : $allNotes->items();

        $myNotes = collect($items)->filter(function ($note) {
            return $note->created_by === auth()->id() && $note->sharedUsers->isEmpty();
        })->values();

        $sharedNotes = collect($items)->filter(function ($note) {
            return ($note->created_by === auth()->id() && $note->sharedUsers->isNotEmpty()) ||
                $note->sharedUsers->contains('id', auth()->id());
        })->values();

        $users = User::where('created_by', createdBy())->orWhere('id', createdBy())
            ->select('id', 'name', 'email')
            ->get();

        $totalPersonalNotes = Note::where('created_by', auth()->id())->whereDoesntHave('sharedUsers')->count();
        $totalSharedNotes = Note::where('created_by', auth()->id())->whereHas('sharedUsers')->count() + Note::whereHas('sharedUsers', function ($q) {
            $q->where('user_id', auth()->id());
        })->where('created_by', '!=', auth()->id())->count();

        return Inertia::render('notes/index', [
            'myNotes' => $isKanban ? [
                'data' => $myNotes,
            ] : [
                'data' => $myNotes,
                'from' => $allNotes->firstItem(),
                'to' => $allNotes->lastItem(),
                'total' => $allNotes->total(),
                'links' => $allNotes->linkCollection()->toArray(),
            ],
            'sharedNotes' => [
                'data' => $sharedNotes,
            ],
            'totalPersonalNotes' => $totalPersonalNotes,
            'totalSharedNotes' => $totalSharedNotes,
            'users' => $users,
            'filters' => $request->all(['search', 'created_by', 'sort_field', 'sort_direction', 'per_page', 'view']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|nullable|string',
            'shared_users' => 'nullable|array',
            'shared_users.*' => 'required|exists:users,id',
        ]);

        $validated['created_by'] = auth()->id();

        $note = Note::create($validated);

        if (!empty($validated['shared_users'])) {
            $note->sharedUsers()->attach($validated['shared_users']);
        }

        return redirect()->back()->with('success', __('Note created successfully.'));
    }

    public function update(Request $request, $noteId)
    {
        $note = Note::where('id', $noteId)
            ->where('created_by', auth()->id())
            ->firstOrFail();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|nullable|string',
            'shared_users' => 'nullable|array',
            'shared_users.*' => 'required|exists:users,id',
        ]);

        if (!$note) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $note->update($validated);

        if (isset($validated['shared_users'])) {
            $note->sharedUsers()->sync($validated['shared_users']);
        }

        return redirect()->back()->with('success', __('Note updated successfully.'));
    }

    public function destroy($noteId)
    {
        $note = Note::where('id', $noteId)
            ->where('created_by', auth()->id())
            ->firstOrFail();

        $note->delete();

        return redirect()->back()->with('success', __('Note deleted successfully.'));
    }
}
