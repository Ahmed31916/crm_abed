<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\PlanOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PlanOrderController extends BaseController
{
    public function index(Request $request)
    {
        $query = PlanOrder::with(['user', 'plan', 'coupon', 'processedBy']);

        if (Auth::user()->hasRole('company')) {
            $query->where('user_id', Auth::user()->id);
        }
        // Apply search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('plan', function ($planQuery) use ($search) {
                        $planQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Apply filters
        if ($request->has('status') && $request->status !== '' && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Handle date range filter
        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->whereDate('ordered_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->whereDate('ordered_at', '<=', $request->date_to);
        }

        // Always use super admin currency for plan pricing
        $superAdmin = User::where('type', 'superadmin')->first();
        $superAdminSettings = settings($superAdmin->id);
        $currency = $superAdminSettings ? ($superAdminSettings['defaultCurrency'] ?? 'USD') : 'USD';
        $currencySymbol = '$';
        if (!empty($currency)) {
            $currencyData = Currency::where('code', $currency)->first();
            $currencySymbol = $currencyData ? $currencyData->symbol : '$';
        }

        // Apply sorting
        $sortField = $request->input('sort_field', 'ordered_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts = ['final_price', 'ordered_at'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $perPage = $request->get('per_page', 10);
        $planOrders = $query->paginate((int)$perPage);

        return Inertia::render('plans/plan-orders', [
            'planOrders' => $planOrders,
            'filters' => $request->only(['search', 'status', 'sort_field', 'sort_direction', 'per_page', 'date_from', 'date_to']),
            'currency' => $currency,
            'currencySymbol' => $currencySymbol
        ]);
    }

    public function approve(PlanOrder $planOrder)
    {
        try {
            $planOrder->approve(Auth::id());

            return redirect()->route('plan-orders.index')
                ->with('success', __('Plan order approved successfully!'));
        } catch (\Exception $e) {
            return redirect()->route('plan-orders.index')
                ->with('error', __('Failed to approve plan order: ') . $e->getMessage());
        }
    }

    public function reject(Request $request, PlanOrder $planOrder)
    {
        try {
            $request->validate([
                'notes' => 'nullable|string|max:500'
            ]);

            $planOrder->reject(Auth::id(), $request->notes);

            return redirect()->route('plan-orders.index')
                ->with('success', __('Plan order rejected successfully!'));
        } catch (\Exception $e) {
            return redirect()->route('plan-orders.index')
                ->with('error', __('Failed to reject plan order: ') . $e->getMessage());
        }
    }
}
