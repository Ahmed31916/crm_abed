<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\User;

class CheckPlanAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            return $next($request);
        }

        // Super admin has full access
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Only company users need plan checks
        if ($user->type !== 'company') {
            $company = User::find($user->created_by);
            if ($company && $company->type === 'company' && $company->isPlanExpired()) {
                auth()->logout();
                return redirect()->route('login')->with('error', __('Access denied. Only company users can access this area.'));
            }
            return $next($request);
        }

        // ---- Company user checks ----

        // If user has NO plan_id at all, they need to select a plan
        if (!$user->plan_id) {
            // Allow access to plans page and license routes to select a plan
            if ($request->routeIs('plans.*') || $request->routeIs('license.*')) {
                return $next($request);
            }
            return redirect()->route('plans.index')->with('error', __('Please subscribe to a plan to continue.'));
        }

        // If user has a plan_id but NO license_key, they need to complete the flow
        if ($user->plan_id && !$user->license_key) {
            // Allow access to plans page and license routes
            if ($request->routeIs('plans.*') || $request->routeIs('license.*')) {
                return $next($request);
            }
            return redirect()->route('plans.index')->with('warning', __('Please complete your plan selection to receive your license key.'));
        }

        // If user has a license_key, check if plan/trial is expired
        if ($user->license_key) {
            // Check if trial is expired
            if ($user->isTrialExpired()) {
                // Allow access to plans page to renew
                if ($request->routeIs('plans.*') || $request->routeIs('license.*')) {
                    return $next($request);
                }
                
                // Reset trial status
                $user->update([
                    'is_trial' => 0,
                    'trial_expire_date' => null
                ]);
                
                return redirect()->route('plans.index')
                    ->with('error', __('Your trial period has expired. Please subscribe to a plan to continue.'));
            }

            // Check if plan is expired (but not on trial)
            if (!$user->is_trial && $user->isPlanExpired()) {
                // Allow access to plans page to renew
                if ($request->routeIs('plans.*') || $request->routeIs('license.*')) {
                    return $next($request);
                }
                
                return redirect()->route('plans.index')
                    ->with('error', __('Your plan has expired. Please renew your subscription.'));
            }

            // User has active plan + license key - allow full access
            return $next($request);
        }

        return $next($request);
    }
}
