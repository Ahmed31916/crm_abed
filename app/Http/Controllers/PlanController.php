<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlanController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Company users see only active plans
        if ($user->type !== 'superadmin') {
            return $this->companyPlansView($request);
        }

        // Admin view
        $billingCycle = $request->input('billing_cycle', 'monthly');

        $dbPlans = Plan::all();
        $hasDefaultPlan = $dbPlans->where('is_default', true)->count() > 0;
        $settings = settings();

        // Always use super admin currency for plan pricing
        $superAdmin = User::where('type', 'superadmin')->first();
        $superAdminSettings = settings($superAdmin->id);
        $currency = $superAdminSettings ? ($superAdminSettings['defaultCurrency'] ?? 'USD') : 'USD';
        $currencySymbol = '$';
        if (!empty($currency)) {
            $currencyData = Currency::where('code', $currency)->first();
            $currencySymbol = $currencyData ? $currencyData->symbol : '$';
        }

        $plans = $dbPlans->map(function ($plan) use ($billingCycle) {
            // Determine features based on plan attributes
            $features = [];
            if ($plan->enable_chatgpt === 'on') $features[] = 'AI Integration';

            // Get price based on billing cycle
            $price = $billingCycle === 'yearly' ? $plan->yearly_price : $plan->price;

            // Format price with currency symbol
            $formattedPrice = '$' . number_format($price, 2);

            // Set duration based on billing cycle
            $duration = $billingCycle === 'yearly' ? 'Yearly' : 'Monthly';

            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => $price,
                'formattedPrice' => $formattedPrice,
                'duration' => $duration,
                'description' => $plan->description,
                'trial_days' => $plan->trial_day,
                'features' => $features,
                'stats' => [
                    'users' => $plan->max_users,
                    'projects' => $plan->max_projects,
                    'contacts' => $plan->max_contacts,
                    'accounts' => $plan->max_accounts,
                    'storage' => $plan->storage_limit . ' GB'
                ],
                'status' => $plan->is_plan_enable === 'on',
                'is_default' => $plan->is_default,
                'recommended' => false // Default to false
            ];
        })->toArray();

        // Mark the plan with most subscribers as recommended
        $planSubscriberCounts = Plan::withCount('users')->get()->pluck('users_count', 'id');
        $mostSubscribedPlanId = $planSubscriberCounts->keys()->first();
        if ($planSubscriberCounts->isNotEmpty()) {
            $mostSubscribedPlanId = $planSubscriberCounts->keys()->sortByDesc(function ($planId) use ($planSubscriberCounts) {
                return $planSubscriberCounts[$planId];
            })->first();
        }

        foreach ($plans as &$plan) {
            if ($plan['id'] == $mostSubscribedPlanId && $plan['price'] != '0') {
                $plan['recommended'] = true;
                break;
            }
        }

        return Inertia::render('plans/index', [
            'plans' => $plans,
            'billingCycle' => $billingCycle,
            'hasDefaultPlan' => $hasDefaultPlan,
            'isAdmin' => true,
            'currency' => $currency,
            'currencySymbol' => $currencySymbol
        ]);
    }

    /**
     * Toggle plan status
     */
    public function toggleStatus(Plan $plan)
    {
        $plan->is_plan_enable = $plan->is_plan_enable === 'on' ? 'off' : 'on';
        $plan->save();

        $status = $plan->is_plan_enable === 'on' ? 'activated' : 'deactivated';
        return back()->with('success', __('Plan :status successfully', ['status' => $status]));
    }

    /**
     * Show the form for creating a new plan
     */
    public function create()
    {
        $hasDefaultPlan = Plan::where('is_default', true)->exists();

        return Inertia::render('plans/create', [
            'hasDefaultPlan' => $hasDefaultPlan
        ]);
    }

    /**
     * Store a newly created plan
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:plans',
            'price' => 'required|numeric|min:0',
            'yearly_price' => 'nullable|numeric|min:0',
            'duration' => 'required|string',
            'description' => 'nullable|string',
            'max_users' => 'required|integer|min:0',
            'max_projects' => 'required|integer|min:0',
            'max_contacts' => 'required|integer|min:0',
            'max_accounts' => 'required|integer|min:0',
            'storage_limit' => 'required|numeric|min:0',
            'enable_branding' => 'nullable|in:on,off',
            'enable_chatgpt' => 'nullable|in:on,off',
            'module' => 'nullable|array',
            'is_trial' => 'nullable|in:on,off',
            'trial_day' => 'nullable|integer|min:0',
            'is_plan_enable' => 'nullable|in:on,off',
            'is_default' => 'nullable|boolean',
        ]);

        // Set default values for nullable fields
        $validated['enable_branding'] = $validated['enable_branding'] ?? 'on';
        $validated['enable_chatgpt'] = $validated['enable_chatgpt'] ?? 'off';
        $validated['is_trial'] = $validated['is_trial'] ?? null;
        $validated['is_plan_enable'] = $validated['is_plan_enable'] ?? 'on';
        $validated['is_default'] = $validated['is_default'] ?? false;

        // If yearly_price is not provided, calculate it as 80% of monthly price * 12
        if (!isset($validated['yearly_price']) || $validated['yearly_price'] === null) {
            $validated['yearly_price'] = $validated['price'] * 12 * 0.8;
        }

        // If this plan is set as default, remove default status from other plans
        if ($validated['is_default']) {
            Plan::where('is_default', true)->update(['is_default' => false]);
        }

        // Create the plan
        Plan::create($validated);

        return redirect()->route('plans.index')->with('success', __('Plan created successfully.'));
    }

    /**
     * Show the form for editing a plan
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
     * Update a plan
     */
    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:plans,name,' . $plan->id,
            'price' => 'required|numeric|min:0',
            'yearly_price' => 'nullable|numeric|min:0',
            'duration' => 'required|string',
            'description' => 'nullable|string',
            'max_users' => 'required|integer|min:0',
            'max_projects' => 'required|integer|min:0',
            'max_contacts' => 'required|integer|min:0',
            'max_accounts' => 'required|integer|min:0',
            'storage_limit' => 'required|numeric|min:0',
            'enable_branding' => 'nullable|in:on,off',
            'enable_chatgpt' => 'nullable|in:on,off',
            'module' => 'nullable|array',
            'is_trial' => 'nullable|in:on,off',
            'trial_day' => 'nullable|integer|min:0',
            'is_plan_enable' => 'nullable|in:on,off',
            'is_default' => 'nullable|boolean',
        ]);

        // Set default values for nullable fields
        $validated['enable_branding'] = $validated['enable_branding'] ?? 'on';
        $validated['enable_chatgpt'] = $validated['enable_chatgpt'] ?? 'off';
        $validated['is_trial'] = $validated['is_trial'] ?? null;
        $validated['is_plan_enable'] = $validated['is_plan_enable'] ?? 'on';
        $validated['is_default'] = $validated['is_default'] ?? false;

        // If yearly_price is not provided, calculate it as 80% of monthly price * 12
        if (!isset($validated['yearly_price']) || $validated['yearly_price'] === null) {
            $validated['yearly_price'] = $validated['price'] * 12 * 0.8;
        }

        // If this plan is set as default, remove default status from other plans
        if ($validated['is_default'] && !$plan->is_default) {
            Plan::where('is_default', true)->update(['is_default' => false]);
        }

        // Update the plan
        $plan->update($validated);

        return redirect()->route('plans.index')->with('success', __('Plan updated successfully.'));
    }

    /**
     * Delete a plan
     */
    public function destroy(Plan $plan)
    {
        // Don't allow deleting the default plan
        if ($plan->is_default) {
            return back()->with('error', __('Cannot delete the default plan.'));
        }

        // Don't allow deleting plans assigned to users
        if ($plan->users()->count() > 0) {
            return back()->with('error', __('The company has subscribed to this plan, so it cannot be deleted.'));
        }

        $plan->delete();

        return redirect()->route('plans.index')->with('success', __('Plan deleted successfully.'));
    }

    private function companyPlansView(Request $request)
    {
        $user = auth()->user();
        $billingCycle = $request->input('billing_cycle', 'monthly');

        $dbPlans = Plan::where('is_plan_enable', 'on')->get();

        // Always use super admin currency for plan pricing
        $superAdmin = User::where('type', 'superadmin')->first();
        $superAdminSettings = settings($superAdmin->id);
        $currency = $superAdminSettings ? ($superAdminSettings['defaultCurrency'] ?? 'USD') : 'USD';
        $currencySymbol = '$';
        if (!empty($currency)) {
            $currencyData = Currency::where('code', $currency)->first();
            $currencySymbol = $currencyData ? $currencyData->symbol : '$';
        }

        $plans = $dbPlans->map(function ($plan) use ($billingCycle, $user) {
            $price = $billingCycle === 'yearly' ? $plan->yearly_price : $plan->price;

            $features = [];
            if ($plan->enable_chatgpt === 'on') $features[] = 'AI Integration';

            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => $price,
                'formatted_price' => '$' . number_format($price, 2),
                'duration' => $billingCycle === 'yearly' ? 'Yearly' : 'Monthly',
                'description' => $plan->description,
                'trial_days' => $plan->trial_day,
                'features' => $features,
                'stats' => [
                    'users' => $plan->max_users,
                    'projects' => $plan->max_projects,
                    'contacts' => $plan->max_contacts,
                    'accounts' => $plan->max_accounts,
                    'storage' => $plan->storage_limit . ' GB'
                ],
                'is_current' => $user->plan_id == $plan->id,
                'is_trial_available' => $plan->is_trial === 'on' && !$user->is_trial,
                'is_default' => $plan->is_default,
                'recommended' => false // Default to false
            ];
        });

        // Mark the plan with most subscribers as recommended
        $planSubscriberCounts = Plan::withCount('users')->get()->pluck('users_count', 'id');
        if ($planSubscriberCounts->isNotEmpty()) {
            $mostSubscribedPlanId = $planSubscriberCounts->keys()->sortByDesc(function ($planId) use ($planSubscriberCounts) {
                return $planSubscriberCounts[$planId];
            })->first();

            $plans = $plans->map(function ($plan) use ($mostSubscribedPlanId) {
                if ($plan['id'] == $mostSubscribedPlanId) {
                    $plan['recommended'] = true;
                }
                return $plan;
            });
        }

        return Inertia::render('plans/index', [
            'plans' => $plans,
            'billingCycle' => $billingCycle,
            'currentPlan' => $user->plan,
            'userTrialUsed' => $user->is_trial,
            'currency' => $currency,
            'currencySymbol' => $currencySymbol
        ]);
    }

    public function requestPlan(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly'
        ]);

        $user = auth()->user();

        // Check if user already has a pending request
        $existingRequest = \App\Models\PlanRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return back()->with('error', __('You already sent request to another plan'));
        }

        $plan = Plan::findOrFail($request->plan_id);

        \App\Models\PlanRequest::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'duration' => $request['billing_cycle'],
            'status' => 'pending'
        ]);

        return back()->with('success', __('Plan request submitted successfully'));
    }

    public function cancelRequest(Request $request)
    {
        $request->validate([
            'request_id' => 'required|exists:plan_requests,id'
        ]);

        $planRequest = \App\Models\PlanRequest::findOrFail($request->request_id);

        $planRequest->delete();

        return back()->with('success', __('Plan request cancelled successfully'));
    }

    public function startTrial(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id'
        ]);

        $user = auth()->user();
        $plan = Plan::findOrFail($request->plan_id);

        if ($user->is_trial || $plan->is_trial !== 'on') {
            return back()->withErrors(['error' => 'Trial not available']);
        }

        $user->update([
            'plan_id' => $plan->id,
            'is_trial' => 1,
            'trial_day' => $plan->trial_day,
            'trial_expire_date' => now()->addDays($plan->trial_day)
        ]);

        return back()->with('success', __('Trial started successfully'));
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly'
        ]);

        $user = auth()->user();
        $plan = Plan::findOrFail($request->plan_id);
        $price = $request->billing_cycle === 'yearly' ? $plan->yearly_price : $plan->price;

        \App\Models\PlanOrder::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'original_price' => $price,
            'final_price' => $price,
            'status' => 'pending'
        ]);

        return back()->with('success', __('Subscription request submitted successfully'));
    }
}
