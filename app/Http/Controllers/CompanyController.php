<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Plan;
use App\Models\PlanOrder;
use App\Models\LeadStatus;
use App\Models\OpportunityStage;
use App\Models\TaskStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()
            ->where('type', 'company')
            ->with('plan');

        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        // Apply status filter
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Apply date filters
        if ($request->has('start_date') && !empty($request->start_date)) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date') && !empty($request->end_date)) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }


        // Apply sorting
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts = ['name', 'created_at'];
        $allowedDirection = ['asc', 'desc'];

        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        // Get paginated results
        $perPage = $request->input('per_page', 10);
        $companies = $query->paginate((int)$perPage)->withQueryString();

        // Transform data for frontend
        $companies->getCollection()->transform(function ($company) {
            return [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'avatar' => $company->avatar,
                'status' => $company->status,
                'created_at' => $company->created_at,
                'plan_name' => $company->plan ? $company->plan->name : __('No Plan'),
                'plan_expiry_date' => $company->plan_expire_date,
            ];
        });

        // Get plans for dropdown
        $plans = Plan::all(['id', 'name']);

        return Inertia::render('companies/index', [
            'companies' => $companies,
            'plans' => $plans,
            'filters' => $request->only(['search', 'status', 'start_date', 'end_date', 'sort_field', 'sort_direction', 'per_page', 'view', 'page'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'nullable|string|min:8',
            'status' => 'required|in:active,inactive',
        ]);

        $company = new User();
        $company->name = $validated['name'];
        $company->email = $validated['email'];

        // Only set password if provided
        if (isset($validated['password'])) {
            $company->password = Hash::make($validated['password']);
        }

        $company->type = 'company';
        $company->status = $validated['status'];
        $company->created_by = createdBy() ?? 1;

        // Set company language same as creator (superadmin)
        $creator = auth()->user();
        if ($creator && $creator->lang) {
            $company->lang = $creator->lang;
        }

        // Assign default plan
        $defaultPlan = Plan::where('is_default', true)->first();
        if ($defaultPlan) {
            $company->plan_id = $defaultPlan->id;

            // Set plan expiry date based on plan duration
            if ($defaultPlan->duration === 'yearly') {
                $company->plan_expire_date = now()->addYear();
            } else {
                $company->plan_expire_date = now()->addMonth();
            }

            // Set plan is active
            $company->plan_is_active = 1;
        }

        $company->save();

        // Assign role and settings to the user
        defaultRoleAndSetting($company);

        // Create default lead statuses for the company
        $this->createDefaultLeadStatuses($company->id);

        // Create default opportunity stages for the company
        $this->createDefaultOpportunityStages($company->id);

        // Create default task statuses for the company
        $this->createDefaultTaskStatuses($company->id);

        // Trigger email notification
        if (!IsDemo()) {
            event(new \App\Events\UserCreated($company, $validated['password'] ?? ''));
        }

        // Check for email errors
        if (session()->has('email_error')) {
            return redirect()->back()->with('warning', __('Company created successfully, but welcome email failed: ') . session('email_error'));
        }

        return redirect()->back()->with('success', __('Company created successfully'));
    }

    public function update(Request $request, User $company)
    {
        // Ensure this is a company type user
        if ($company->type !== 'company') {
            return redirect()->back()->with('error', __('Invalid company record'));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $company->id,
            // 'status' => 'required|in:active,inactive',
        ]);

        $company->name = $validated['name'];
        $company->email = $validated['email'];
        // $company->status = $validated['status'];
        // // Only set password if provided
        // if (isset($validated['password'])) {
        //     $company->password = Hash::make($validated['password']);
        // }

        $company->save();

        return redirect()->back()->with('success', __('Company updated successfully'));
    }

    public function destroy(User $company)
    {
        // Ensure this is a company type user
        if ($company->type !== 'company') {
            return redirect()->back()->with('error', __('Invalid company record'));
        }

        $company->delete();

        return redirect()->back()->with('success', __('Company deleted successfully'));
    }

    public function resetPassword(Request $request, User $company)
    {
        // Ensure this is a company type user
        if ($company->type !== 'company') {
            return redirect()->back()->with('error', __('Invalid company record'));
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $company->password = Hash::make($validated['password']);
        $company->save();

        return redirect()->back()->with('success', __('Password reset successfully'));
    }

    public function toggleStatus(User $company)
    {
        // Ensure this is a company type user
        if ($company->type !== 'company') {
            return redirect()->back()->with('error', __('Invalid company record'));
        }

        $company->status = $company->status === 'active' ? 'inactive' : 'active';
        $company->save();

        return redirect()->back()->with('success', __('Company status updated successfully'));
    }

    /**
     * Get available plans for upgrade
     */
    public function getPlans(User $company)
    {
        // Ensure this is a company type user
        if ($company->type !== 'company') {
            return response()->json(['error' => __('Invalid company record')], 400);
        }

        $plans = Plan::where('is_plan_enable', 'on')->get();

        $formattedPlans = [];

        foreach ($plans as $plan) {
            // Format features
            $features = [];
            if ($plan->features) {
                $enabledFeatures = $plan->getEnabledFeatures();
                $featureLabels = [
                    'ai_integration' => __('AI Integration'),
                    'password_protection' => __('Password Protection')
                ];
                foreach ($enabledFeatures as $feature) {
                    if (isset($featureLabels[$feature])) {
                        $features[] = $featureLabels[$feature];
                    }
                }
            } else {
                // Fallback to legacy columns
                if ($plan->enable_chatgpt === 'on') $features[] = __('AI Integration');
            }

            // Monthly plan
            $formattedPlans[] = [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => $plan->price,
                'duration' => 'Monthly',
                'description' => $plan->description,
                'features' => $features,
                'max_users' => $plan->max_users,
                'max_projects' => $plan->max_projects,
                'max_contacts' => $plan->max_contacts,
                'max_accounts' => $plan->max_accounts,
                'storage_limit' => $plan->storage_limit,
                'enable_branding' => $plan->enable_branding,
                'enable_chatgpt' => $plan->enable_chatgpt,
                'module' => json_decode($plan->module, true),
                'is_trial' => $plan->is_trial,
                'trial_day' => $plan->trial_day,
                'is_current' => $company->plan_id === $plan->id,
                'is_default' => $plan->is_default
            ];

            // Yearly plan (create a separate entry)
            $yearlyPrice = $plan->yearly_price ?? ($plan->price * 12 * 0.8);
            $formattedPlans[] = [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => $yearlyPrice,
                'duration' => 'Yearly',
                'description' => $plan->description,
                'features' => $features,
                'max_users' => $plan->max_users,
                'max_projects' => $plan->max_projects,
                'max_contacts' => $plan->max_contacts,
                'max_accounts' => $plan->max_accounts,
                'storage_limit' => $plan->storage_limit,
                'enable_branding' => $plan->enable_branding,
                'enable_chatgpt' => $plan->enable_chatgpt,
                'module' => json_decode($plan->module, true),
                'is_trial' => $plan->is_trial,
                'trial_day' => $plan->trial_day,
                'is_current' => $company->plan_id === $plan->id,
                'is_default' => $plan->is_default
            ];
        }

        return response()->json([
            'plans' => $formattedPlans,
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'current_plan_id' => $company->plan_id
            ]
        ]);
    }


    public function upgradePlan(Request $request, User $company)
    {
        // Ensure this is a company type user
        if ($company->type !== 'company') {
            return back()->with('error', __('Invalid company record'));
        }

        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'duration' => 'required|in:yearly,monthly',
        ]);

        $plan = Plan::find($validated['plan_id']);
        if (!$plan) {
            return back()->with('error', __('Plan not found'));
        }


        // Create plan order entry for tracking
        $planOrder = new PlanOrder();
        $planOrder->user_id = $company->id;
        $planOrder->plan_id = $plan->id;
        $planOrder->billing_cycle = $request->duration === 'yearly' ? 'yearly' : 'monthly';
        $planOrder->original_price = $request->duration === 'yearly' ? ($plan->yearly_price ?? 0) : $plan->price;
        $planOrder->discount_amount = 0;
        $planOrder->final_price = $planOrder->original_price;
        $planOrder->payment_method = 'admin_upgrade';
        $planOrder->status = 'approved';
        $planOrder->ordered_at = now();
        $planOrder->processed_at = now();
        $planOrder->processed_by = auth()->id();
        $planOrder->notes = 'Plan upgraded by super admin';
        $planOrder->save();
        // Update company plan
        $company->plan_id = $plan->id;

        // Set plan expiry date based on plan duration
        if ($request->duration === 'yearly') {
            $company->plan_expire_date = now()->addYear();
        } else {
            $company->plan_expire_date = now()->addMonth();
        }

        // Set plan is active
        $company->plan_is_active = 1;

        $company->save();

        return back()->with('success', __('Plan upgraded successfully'));
    }

    /**
     * Create default lead statuses for a new company
     */
    private function createDefaultLeadStatuses($companyId)
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
                'created_by' => $companyId,
            ]);
        }
    }

    /**
     * Create default opportunity stages for a new company
     */
    private function createDefaultOpportunityStages($companyId)
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
                'created_by' => $companyId,
            ]);
        }
    }

    /**
     * Create default task statuses for a new company
     */
    private function createDefaultTaskStatuses($companyId)
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
                'created_by' => $companyId,
            ]);
        }
    }
}
