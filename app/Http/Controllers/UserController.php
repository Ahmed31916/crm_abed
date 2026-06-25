<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
     *
     * ============================================================
     * MODIFICATION v6: وراثة license_key + hardware_id + plan + slug فريد من الشركة المالكة
     * ============================================================
     * عندما ينشئ مستخدم من نوع "company" موظفاً جديداً:
     *   - يُفرض نوع المستخدم = 'staff'
     *   - يرث license_key, license_id, hardware_id, company_name,
     *     plan_id, plan_is_active, plan_expire_date, is_trial, trial_expire_date
     *     من الشركة المالكة
     *   - slug يُولّد بشكل فريد بتنسيق {company-slug}-staff[-{N}] لتفادي
     *     التعارض مع قيد UNIQUE على users.slug (لا يُورَّث مباشرة)
     *   - يصبح roles اختيارياً (يمكن للشركة إنشاء موظف بدون دور)
     * هذا يضمن أن يستطيع الموظف تسجيل الدخول والدخول مباشرة للوحة
     * التحكم دون توجيهه لصفحة /plans، لأن middleware CheckPlanAccess
     * يعتمد على created_by لفحص خطة الشركة.
     *
     * ملاحظة عن license_key و hardware_id:
     *   - القيم المُرسلة من الفورم (إن وُجدت) تُفضّل على قيم الشركة
     *   - إن لم تُرسل من الفورم، تُورَّث من الشركة المالكة
     *   - هذا يسمح للشركة بتعيين license_key/hardware_id مخصص للموظف
     *     أو تركها فارغة لتوريث القيم الافتراضية للشركة.
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
        // MODIFICATION v6 START: بناء بيانات المستخدم مع وراثة الخطة + slug + license + hardware
        // ============================================================
        $isCompanyCreator = $authUser->type === 'company';

        // قراءة الحقول الاختيارية من الفورم (إن وُجدت) مع trim + normalize empty → null
        $formLicenseKey  = $request->input('license_key');
        $formHardwareId  = $request->input('hardware_id');
        $formLicenseKey  = is_string($formLicenseKey) ? trim($formLicenseKey) : $formLicenseKey;
        $formHardwareId  = is_string($formHardwareId) ? trim($formHardwareId) : $formHardwareId;
        $formLicenseKey  = $formLicenseKey === '' ? null : $formLicenseKey;
        $formHardwareId  = $formHardwareId === '' ? null : $formHardwareId;

        $userData = [
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'created_by' => $created_by,
            'lang'       => $userLang,
        ];

        // إذا المنشئ شركة: نوظّف المستخدم كـ staff و نُورّث بيانات الخطة والـ license + slug
        // هذا يضمن دخوله المباشر للوحة التحكم دون التوجيه لصفحة /plans
        if ($isCompanyCreator) {
            $userData['type']             = 'staff';
            $userData['license_key']      = $formLicenseKey ?? $authUser->license_key;
            $userData['license_id']       = $authUser->license_id;
            $userData['hardware_id']      = $formHardwareId ?? $authUser->hardware_id;
            $userData['plan_id']          = $authUser->plan_id;
            $userData['plan_is_active']   = $authUser->plan_is_active;
            $userData['plan_expire_date'] = $authUser->plan_expire_date;
            $userData['is_trial']         = $authUser->is_trial;
            $userData['trial_expire_date']= $authUser->trial_expire_date;
            // بيانات إضافية تُورَّث من الشركة لسياق الموظف
            $userData['company_name']     = $authUser->company_name;
            $userData['country_id']       = $authUser->country_id;
            $userData['api_environment']  = $authUser->api_environment;
            // ============================================================
            // تنبيه هام حول slug:
            // عمود users.slug عليه قيد UNIQUE في قاعدة البيانات، لذا لا
            // يمكن وراثة slug الشركة مباشرةً للموظف (الموظف الثاني سيفشل
            // بـ Duplicate entry). بدلاً من ذلك نُولّد slug فريد لكل موظف
            // بتنسيق {company-slug}-staff[-{N}].
            // هذا لا يؤثر على مسارات API مثل GET /api/{slug}/products لأن
            // المتحكّم يبحث دائماً بـ where('type', 'company').
            // ============================================================
            $userData['slug'] = $this->generateUniqueStaffSlug($authUser->slug);
        } else {
            // للسوبر ادمن: احترم القيم المُرسلة من الفورم إن وُجدت
            $userData['license_key']      = $formLicenseKey;
            $userData['hardware_id']      = $formHardwareId;
        }
        // ============================================================
        // MODIFICATION v6 END
        // ============================================================

        $user = User::create($userData);

        // ============================================================
        // MODIFICATION: roles أصبح اختيارياً للشركة
        // ============================================================
        // إذا تم تمرير roles: نُعيّن الدور بشكل طبيعي (للسوبر ادمن أو إذا اختارت الشركة دوراً)
        if ($user && $request->filled('roles')) {
            // Convert role names to IDs for syncing
            $role = Role::where('id', $request->roles)
                ->where('created_by', $created_by)->first();

            if ($role) {
                $user->roles()->sync([$role->id]);

                // للشركة: نظلّ نوع المستخدم staff حتى لو كان للدور اسم آخر
                // للسوبر ادمن: نستخدم اسم الدور كنوع (السلوك الأصلي)
                $user->type = $isCompanyCreator ? 'staff' : $role->name;
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
        }
        // إذا لم يتم تمرير roles والمنشئ شركة: نعتبر العملية ناجحة (موظف بدون دور)
        elseif ($user && $isCompanyCreator) {
            // Trigger email notification
            if (isEmailTemplateEnabled('User Created', createdBy()) && !IsDemo()) {
                event(new \App\Events\UserCreated($user, $request->password));
            }

            // Check for email errors
            if (session()->has('email_error')) {
                return redirect()->route('users.index')->with('warning', __('Staff user created successfully, but welcome email failed: ') . session('email_error'));
            }

            return redirect()->route('users.index')->with('success', __('Staff user created successfully. They can now login with their email and password.'));
        }
        // ============================================================
        // MODIFICATION END
        // ============================================================

        return redirect()->back()->with('error', __('Unable to create User. Please try again!'));
    }

    /**
     * توليد slug فريد لموظف جديد.
     *
     * عمود users.slug عليه قيد UNIQUE، لذا لا يمكن وراثة slug الشركة
     * مباشرةً للموظف (الموظف الثاني سيرفض بـ Duplicate entry). هذه الدالة
     * تُولّد slug فريد بتنسيق:
     *   - {company-slug}-staff        (للموظف الأول)
     *   - {company-slug}-staff-1      (للموظف الثاني)
     *   - {company-slug}-staff-2      (للموظف الثالث)
     *   ...
     * وإن لم تكن الشركة تملك slug، نستخدم "staff" كقاعدة.
     *
     * @param  string|null  $companySlug  slug الشركة المالكة (قد يكون null)
     * @return string                    slug فريد للموظف الجديد
     */
    private function generateUniqueStaffSlug(?string $companySlug): string
    {
        // تنظيف slug الشركة: trim + استبدال الفراغات بشرطة + lower
        $cleaned = is_string($companySlug) ? trim($companySlug) : '';
        $cleaned = $cleaned === '' ? '' : \Illuminate\Support\Str::slug($cleaned);

        $base = $cleaned !== '' ? $cleaned . '-staff' : 'staff';
        $slug = $base;
        $counter = 1;

        // حلقة تفادٍ للتعارض: نُزيد اللاحقة الرقمية حتى نجد slug غير مستخدم
        while (User::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Update the specified resource in storage.
     *
     * ============================================================
     * MODIFICATION v6: حفظ license_key + hardware_id عند التعديل
     * ============================================================
     * الإصلاحات:
     *   1) حفظ license_key و hardware_id المرسلين من الفورم.
     *   2) password اختياري عند التعديل: إن أُرسل وغير فارغ نُحدّثه،
     *      وإن لم يُرسل نتركه كما هو (لا نُفرض required على الـ update).
     *   3) لا نطلب roles إلزامياً: إن لم تُرسل نحتفظ بالأدوار الحالية.
     *   4) type يبقى كما هو للشركة (staff) ولا يتأثر بتعديل اسم الدور.
     *
     * v6.4: تم استبدال route model binding (User $user) بمعامل $id صريح
     *       + User::findOrFail($id). هذا يتجنب فشل الـ binding عند وجود:
     *       - getRouteKeyName() مخصص في نموذج User (مثل 'slug').
     *       - global scope يُصفّي المستخدمين.
     *       - policy تُعيد denyAsNotFound.
     *       كذلك أضفنا Log::info لتتبع وصول الطلب للمتحكّم عند التشخيص.
     */
    public function update(UserRequest $request, $id)
    {
 
        $user = User::findOrFail($id);

        if ($user) {
            $user->name  = $request->name;
            $user->email = $request->email;

            // ============================================================
            // MODIFICATION v6 START: حفظ license_key + hardware_id
            // ============================================================
            $licenseKey = $request->input('license_key');
            $hardwareId = $request->input('hardware_id');
            // trim + normalize empty → null
            if (is_string($licenseKey)) {
                $licenseKey = trim($licenseKey);
                $licenseKey = $licenseKey === '' ? null : $licenseKey;
            }
            if (is_string($hardwareId)) {
                $hardwareId = trim($hardwareId);
                $hardwareId = $hardwareId === '' ? null : $hardwareId;
            }
            $user->license_key = $licenseKey;
            $user->hardware_id = $hardwareId;
            // ============================================================
            // MODIFICATION v6 END
            // ============================================================

            // ============================================================
            // MODIFICATION v6 START: تحديث الباسوورد اختيارياً
            // ============================================================
            // فقط إن أُرسل password وغير فارغ نُحدّثه
            $newPassword = $request->input('password');
            if (!empty($newPassword)) {
                $user->password = Hash::make($newPassword);
            }
            // ============================================================
            // MODIFICATION v6 END
            // ============================================================

            // find and syncing role (اختياري)
            if ($request->filled('roles')) {
                if (!in_array(auth()->user()->type, ['superadmin', 'company'])) {
                    $created_by = auth()->user()->created_by;
                } else {
                    $created_by = auth()->id();
                }
                $role = Role::where('id', $request->roles)
                    ->where('created_by', $created_by)->first();

                if ($role) {
                    $user->roles()->sync([$role->id]);
                    // للشركة: نُبقي type كـ staff حتى لو تغيّر الدور
                    // للسوبر ادمن: نستخدم اسم الدور كنوع
                    $isCompanyCreator = auth()->user()->type === 'company';
                    $user->type = $isCompanyCreator ? 'staff' : $role->name;
                }
            }

            $user->save();
            return redirect()->route('users.index')->with('success', __('User updated with roles'));
        }
        return redirect()->back()->with('error', __('Unable to update User. Please try again!'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * v6.4: استبدال route model binding بـ $id + findOrFail لتجنّب 404
     *       الناتج عن فشل الـ binding (مثلاً إذا كان getRouteKeyName يُعيد 'slug').
     */
    public function destroy($id)
    {
        Log::info('[Users] destroy() reached', ['id' => $id, 'auth_id' => Auth::id()]);

        $user = User::findOrFail($id);

        if ($user) {
            $user->delete();
            return redirect()->route('users.index')->with('success', __('User deleted with roles'));
        }
        return redirect()->back()->with('error', __('Unable to delete User. Please try again!'));
    }

    /**
     * Reset user password
     *
     * v6.4: استبدال route model binding بـ $id + findOrFail.
     */
    public function resetPassword(Request $request, $id)
    {
        Log::info('[Users] resetPassword() reached', ['id' => $id, 'auth_id' => Auth::id()]);

        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::findOrFail($id);
        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('users.index')->with('success', __('Password reset successfully'));
    }

    /**
     * Display the specified resource.
     *
     * v6.4: استبدال route model binding بـ $id + findOrFail.
     */
    public function show($id)
    {
        $user = User::findOrFail($id);

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
     *
     * v6.4: استبدال route model binding بـ $id + findOrFail.
     *       كذلك أضفنا Log::info لتتبع وصول الطلب (مفيد لتشخيص "لا يتعدّل الحالة").
     */
    public function toggleStatus($id)
    {
        Log::info('[Users] toggleStatus() reached', ['id' => $id, 'auth_id' => Auth::id()]);

        $user = User::findOrFail($id);
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
     *
     * v6.4: استبدال route model binding بـ $id + findOrFail.
     */
    public function updateProductLimit(Request $request, $id)
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized Access Prevented');
        }

        $request->validate([
            'product_limit' => 'required|integer|min:1',
        ]);

        $user = User::findOrFail($id);
        $user->update([
            'product_limit' => $request->product_limit,
        ]);

        return redirect()->back()->with('success', __('Product limit updated successfully.'));
    }
}
