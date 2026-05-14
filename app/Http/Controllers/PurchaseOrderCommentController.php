<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderActivity;
use Illuminate\Http\Request;

class PurchaseOrderCommentController extends Controller
{
    public function store(Request $request, $purchaseOrderId)
    {
        $purchaseOrder = PurchaseOrder::where('id', $purchaseOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$purchaseOrder) {
            return redirect()->back()->with('error', __('Purchase Order not found.'));
        }

        $validated = $request->validate([
            'comment' => 'required|string|max:1000'
        ]);

        $comment = \App\Models\PurchaseOrderComment::create([
            'purchase_order_id' => $purchaseOrder->id,
            'user_id' => auth()->id(),
            'comment' => $validated['comment'],
            'created_by' => createdBy()
        ]);

        // Create activity record
        PurchaseOrderActivity::create([
            'purchase_order_id' => $purchaseOrder->id,
            'user_id' => auth()->id(),
            'activity_type' => 'comment',
            'title' => auth()->user()->name . ' added a comment',
            'description' => $validated['comment'],
            'created_by' => createdBy()
        ]);

        return redirect()->back()->with('success', __('Comment added successfully.'));
    }

    public function updateActivity(Request $request, $purchaseOrderId, $activityId)
    {
        $purchaseOrder = PurchaseOrder::where('id', $purchaseOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$purchaseOrder) {
            return redirect()->back()->with('error', __('Purchase Order not found.'));
        }

        $activity = PurchaseOrderActivity::where('id', $activityId)
            ->where('purchase_order_id', $purchaseOrder->id)
            ->where('activity_type', 'comment')
            ->where('user_id', auth()->id())
            ->first();

        if (!$activity) {
            return redirect()->back()->with('error', __('Comment not found.'));
        }

        $validated = $request->validate([
            'comment' => 'required|string|max:1000'
        ]);

        $activity->update([
            'description' => $validated['comment']
        ]);

        return redirect()->back()->with('success', __('Comment updated successfully.'));
    }
}