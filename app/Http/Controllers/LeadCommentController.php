<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadComment;
use App\Models\LeadActivity;
use Illuminate\Http\Request;

class LeadCommentController extends Controller
{
    public function store(Request $request, $leadId)
    {
        try {
            $lead = Lead::where('id', $leadId)
                ->where('created_by', createdBy())
                ->first();

            if (!$lead) {
                return redirect()->back()->with('error', __('Lead not found.'));
            }

            $validated = $request->validate([
                'comment' => 'required|string|max:1000',
            ]);

            $comment = LeadComment::create([
                'lead_id' => $lead->id,
                'comment' => $validated['comment'],
                'user_id' => auth()->id(),
                'created_by' => createdBy(),
            ]);

            // Create activity entry
            LeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => auth()->id(),
                'activity_type' => 'comment',
                'title' => auth()->user()->name . ' added a comment',
                'description' => $validated['comment'],
                'created_by' => createdBy(),
            ]);

            return redirect()->back()->with('success', __('Comment added successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to add comment: :error', ['error' => $e->getMessage()]));
        }
    }

    public function update(Request $request, $leadId, $commentId)
    {
        $lead = Lead::where('id', $leadId)
            ->where('created_by', createdBy())
            ->first();

        if (!$lead) {
            return redirect()->back()->with('error', __('Lead not found.'));
        }

        $comment = LeadComment::where('id', $commentId)
            ->where('lead_id', $lead->id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$comment) {
            return redirect()->back()->with('error', __('Comment not found.'));
        }

        $validated = $request->validate([
            'comment' => 'required|string',
        ]);

        $comment->update($validated);

        return redirect()->back()->with('success', __('Comment updated successfully.'));
    }

    public function updateActivity(Request $request, $leadId, $activityId)
    {
        $lead = Lead::where('id', $leadId)
            ->where('created_by', createdBy())
            ->first();

        if (!$lead) {
            return redirect()->back()->with('error', __('Lead not found.'));
        }

        $activity = LeadActivity::where('id', $activityId)
            ->where('lead_id', $lead->id)
            ->where('user_id', auth()->id())
            ->where('activity_type', 'comment')
            ->first();

        if (!$activity) {
            return redirect()->back()->with('error', __('Comment not found.'));
        }

        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $activity->update([
            'description' => $validated['comment']
        ]);

        return redirect()->back()->with('success', __('Comment updated successfully.'));
    }

    public function destroy($leadId, $commentId)
    {
        $lead = Lead::where('id', $leadId)
            ->where('created_by', createdBy())
            ->first();

        if (!$lead) {
            return redirect()->back()->with('error', __('Lead not found.'));
        }

        $comment = LeadComment::where('id', $commentId)
            ->where('lead_id', $lead->id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$comment) {
            return redirect()->back()->with('error', __('Comment not found.'));
        }

        $comment->delete();

        return redirect()->back()->with('success', __('Comment deleted successfully.'));
    }
}