<?php

namespace App\Http\Controllers;

use App\Models\CompanyNotification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompanyNotificationController extends Controller
{
    /**
     * Display notifications for the current company user.
     */
    public function index(Request $request)
    {
        if (!auth()->user()->type === 'company' && !auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }

        $companyId = createdBy();

        $notifications = CompanyNotification::forCompany($companyId)
            ->with('product')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return Inertia::render('company-notifications/index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Get unread notification count (for bell icon badge).
     * Returns JSON for API calls from React components.
     */
    public function unreadCount()
    {
        $companyId = createdBy();
        $count = CompanyNotification::forCompany($companyId)
            ->unread()
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Get recent notifications (for dropdown/popup).
     * Returns JSON for API calls from React components.
     */
    public function recent()
    {
        $companyId = createdBy();
        $notifications = CompanyNotification::forCompany($companyId)
            ->with('product:id,name,sku')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json($notifications);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead($id)
    {
        $notification = CompanyNotification::forCompany(createdBy())
            ->findOrFail($id);

        $notification->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read for the current company.
     */
    public function markAllAsRead()
    {
        CompanyNotification::forCompany(createdBy())
            ->unread()
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Delete a specific notification.
     */
    public function destroy($id)
    {
        $notification = CompanyNotification::forCompany(createdBy())
            ->findOrFail($id);

        $notification->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Delete all notifications for the current company.
     */
    public function deleteAll()
    {
        CompanyNotification::forCompany(createdBy())->delete();

        return response()->json(['success' => true]);
    }
}
