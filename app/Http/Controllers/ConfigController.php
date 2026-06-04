<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PlanFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConfigController extends Controller
{
    /**
     * Get the config/features for a specific user.
     * Used by the desktop application to know what features the user can use.
     *
     * GET /api/config/{license_key}
     * or
     * POST /api/config
     *   Body: { "license_key": "xxx" } or { "hardware_id": "xxx" }
     *
     * Response:
     * {
     *   "success": true,
     *   "user": { "name", "email", "company_name", "license_key", ... },
     *   "plan": { "id", "name", "price", "duration", ... },
     *   "config": [
     *     { "feature_name": "Max Users", "feature_value": "5" },
     *     { "feature_name": "Storage", "feature_value": "1 GB" },
     *     ...
     *   ]
     * }
     */
    public function getConfig(Request $request, $licenseKey = null)
    {
        // Get license_key from URL param, request body, or query string
        $licenseKey = $licenseKey 
            ?? $request->input('license_key') 
            ?? $request->query('license_key');

        $hardwareId = $request->input('hardware_id') 
            ?? $request->query('hardware_id');

        // Find user by license_key or hardware_id
        $user = null;

        if ($licenseKey) {
            $user = User::where('license_key', $licenseKey)->first();
        }

        if (!$user && $hardwareId) {
            $user = User::where('hardware_id', $hardwareId)->first();
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('User not found'),
            ], 404);
        }

        // Check if user has an active plan
        if (!$user->plan_id || !$user->license_key) {
            return response()->json([
                'success' => false,
                'message' => __('User does not have an active subscription'),
            ], 403);
        }

        // Check if plan is expired
        if ($user->plan_expire_date && $user->plan_expire_date < now()) {
            return response()->json([
                'success' => false,
                'message' => __('Subscription has expired'),
                'expired_at' => $user->plan_expire_date->format('Y-m-d'),
            ], 403);
        }

        $plan = $user->plan;

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => __('Plan not found'),
            ], 404);
        }

        // Get features/config for this plan
        $config = $plan->features()->get()->map(function ($feature) {
            return [
                'feature_name' => $feature->feature_name,
                'feature_value' => $feature->feature_value,
            ];
        })->values()->toArray();

        Log::info('Config API: Returning config', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'license_key' => $user->license_key,
            'config_count' => count($config),
        ]);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company_name' => $user->company_name,
                'phone' => $user->phone,
                'hardware_id' => $user->hardware_id,
                'license_key' => $user->license_key,
                'license_id' => $user->license_id,
                'plan_is_active' => $user->plan_is_active,
                'plan_expire_date' => $user->plan_expire_date ? $user->plan_expire_date->format('Y-m-d') : null,
                'is_trial' => $user->is_trial,
                'trial_expire_date' => $user->trial_expire_date ? $user->trial_expire_date->format('Y-m-d') : null,
            ],
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => $plan->price,
                'yearly_price' => $plan->yearly_price,
                'duration' => $plan->duration,
                'is_trial' => $plan->is_trial,
                'trial_day' => $plan->trial_day,
            ],
            'config' => $config,
        ]);
    }

    /**
     * Get config by POST request.
     * Same as getConfig but accepts POST.
     */
    public function getConfigPost(Request $request)
    {
        return $this->getConfig($request, null);
    }
}
