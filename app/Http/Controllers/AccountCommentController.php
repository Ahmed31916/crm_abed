<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountActivity;
use App\Models\AccountComment;
use Illuminate\Http\Request;

class AccountCommentController extends Controller
{
    public function store(Request $request, $accountId)
    {
        $account = Account::where('id', $accountId)
            ->where('created_by', createdBy())
            ->firstOrFail();

        $validated = $request->validate([
            'comment' => 'required|string|max:1000'
        ]);

        $comment = AccountComment::create([
            'account_id' => $account->id,
            'user_id' => auth()->id(),
            'comment' => $validated['comment'],
            'created_by' => createdBy()
        ]);

        AccountActivity::create([
            'account_id' => $account->id,
            'user_id' => auth()->id(),
            'activity_type' => 'comment',
            'title' => auth()->user()->name . ' added a comment',
            'description' => $validated['comment'],
            'created_by' => createdBy()
        ]);

        return redirect()->back()->with('success', __('Comment added successfully'));
    }

    public function updateActivity(Request $request, $accountId, $activityId)
    {
        $account = Account::where('id', $accountId)
            ->where('created_by', createdBy())
            ->firstOrFail();

        $activity = AccountActivity::where('id', $activityId)
            ->where('account_id', $account->id)
            ->where('activity_type', 'comment')
            ->firstOrFail();

        $validated = $request->validate([
            'comment' => 'required|string|max:1000'
        ]);

        $activity->update([
            'description' => $validated['comment']
        ]);

        return redirect()->back()->with('success', __('Comment updated successfully'));
    }
}