<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceComment;
use App\Models\InvoiceActivity;
use Illuminate\Http\Request;

class InvoiceCommentController extends Controller
{
    public function store(Request $request, $invoiceId)
    {
        $invoice = Invoice::where('id', $invoiceId)
            ->where('created_by', createdBy())
            ->first();

        if (!$invoice) {
            return redirect()->back()->with('error', __('Invoice not found.'));
        }

        $validated = $request->validate([
            'comment' => 'required|string|max:1000'
        ]);

        $comment = InvoiceComment::create([
            'invoice_id' => $invoice->id,
            'user_id' => auth()->id(),
            'comment' => $validated['comment'],
            'created_by' => createdBy()
        ]);

        // Create activity record
        InvoiceActivity::create([
            'invoice_id' => $invoice->id,
            'user_id' => auth()->id(),
            'activity_type' => 'comment',
            'title' => auth()->user()->name . ' added a comment',
            'description' => $validated['comment'],
            'created_by' => createdBy()
        ]);

        return redirect()->back()->with('success', __('Comment added successfully.'));
    }

    public function updateActivity(Request $request, $invoiceId, $activityId)
    {
        $invoice = Invoice::where('id', $invoiceId)
            ->where('created_by', createdBy())
            ->first();

        if (!$invoice) {
            return redirect()->back()->with('error', __('Invoice not found.'));
        }

        $activity = InvoiceActivity::where('id', $activityId)
            ->where('invoice_id', $invoice->id)
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