<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $authUser     = Auth::user();
        $authUserRole = $authUser->roles->first()?->name;
        // Allow superadmin, admin, product-manager, contact-manager, viewer
        if (!$authUser->hasPermissionTo('view-users')) {
            abort(403, 'Unauthorized Access Prevented');
        }

        $userQuery = User::withPermissionCheck()->with(['roles', 'creator']);
        # Admin
        if ($authUserRole === 'super admin') {
            $userQuery->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'super admin');
            });
        }

        // Handle search
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $userQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Handle role filter
        if ($request->has('role') && $request->role !== 'all') {
            $userQuery->whereHas('roles', function ($q) use ($request) {
                $q->where('roles.id', $request->role);
            });
        }

        // Handle sorting
        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts = ['id', 'name', 'created_at'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $userQuery->orderBy($sortField, $sortDirection);
        }

        // Handle pagination
        $perPage = $request->has('per_page') ? (int)$request->per_page : 10;
        $users = $userQuery->paginate((int)$perPage)->withQueryString();
        // Add product count for companies (super admin view)
        if (auth()->user()->isSuperAdmin()) {
            $users->each(function ($user) {
                if ($user->type === 'company') {
                    $user->product_count = \App\Models\Product::where('created_by', $user->id)->count();
                    // product_limit already exists on the user model
                }
            });
        }
        # Roles listing - Get roles based on user type
        if ($authUser->type === 'company') {
            $roles = Role::where('created_by', $authUser->id)->get();
        } elseif ($authUser->type === 'superadmin') {
            $roles = Role::get();
        } else {
            // Staff users see roles from their company
            $roles = Role::where('created_by', $authUser->created_by)->get();
        }

        // Get plan limits for company users and staff users
        $planLimits = null;
        if ($authUser->type === 'company' && $authUser->plan) {
            $currentUserCount = User::where('created_by', $authUser->id)->count();
            $planLimits = [
                'current_users' => $currentUserCount,
                'max_users' => $authUser->plan->max_users,
                'can_create' => $currentUserCount < $authUser->plan->max_users
            ];
        }
        // Check for staff users (created by company users)
        elseif ($authUser->type !== 'superadmin' && $authUser->created_by) {
            $companyUser = User::find($authUser->created_by);
            if ($companyUser && $companyUser->type === 'company' && $companyUser->plan) {
                $currentUserCount = User::where('created_by', $companyUser->id)->count();
                $planLimits = [
                    'current_users' => $currentUserCount,
                    'max_users' => $companyUser->plan->max_users,
                    'can_create' => $currentUserCount < $companyUser->plan->max_users
                ];
            }
        }

        return Inertia::render('users/index', [
            'users' => $users,
            'roles' => $roles,
            'planLimits' => $planLimits,
            'isSuperAdmin' => auth()->user()->isSuperAdmin(),
            'filters' => $request->only(['search', 'role', 'sort_field', 'sort_direction', 'per_page', 'view', 'page'])
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request)
    {
        // Set user language same as creator (company)
        $authUser = Auth::user();

        $companySettings = settings();
        $userLang = isset($companySettings['defaultLanguage']) ? $companySettings['defaultLanguage'] : $authUser->lang;
        // Check plan limits for company users
        if ($authUser->type === 'company' && $authUser->plan) {
            $currentUserCount = User::where('created_by', $authUser->id)->count();
            $maxUsers = $authUser->plan->max_users;

            if ($currentUserCount >= $maxUsers) {
                return redirect()->back()->with('error', __('User limit exceeded. Your plan allows maximum :max users. Please upgrade your plan.', ['max' => $maxUsers]));
            }
        }
        // Check plan limits for staff users (created by company users)
        elseif ($authUser->type !== 'superadmin' && $authUser->created_by) {
            $companyUser = User::find($authUser->created_by);
            if ($companyUser && $companyUser->type === 'company' && $companyUser->plan) {
                $currentUserCount = User::where('created_by', $companyUser->id)->count();
                $maxUsers = $companyUser->plan->max_users;

                if ($currentUserCount >= $maxUsers) {
                    return redirect()->back()->with('error', __('User limit exceeded. Your company plan allows maximum :max users. Please contact your administrator.', ['max' => $maxUsers]));
                }
            }
        }

        if (!in_array(auth()->user()->type, ['superadmin', 'company'])) {
            $created_by = auth()->user()->created_by;
        } else {
            $created_by = auth()->id();
        }

        // ============================================================
        // LICENSE ID + HARDWARE ID (v4 addition)
        // ============================================================
        // Read the two optional license-related fields from the request.
        // They are validated in UserRequest as nullable strings; if the
        // request didn't include them at all we fall back to null.
        // These are stored verbatim on the user row — no API call is made
        // here. The desktop client (or LicenseKeyService) can later use
        // them to activate / verify the license.
        $licenseId  = $request->input('license_id');
        $hardwareId = $request->input('hardware_id');

        // Trim whitespace and convert empty strings to null so the DB
        // stores a clean NULL instead of ''.
        $licenseId  = is_string($licenseId)  ? trim($licenseId)  : $licenseId;
        $hardwareId = is_string($hardwareId) ? trim($hardwareId) : $hardwareId;
        $licenseId  = $licenseId  !== '' ? $licenseId  : null;
        $hardwareId = $hardwareId !== '' ? $hardwareId : null;

        $user = User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'created_by'  => $created_by,
            'lang'        => $userLang,
            'license_id'  => $licenseId,
            'hardware_id' => $hardwareId,
        ]);

        if ($user && $request->roles) {
            // Convert role names to IDs for syncing
            $role = Role::where('id', $request->roles)
                ->where('created_by', $created_by)->first();

            $user->roles()->sync([$role->id]);
            $user->type = $role->name;
            $user->save();

            // Trigger email notification
            if (isEmailTemplateEnabled('User Created', createdBy()) && !IsDemo()) {
                event(new \App\Events\UserCreated($user, $request->password));
            }

            // Check for email errors
            if (session()->has('email_error')) {
                return redirect()->route('users.index')->with('warning', __('User created successfully, but welcome email failed: ') . session('email_error'));
            }

            return redirect()->route('users.index')->with('success', __('User created with roles'));
        }
        return redirect()->back()->with('error', __('Unable to create User. Please try again!'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, User $user)
    {
        if ($user) {
            $user->name  = $request->name;
            $user->email = $request->email;

            // ============================================================
            // LICENSE ID + HARDWARE ID (v4 addition)
            // ============================================================
            // Same trim/normalize logic as in store(). Allows the admin
            // to update or clear these fields later from the edit form.
            $licenseId  = $request->input('license_id');
            $hardwareId = $request->input('hardware_id');

            $licenseId  = is_string($licenseId)  ? trim($licenseId)  : $licenseId;
            $hardwareId = is_string($hardwareId) ? trim($hardwareId) : $hardwareId;
            $user->license_id  = $licenseId  !== '' ? $licenseId  : null;
            $user->hardware_id = $hardwareId !== '' ? $hardwareId : null;

            // find and syncing role
            if ($request->roles) {
                if (!in_array(auth()->user()->type, ['superadmin', 'company'])) {
                    $created_by = auth()->user()->created_by;
                } else {
                    $created_by = auth()->id();
                }
                $role = Role::where('id', $request->roles)
                    ->where('created_by', $created_by)->first();

                $user->roles()->sync([$role->id]);
                $user->type = $role->name;
            }

            $user->save();
            return redirect()->route('users.index')->with('success', __('User updated with roles'));
        }
        return redirect()->back()->with('error', __('Unable to update User. Please try again!'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if ($user) {
            $user->delete();
            return redirect()->route('users.index')->with('success', __('User deleted with roles'));
        }
        return redirect()->back()->with('error', __('Unable to delete User. Please try again!'));
    }

    /**
     * Reset user password
     */
    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('users.index')->with('success', __('Password reset successfully'));
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        // Get meetings where user is an attendee
        $meetings = \App\Models\Meeting::where('created_by', createdBy())
            ->whereHas('attendees', function ($q) use ($user) {
                $q->where('attendee_type', 'user')
                    ->where('attendee_id', $user->id);
            })
            ->with(['creator', 'assignedUser'])
            ->orderBy('start_date', 'desc')
            ->get();

        return Inertia::render('users/show', [
            'user' => $user->load(['roles', 'creator']),
            'meetings' => $meetings
        ]);
    }

    /**
     * Toggle user status
     */
    public function toggleStatus(User $user)
    {
        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        return redirect()->route('users.index')->with('success', __('User status updated successfully'));
    }


    /**
     * Display all user logs created by current user
     */
    public function allUserLogs(Request $request)
    {
        $authUser = Auth::user();

        if ($authUser->type === 'superadmin') {
            // For superadmin: show superadmin logs and company type logs created by superadmin
            $loginHistoriesQuery = \App\Models\LoginHistory::whereHas('user', function ($q) {
                $q->where('type', 'superadmin')
                    ->orWhere(function ($subQ) {
                        $subQ->where('type', 'company');
                    });
            })
                ->with('user')
                ->orderBy('created_at', 'desc');
        } else {
            // For other users: show logs created by current user
            $loginHistoriesQuery = \App\Models\LoginHistory::where('created_by', createdBy())
                ->with('user')
                ->orderBy('created_at', 'desc');
        }

        // Handle search
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $loginHistoriesQuery->where(function ($q) use ($search) {
                $q->where('ip', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Handle pagination
        $perPage = $request->get('per_page', 10);
        $loginHistories = $loginHistoriesQuery->paginate((int)$perPage)->withQueryString();

        return Inertia::render('users/all-logs', [
            'loginHistories' => $loginHistories,
            'filters' => [
                'search' => $request->search ?? '',
                'per_page' => $perPage,
            ],
        ]);
    }

    // switchBusiness method removed

    /**
     * Update the product limit for a specific company user.
     * Only super admin can update product limits.
     */
    public function updateProductLimit(Request $request, User $user)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized Access Prevented');
        }

        $request->validate([
            'product_limit' => 'required|integer|min:1',
        ]);

        $user->update([
            'product_limit' => $request->product_limit,
        ]);

        return redirect()->back()->with('success', __('Product limit updated successfully.'));
    }
}
