<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PlanRequest;
use App\Services\LicenseKeyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class LicenseKeyController extends Controller
{
    protected LicenseKeyService $licenseKeyService;

    public function __construct(LicenseKeyService $licenseKeyService)
    {
        $this->licenseKeyService = $licenseKeyService;
    }

    /**
     * Generate a license key for the authenticated user and selected plan.
     * After successful generation, redirects to the license key display page.
     *
     * مهم: إذا المستخدم عنده license_key موجود (مستخدم قديم من الديسكتوب)
     * ما ننشئ له license جديدة - نرجعه لصفحة الـ license الحالية.
     *
     * ملاحظة: الـ Service يقرأ إعدادات API من .env تلقائياً
     * staging → يوصل على API التست
     * production → يوصل على API البرودكشن
     */
    public function generate(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $user = Auth::user();

        // Only company users can generate license keys
        if ($user->type !== 'company') {
            return redirect()->route('plans.index')
                ->with('error', __('Only company users can generate license keys.'));
        }

        // ──────────────────────────────────────────────────────────────────
        // فحص مهم: إذا المستخدم عنده license_key موجود بالفعل
        // هذا يشمل المستخدمين القُدَم اللي استوردوا الـ license من الديسكتوب
        // ما ننشئ license جديدة - الـ license الحالية شغالة ومفعّلة
        // ──────────────────────────────────────────────────────────────────
        if (!empty($user->license_key)) {
            Log::info('LicenseKeyController: User already has a license key, skipping generation', [
                'user_id' => $user->id,
                'environment' => $this->licenseKeyService->getEnvironmentName(),
                'existing_license_key' => $user->license_key,
            ]);

            // نحدّث الخطة بس بدون إنشاء license جديدة
            $plan = Plan::findOrFail($request->plan_id);
            $billingCycle = $request->billing_cycle;

            $user->update([
                'plan_id' => $plan->id,
            ]);

            // Create a PlanRequest record for tracking
            PlanRequest::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'duration' => $billingCycle,
                'status' => 'approved',
            ]);

            // نوجّه لصفحة الـ license الحالية
            return redirect()->route('license.show')
                ->with('info', __('You already have an active license key. No new license was created.'));
        }

        $plan = Plan::findOrFail($request->plan_id);
        $billingCycle = $request->billing_cycle;

        // Update user's plan first
        $user->update([
            'plan_id' => $plan->id,
        ]);

        // Create a PlanRequest record for tracking
        PlanRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'duration' => $billingCycle,
            'status' => 'approved',
        ]);

        // Generate the license key via Vital.Manager API
        // الـ Service يقرأ الإعدادات من .env تلقائياً حسب البيئة
        $result = $this->licenseKeyService->generateLicenseKey(
            $user,
            $plan->id,
            $billingCycle
        );

        // Verify the data was actually saved
        $user->refresh();

        Log::info('LicenseKeyController: After generation', [
            'user_id' => $user->id,
            'environment' => $this->licenseKeyService->getEnvironmentName(),
            'result_success' => $result['success'] ?? false,
            'result_license_key' => $result['license_key'] ?? 'NOT SET',
            'db_license_key' => $user->license_key ?? 'NULL',
            'db_license_id' => $user->license_id ?? 'NULL',
            'db_plan_is_active' => $user->plan_is_active ?? 'NULL',
        ]);

        if ($result['success'] && $user->license_key) {
            // Get currency settings
            $currencySymbol = getSetting('currency_symbol', '$');

            // Render the license key display page
            return Inertia::render('plans/license-key', [
                'licenseKey' => $user->license_key,
                'planName' => $plan->name,
                'planPrice' => $plan->getPriceForCycle($billingCycle),
                'planDuration' => $billingCycle === 'yearly' ? 'Yearly' : $plan->duration,
                'expiresAt' => $user->plan_expire_date ? $user->plan_expire_date->format('Y-m-d') : __('N/A'),
                'currencySymbol' => $currencySymbol,
                'issuedTo' => $user->company_name ?? $user->name,
                'licenseId' => $user->license_id ?? $result['license_id'] ?? null,
                'isActivated' => !empty($user->hardware_id),
                'environment' => $this->licenseKeyService->getEnvironmentName(),
            ]);
        }

        // If license generation failed, redirect back to plans page with error
        $errorMessage = $result['message'] ?? __('Failed to generate license key. Please try again.');
        
        // Also log the failure details
        Log::error('LicenseKeyController: License generation failed', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'environment' => $this->licenseKeyService->getEnvironmentName(),
            'result' => $result,
            'user_license_key_after' => $user->license_key,
        ]);

        return redirect()->route('plans.index')
            ->with('error', $errorMessage);
    }

    /**
     * Show the license key display page for the authenticated user.
     */
    public function show(Request $request)
    {
        $user = Auth::user();

        // Only company users can view license keys
        if ($user->type !== 'company') {
            return redirect()->route('plans.index');
        }

        // If user doesn't have a license key yet, redirect to plans page
        if (empty($user->license_key)) {
            return redirect()->route('plans.index')
                ->with('warning', __('Please select a plan first to receive your license key.'));
        }

        $plan = $user->plan;
        if (!$plan) {
            return redirect()->route('plans.index')
                ->with('error', __('Plan not found. Please contact support.'));
        }

        // Determine the billing cycle from the plan expire date
        $billingCycle = 'monthly';
        if ($user->plan_expire_date) {
            $diffInDays = now()->diffInDays($user->plan_expire_date);
            if ($diffInDays > 60) {
                $billingCycle = 'yearly';
            }
        }

        // Get currency settings
        $currencySymbol = getSetting('currency_symbol', '$');

        return Inertia::render('plans/license-key', [
            'licenseKey' => $user->license_key,
            'planName' => $plan->name,
            'planPrice' => $plan->getPriceForCycle($billingCycle),
            'planDuration' => $billingCycle === 'yearly' ? 'Yearly' : $plan->duration,
            'expiresAt' => $user->plan_expire_date ? $user->plan_expire_date->format('Y-m-d') : __('N/A'),
            'currencySymbol' => $currencySymbol,
            'issuedTo' => $user->company_name ?? $user->name,
            'licenseId' => $user->license_id,
            'isActivated' => !empty($user->hardware_id),
            'environment' => $this->licenseKeyService->getEnvironmentName(),
        ]);
    }

    /**
     * Validate a license key against the Vital.Manager API.
     *
     * POST /api/licenses/validate (multipart/form-data)
     */
    public function validate(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string',
            'hardware_id' => 'nullable|string',
        ]);

        $result = $this->licenseKeyService->validateLicenseKey(
            $request->license_key,
            $request->hardware_id
        );

        return response()->json($result);
    }

    /**
     * Activate a license on a specific hardware device.
     *
     * POST /api/licenses/activate
     */
    public function activate(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string',
            'hardware_id' => 'required|string',
        ]);

        $result = $this->licenseKeyService->activateLicense(
            $request->hardware_id,
            $request->license_key
        );

        return response()->json($result);
    }

    /**
     * Submit a trial license request.
     *
     * POST /api/trial-requests (public endpoint)
     */
    public function submitTrialRequest(Request $request)
    {
        $user = Auth::user();

        $result = $this->licenseKeyService->submitTrialRequest($user);

        return response()->json($result);
    }
}
