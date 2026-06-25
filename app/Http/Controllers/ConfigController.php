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
     * POST /api/config
     * Body: { "license_key": "xxx" } or { "hardware_id": "xxx" }
     *
     * Response:
     * {
     *   "success": true,
     *   "user": { "name", "email", "company_name", "license_key", "slug", ... },
     *   "plan": { "id", "name", "price", "duration", ... },
     *   "config": [
     *     { "feature_name": "Max Users", "feature_value": "5" },
     *     ...
     *   ]
     * }
     *
     * ============================================================
     * v6.5 — Company Slug Resolution for Staff Users
     * ============================================================
     * When a STAFF user (type='staff') queries this endpoint, the
     * `slug` field in the response is the COMPANY OWNER's slug — not
     * the staff user's personal slug (which is `{company-slug}-staff[-N]`).
     *
     * Why: the desktop app uses `slug` to build company-scoped URLs
     * (e.g. GET /api/{slug}/products). Staff users belong to their
     * company and must use the company's slug, not their unique one.
     *
     * Implementation:
     *   1. If the user is `type='staff'` AND has `created_by` set,
     *      look up the company owner via User::find($user->created_by).
     *   2. If found AND the owner has a non-empty slug, use it.
     *   3. Otherwise, fall back to the user's own slug.
     *
     * For company owners (type='company'), the user's own slug is used
     * directly — no extra query needed.
     * ============================================================
     */
    public function getConfig(Request $request)
    {
        // التأكد من أن الطلب هو POST فقط
        if (!$request->isMethod('post')) {
            return response()->json([
                'success' => false,
                'message' => __('Method Not Allowed. Only POST requests are accepted.'),
            ], 405);
        }

        // جلب البيانات من الـ Body فقط باستخدام ()post
        $licenseKey = $request->post('license_key');
        $hardwareId = $request->post('hardware_id');

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
        if (!$user->license_key) {
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

        // ============================================================
        // v6.5: Resolve the effective company slug
        // ============================================================
        // - For company owners (type='company' or similar): use their
        //   own slug directly.
        // - For staff users (type='staff'): look up the company owner
        //   via `created_by` and use the owner's slug.
        // - Fallback: if anything goes wrong, use the user's own slug.
        //
        // We select only `id, slug, type` to keep the lookup cheap.
        // ============================================================
        $effectiveSlug = $user->slug;
        $companyOwnerId = null;
        $companyOwnerSlug = null;

        // Detect staff users. We check both `type` and `created_by`
        // to be defensive — some legacy staff rows might not have
        // `type='staff'` but DO have `created_by` pointing to a
        // company owner.
        $isStaff = ($user->type === 'staff')
                || ($user->created_by && $user->type !== 'company' && $user->type !== 'superadmin');

        if ($isStaff && $user->created_by) {
            $companyOwner = User::select(['id', 'slug', 'type'])
                ->find($user->created_by);

            if ($companyOwner && !empty($companyOwner->slug)) {
                $effectiveSlug    = $companyOwner->slug;
                $companyOwnerId   = $companyOwner->id;
                $companyOwnerSlug = $companyOwner->slug;
            }
        }
        // ============================================================

        Log::info('Config API: Returning config', [
            'user_id'           => $user->id,
            'plan_id'           => $plan->id,
            'license_key'       => $user->license_key,
            'user_type'         => $user->type,
            'user_slug'         => $user->slug,         // السجل الشخصي للمستخدم
            'effective_slug'    => $effectiveSlug,       // ما يُعاد في الاستجابة
            'company_owner_id'  => $companyOwnerId,      // إن كان staff
            'company_owner_slug'=> $companyOwnerSlug,    // إن كان staff
            'config_count'      => count($config),
        ]);

        return response()->json([
            'success' => true,
            'user' => [
                'id'                => $user->id,
                'name'              => $user->name,
                'email'             => $user->email,
                'company_name'      => $user->company_name,
                'phone'             => $user->phone,
                'slug'              => $effectiveSlug,    // ← v6.5: slug الشركة للموظف
                'hardware_id'       => $user->hardware_id,
                'license_key'       => $user->license_key,
                'license_id'        => $user->license_id,
                'plan_is_active'    => $user->plan_is_active,
                'plan_expire_date'  => $user->plan_expire_date ? $user->plan_expire_date->format('Y-m-d') : null,
                'is_trial'          => $user->is_trial,
                'trial_expire_date' => $user->trial_expire_date ? $user->trial_expire_date->format('Y-m-d') : null,
            ],
            'plan' => [
                'id'           => $plan->id,
                'name'         => $plan->name,
                'price'        => $plan->price,
                'yearly_price' => $plan->yearly_price,
                'duration'     => $plan->duration,
                'is_trial'     => $plan->is_trial,
                'trial_day'    => $plan->trial_day,
            ],
            'config' => $config,
        ]);
    }
}
