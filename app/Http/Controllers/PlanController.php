<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PlanOrder;
use App\Models\PlanRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PlanController extends Controller
{
    /**
     * Display the plans page.
     * For new users without a plan/license key, shows the select-plan page.
     * For admin users, shows the admin plans management page.
     * For existing users with plans, shows the subscription management page.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $billingCycle = $request->input('billing_cycle', 'monthly');
        $selectedPlanId = $request->get('selected');

        // Get currency settings
        $currency = getSetting('currency', 'USD');
        $currencySymbol = getSetting('currency_symbol', '$');

        // Check if user is admin/superadmin
        $isAdmin = $user->isSuperAdmin() || $user->isAdmin();

        // Check if user has a license key (completed plan selection)
        $hasLicenseKey = !empty($user->license_key);

        // Get current plan details
        $currentPlan = null;
        if ($user->plan_id) {
            $plan = $user->plan;
            $currentPlan = [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => $plan->price,
                'expires_at' => $user->plan_expire_date ? $user->plan_expire_date->format('Y-m-d') : null,
            ];
        }

        // Check if user has used trial
        $userTrialUsed = $user->is_trial ?? false;

        // Fetch plans based on user type (Admin sees all, Company sees active only)
        $dbPlans = $isAdmin ? Plan::all() : Plan::where('is_plan_enable', 'on')->get();
        $hasDefaultPlan = Plan::where('is_default', true)->exists();

        $plans = $dbPlans->map(function ($plan) use ($billingCycle, $user, $currencySymbol) {
            $price = $plan->getPriceForCycle($billingCycle);
            
            $isCurrent = false;
            $isTrialAvailable = false;

            if ($user->type === 'company') {
                $isCurrent = $user->plan_id === $plan->id;
                $isTrialAvailable = $plan->is_trial && $plan->trial_day > 0;
            }

            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => $price,
                'formatted_price' => $currencySymbol . number_format($price, 2),
                'duration' => $billingCycle === 'yearly' ? 'Yearly' : $plan->duration,
                'description' => $plan->description,
                'trial_days' => $plan->trial_day ?? 0,
                'features' => $plan->module ? array_keys(array_filter($plan->module)) : [],
                'stats' => [
                    'users' => $plan->max_users ?? 'Unlimited',
                    'projects' => $plan->max_projects ?? 'Unlimited',
                    'contacts' => $plan->max_contacts ?? 'Unlimited',
                    'accounts' => $plan->max_accounts ?? 'Unlimited',
                    'storage' => $plan->storage_limit ? $plan->storage_limit . ' GB' : 'Unlimited',
                ],
                'status' => $plan->is_plan_enable,
                'recommended' => $plan->is_default,
                'is_default' => $plan->is_default,
                'is_current' => $isCurrent,
                'is_trial_available' => $isTrialAvailable,
            ];
        });

        // Mark the plan with most subscribers as recommended (kept from original file logic)
        $planSubscriberCounts = Plan::withCount('users')->get()->pluck('users_count', 'id');
        if ($planSubscriberCounts->isNotEmpty()) {
            $mostSubscribedPlanId = $planSubscriberCounts->keys()->sortByDesc(function ($planId) use ($planSubscriberCounts) {
                return $planSubscriberCounts[$planId];
            })->first();

            $plans = $plans->map(function ($plan) use ($mostSubscribedPlanId) {
                if ($plan['id'] == $mostSubscribedPlanId && $plan['price'] != 0) {
                    $plan['recommended'] = true;
                }
                return $plan;
            });
        }

        // For new users without a license key, render the select-plan page
        if (!$isAdmin && !$hasLicenseKey && $user->type === 'company') {
            return Inertia::render('plans/select-plan', [
                'plans' => $plans,
                'billingCycle' => $billingCycle,
                'currentPlan' => $currentPlan,
                'userTrialUsed' => $userTrialUsed,
                'currency' => $currency,
                'currencySymbol' => $currencySymbol,
                'hasLicenseKey' => $hasLicenseKey,
            ]);
        }

        // For admin users or users with existing plans, render the standard plans page
        return Inertia::render('plans/index', [
            'plans' => $plans,
            'billingCycle' => $billingCycle,
            'isAdmin' => $isAdmin,
            'currentPlan' => $currentPlan,
            'userTrialUsed' => $userTrialUsed,
            'hasDefaultPlan' => $hasDefaultPlan,
            'currency' => $currency,
            'currencySymbol' => $currencySymbol,
        ]);
    }

    /**
     * Toggle plan status (admin only).
     */
    public function toggleStatus(Plan $plan)
    {
        $plan->update([
            'is_plan_enable' => !$plan->is_plan_enable,
        ]);

        return redirect()->back()
            ->with('success', __('Plan status updated successfully.'));
    }

    /**
     * Show the form for creating a new plan (admin only).
     */
    public function create()
    {
        $hasDefaultPlan = Plan::where('is_default', true)->exists();

        return Inertia::render('plans/create', [
            'hasDefaultPlan' => $hasDefaultPlan
        ]);
    }

    /**
     * Store a newly created plan (admin only).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:plans',
            'price' => 'required|numeric|min:0',
            'yearly_price' => 'nullable|numeric|min:0',
            'duration' => 'required|string|in:Monthly,Yearly,Lifetime',
            'description' => 'nullable|string',
            'max_users' => 'nullable|integer',
            'max_projects' => 'nullable|integer',
            'max_contacts' => 'nullable|integer',
            'max_accounts' => 'nullable|integer',
            'storage_limit' => 'nullable|numeric',
            'enable_branding' => 'nullable|boolean',
            'enable_chatgpt' => 'nullable|boolean',
            'module' => 'nullable|array',
            'is_trial' => 'boolean',
            'trial_day' => 'nullable|integer',
            'is_plan_enable' => 'boolean',
            'is_default' => 'boolean',
        ]);

        // Set default values for nullable fields
        $validated['enable_branding'] = $validated['enable_branding'] ?? true;
        $validated['enable_chatgpt'] = $validated['enable_chatgpt'] ?? false;
        $validated['is_trial'] = $validated['is_trial'] ?? false;
        $validated['is_plan_enable'] = $validated['is_plan_enable'] ?? true;
        $validated['is_default'] = $validated['is_default'] ?? false;

        // If yearly_price is not provided, calculate it as 80% of monthly price * 12
        if (!isset($validated['yearly_price']) || $validated['yearly_price'] === null) {
            $validated['yearly_price'] = $validated['price'] * 12 * 0.8;
        }

        // If this plan is set as default, remove default status from other plans
        if ($validated['is_default']) {
            Plan::where('is_default', true)->update(['is_default' => false]);
        }

        Plan::create($validated);

        return redirect()->route('plans.index')
            ->with('success', __('Plan created successfully.'));
    }

    /**
     * Show the form for editing a plan (admin only).
     */
    public function edit(Plan $plan)
    {
        $otherDefaultPlanExists = Plan::where('is_default', true)
            ->where('id', '!=', $plan->id)
            ->exists();

        return Inertia::render('plans/edit', [
            'plan' => $plan,
            'otherDefaultPlanExists' => $otherDefaultPlanExists
        ]);
    }

    /**
     * Update the specified plan (admin only).
     */
    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:plans,name,' . $plan->id,
            'price' => 'required|numeric|min:0',
            'yearly_price' => 'nullable|numeric|min:0',
            'duration' => 'required|string|in:Monthly,Yearly,Lifetime',
            'description' => 'nullable|string',
            'max_users' => 'nullable|integer',
            'max_projects' => 'nullable|integer',
            'max_contacts' => 'nullable|integer',
            'max_accounts' => 'nullable|integer',
            'storage_limit' => 'nullable|numeric',
            'enable_branding' => 'nullable|boolean',
            'enable_chatgpt' => 'nullable|boolean',
            'module' => 'nullable|array',
            'is_trial' => 'boolean',
            'trial_day' => 'nullable|integer',
            'is_plan_enable' => 'boolean',
            'is_default' => 'boolean',
        ]);

        // Set default values for nullable fields
        $validated['enable_branding'] = $validated['enable_branding'] ?? true;
        $validated['enable_chatgpt'] = $validated['enable_chatgpt'] ?? false;
        $validated['is_trial'] = $validated['is_trial'] ?? false;
        $validated['is_plan_enable'] = $validated['is_plan_enable'] ?? true;
        $validated['is_default'] = $validated['is_default'] ?? false;

        // If yearly_price is not provided, calculate it as 80% of monthly price * 12
        if (!isset($validated['yearly_price']) || $validated['yearly_price'] === null) {
            $validated['yearly_price'] = $validated['price'] * 12 * 0.8;
        }

        // If this plan is set as default, remove default status from other plans
        if ($validated['is_default'] && !$plan->is_default) {
            Plan::where('is_default', true)->update(['is_default' => false]);
        }

        $plan->update($validated);

        return redirect()->route('plans.index')
            ->with('success', __('Plan updated successfully.'));
    }

    /**
     * Remove the specified plan (admin only).
     */
    public function destroy(Plan $plan)
    {
        if ($plan->is_default) {
            return redirect()->route('plans.index')
                ->with('error', __('Cannot delete the default plan.'));
        }

        // Don't allow deleting plans assigned to users (Kept from attached file for safety)
        if ($plan->users()->count() > 0) {
            return redirect()->route('plans.index')
                ->with('error', __('The company has subscribed to this plan, so it cannot be deleted.'));
        }

        $plan->delete();

        return redirect()->route('plans.index')
            ->with('success', __('Plan deleted successfully.'));
    }

    /**
     * Handle plan request from company user.
     */
    public function requestPlan(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $user = Auth::user();

        // Check if user already has a pending request
        $existingRequest = PlanRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return redirect()->route('plans.index')
                ->with('error', __('You already sent request to another plan'));
        }

        $plan = Plan::findOrFail($request->plan_id);

        // Store the requested plan on the user model (Modification from File 1)
        $user->update([
            'requested_plan' => $request->plan_id,
        ]);

        PlanRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'duration' => $request->billing_cycle,
            'status' => 'pending'
        ]);

        return redirect()->route('plans.index')
            ->with('success', __('Plan request submitted successfully. We will review your request shortly.'));
    }

    /**
     * Cancel a plan request
     */
    public function cancelRequest(Request $request)
    {
        $request->validate([
            'request_id' => 'required|exists:plan_requests,id'
        ]);

        $planRequest = PlanRequest::findOrFail($request->request_id);
        $planRequest->delete();

        return redirect()->back()
            ->with('success', __('Plan request cancelled successfully'));
    }

    /**
     * Start a trial for the given plan.
     */
    public function startTrial(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
        ]);

        $user = Auth::user();
        $plan = Plan::findOrFail($request->plan_id);

        if (!$plan->is_trial || !$plan->trial_day) {
            return redirect()->route('plans.index')
                ->with('error', __('This plan does not offer a trial period.'));
        }

        if ($user->is_trial) {
            return redirect()->route('plans.index')
                ->with('error', __('You have already used your trial period.'));
        }

        $user->update([
            'plan_id' => $plan->id,
            'is_trial' => 1,
            'trial_day' => $plan->trial_day,
            'trial_expire_date' => now()->addDays($plan->trial_day),
            'plan_is_active' => 1, // Modification from File 1
        ]);

        return redirect()->route('plans.index')
            ->with('success', __('Trial started successfully. Enjoy your {{days}} day trial!', ['days' => $plan->trial_day]));
    }

    /**
     * Subscribe to a plan.
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
        ]);

        $user = Auth::user();
        $plan = Plan::findOrFail($request->plan_id);
        $price = $plan->getPriceForCycle($request->billing_cycle);

        PlanOrder::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'original_price' => $price,
            'final_price' => $price,
            'status' => 'pending'
        ]);

        return redirect()->route('plans.index')
            ->with('success', __('Subscription request submitted successfully.'));
    }
}