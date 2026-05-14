<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\SalesOrderActivity;
use Illuminate\Http\Request;

class SalesOrderCommentController extends Controller
{
    public function store(Request $request, $salesOrderId)
    {
        $salesOrder = SalesOrder::where('id', $salesOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$salesOrder) {
            return redirect()->back()->with('error', __('Sales Order not found.'));
        }

        $validated = $request->validate([
            'comment' => 'required|string|max:1000'
        ]);

        SalesOrderActivity::create([
            'sales_order_id' => $salesOrder->id,
            'user_id' => auth()->id(),
            'activity_type' => 'comment',
            'title' => auth()->user()->name . ' added a comment',
            'description' => $validated['comment'],
            'created_by' => createdBy()
        ]);

        return redirect()->back()->with('success', __('Comment added successfully.'));
    }

    public function updateActivity(Request $request, $salesOrderId, $activityId)
    {
        $salesOrder = SalesOrder::where('id', $salesOrderId)
            ->where('created_by', createdBy())
            ->first();

        if (!$salesOrder) {
            return redirect()->back()->with('error', __('Sales Order not found.'));
        }

        $activity = SalesOrderActivity::where('id', $activityId)
            ->where('sales_order_id', $salesOrder->id)
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