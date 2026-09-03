<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\PlanOrder;
use App\Models\PlanRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PlanController extends Controller
{
    /**
     * Display the plans page.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $billingCycle = $request->input('billing_cycle', 'monthly');

        // Get currency settings
        $currency = getSetting('currency', 'USD');
        $currencySymbol = getSetting('currency_symbol', '$');

        // Check if user is admin/superadmin
        $isAdmin = $user->isSuperAdmin() || $user->isAdmin();

        // Check if user has a license key
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

        // Fetch plans based on user type
        $dbPlans = $isAdmin ? Plan::all() : Plan::where('is_plan_enable', 'on')->get();
        $hasDefaultPlan = Plan::where('is_default', true)->exists();

        $plans = $dbPlans->map(function ($plan) use ($billingCycle, $user) {
            $price = $plan->getPriceForCycle($billingCycle);

            $isCurrent = false;
            if ($user->type === 'company') {
                $isCurrent = $user->plan_id === $plan->id;
            }

            // Get features from plan_features table
            $features = $plan->features()->get()->map(function ($f) {
                return [
                    'feature_name' => $f->feature_name,
                    'feature_value' => $f->feature_value,
                ];
            })->values()->toArray();

            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => $price,
                'formatted_price' => number_format($price, 2),
                'duration' => $billingCycle === 'yearly' ? 'Yearly' : $plan->duration,
                'description' => $plan->description,
                'trial_days' => $plan->trial_day ?? 0,
                'features' => $features,
                'status' => $plan->is_plan_enable,
                'recommended' => $plan->is_default,
                'is_default' => $plan->is_default,
                'is_current' => $isCurrent,
                'is_trial_available' => $plan->is_trial && $plan->trial_day > 0,
            ];
        });

        // Mark most subscribed plan as recommended
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

        // For admin users or users with existing plans
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
            'is_plan_enable' => $plan->is_plan_enable === 'on' ? 'off' : 'on',
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
            'is_plan_enable' => 'nullable|string|in:on,off',
            'is_default' => 'boolean',
            'features' => 'nullable|array',
            'features.*.feature_name' => 'required_with:features|string|max:255',
            'features.*.feature_value' => 'required_with:features|string|max:255',
        ]);

        // Set defaults
        $validated['is_plan_enable'] = $validated['is_plan_enable'] ?? 'on';
        $validated['is_default'] = $validated['is_default'] ?? false;
        $validated['duration'] = 'Monthly';
        $validated['description'] = '';

        // Calculate yearly price if empty
        if (!isset($validated['yearly_price']) || $validated['yearly_price'] === null) {
            $validated['yearly_price'] = $validated['price'] * 12 * 0.8;
        }

        // Remove features from validated before creating plan
        $featuresData = $validated['features'] ?? [];
        unset($validated['features']);

        // If default, remove default from other plans
        if ($validated['is_default']) {
            Plan::where('is_default', true)->update(['is_default' => false]);
        }

        DB::transaction(function () use ($validated, $featuresData) {
            $plan = Plan::create($validated);

            // Create features
            foreach ($featuresData as $feature) {
                if (!empty($feature['feature_name']) || !empty($feature['feature_value'])) {
                    PlanFeature::create([
                        'plan_id' => $plan->id,
                        'feature_name' => $feature['feature_name'],
                        'feature_value' => $feature['feature_value'],
                    ]);
                }
            }
        });

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

        // Load features for the plan
        $planData = $plan->toArray();
        $planData['features'] = $plan->features()->get()->map(function ($f) {
            return [
                'feature_name' => $f->feature_name,
                'feature_value' => $f->feature_value,
            ];
        })->values()->toArray();

        return Inertia::render('plans/edit', [
            'plan' => $planData,
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
            'is_plan_enable' => 'nullable|string|in:on,off',
            'is_default' => 'boolean',
            'features' => 'nullable|array',
            'features.*.feature_name' => 'required_with:features|string|max:255',
            'features.*.feature_value' => 'required_with:features|string|max:255',
        ]);

        // Set defaults
        $validated['is_plan_enable'] = $validated['is_plan_enable'] ?? 'on';
        $validated['is_default'] = $validated['is_default'] ?? false;

        // Calculate yearly price if empty
        if (!isset($validated['yearly_price']) || $validated['yearly_price'] === null) {
            $validated['yearly_price'] = $validated['price'] * 12 * 0.8;
        }

        // Remove features from validated before updating plan
        $featuresData = $validated['features'] ?? [];
        unset($validated['features']);

        // If default, remove default from other plans
        if ($validated['is_default'] && !$plan->is_default) {
            Plan::where('is_default', true)->update(['is_default' => false]);
        }

        DB::transaction(function () use ($plan, $validated, $featuresData) {
            $plan->update($validated);

            // Delete old features and recreate
            PlanFeature::where('plan_id', $plan->id)->delete();

            // Create new features
            foreach ($featuresData as $feature) {
                if (!empty($feature['feature_name']) || !empty($feature['feature_value'])) {
                    PlanFeature::create([
                        'plan_id' => $plan->id,
                        'feature_name' => $feature['feature_name'],
                        'feature_value' => $feature['feature_value'],
                    ]);
                }
            }
        });

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

        if ($plan->users()->count() > 0) {
            return redirect()->route('plans.index')
                ->with('error', __('The company has subscribed to this plan, so it cannot be deleted.'));
        }

        // Features will be cascade deleted
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

        $existingRequest = PlanRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return redirect()->route('plans.index')
                ->with('error', __('You already sent request to another plan'));
        }

        $plan = Plan::findOrFail($request->plan_id);

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
            'plan_is_active' => 1,
        ]);

        return redirect()->route('plans.index')
            ->with('success', __('Trial started successfully.'));
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
