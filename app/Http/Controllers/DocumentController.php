<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use App\Models\Account;
use App\Models\DocumentFolder;
use App\Models\DocumentType;
use App\Models\Opportunity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Document::with(['account', 'folder', 'type', 'opportunity', 'creator', 'assignedUser', 'media']);

        // Apply search filter
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply account filter
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        // Apply folder filter
        if ($request->filled('folder_id')) {
            $query->where('folder_id', $request->folder_id);
        }

        // Apply type filter
        if ($request->filled('type_id')) {
            $query->where('type_id', $request->type_id);
        }

        // Apply assigned_to filter
        if ($request->filled('assigned_to') && $request->assigned_to !== 'all') {
            if ($request->assigned_to === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->assigned_to);
            }
        }

        // Apply sorting
        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts=['id', 'name', 'publish_date', 'expiration_date', 'created_at'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        // Apply authorization filter - show assigned_to user wise data
        if (auth()->user()->type === 'superadmin' || auth()->user()->type === 'super admin') {
            // Super admin sees all documents
        } elseif (auth()->user()->type === 'company') {
            $query->where('created_by', createdBy());
        } else {
            $query->where(function($q) {
                $q->where('assigned_to', auth()->id())
                  ->orWhere('created_by', auth()->id());
            });
        }

        $perPage = $request->get('per_page', 10);
        $documents = $query->paginate((int)$perPage);

        // Get dropdown data
        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        $accounts = Account::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name')->get();

        $folders = DocumentFolder::where('created_by', createdBy())
            ->select('id', 'name')->get();

        $types = DocumentType::where('created_by', createdBy())
            ->select('id', 'type_name')->get();

        $opportunities = Opportunity::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name')->get();

        return Inertia::render('documents/index', [
            'documents' => $documents,
            'users' => $users,
            'accounts' => $accounts,
            'folders' => $folders,
            'types' => $types,
            'opportunities' => $opportunities,
            'filters' => $request->only(['search', 'status', 'account_id', 'folder_id', 'type_id', 'assigned_to', 'sort_field', 'sort_direction', 'per_page', 'view', 'page'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        $accounts = Account::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name')->get();

        $folders = DocumentFolder::where('created_by', createdBy())
            ->select('id', 'name')->get();

        $types = DocumentType::where('created_by', createdBy())
            ->select('id', 'type_name')->get();

        $opportunities = Opportunity::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name')->get();

        return Inertia::render('documents/create', [
            'users' => $users,
            'accounts' => $accounts,
            'folders' => $folders,
            'types' => $types,
            'opportunities' => $opportunities,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Document $document)
    {
        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        $accounts = Account::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name')->get();

        $folders = DocumentFolder::where('created_by', createdBy())
            ->select('id', 'name')->get();

        $types = DocumentType::where('created_by', createdBy())
            ->select('id', 'type_name')->get();

        $opportunities = Opportunity::where('created_by', createdBy())
            ->when(auth()->user()->type !== 'company', function($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->select('id', 'name')->get();

        return Inertia::render('documents/edit', [
            'document' => $document,
            'users' => $users,
            'accounts' => $accounts,
            'folders' => $folders,
            'types' => $types,
            'opportunities' => $opportunities,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'account_id' => 'required|exists:accounts,id',
            'folder_id' => 'required|exists:document_folders,id',
            'type_id' => 'required|exists:document_types,id',
            'opportunity_id' => 'required|exists:opportunities,id',
            'status' => 'nullable|in:active,inactive',
            'publish_date' => 'nullable|date',
            'expiration_date' => 'nullable|date',
            'attachment' => 'required|string',
            'description' => 'nullable|string',
            'assigned_to' => auth()->user()->type == 'company' ? 'required|exists:users,id' : 'nullable|exists:users,id',
        ]);

        $document = Document::create([
            'name' => $request->name,
            'account_id' => $request->account_id,
            'folder_id' => $request->folder_id,
            'type_id' => $request->type_id,
            'opportunity_id' => $request->opportunity_id,
            'status' => $request->status ?? 'active',
            'publish_date' => $request->publish_date,
            'expiration_date' => $request->expiration_date,
            'description' => $request->description,
            'created_by' => createdBy(),
            'assigned_to' => auth()->user()->type === 'company' ? $request->assigned_to : auth()->id(),
        ]);

        // Handle attachment from media library
        if ($request->filled('attachment')) {
            try {
                $document->addMediaFromUrl($request->attachment)
                    ->toMediaCollection('attachments');
            } catch (\Exception $e) {
                \Log::error('Document attachment upload failed: ' . $e->getMessage());
            }
        }

        return redirect()->route('documents.index')
            ->with('success', __('Document created successfully.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Document $document)
    {
        $document->load(['account', 'folder', 'type', 'opportunity', 'creator', 'assignedUser', 'media']);

        return Inertia::render('documents/show', [
            'document' => $document
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Document $document)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'account_id' => 'required|exists:accounts,id',
            'folder_id' => 'required|exists:document_folders,id',
            'type_id' => 'required|exists:document_types,id',
            'opportunity_id' => 'required|exists:opportunities,id',
            'status' => 'nullable|in:active,inactive',
            'publish_date' => 'nullable|date',
            'expiration_date' => 'nullable|date',
            'attachment' => 'required|string',
            'description' => 'nullable|string',
            'assigned_to' => auth()->user()->type == 'company' ? 'required|exists:users,id' : 'nullable|exists:users,id',
        ]);

        $document->update([
            'name' => $request->name,
            'account_id' => $request->account_id,
            'folder_id' => $request->folder_id,
            'type_id' => $request->type_id,
            'opportunity_id' => $request->opportunity_id,
            'status' => $request->status,
            'publish_date' => $request->publish_date,
            'expiration_date' => $request->expiration_date,
            'description' => $request->description,
            'assigned_to' => auth()->user()->type === 'company' ? $request->assigned_to : auth()->id(),
        ]);

        // Handle attachment update only if changed
        if ($request->has('attachment')) {
            $currentMedia = $document->getFirstMedia('attachments');
            $currentUrl = $currentMedia ? $currentMedia->getUrl() : null;

            // Only update if attachment URL has changed
            if ($request->attachment !== $currentUrl) {
                try {
                    $document->clearMediaCollection('attachments');
                    if ($request->filled('attachment')) {
                        $document->addMediaFromUrl($request->attachment)
                            ->toMediaCollection('attachments');
                    }
                } catch (\Exception $e) {
                    \Log::error('Document attachment upload failed: ' . $e->getMessage());
                }
            }
        }

        return redirect()->route('documents.index')
            ->with('success', __('Document updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Document $document)
    {
        $document->delete();

        return redirect()->route('documents.index')
            ->with('success', __('Document deleted successfully.'));
    }

    /**
     * Toggle the status of the specified resource.
     */
    public function toggleStatus(Document $document)
    {
        $newStatus = $document->status === 'active' ? 'inactive' : 'active';
        $document->update(['status' => $newStatus]);

        return redirect()->route('documents.index')
            ->with('success', __('Document status updated successfully.'));
    }

    /**
     * Download the document attachment.
     */
    public function download(Document $document)
    {
        $media = $document->getFirstMedia('attachments');
        if (!$media) {
            abort(404, __('File not found'));
        }

        return response()->download($media->getPath(), $media->file_name);
    }
}
