<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Plan;
use App\Models\PlanOrder;
use App\Models\Coupon;
use Illuminate\Support\Facades\DB;


class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Super admin always gets dashboard
        if ($user->type === 'superadmin' || $user->type === 'super admin') {
            return $this->renderDashboard();
        }

        // Check if user has dashboard permission (skip if permission doesn't exist)
        try {
            if ($user->hasPermissionTo('manage-dashboard')) {
                return $this->renderDashboard();
            }
        } catch (\Exception $e) {
            // Permission doesn't exist, continue to dashboard for authenticated users
            return $this->renderDashboard();
        }

        // Redirect to first available page
        return $this->redirectToFirstAvailablePage();
    }

    public function redirectToFirstAvailablePage()
    {
        $user = auth()->user();

        // Define available routes with their permissions
        $routes = [
            ['route' => 'users.index', 'permission' => 'manage-users'],
            ['route' => 'roles.index', 'permission' => 'manage-roles'],

            ['route' => 'plans.index', 'permission' => 'manage-plans'],
            ['route' => 'referral.index', 'permission' => 'manage-referral'],
            ['route' => 'settings.index', 'permission' => 'manage-settings'],
        ];

        // Find first available route
        foreach ($routes as $routeData) {
            try {
                if ($user->hasPermissionTo($routeData['permission'])) {
                    return redirect()->route($routeData['route']);
                }
            } catch (\Exception $e) {
                // Permission doesn't exist, continue to next route
                continue;
            }
        }

        // If no permissions found, logout user
        auth()->logout();
        return redirect()->route('login')->with('error', __('No access permissions found.'));
    }

    private function renderDashboard()
    {
        $user = auth()->user();

        if ($user->type === 'superadmin' || $user->type === 'super admin') {
            return $this->renderSuperAdminDashboard();
        } else {
            return $this->renderCompanyDashboard();
        }
    }

    private function renderSuperAdminDashboard()
    {
        $totalCompanies = User::where('type', 'company')->count();
        $totalUsers = User::where('type', '!=', 'superadmin')->count();
        $activePlans = Plan::where('is_plan_enable', 'on')->count();
        $activeCoupons = isDemo() ? Coupon::count() : (Coupon::where('status', 1)->whereNull('expiry_date')
            ->orWhereDate('expiry_date', '>', \Carbon\Carbon::today())
            ->count());
        $totalSubscriptions = 0;
        $totalRevenue = 0;

        try {
            $totalSubscriptions = PlanOrder::where('status', 'approved')->count();
            $totalRevenue = PlanOrder::where('status', 'approved')->sum('final_price') ?? 0;
        } catch (\Exception $e) {
            // PlanOrder table might not exist or be empty
        }

        $activeCompanies = User::where('type', 'company')->where('plan_is_active', 1)->count();
        $inactiveCompanies = $totalCompanies - $activeCompanies;

        $currentMonthCompanies = User::where('type', 'company')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $previousMonthCompanies = User::where('type', 'company')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
        $monthlyGrowth = isDemo() ? 50 : ($previousMonthCompanies > 0
            ? round((($currentMonthCompanies - $previousMonthCompanies) / $previousMonthCompanies) * 100, 1)
            : ($currentMonthCompanies > 0 ? 100 : 0));

        $companyGrowthData = [];
        $revenueData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $companiesCount = User::where('type', 'company')
                ->whereDate('created_at', '<=', $date->endOfMonth())
                ->count();

            $monthRevenue = 0;
            try {
                $monthRevenue = PlanOrder::where('status', 'approved')
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->sum('final_price') ?? 0;
            } catch (\Exception $e) {
                // Handle missing table
            }

            $companyGrowthData[] = ['month' => $date->format('M'), 'companies' => $companiesCount];
            $revenueData[] = ['month' => $date->format('M'), 'revenue' => $monthRevenue];
        }

        $subscriptionDistribution = [];
        try {
            $plans = Plan::where('is_plan_enable', 'on')->get();
            $colors = ['#3b82f6', '#10b77f', '#f59e0b', '#ef4444', '#8b5cf6'];

            foreach ($plans as $index => $plan) {
                $userCount = User::where('plan_id', $plan->id)->count();
                if ($userCount > 0) {
                    $subscriptionDistribution[] = [
                        'name' => $plan->name,
                        'value' => $userCount,
                        'color' => $colors[$index % count($colors)]
                    ];
                }
            }
        } catch (\Exception $e) {
            // Handle missing Plan table or relationship
        }

        $recentCompanies = User::where('type', 'company')
            ->latest()
            ->take(2)
            ->get(['id', 'name', 'created_at']);

        $recentOrders = collect();
        $recentPlanRequests = collect();
        try {
            $recentOrders = PlanOrder::where('status', 'approved')
                ->with('user:id,name', 'plan:id,name')
                ->latest()
                ->take(2)
                ->get(['id', 'user_id', 'plan_id', 'final_price', 'created_at']);

            $recentPlanRequests = \App\Models\PlanRequest::with('user:id,name', 'plan:id,name')
                ->latest()
                ->take(2)
                ->get(['id', 'user_id', 'plan_id', 'status', 'created_at']);
        } catch (\Exception $e) {
            // Handle missing tables
        }

        $recentActivity = [];
        foreach ($recentCompanies as $company) {
            $recentActivity[] = [
                'id' => $company->id,
                'type' => 'company',
                'message' => 'New company registered: ' . $company->name,
                'time' => $company->created_at->diffForHumans(),
                'status' => 'success',
                'created_at' => $company->created_at
            ];
        }
        foreach ($recentOrders as $order) {
            $recentActivity[] = [
                'id' => $order->id,
                'type' => 'subscription',
                'message' => ($order->user->name ?? 'Company') . ' subscribed to ' . ($order->plan->name ?? 'Plan') . ' ($' . $order->final_price . ')',
                'time' => $order->created_at->diffForHumans(),
                'status' => 'success',
                'created_at' => $order->created_at
            ];
        }
        foreach ($recentPlanRequests as $request) {
            $statusColor = $request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'error' : 'warning');
            $recentActivity[] = [
                'id' => $request->id,
                'type' => 'plan',
                'message' => 'Plan request ' . $request->status . ': ' . ($request->plan->name ?? 'Plan') . ' by ' . ($request->user->name ?? 'User'),
                'time' => $request->created_at->diffForHumans(),
                'status' => $statusColor,
                'created_at' => $request->created_at
            ];
        }

        // Sort by created_at if available
        usort($recentActivity, function ($a, $b) {
            $timeA = isset($a['created_at']) ? strtotime($a['created_at']) : 0;
            $timeB = isset($b['created_at']) ? strtotime($b['created_at']) : 0;
            return $timeB - $timeA;
        });
        $recentActivity = array_slice($recentActivity, 0, 4);

        // Top performing plans
        $topPlans = [];
        try {
            $plans = Plan::where('is_plan_enable', 'on')->get();
            foreach ($plans as $plan) {
                $subscribers = User::where('plan_id', $plan->id)->count();
                $revenue = PlanOrder::where('plan_id', $plan->id)
                    ->where('status', 'approved')
                    ->sum('final_price') ?? 0;

                $topPlans[] = [
                    'name' => $plan->name,
                    'subscribers' => $subscribers,
                    'revenue' => $revenue
                ];
            }

            // Sort by revenue descending and take top 5
            usort($topPlans, function ($a, $b) {
                return $b['revenue'] <=> $a['revenue'];
            });
            $topPlans = array_slice($topPlans, 0, 5);
        } catch (\Exception $e) {
            // Handle missing relationships
        }

        $dashboardData = [
            'stats' => [
                'totalCompanies' => $totalCompanies,
                'totalUsers' => $totalUsers,
                'activePlans' => $activePlans,
                'activeCoupons' => $activeCoupons,
                'totalSubscriptions' => $totalSubscriptions,
                'totalRevenue' => $totalRevenue,
                'activeCompanies' => $activeCompanies,
                'inactiveCompanies' => $inactiveCompanies,
                'monthlyGrowth' => $monthlyGrowth,
            ],
            'charts' => [
                'companyGrowth' => $companyGrowthData,
                'subscriptionDistribution' => $subscriptionDistribution,
                'revenueByMonth' => $revenueData,
            ],
            'recentActivity' => $recentActivity,
            'topPlans' => $topPlans
        ];

        return Inertia::render('superadmin/dashboard', [
            'dashboardData' => $dashboardData
        ]);
    }

    private function renderCompanyDashboard()
    {
        $user = auth()->user();
        $companyId = $user->type === 'company' ? $user->id : $user->creatorId();

        $totalEmployees = User::where('created_by', $companyId)->count();
        $totalLeads = 0;
        $totalSales = 0;
        $totalCustomers = 0;
        $totalProjects = 0;
        $companyRevenue = 0;

        try {
            if (class_exists('\App\Models\Lead')) {
                $totalLeads = \App\Models\Lead::where('created_by', $companyId)->count();
            }
        } catch (\Exception $e) {
        }

        try {
            if (class_exists('\App\Models\SalesOrder')) {
                $totalSales = \App\Models\SalesOrder::where('created_by', $companyId)->count();
            }
        } catch (\Exception $e) {
        }

        try {
            if (class_exists('\App\Models\Account')) {
                $totalCustomers = \App\Models\Account::where('created_by', $companyId)->count();
            }
        } catch (\Exception $e) {
        }

        try {
            if (class_exists('\App\Models\Project')) {
                $totalProjects = \App\Models\Project::where('created_by', $companyId)->count();
            }
        } catch (\Exception $e) {
        }

        try {
            if (class_exists('\App\Models\Invoice')) {
                $companyRevenue = \App\Models\Invoice::where('created_by', $companyId)
                    ->where('status', 'paid')
                    ->sum('total_amount') ?? 0;
            }
        } catch (\Exception $e) {
        }

        $currentMonthLeads = 0;
        $previousMonthLeads = 0;

        try {
            if (class_exists('\App\Models\Lead')) {
                $currentMonthLeads = \App\Models\Lead::where('created_by', $companyId)
                    ->whereMonth('created_at', now()->month)
                    ->count();
                $previousMonthLeads = \App\Models\Lead::where('created_by', $companyId)
                    ->whereMonth('created_at', now()->subMonth()->month)
                    ->count();
            }
        } catch (\Exception $e) {
        }
        $monthlyGrowth = IsDemo() ? 50 : ($previousMonthLeads > 0
            ? round((($currentMonthLeads - $previousMonthLeads) / $previousMonthLeads) * 100, 1)
            : ($currentMonthLeads > 0 ? 100 : 0));

        $totalConvertedLeads = 0;
        try {
            if (class_exists('\App\Models\Lead')) {
                $totalConvertedLeads = \App\Models\Lead::where('created_by', $companyId)
                    ->where('is_converted', 1)
                    ->count();
            }
        } catch (\Exception $e) {
        }

        $conversionRate = $totalLeads > 0 ? round(($totalConvertedLeads / $totalLeads) * 100, 1) : 0;

        $salesTrendsData = [];
        $leadConversionsData = [];
        if (IsDemo()) {
            $salesTrendsData = [
                [
                    "month" => "Jul",
                    "sales" => 0
                ],
                [
                    "month" => "Aug",
                    "sales" => 12
                ],
                [
                    "month" => "Sep",
                    "sales" => 15
                ],
                [
                    "month" => "Oct",
                    "sales" => 10
                ],
                [
                    "month" => "Nov",
                    "sales" => 12
                ],
                [
                    "month" => "Dec",
                    "sales" => 13
                ]
            ];
            $leadConversionsData = [
                [
                    "month" => "Jul",
                    "leads" => 5,
                    "conversions" => 5
                ],
                [
                    "month" => "Aug",
                    "leads" => 12,
                    "conversions" => 10
                ],
                [
                    "month" => "Sep",
                    "leads" => 15,
                    "conversions" => 17
                ],
                [
                    "month" => "Oct",
                    "leads" => 10,
                    "conversions" => 8
                ],
                [
                    "month" => "Nov",
                    "leads" => 12,
                    "conversions" => 14
                ],
                [
                    "month" => "Dec",
                    "leads" => 13,
                    "conversions" => 13
                ]
            ];
        } else {
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthlySales = 0;
                $monthlyLeads = 0;
                $monthlyConversions = 0;

                try {
                    if (class_exists('\App\Models\SalesOrder')) {
                        $monthlySales = \App\Models\SalesOrder::where('created_by', $companyId)
                            ->whereMonth('created_at', $date->month)
                            ->whereYear('created_at', $date->year)
                            ->count();
                    }
                } catch (\Exception $e) {
                }

                try {
                    if (class_exists('\App\Models\Lead')) {
                        $monthlyLeads = \App\Models\Lead::where('created_by', $companyId)
                            ->whereMonth('created_at', $date->month)
                            ->whereYear('created_at', $date->year)
                            ->count();

                        // Converted leads (Lead → Account)
                        $monthlyConversions = \App\Models\Lead::where('created_by', $companyId)
                            ->where('is_converted', 1)
                            ->whereMonth('updated_at', $date->month)
                            ->whereYear('updated_at', $date->year)
                            ->count();
                    }
                } catch (\Exception $e) {
                }

                $salesTrendsData[] = ['month' => $date->format('M'), 'sales' => $monthlySales];
                $leadConversionsData[] = [
                    'month' => $date->format('M'),
                    'leads' => $monthlyLeads,
                    'conversions' => $monthlyConversions
                ];
            }
        }

        $customerTypes = collect();
        try {
            if (class_exists('\App\Models\Account')) {
                $customerTypes = \App\Models\Account::where('created_by', $companyId)
                    ->select('account_type_id', DB::raw('COUNT(*) as count'))
                    ->with('accountType:id,name')
                    ->groupBy('account_type_id')
                    ->get();
            }
        } catch (\Exception $e) {
        }

        $customerDistribution = [];
        $colors = ['#3b82f6', '#10b77f', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#f97316', '#84cc16', '#ec4899', '#6366f1'];
        foreach ($customerTypes as $index => $type) {
            $customerDistribution[] = [
                'name' => $type->accountType->name ?? 'Other',
                'value' => $type->count,
                'color' => $colors[$index % count($colors)]
            ];
        }

        $employeeRoles = User::where('created_by', $companyId)
            ->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->get();

        $employeeDistribution = [];
        foreach ($employeeRoles as $index => $role) {
            $employeeDistribution[] = [
                'name' => ucfirst($role->type),
                'value' => $role->count,
                'color' => $colors[$index % count($colors)]
            ];
        }

        $recentLeads = collect();
        $recentSales = collect();
        $recentProjects = collect();
        $recentCustomers = collect();

        try {
            if (class_exists('\App\Models\Lead')) {
                $recentLeads = \App\Models\Lead::where('created_by', $companyId)
                    ->latest()
                    ->take(5)
                    ->get(['id', 'name', 'email', 'status', 'created_at']);
            }
        } catch (\Exception $e) {
        }

        try {
            if (class_exists('\App\Models\SalesOrder')) {
                $recentSales = \App\Models\SalesOrder::where('created_by', $companyId)
                    ->with('account:id,name')
                    ->latest()
                    ->take(5)
                    ->get(['id', 'account_id', 'total_amount', 'status', 'created_at']);
            }
        } catch (\Exception $e) {
        }

        try {
            if (class_exists('\App\Models\Project')) {
                $recentProjects = \App\Models\Project::where('created_by', $companyId)
                    ->latest()
                    ->take(5)
                    ->get(['id', 'name', 'status', 'created_at']);
            }
        } catch (\Exception $e) {
        }

        try {
            if (class_exists('\App\Models\Account')) {
                $recentCustomers = \App\Models\Account::where('created_by', $companyId)
                    ->with('accountType:id,name')
                    ->latest()
                    ->take(5)
                    ->get(['id', 'name', 'account_type_id', 'created_at']);
            }
        } catch (\Exception $e) {
        }

        $recentAnnouncements = collect();
        try {
            if (class_exists('\App\Models\Announcement')) {
                $recentAnnouncements = \App\Models\Announcement::where('created_by', $companyId)
                    ->where('status', 'active')
                    ->with('category:id,name')
                    ->orderBy('is_featured', 'desc')
                    ->latest()
                    ->take(5)
                    ->get(['id', 'title', 'announcement_category_id', 'is_featured', 'created_at']);
            }
        } catch (\Exception $e) {
        }

        // Storage usage calculation with plan limits
        $storageUsed = 0;
        $storageLimit = 1024 * 1024 * 1024; // Default 1GB in bytes

        // Get plan storage limit
        $company = User::find($companyId);
        if ($company && $company->plan && $company->plan->storage_limit) {
            $storageLimit = $company->plan->storage_limit * 1024 * 1024 * 1024; // Convert GB to bytes
        }

        // Calculate actual storage usage from media files
        if (IsDemo()) {
            $storageUsed = $storageLimit / 4;
        } else {
            try {
                $companyUsers = User::where('created_by', $companyId)->pluck('id')->push($companyId);
                $storageUsed = \Spatie\MediaLibrary\MediaCollections\Models\Media::whereIn('user_id', $companyUsers)->sum('size');
            } catch (\Exception $e) {
            }
        }

        if (IsDemo()) {
            $storageUsagePercent = 25;
        } else {
            $storageUsagePercent = $storageLimit > 0 ? ($storageUsed / $storageLimit) * 100 : 0;
        }

        $dashboardData = [
            'stats' => [
                'totalEmployees' => $totalEmployees,
                'totalLeads' => $totalLeads,
                'totalSales' => $totalSales,
                'totalCustomers' => $totalCustomers,
                'totalProjects' => $totalProjects,
                'companyRevenue' => $companyRevenue,
                'monthlyGrowth' => $monthlyGrowth,
                'conversionRate' => $conversionRate,
                'storageUsed' => $storageUsed,
                'storageLimit' => $storageLimit,
                'storageUsagePercent' => min(100, $storageUsagePercent),
                'storageUsedMB' => round($storageUsed / (1024 * 1024), 2),
                'storageLimitGB' => round($storageLimit / (1024 * 1024 * 1024), 2),
            ],
            'charts' => [
                'salesTrends' => $salesTrendsData,
                'leadConversions' => $leadConversionsData,
                'customerDistribution' => $customerDistribution,
                'employeeDistribution' => $employeeDistribution,
            ],
            'recentActivities' => [
                'leads' => $recentLeads->map(function ($lead) {
                    return [
                        'id' => $lead->id,
                        'name' => $lead->name,
                        'email' => $lead->email,
                        'status' => $lead->status ?? 'new',
                        'created_at' => $lead->created_at->toISOString()
                    ];
                }),
                'sales' => $recentSales->map(function ($sale) {
                    return [
                        'id' => $sale->id,
                        'customer' => $sale->account->name ?? 'Customer',
                        'amount' => $sale->total_amount ?? 0,
                        'status' => $sale->status ?? 'pending',
                        'created_at' => $sale->created_at->toISOString()
                    ];
                }),
                'projects' => $recentProjects->map(function ($project) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'status' => $project->status ?? 'planning',
                        'created_at' => $project->created_at->toISOString()
                    ];
                }),
                'customers' => $recentCustomers->map(function ($customer) {
                    return [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'type' => $customer->accountType->name ?? 'customer',
                        'created_at' => $customer->created_at->toISOString()
                    ];
                }),
                'announcements' => $recentAnnouncements->map(function ($announcement) {
                    return [
                        'id' => $announcement->id,
                        'title' => $announcement->title,
                        'category' => $announcement->category->name ?? 'General',
                        'is_featured' => $announcement->is_featured ?? false,
                        'created_at' => $announcement->created_at->toISOString()
                    ];
                })
            ]
        ];

        return Inertia::render('dashboard', [
            'dashboardData' => $dashboardData
        ]);
    }

    private function getDirectorySize($directory)
    {
        $size = 0;
        if (is_dir($directory)) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
                $size += $file->getSize();
            }
        }
        return $size;
    }
}
