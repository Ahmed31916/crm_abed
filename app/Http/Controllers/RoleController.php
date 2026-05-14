<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Inertia\Inertia;
use App\Models\Permission;
use Illuminate\Support\Str;
use App\Http\Requests\RoleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleController extends BaseController
{
    /**
     * Constructor to apply middleware
     */


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Role::withPermissionCheck()->with(['permissions', 'creator']);

        // Handle search
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('label', 'like', '%' . $request->search . '%')
                    ->orWhere('name', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Handle sorting
        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts=['id', 'label', 'name', 'created_at'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $perPage = $request->get('per_page', 10);
        $roles = $query->paginate((int)$perPage);

        $permissions = $this->getFilteredPermissions();

        return Inertia::render('roles/index', [
            'roles'       => $roles,
            'permissions' => $permissions,
            'filters' => $request->all(['search', 'sort_field', 'sort_direction', 'per_page']),
        ]);
    }

    /**
     * Get permissions filtered by user role
     */
    private function getFilteredPermissions()
    {
        $user = Auth::user();
        $userType = $user->type ?? 'company';

        // Superadmin can see all permissions
        if ($userType === 'superadmin' || $userType === 'super admin') {
            return Permission::all()->groupBy('module');
        }

        // Get allowed modules - use company config for both company and staff users
        $allowedModules = config('role-permissions.company');

        // Filter permissions by allowed modules
        $query = Permission::whereIn('module', $allowedModules);

        // For company users, exclude notification template permissions from role management
        if ($userType === 'company') {
            $query->where(function ($q) {
                $q->where('module', '!=', 'settings')
                    ->where('module', '!=', 'notification_templates')
                    ->orWhereIn('name', [
                        'manage-email-settings',
                        'manage-system-settings',
                        'manage-brand-settings'
                    ]);
            });
        }

        $permissions = $query->get()->groupBy('module');

        return $permissions;
    }

    /**
     * Validate permissions against user's allowed modules
     */
    private function validatePermissions(array $permissionNames)
    {
        $user = Auth::user();
        $userType = $user->type ?? 'company';

        // Superadmin can assign any permission
        if ($userType === 'superadmin' || $userType === 'super admin') {
            return $permissionNames;
        }

        // Get allowed modules for current user role
        $allowedModules = config('role-permissions.' . $userType, config('role-permissions.company'));

        // Build query to get valid permissions
        $query = Permission::whereIn('module', $allowedModules)
            ->whereIn('name', $permissionNames);

        // For company users, restrict settings and notification template permissions
        if ($userType === 'company') {
            $query->where(function ($q) {
                $q->where('module', '!=', 'settings')
                    ->where('module', '!=', 'notification_templates')
                    ->orWhereIn('name', [
                        'manage-email-settings',
                        'manage-system-settings',
                        'manage-brand-settings'
                    ]);
            });
        }

        $validPermissions = $query->pluck('name')->toArray();

        return $validPermissions;
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
    public function store(RoleRequest $request)
    {
        // Validate permissions against user's allowed modules
        $validatedPermissions = $this->validatePermissions($request->permissions ?? []);

        // Use direct model creation to bypass Spatie's duplicate check
        $role = new Role();
        $role->label = $request->label;
        $role->name = Str::slug($request->label);
        $role->description = $request->description;
        $role->created_by = createdBy();
        $role->guard_name = 'web';
        $role->save();

        if ($role) {
            $role->syncPermissions($validatedPermissions);

            return redirect()->route('roles.index')->with('success', __('Role created successfully with Permissions!'));
        }
        return redirect()->back()->with('error', __('Unable to create Role with permissions. Please try again!'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleRequest $request, Role $role)
    {
        if ($role) {
            // Validate permissions against user's allowed modules
            $validatedPermissions = $this->validatePermissions($request->permissions ?? []);

            $newSlug = Str::slug($request->label);

            // Only update name if it's different to avoid duplicate key error
            if ($role->name !== $newSlug && !in_array($role->name, isDisabledEditRole())) {
                $role->name = $newSlug;
                $role->label = $request->label;
            }

            $role->description = $request->description;

            $role->save();

            # Update the permissions
            $role->syncPermissions($validatedPermissions);

            return redirect()->route('roles.index')->with('success', __('Role updated successfully with Permissions!'));
        }
        return redirect()->back()->with('error', __('Unable to update Role with permissions. Please try again!'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        if ($role) {
            // Prevent deletion of system roles
            if ($role->is_system_role || in_array($role->name, isDisabledDeleteRole())) {
                return redirect()->back()->with('error', __('System roles cannot be deleted!'));
            }

            // if the role has users assigned, prevent deletion
            if ($role->users()->count() > 0) {
                return redirect()->back()->with('error', __('Role cannot be deleted as it is assigned to users!'));
            }

            $role->delete();

            return redirect()->route('roles.index')->with('success', __('Role deleted successfully!'));
        }
        return redirect()->back()->with('error', __('Unable to delete Role. Please try again!'));
    }
}
