<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Plan;
use App\Models\Country;
use App\Models\Referral;
use App\Models\ReferralSetting;
use App\Models\LeadStatus;
use App\Models\OpportunityStage;
use App\Models\PlanRequest;
use App\Models\TaskStatus;
use App\Services\UserService;
use App\Services\LicenseKeyService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     *
     * السيناريوهات المدعومة:
     * ─────────────────────────────────────────────────────────────────────
     * | 1. مستخدم جديد (بدون license):
     * |    /register?hardware_id=ABC123
     * |    → يسجل → يختار خطة → يتم إنشاء license جديدة
     * |
     * | 2. مستخدم قديم (عنده license من الديسكتوب):
     * |    /register?hardware_id=ABC123&license_key=VTL-XXXX-XXXX-XXXX
     * |    → يسجل → يتم حفظ الـ license الموجودة مباشرة بدون إنشاء واحدة جديدة
     * |    → يتم توجيهه للداشبورد مباشرة (لا يحتاج اختيار خطة)
     * ─────────────────────────────────────────────────────────────────────
     */
    public function create(Request $request)
    {

        if (!isRegistrationEnabled()) {
            return to_route('login');
        }

        $referralCode = $request->get('ref');
        $encryptedPlanId = $request->get('plan');
        $hardwareId = $request->get('hardware_id');
        $licenseKey = $request->get('license_key'); // للمستخدمين القُدَم اللي عندهم license
        $planId = null;
        $referrer = null;

        // Decrypt and validate plan ID
        if ($encryptedPlanId) {
            $planId = $this->decryptPlanId($encryptedPlanId);
            if ($planId && !Plan::find($planId)) {
                $planId = null; // Invalid plan ID
            }
        }

        if ($referralCode) {
            $referrer = User::where('referral_code', $referralCode)
                ->where('type', 'company')
                ->first();
        }

        // Get all active countries for the select dropdown
        $countries = Country::orderBy('name')->get(['id', 'name', 'code', 'phone_code']);

        // التحقق هل المستخدم القديم (عنده license_key من الديسكتوب)
        $isLegacyUser = !empty($licenseKey);

        return Inertia::render('auth/register', [
            'referralCode' => $referralCode,
            'planId' => $planId,
            'referrer' => $referrer ? $referrer->name : null,
            'countries' => $countries,
            'hardwareId' => $hardwareId,
            'licenseKey' => $licenseKey,
            'isLegacyUser' => $isLegacyUser,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        if (!isRegistrationEnabled()) {
            return to_route('login');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'country_id' => 'required|integer|exists:countries,id',
            'hardware_id' => 'nullable|string|max:255',
            'license_key' => 'nullable|string|max:255', // للمستخدمين القُدَم
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => 'required|accepted',
        ]);

        $isLegacyUser = !empty($request->license_key);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => 'company',
            'is_active' => 1,
            'is_enable_login' => 1,
            'created_by' => 1,
            'company_name' => $request->company_name,
            'phone' => $request->phone,
            'country_id' => $request->country_id,
            'hardware_id' => $request->hardware_id,
        ];

        // ──────────────────────────────────────────────────────────────────
        // مستخدم قديم - عنده license شغال من الديسكتوب
        // ──────────────────────────────────────────────────────────────────
        if ($isLegacyUser) {
            $userData['license_key'] = $request->license_key;
            $userData['plan_is_active'] = 1; // الـ license شغال

            // نحاول نتحقق من الـ license عبر API ونستخرج المعلومات
            $licenseService = app(LicenseKeyService::class);
            $validationResult = $licenseService->validateAndImportLicense(
                $request->license_key,
                $request->hardware_id
            );

            Log::info('RegisteredUserController: Legacy user registration - license validation', [
                'license_key' => $request->license_key,
                'hardware_id' => $request->hardware_id,
                'validation_success' => $validationResult['success'] ?? false,
                'validation_source' => $validationResult['source'] ?? 'none',
            ]);

            // إذا الـ API رجع معلومات مفيدة، نحفظها
            if (!empty($validationResult['license_id'])) {
                $userData['license_id'] = $validationResult['license_id'];
            }

            // إذا الـ API رجع تاريخ انتهاء، نستخدمه
            if (!empty($validationResult['expiration_date'])) {
                try {
                    $expirationDate = \Carbon\Carbon::parse($validationResult['expiration_date']);
                    $userData['plan_expire_date'] = $expirationDate;
                } catch (\Exception $e) {
                    // إذا التاريخ غير صالح، نعطيه سنة افتراضية
                    $userData['plan_expire_date'] = now()->addYear();
                }
            } else {
                // ما عندنا تاريخ انتهاء من API → نعطيه سنة من الآن كافتراضي
                // المستخدم القديم لسه شغال على الديسكتوب فـ الـ license فعّال
                $userData['plan_expire_date'] = now()->addYear();
            }

            // نحاول نعطيه خطة افتراضية إذا ما عنده
            if (empty($userData['plan_id'])) {
                $defaultPlan = Plan::getDefaultPlan();
                if ($defaultPlan) {
                    $userData['plan_id'] = $defaultPlan->id;
                } else {
                    // أول خطة نشطة كافتراضية
                    $firstPlan = Plan::where('status', 'active')->first();
                    if ($firstPlan) {
                        $userData['plan_id'] = $firstPlan->id;
                    }
                }
            }
        } else {
            // ──────────────────────────────────────────────────────────────
            // مستخدم جديد - تعيين الخطة الافتراضية تلقائياً لمدة شهرين
            // ──────────────────────────────────────────────────────────────
            $defaultPlan = Plan::getDefaultPlan();
            if ($defaultPlan) {
                $userData['plan_id'] = $defaultPlan->id;
                $userData['plan_is_active'] = 1;
                $userData['plan_expire_date'] = now()->addMonths(2); // إضافة شهرين للخطة
            } else {
                $userData['plan_is_active'] = 0;
            }
        }

        if ($request->referral_code) {
            $referrer = User::where('referral_code', $request->referral_code)
                ->where('type', 'company')
                ->first();

            if ($referrer) {
                $userData['used_referral_code'] = $request->referral_code;
            }
        }

        $user = User::create($userData);

        // Assign role and settings to the user
        defaultRoleAndSetting($user);

        // Create default lead statuses
        $this->createDefaultLeadStatuses($user->id);

        // Create default opportunity stages
        $this->createDefaultOpportunityStages($user->id);

        // Create default task statuses
        $this->createDefaultTaskStatuses($user->id);

        Auth::login($user);

        // ──────────────────────────────────────────────────────────────────
        // توليد الـ License Key تلقائياً للمستخدم الجديد
        // ──────────────────────────────────────────────────────────────────
        if (!$isLegacyUser && !empty($user->plan_id)) {
            $licenseService = app(LicenseKeyService::class);
            $result = $licenseService->generateLicenseKey(
                $user,
                $user->plan_id,
                'monthly' // القيمة الافتراضية المتوقعة في السيرفيس
            );

            if (isset($result['success']) && $result['success']) {
                // تسجيل الطلب كأنه تمت الموافقة عليه (نفس سلوك زر Generate)
                PlanRequest::create([
                    'user_id' => $user->id,
                    'plan_id' => $user->plan_id,
                    'duration' => 'monthly',
                    'status' => 'approved',
                ]);
                $user->refresh();
            } else {
                Log::error('RegisteredUserController: Failed to auto-generate license key for new user', [
                    'user_id' => $user->id,
                    'plan_id' => $user->plan_id,
                    'result' => $result
                ]);
            }
        }

        // Check if email verification is enabled
        $emailVerificationEnabled = getSetting('emailVerification', false);
        if ($emailVerificationEnabled) {
            event(new Registered($user));
            return redirect()->route('verification.notice');
        }

        // ──────────────────────────────────────────────────────────────────
        // التوجيه النهائي حسب نوع المستخدم
        // ──────────────────────────────────────────────────────────────────
        if ($isLegacyUser) {
            Log::info('RegisteredUserController: Legacy user registered, redirecting to dashboard', [
                'user_id' => $user->id,
                'license_key' => $user->license_key,
            ]);

            return redirect()->route('dashboard')
                ->with('success', __('Your account has been created and your existing license has been linked successfully.'));
        }

        // مستخدم جديد → توجيه مباشرة لصفحة Key Generated Successfully 
        return redirect()->route('license.show')
            ->with('success', __('Your account has been created and your license key generated successfully.'));
    }

    /**
     * Decrypt plan ID from encrypted string
     */
    private function decryptPlanId($encryptedPlanId)
    {
        try {
            $key = 'vCardGo2024';
            $encrypted = base64_decode($encryptedPlanId);
            $decrypted = '';

            for ($i = 0; $i < strlen($encrypted); $i++) {
                $decrypted .= chr(ord($encrypted[$i]) ^ ord($key[$i % strlen($key)]));
            }

            return is_numeric($decrypted) ? (int)$decrypted : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Create referral record when user purchases a plan
     */
    private function createReferralRecord(User $user)
    {
        $settings = ReferralSetting::current();

        if (!$settings->is_enabled) {
            return;
        }

        $referrer = User::where('referral_code', $user->used_referral_code)->first();
        if (!$referrer || !$user->plan) {
            return;
        }

        // Calculate commission based on plan price
        $planPrice = $user->plan->price ?? 0;
        $commissionAmount = ($planPrice * $settings->commission_percentage) / 100;

        if ($commissionAmount > 0) {
            Referral::create([
                'user_id' => $user->id,
                'company_id' => $referrer->id,
                'commission_percentage' => $settings->commission_percentage,
                'amount' => $commissionAmount,
                'plan_id' => $user->plan_id,
            ]);
        }
    }

    /**
     * Create default lead statuses for new company
     */
    private function createDefaultLeadStatuses($userId): void
    {
        $defaultStatuses = [
            ['name' => 'New', 'color' => '#3B82F6'],
            ['name' => 'Contacted', 'color' => '#F59E0B'],
            ['name' => 'Qualified', 'color' => '#10b77f'],
            ['name' => 'Proposal Sent', 'color' => '#8B5CF6'],
            ['name' => 'Converted', 'color' => '#059669'],
            ['name' => 'Lost', 'color' => '#EF4444'],
        ];

        foreach ($defaultStatuses as $status) {
            LeadStatus::create([
                'name' => $status['name'],
                'color' => $status['color'],
                'status' => 'active',
                'created_by' => $userId,
            ]);
        }
    }

    /**
     * Create default opportunity stages for new company
     */
    private function createDefaultOpportunityStages($userId): void
    {
        $defaultStages = [
            ['name' => 'Prospecting', 'color' => '#6B7280', 'probability' => 10],
            ['name' => 'Qualification', 'color' => '#3B82F6', 'probability' => 25],
            ['name' => 'Proposal', 'color' => '#F59E0B', 'probability' => 50],
            ['name' => 'Negotiation', 'color' => '#8B5CF6', 'probability' => 75],
            ['name' => 'Closed Won', 'color' => '#10b77f', 'probability' => 100],
            ['name' => 'Closed Lost', 'color' => '#EF4444', 'probability' => 0],
        ];

        foreach ($defaultStages as $stage) {
            OpportunityStage::create([
                'name' => $stage['name'],
                'color' => $stage['color'],
                'probability' => $stage['probability'],
                'status' => 'active',
                'created_by' => $userId,
            ]);
        }
    }

    /**
     * Create default task statuses for new company
     */
    private function createDefaultTaskStatuses($userId): void
    {
        $defaultStatuses = [
            ['name' => 'To Do', 'color' => '#6B7280'],
            ['name' => 'In Progress', 'color' => '#3B82F6'],
            ['name' => 'Review', 'color' => '#F59E0B'],
            ['name' => 'Done', 'color' => '#10b77f'],
        ];

        foreach ($defaultStatuses as $status) {
            TaskStatus::create([
                'name' => $status['name'],
                'color' => $status['color'],
                'status' => 'active',
                'created_by' => $userId,
            ]);
        }
    }
}
