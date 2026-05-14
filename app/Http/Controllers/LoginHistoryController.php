<?php

namespace App\Http\Controllers;

use App\Models\LoginHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoginHistoryController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-login-history')) {
            $query = LoginHistory::with('user:id,name,email,type')->where(function ($q) {
                if (Auth::user()->hasRole('superadmin')) {
                    $q->where('created_by', Auth::id())->orWhereHas('user', function ($u) {
                        $u->where('created_by', Auth::id());
                    });
                } else if (Auth::user()->hasRole('company')) {
                    $q->where('created_by', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhere('ip', 'like', "%{$search}%");
            }

            // Sorting
            $sortField = $request->input('sort_field', 'id');
            $sortDirection = $request->input('sort_direction', 'desc');
            $allowedSorts = ['id', 'name', 'created_at'];
            $allowedDirection = ['asc', 'desc'];
            if (!in_array($sortDirection, $allowedDirection)) {
                $sortDirection = 'desc';
            }
            if (in_array($sortField, $allowedSorts)) {
                $query->orderBy($sortField, $sortDirection);
            }

            // Pagination
            $perPage = $request->get('per_page', 10);
            $loginHistory = $query->paginate((int)$perPage)->withQueryString();

            return Inertia::render('login-history/index', [
                'loginHistory' => $loginHistory,
                'filters' => $request->only(['search', 'sort_field', 'sort_direction', 'per_page'])
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function destroy(LoginHistory $loginDetail)
    {
        if (Auth::user()->can('delete-login-history')) {
            $loginDetail->delete();
            return redirect()->back()->with('success', 'Login history deleted successfully.');
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}
