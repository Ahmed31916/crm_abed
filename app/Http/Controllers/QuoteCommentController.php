<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\QuoteComment;
use App\Models\QuoteActivity;
use Illuminate\Http\Request;

class QuoteCommentController extends Controller
{
    public function store(Request $request, $quoteId)
    {
        $quote = Quote::where('id', $quoteId)
            ->where('created_by', createdBy())
            ->first();

        if (!$quote) {
            return redirect()->back()->with('error', __('Quote not found.'));
        }

        $validated = $request->validate([
            'comment' => 'required|string|max:1000'
        ]);

        $comment = QuoteComment::create([
            'quote_id' => $quote->id,
            'user_id' => auth()->id(),
            'comment' => $validated['comment'],
            'created_by' => createdBy()
        ]);

        // Create activity record
        QuoteActivity::create([
            'quote_id' => $quote->id,
            'user_id' => auth()->id(),
            'activity_type' => 'comment',
            'title' => auth()->user()->name . ' added a comment',
            'description' => $validated['comment'],
            'created_by' => createdBy()
        ]);

        return redirect()->back()->with('success', __('Comment added successfully.'));
    }

    public function updateActivity(Request $request, $quoteId, $activityId)
    {
        $quote = Quote::where('id', $quoteId)
            ->where('created_by', createdBy())
            ->first();

        if (!$quote) {
            return redirect()->back()->with('error', __('Quote not found.'));
        }

        $activity = QuoteActivity::where('id', $activityId)
            ->where('quote_id', $quote->id)
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