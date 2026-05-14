<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Models\OpportunityActivity;
use App\Models\OpportunityComment;
use Illuminate\Http\Request;

class OpportunityCommentController extends Controller
{
    public function store(Request $request, $opportunityId)
    {
        $opportunity = Opportunity::where('id', $opportunityId)
            ->where('created_by', createdBy())
            ->firstOrFail();

        $validated = $request->validate([
            'comment' => 'required|string|max:1000'
        ]);

        $comment = OpportunityComment::create([
            'opportunity_id' => $opportunity->id,
            'user_id' => auth()->id(),
            'comment' => $validated['comment'],
            'created_by' => createdBy()
        ]);

        OpportunityActivity::create([
            'opportunity_id' => $opportunity->id,
            'user_id' => auth()->id(),
            'activity_type' => 'comment',
            'title' => auth()->user()->name . ' added a comment',
            'description' => $validated['comment'],
            'created_by' => createdBy()
        ]);

        return redirect()->back()->with('success', __('Comment added successfully.'));
    }

    public function updateActivity(Request $request, $opportunityId, $activityId)
    {
        $opportunity = Opportunity::where('id', $opportunityId)
            ->where('created_by', createdBy())
            ->firstOrFail();

        $activity = OpportunityActivity::where('id', $activityId)
            ->where('opportunity_id', $opportunity->id)
            ->where('activity_type', 'comment')
            ->firstOrFail();

        $validated = $request->validate([
            'comment' => 'required|string|max:1000'
        ]);

        $activity->update([
            'description' => $validated['comment']
        ]);

        return redirect()->back()->with('success', __('Comment updated successfully.'));
    }
}