<?php

namespace App\Http\Controllers;

use App\Models\DocumentFolder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DocumentFolderController extends Controller
{
    public function index(Request $request)
    {
        $query = DocumentFolder::query()
            ->with(['parentFolder', 'creator'])
            ->where(function($q) {
                if (auth()->user()->type === 'company') {
                    $q->where('created_by', createdBy());
                } else {
                    // Staff users can see folders created by their company
                    $q->where('created_by', createdBy());
                }
            });

        // Handle search
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Handle filters
        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('parent_folder_id') && !empty($request->parent_folder_id) && $request->parent_folder_id !== 'all') {
            $query->where('parent_folder_id', $request->parent_folder_id);
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
        $documentFolders = $query->paginate((int)$perPage);

        // Get data for dropdowns with parent folder name
        $parentFolders = DocumentFolder::where('created_by', createdBy())
            ->where('status', 'active')
            ->with('parentFolder')
            ->get(['id', 'name', 'parent_folder_id'])
            ->map(function ($folder) {
                $displayName = $folder->name;
                if ($folder->parentFolder) {
                    $displayName = $folder->parentFolder->name . ' / ' . $folder->name;
                }
                return [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'display_name' => $displayName
                ];
            });

        return Inertia::render('document-folders/index', [
            'documentFolders' => $documentFolders,
            'parentFolders' => $parentFolders,
            'filters' => $request->all(['search', 'status', 'parent_folder_id', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }

    public function show($id)
    {
        $documentFolder = DocumentFolder::with(['parentFolder', 'creator', 'subFolders'])
            ->where('created_by', createdBy())
            ->findOrFail($id);

        return Inertia::render('document-folders/show', [
            'documentFolder' => $documentFolder,
        ]);
    }

    public function create()
    {
        $parentFolders = DocumentFolder::where('created_by', createdBy())
            ->where('status', 'active')
            ->get(['id', 'name']);

        return Inertia::render('document-folders/create', [
            'parentFolders' => $parentFolders,
        ]);
    }

    public function edit($id)
    {
        $documentFolder = DocumentFolder::where('id', $id)
            ->where('created_by', createdBy())
            ->firstOrFail();

        $parentFolders = DocumentFolder::where('created_by', createdBy())
            ->where('status', 'active')
            ->where('id', '!=', $id) // Exclude current folder to prevent circular reference
            ->get(['id', 'name']);

        return Inertia::render('document-folders/edit', [
            'documentFolder' => $documentFolder,
            'parentFolders' => $parentFolders,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_folder_id' => 'nullable|exists:document_folders,id',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        // Validate unique name with parent_folder_id and created_by
        $exists = DocumentFolder::where('name', $validated['name'])
            ->where('parent_folder_id', $validated['parent_folder_id'] ?? null)
            ->where('created_by', createdBy())
            ->exists();

        if ($exists) {
            return redirect()->back()->withErrors(['name' => __('A folder with this name already exists in the selected location.')])->withInput();
        }

        // Set created_by to company ID for both company and staff users
        $validated['created_by'] = createdBy();
        $validated['status'] = $validated['status'] ?? 'active';

        // Validate parent folder belongs to same company if specified
        if (!empty($validated['parent_folder_id'])) {
            $parentFolder = DocumentFolder::where('id', $validated['parent_folder_id'])
                ->where('created_by', createdBy())
                ->first();

            if (!$parentFolder) {
                return redirect()->back()->with('error', __('Invalid parent folder selected.'));
            }
        }

        DocumentFolder::create($validated);

        return redirect()->back()->with('success', __('Document folder created successfully.'));
    }

    public function update(Request $request, $documentFolderId)
    {
        $documentFolder = DocumentFolder::where('id', $documentFolderId)
            ->where('created_by', createdBy())
            ->first();

        if ($documentFolder) {
            try {
                $validated = $request->validate([
                    'name' => 'required|string|max:255',
                    'parent_folder_id' => 'nullable|exists:document_folders,id',
                    'description' => 'nullable|string',
                    'status' => 'nullable|in:active,inactive',
                ]);

                // Validate unique name with parent_folder_id and created_by
                $exists = DocumentFolder::where('name', $validated['name'])
                    ->where('parent_folder_id', $validated['parent_folder_id'] ?? null)
                    ->where('created_by', createdBy())
                    ->where('id', '!=', $documentFolderId)
                    ->exists();

                if ($exists) {
                    return redirect()->back()->withErrors(['name' => __('A folder with this name already exists in the selected location.')])->withInput();
                }

                $documentFolder->update($validated);

                return redirect()->back()->with('success', __('Document folder updated successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update document folder.'));
            }
        } else {
            return redirect()->back()->with('error', __('Document folder not found.'));
        }
    }

    public function destroy($documentFolderId)
    {
        $documentFolder = DocumentFolder::where('id', $documentFolderId)
            ->where('created_by', createdBy())
            ->first();

        if ($documentFolder) {
            try {
                $documentFolder->delete();
                return redirect()->back()->with('success', __('Document folder deleted successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete document folder.'));
            }
        } else {
            return redirect()->back()->with('error', __('Document folder not found.'));
        }
    }

    public function toggleStatus($documentFolderId)
    {
        $documentFolder = DocumentFolder::where('id', $documentFolderId)
            ->where('created_by', createdBy())
            ->first();

        if ($documentFolder) {
            try {
                $documentFolder->status = $documentFolder->status === 'active' ? 'inactive' : 'active';
                $documentFolder->save();

                return redirect()->back()->with('success', __('Document folder status updated successfully.'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update document folder status.'));
            }
        } else {
            return redirect()->back()->with('error', __('Document folder not found.'));
        }
    }
}
