<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PlanRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PlanRequestController extends BaseController
{
    public function index(Request $request)
    {
        $query = PlanRequest::with(['user', 'plan', 'approver', 'rejector']);

        if (Auth::user()->hasRole('company')) {
            $query->where('user_id', Auth::user()->id);
        }

        // Apply search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                    ->orWhereHas('plan', function ($planQuery) use ($search) {
                        $planQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Apply filters
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts = ['id', 'created_at'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $perPage = $request->get('per_page', 10);
        $planRequests = $query->paginate((int)$perPage);

        // ============================================================
        // v6.8: جلب قائمة الخطط لاستخدامها في dropdown تغيير الخطة
        // ============================================================
        $plans = Plan::where('is_plan_enable', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'duration',
                'price',
                'yearly_price',
            ]);

        return Inertia::render('plans/plan-request', [
            'planRequests' => $planRequests,
            'plans'        => $plans,
            'filters'      => $request->only(['search', 'status', 'per_page', 'sort_field', 'sort_direction']),
        ]);
    }

    /**
     * v6.8: عرض تفاصيل طلب الخطة شاملة license_key و hardware_id
     * معلومات الشركة الأم إن كان المستخدم موظفاً.
     */
    public function show(Request $request, $id)
    {
        $planRequest = PlanRequest::with(['user', 'plan', 'approver', 'rejector'])
            ->findOrFail($id);

        $user = $planRequest->user;

        // تحديد ما إذا كان المستخدم صاحب الشركة أم موظف لديها
        // غالباً ما تكون field اسمها type، وقد تكون 'company' / 'staff' / 'superadmin'
        $userType = $user?->type ?? null;
        $isOwner  = in_array($userType, ['company', 'superadmin'], true);
        $isStaff  = $userType === 'staff';

        // محاولة العثور على الشركة الأم إن كان المستخدم موظفاً
        $company = null;
        if ($isStaff && $user) {
            // أسلوب 1: عبر created_by إن وُجدت
            if (isset($user->created_by) && $user->created_by) {
                $company = User::where('id', $user->created_by)
                    ->where('type', 'company')
                    ->first();
            }
            // أسلوب 2: عبر slug الشركة المُورَّث من الموظف
            if (!$company && isset($user->slug) && $user->slug) {
                $company = User::where('type', 'company')
                    ->where('slug', $user->slug)
                    ->first();
            }
            // أسلوب 3: عبر company_name إن وُجد
            if (!$company && isset($user->company_name) && $user->company_name) {
                $company = User::where('type', 'company')
                    ->where('name', $user->company_name)
                    ->first();
            }
        }

        return response()->json([
            'id'            => $planRequest->id,
            'status'        => $planRequest->status,
            'created_at'    => $planRequest->created_at,
            'approved_at'   => $planRequest->approved_at,
            'rejected_at'   => $planRequest->rejected_at,
            'user'          => [
                'id'           => $user?->id,
                'name'         => $user?->name,
                'email'        => $user?->email,
                'avatar'       => $user?->avatar,
                'type'         => $userType,
                'is_owner'     => $isOwner,
                'is_staff'     => $isStaff,
                'license_key'  => $user?->license_key,
                'hardware_id'  => $user?->hardware_id,
                'company_name' => $company?->name ?? $user?->company_name,
            ],
            'plan'          => [
                'id'       => $planRequest->plan?->id,
                'name'     => $planRequest->plan?->name,
                'duration' => $planRequest->plan?->duration,
                'price'    => $planRequest->plan?->price,
            ],
            'approver'      => $planRequest->approver?->name,
            'rejector'      => $planRequest->rejector?->name,
        ]);
    }

    public function approve(PlanRequest $planRequest)
    {
        $planRequest->update([
            'status'      => 'approved',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);

        // Assign the plan to the user
        $planRequest->user->update([
            'plan_id' => $planRequest->plan_id,
        ]);

        // Create plan order for history
        \App\Models\PlanOrder::create([
            'user_id'        => $planRequest->user_id,
            'plan_id'        => $planRequest->plan_id,
            'original_price' => 0,
            'final_price'    => 0,
            'status'         => 'approved',
            'ordered_at'     => now(),
        ]);

        return redirect()->route('plan-requests.index')->with('success', __('Plan request approved successfully!'));
    }

    public function reject(PlanRequest $planRequest)
    {
        $planRequest->update([
            'status'      => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => Auth::id(),
        ]);

        return redirect()->route('plan-requests.index')->with('success', __('Plan request rejected successfully!'));
    }

    /**
     * v6.8: تغيير خطة طلب موجود.
     * - يحدّث plan_id في سجل plan_request.
     * - إن كان الطلب approved، يُحدِّث خطة المستخدم أيضاً لتجنّب التعارض.
     */
    public function changePlan(Request $request, $id)
    {
        $planRequest = PlanRequest::with('user')->findOrFail($id);

        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $oldPlanId = $planRequest->plan_id;
        $newPlanId = (int) $validated['plan_id'];

        if ($oldPlanId === $newPlanId) {
            return redirect()->route('plan-requests.index')
                ->with('error', __('New plan is the same as current plan.'));
        }

        $planRequest->update([
            'plan_id' => $newPlanId,
        ]);

        // إن كان الطلب approved بالفعل، نُحدّث خطة المستخدم لتناسب الخطة الجديدة
        if ($planRequest->status === 'approved' && $planRequest->user) {
            $planRequest->user->update([
                'plan_id' => $newPlanId,
            ]);
        }

        Log::info('PlanRequest plan changed', [
            'plan_request_id' => $planRequest->id,
            'old_plan_id'      => $oldPlanId,
            'new_plan_id'      => $newPlanId,
            'changed_by'       => Auth::id(),
        ]);

        return redirect()->route('plan-requests.index')
            ->with('success', __('Plan updated successfully!'));
    }
}
