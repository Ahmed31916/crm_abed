<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\Referral;
use App\Models\PayoutRequest;
use App\Models\ReferralSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReferralController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $settings = ReferralSetting::current();

        if ($user->isSuperAdmin()) {
            return $this->superAdminView($settings);
        } else {
            return $this->companyView($user, $settings);
        }
    }

    private function superAdminView($settings)
    {
        $totalReferralUsers = User::whereNotNull('used_referral_code')->where('used_referral_code', '!=', 0)->count();
        $pendingPayouts = PayoutRequest::where('status', 'pending')->count();
        $totalCommissionPaid = PayoutRequest::where('status', 'approved')->sum('amount');

        $monthlyReferrals = User::whereNotNull('used_referral_code')
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $monthlyPayouts = PayoutRequest::where('status', 'approved')
            ->selectRaw('MONTH(created_at) as month, SUM(amount) as total')
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $topCompanies = User::select('users.id', 'users.name', 'users.email', 'users.referral_code')
            ->selectRaw('COUNT(referrals.id) as referral_count, SUM(referrals.amount) as total_earned')
            ->leftJoin('referrals', 'users.id', '=', 'referrals.company_id')
            ->where('users.type', 'company')
            ->whereNotNull('users.referral_code')
            ->groupBy('users.id', 'users.name', 'users.email', 'users.referral_code')
            ->orderByDesc('referral_count')
            ->limit(10)
            ->get();

        $payoutRequests = PayoutRequest::with('company')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        // Always use super admin currency for plan pricing
        $superAdmin = User::where('type', 'superadmin')->first();
        $superAdminSettings = settings($superAdmin->id);
        $currency = $superAdminSettings ? ($superAdminSettings['defaultCurrency'] ?? 'USD') : 'USD';
        $currencySymbol = '$';
        if (!empty($currency)) {
            $currencyData = Currency::where('code', $currency)->first();
            $currencySymbol = $currencyData ? $currencyData->symbol : '$';
        }

        $referredUsers = User::whereNotNull('used_referral_code')
            ->with(['plan', 'referrals', 'planOrders' => function ($query) {
                $query->where('status', 'approved')->orderBy('created_at', 'desc')->limit(1);
            }])
            ->where('used_referral_code', '!=', 0)
            ->orderBy('created_at', 'desc')
            ->paginate(5)
            ->withQueryString();

        return Inertia::render('referral/index', [
            'userType' => 'superadmin',
            'settings' => $settings,
            'stats' => [
                'totalReferralUsers' => $totalReferralUsers,
                'pendingPayouts' => $pendingPayouts,
                'totalCommissionPaid' => $totalCommissionPaid,
                'monthlyReferrals' => $monthlyReferrals,
                'monthlyPayouts' => $monthlyPayouts,
                'topCompanies' => $topCompanies,
            ],
            'payoutRequests' => $payoutRequests,
            'referredUsers' => $referredUsers,
            'currency' => $currency,
            'currencySymbol' => $currencySymbol
        ]);
    }

    private function companyView($user, $settings)
    {
        $totalReferrals = Referral::where('company_id', $user->id)->count();
        $totalEarned = Referral::where('company_id', $user->id)->sum('amount');
        $totalPayoutRequests = PayoutRequest::where('company_id', $user->id)->count();
        $pendingAmount = PayoutRequest::where('company_id', $user->id)
            ->where('status', 'pending')
            ->sum('amount');
        $availableBalance = $totalEarned - PayoutRequest::where('company_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->sum('amount');

        $payoutRequests = PayoutRequest::where('company_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Get referred users count (users who used this company's referral code)
        $referredUsersCount = User::where('used_referral_code', $user->referral_code)->count();

        // Get recent referred users
        $recentReferredUsers = User::where('used_referral_code', $user->referral_code)
            ->with(['plan', 'planOrders' => function ($query) {
                $query->where('status', 'approved')->orderBy('created_at', 'desc')->limit(1);
            }])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Generate referral code if not exists
        if (!$user->referral_code) {
            $user->referral_code = 'REF' . str_pad($user->id, 6, '0', STR_PAD_LEFT);
            $user->save();
        }

        $referralLink = url('/register?ref=' . $user->referral_code);

        // Get all referred users for the company with pagination
        $referredUsers = User::where('used_referral_code', $user->referral_code)
            ->with(['plan', 'referrals', 'planOrders' => function ($query) {
                $query->where('status', 'approved')->orderBy('created_at', 'desc')->limit(1);
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(5)
            ->withQueryString();

        // Always use super admin currency for plan pricing
        $superAdmin = User::where('type', 'superadmin')->first();
        $superAdminSettings = settings($superAdmin->id);
        $currency = $superAdminSettings ? ($superAdminSettings['defaultCurrency'] ?? 'USD') : 'USD';
        $currencySymbol = '$';
        if (!empty($currency)) {
            $currencyData = Currency::where('code', $currency)->first();
            $currencySymbol = $currencyData ? $currencyData->symbol : '$';
        }

        return Inertia::render('referral/index', [
            'userType' => 'company',
            'settings' => $settings,
            'stats' => [
                'totalReferrals' => $totalReferrals,
                'totalEarned' => $totalEarned,
                'totalPayoutRequests' => $totalPayoutRequests,
                'availableBalance' => $availableBalance,
                'referredUsersCount' => $referredUsersCount,
            ],
            'payoutRequests' => $payoutRequests,
            'referralLink' => $referralLink,
            'recentReferredUsers' => $recentReferredUsers,
            'referredUsers' => $referredUsers,
            'currency' => $currency,
            'currencySymbol' => $currencySymbol
        ]);
    }


    public function updateSettings(Request $request)
    {
        $request->validate([
            'commission_percentage' => 'required|numeric|min:0|max:100',
            'threshold_amount' => 'required|numeric|min:0',
            'guidelines' => 'nullable|string',
            'is_enabled' => 'boolean',
        ]);

        $settings = ReferralSetting::current();
        $settings->update($request->all());

        return back()->with('success', __('Referral settings updated successfully'));
    }

    public function createPayoutRequest(Request $request)
    {
        $user = Auth::user();
        $settings = ReferralSetting::current();

        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $totalEarned = Referral::where('company_id', $user->id)->sum('amount');
        $totalRequested = PayoutRequest::where('company_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->sum('amount');
        $availableBalance = $totalEarned - $totalRequested;

        if ($request->amount > $availableBalance) {
            return back()->withErrors(['amount' => __('Insufficient balance')]);
        }

        if ($request->amount < $settings->threshold_amount) {
            return back()->withErrors(['amount' => __('Amount must be at least $ :amount', ['amount' => $settings->threshold_amount])]);
        }

        PayoutRequest::create([
            'company_id' => $user->id,
            'amount' => $request->amount,
            'status' => 'pending',
        ]);

        return back()->with('success', __('Payout request submitted successfully'));
    }

    public function approvePayoutRequest(PayoutRequest $payoutRequest)
    {
        $payoutRequest->update(['status' => 'approved']);
        return back()->with('success', __('Payout request approved'));
    }

    public function rejectPayoutRequest(PayoutRequest $payoutRequest, Request $request)
    {
        $payoutRequest->update([
            'status' => 'rejected',
            'notes' => $request->notes,
        ]);
        return back()->with('success', __('Payout request rejected'));
    }


    public function getReferredUsers(Request $request)
    {
        $user = Auth::user();
        // Always use super admin currency for plan pricing
        $superAdmin = User::where('type', 'superadmin')->first();
        $superAdminSettings = settings($superAdmin->id);
        $currency = $superAdminSettings ? ($superAdminSettings['defaultCurrency'] ?? 'USD') : 'USD';
        $currencySymbol = '$';
        if (!empty($currency)) {
            $currencyData = Currency::where('code', $currency)->first();
            $currencySymbol = $currencyData ? $currencyData->symbol : '$';
        }
        if ($user->isSuperAdmin()) {
            // Super admin can see all referred users
            $referredUsers = User::whereNotNull('used_referral_code')
                ->with(['plan', 'referrals', 'planOrders' => function ($query) {
                    $query->where('status', 'approved')->orderBy('created_at', 'desc')->limit(1);
                }])
                ->where('used_referral_code', '!=', 0)
                ->orderBy('created_at', 'desc')
                ->paginate(15)
                ->withQueryString();
        } else {
            // Company can see users who used their referral code
            $referredUsers = User::where('used_referral_code', $user->referral_code)
                ->with(['plan', 'referrals', 'planOrders' => function ($query) {
                    $query->where('status', 'approved')->orderBy('created_at', 'desc')->limit(1);
                }])
                ->orderBy('created_at', 'desc')
                ->paginate(15)
                ->withQueryString();
        }
        return Inertia::render('referral/referred-users', [
            'referredUsers' => $referredUsers,
            'userType' => $user->isSuperAdmin() ? 'superadmin' : 'company',
            'currency' => $currency,
            'currencySymbol' => $currencySymbol
        ]);
    }

    /**
     * Create referral record when user purchases a plan
     */
    public static function createReferralRecord(User $user, $billingCycle = null)
    {
        $settings = ReferralSetting::current();

        if (!$settings->is_enabled || !$user->used_referral_code || !$user->plan) {
            return;
        }

        // Check if referral record already exists
        $existingReferral = Referral::where('user_id', $user->id)
            ->where('plan_id', $user->plan_id)
            ->first();

        if ($existingReferral) {
            return; // Already created
        }

        $referrer = User::where('referral_code', $user->used_referral_code)
            ->where('type', 'company')
            ->first();

        if (!$referrer) {
            return;
        }

        // Get the actual paid amount from the most recent plan order
        $planOrder = \App\Models\PlanOrder::where('user_id', $user->id)
            ->where('plan_id', $user->plan_id)
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->first();

        // Use the actual paid amount if available, otherwise use plan price based on billing cycle
        if ($planOrder && $planOrder->final_price > 0) {
            $planPrice = $planOrder->final_price;
        } elseif ($planOrder && $planOrder->billing_cycle === 'yearly' && $user->plan->yearly_price) {
            $planPrice = $user->plan->yearly_price;
        } else {
            $planPrice = $user->plan->price ?? 0;
        }
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
}
