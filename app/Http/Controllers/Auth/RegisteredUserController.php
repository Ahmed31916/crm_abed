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
use App\Models\TaskStatus;
use App\Services\UserService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(Request $request)
    {

        if (!isRegistrationEnabled()) {
            return to_route('login');
        }

        $referralCode = $request->get('ref');
        $encryptedPlanId = $request->get('plan');
        $hardwareId = $request->get('hardware_id');
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

        return Inertia::render('auth/register', [
            'referralCode' => $referralCode,
            'planId' => $planId,
            'referrer' => $referrer ? $referrer->name : null,
            'countries' => $countries,
            'hardwareId' => $hardwareId,
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
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => 'required|accepted',
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => 'company',
            'is_active' => 1,
            'is_enable_login' => 1,
            'created_by' => 1,
            'plan_is_active' => 0,
            'company_name' => $request->company_name,
            'phone' => $request->phone,
            'country_id' => $request->country_id,
            'hardware_id' => $request->hardware_id,
        ];

        // Handle referral code
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

        // Note: Referral record will be created when user purchases a plan
        // This is handled in the PlanController or payment controllers

        Auth::login($user);

        // Check if email verification is enabled
        $emailVerificationEnabled = getSetting('emailVerification', false);
        if ($emailVerificationEnabled) {
            event(new Registered($user));
            return redirect()->route('verification.notice');
        }

        // Always redirect to plans page for new users to select a plan
        return redirect()->route('plans.index');
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
