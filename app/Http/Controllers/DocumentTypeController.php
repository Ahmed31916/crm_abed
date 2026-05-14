<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class DocumentTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = DocumentType::with(['creator']);

        // Apply search filter
        if ($request->filled('search')) {
            $query->where('type_name', 'like', '%' . $request->search . '%');
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply sorting
       $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts=['id', 'type_name', 'created_at'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        // Apply authorization filter
        $query->where('created_by', createdBy());

        $perPage = $request->get('per_page', 10);
        $documentTypes = $query->paginate((int)$perPage);

        return Inertia::render('document-types/index', [
            'documentTypes' => $documentTypes,
            'filters' => $request->only(['search', 'status', 'sort_field', 'sort_direction', 'per_page'])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type_name' => 'required|string|max:255|unique:document_types,type_name,NULL,id,created_by,' . createdBy(),
            'status' => 'required|in:active,inactive',
        ]);

        DocumentType::create([
            'type_name' => $request->type_name,
            'status' => $request->status,
            'created_by' => createdBy(),
        ]);

        return redirect()->route('document-types.index')
            ->with('success', __('Document type created successfully.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(DocumentType $documentType)
    {
        // Check if user has access to this document type
        if ($documentType->created_by !== createdBy()) {
            abort(403, 'Unauthorized access.');
        }

        $documentType->load(['creator']);

        return Inertia::render('document-types/show', [
            'documentType' => $documentType
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DocumentType $documentType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DocumentType $documentType)
    {
        // Check if user has access to this document type
        if ($documentType->created_by !== createdBy()) {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'type_name' => 'required|string|max:255|unique:document_types,type_name,' . $documentType->id . ',id,created_by,' . createdBy(),
            'status' => 'required|in:active,inactive',
        ]);

        $documentType->update([
            'type_name' => $request->type_name,
            'status' => $request->status,
        ]);

        return redirect()->route('document-types.index')
            ->with('success', __('Document type updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DocumentType $documentType)
    {
        // Check if user has access to this document type
        if ($documentType->created_by !== createdBy()) {
            abort(403, 'Unauthorized access.');
        }

        $documentType->delete();

        return redirect()->route('document-types.index')
            ->with('success', __('Document type deleted successfully.'));
    }

    /**
     * Toggle the status of the specified resource.
     */
    public function toggleStatus(DocumentType $documentType)
    {
        // Check if user has access to this document type
        if ($documentType->created_by !== createdBy()) {
            abort(403, 'Unauthorized access.');
        }

        $newStatus = $documentType->status === 'active' ? 'inactive' : 'active';
        $documentType->update(['status' => $newStatus]);

        return redirect()->route('document-types.index')
            ->with('success', __('Document type status updated successfully.'));
    }
}
