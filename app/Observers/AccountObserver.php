<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\AccountActivity;

class AccountObserver
{
    public function created(Account $account): void
    {
        $creatorName = $account->creator ? $account->creator->name : 'System';
        $assignedName = $account->assignedUser ? $account->assignedUser->name : 'Unassigned';
        $selfAssigned = $account->creator && $account->assignedUser && $account->creator->id === $account->assignedUser->id;
        
        AccountActivity::create([
            'account_id' => $account->id,
            'user_id' => auth()->id() ?? $account->created_by,
            'activity_type' => 'created',
            'title' => $creatorName . ' created this account',
            'description' => ucfirst($account->status ?? 'Active'),
            'new_values' => $account->toArray(),
            'created_by' => $account->created_by
        ]);
        
        if (!$selfAssigned && $account->assignedUser) {
            AccountActivity::create([
                'account_id' => $account->id,
                'user_id' => auth()->id() ?? $account->created_by,
                'activity_type' => 'assigned',
                'title' => $creatorName . ' assigned to ' . $assignedName,
                'description' => '',
                'new_values' => ['assigned_to' => $account->assigned_to],
                'created_by' => $account->created_by
            ]);
        } elseif ($selfAssigned) {
            AccountActivity::create([
                'account_id' => $account->id,
                'user_id' => auth()->id() ?? $account->created_by,
                'activity_type' => 'assigned',
                'title' => $creatorName . ' self-assigned this account',
                'description' => '',
                'new_values' => ['assigned_to' => $account->assigned_to],
                'created_by' => $account->created_by
            ]);
        }
    }

    public function updated(Account $account): void
    {
        $changes = $account->getChanges();
        $original = $account->getOriginal();
        
        if (empty($changes)) return;
        
        $userName = auth()->user()->name ?? 'System';
        
        foreach ($changes as $field => $newValue) {
            if (in_array($field, ['updated_at'])) continue;
            
            $oldValue = $original[$field] ?? null;
            
            $title = $this->getUpdateTitle($field, $oldValue, $newValue, $userName, $account);
            $description = $this->getUpdateDescription($field, $oldValue, $newValue, $account);
            
            $newValues = [$field => $newValue];
            
            // Store account type colors for historical accuracy
            if ($field === 'account_type_id') {
                if ($oldValue) {
                    $oldAccountType = \App\Models\AccountType::find($oldValue);
                    if ($oldAccountType) {
                        $newValues['old_account_type_color'] = $oldAccountType->color;
                    }
                }
                if ($newValue) {
                    $newAccountType = \App\Models\AccountType::find($newValue);
                    if ($newAccountType) {
                        $newValues['account_type_color'] = $newAccountType->color;
                    }
                }
            }
            
            // Store account industry colors for historical accuracy
            if ($field === 'account_industry_id') {
                if ($oldValue) {
                    $oldAccountIndustry = \App\Models\AccountIndustry::find($oldValue);
                    if ($oldAccountIndustry) {
                        $newValues['old_account_industry_color'] = $oldAccountIndustry->color;
                    }
                }
                if ($newValue) {
                    $newAccountIndustry = \App\Models\AccountIndustry::find($newValue);
                    if ($newAccountIndustry) {
                        $newValues['account_industry_color'] = $newAccountIndustry->color;
                    }
                }
            }
            
            AccountActivity::create([
                'account_id' => $account->id,
                'user_id' => auth()->id() ?? $account->created_by,
                'activity_type' => 'updated',
                'title' => $title,
                'description' => $description,
                'field_changed' => $field,
                'old_values' => [$field => $oldValue],
                'new_values' => $newValues,
                'created_by' => $account->created_by
            ]);
        }
    }
    
    private function getUpdateTitle($field, $oldValue, $newValue, $userName, $account)
    {
        switch ($field) {
            case 'status':
                return $userName . ' updated status';
            case 'account_type_id':
                return $userName . ' updated account type';
            case 'account_industry_id':
                return $userName . ' updated account industry';
            case 'name':
                return $userName . ' updated name';
            case 'assigned_to':
                $newUser = $newValue ? \App\Models\User::find($newValue)?->name : 'Unassigned';
                if ($newUser === $userName) {
                    return $userName . ' self-assigned this account';
                }
                return $userName . ' assigned to ' . $newUser;
            default:
                $fieldName = str_replace(['_id', '_'], [' ', ' '], $field);
                return $userName . ' updated ' . $fieldName;
        }
    }
    
    private function getUpdateDescription($field, $oldValue, $newValue, $account)
    {
        switch ($field) {
            case 'status':
                $statusColors = [
                    'active' => 'bg-green-50 text-green-700 ring-green-600/20',
                    'inactive' => 'bg-red-50 text-red-700 ring-red-600/20'
                ];
                $oldColor = $statusColors[$oldValue] ?? $statusColors['active'];
                $newColor = $statusColors[$newValue] ?? $statusColors['active'];
                $oldStatus = $oldValue ? '<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ' . $oldColor . '">' . ucfirst($oldValue) . '</span>' : 'None';
                $newStatus = $newValue ? '<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ' . $newColor . '">' . ucfirst($newValue) . '</span>' : 'None';
                return $oldStatus . ' into ' . $newStatus;
            case 'account_type_id':
                $oldAccountType = $oldValue ? \App\Models\AccountType::find($oldValue) : null;
                $newAccountType = $newValue ? \App\Models\AccountType::find($newValue) : null;
                $oldPart = $oldAccountType ? '<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset" style="background-color: rgba(' . hexdec(substr($oldAccountType->color ?? '#6b7280', 1, 2)) . ', ' . hexdec(substr($oldAccountType->color ?? '#6b7280', 3, 2)) . ', ' . hexdec(substr($oldAccountType->color ?? '#6b7280', 5, 2)) . ', 0.125); color: ' . ($oldAccountType->color ?? '#6b7280') . '; border-color: rgba(' . hexdec(substr($oldAccountType->color ?? '#6b7280', 1, 2)) . ', ' . hexdec(substr($oldAccountType->color ?? '#6b7280', 3, 2)) . ', ' . hexdec(substr($oldAccountType->color ?? '#6b7280', 5, 2)) . ', 0.25);">' . $oldAccountType->name . '</span>' : 'None';
                $newPart = $newAccountType ? '<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset" style="background-color: rgba(' . hexdec(substr($newAccountType->color ?? '#6b7280', 1, 2)) . ', ' . hexdec(substr($newAccountType->color ?? '#6b7280', 3, 2)) . ', ' . hexdec(substr($newAccountType->color ?? '#6b7280', 5, 2)) . ', 0.125); color: ' . ($newAccountType->color ?? '#6b7280') . '; border-color: rgba(' . hexdec(substr($newAccountType->color ?? '#6b7280', 1, 2)) . ', ' . hexdec(substr($newAccountType->color ?? '#6b7280', 3, 2)) . ', ' . hexdec(substr($newAccountType->color ?? '#6b7280', 5, 2)) . ', 0.25);">' . $newAccountType->name . '</span>' : 'None';
                return $oldPart . ' into ' . $newPart;
            case 'account_industry_id':
                $oldAccountIndustry = $oldValue ? \App\Models\AccountIndustry::find($oldValue) : null;
                $newAccountIndustry = $newValue ? \App\Models\AccountIndustry::find($newValue) : null;
                $oldPart = $oldAccountIndustry ? '<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset" style="background-color: rgba(' . hexdec(substr($oldAccountIndustry->color ?? '#6b7280', 1, 2)) . ', ' . hexdec(substr($oldAccountIndustry->color ?? '#6b7280', 3, 2)) . ', ' . hexdec(substr($oldAccountIndustry->color ?? '#6b7280', 5, 2)) . ', 0.125); color: ' . ($oldAccountIndustry->color ?? '#6b7280') . '; border-color: rgba(' . hexdec(substr($oldAccountIndustry->color ?? '#6b7280', 1, 2)) . ', ' . hexdec(substr($oldAccountIndustry->color ?? '#6b7280', 3, 2)) . ', ' . hexdec(substr($oldAccountIndustry->color ?? '#6b7280', 5, 2)) . ', 0.25);">' . $oldAccountIndustry->name . '</span>' : 'None';
                $newPart = $newAccountIndustry ? '<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset" style="background-color: rgba(' . hexdec(substr($newAccountIndustry->color ?? '#6b7280', 1, 2)) . ', ' . hexdec(substr($newAccountIndustry->color ?? '#6b7280', 3, 2)) . ', ' . hexdec(substr($newAccountIndustry->color ?? '#6b7280', 5, 2)) . ', 0.125); color: ' . ($newAccountIndustry->color ?? '#6b7280') . '; border-color: rgba(' . hexdec(substr($newAccountIndustry->color ?? '#6b7280', 1, 2)) . ', ' . hexdec(substr($newAccountIndustry->color ?? '#6b7280', 3, 2)) . ', ' . hexdec(substr($newAccountIndustry->color ?? '#6b7280', 5, 2)) . ', 0.25);">' . $newAccountIndustry->name . '</span>' : 'None';
                return $oldPart . ' into ' . $newPart;
            case 'name':
                return '<span class="font-bold text-base">' . ($oldValue ?? '') . '</span> into <span class="font-bold text-base">' . ($newValue ?? '') . '</span>';
            case 'assigned_to':
                $oldUser = $oldValue ? \App\Models\User::find($oldValue)?->name : 'Unassigned';
                $newUser = $newValue ? \App\Models\User::find($newValue)?->name : 'Unassigned';
                return '<span class="font-bold text-base">' . $oldUser . '</span> into <span class="font-bold text-base">' . $newUser . '</span>';
            default:
                // Handle date fields
                if (str_contains($field, '_date') || str_contains($field, '_at') || in_array($field, ['valid_until'])) {
                    $oldDate = $oldValue ? date('Y-m-d', strtotime($oldValue)) : 'None';
                    $newDate = $newValue ? date('Y-m-d', strtotime($newValue)) : 'None';
                    return '<span class="font-bold text-base">' . $oldDate . '</span> into <span class="font-bold text-base">' . $newDate . '</span>';
                }
                $oldVal = $oldValue ?? 'None';
                $newVal = $newValue ?? 'None';
                return '<span class="font-bold text-base">' . $oldVal . '</span> into <span class="font-bold text-base">' . $newVal . '</span>';
        }
    }
}