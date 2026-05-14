<?php

namespace App\Observers;

use App\Models\Lead;
use App\Models\LeadActivity;

class LeadObserver
{
    public function created(Lead $lead): void
    {
        $creatorName = $lead->creator ? $lead->creator->name : 'System';
        $assignedName = $lead->assignedUser ? $lead->assignedUser->name : 'Unassigned';
        $selfAssigned = $lead->creator && $lead->assignedUser && $lead->creator->id === $lead->assignedUser->id;
        
        LeadActivity::create([
            'lead_id' => $lead->id,
            'user_id' => auth()->id() ?? $lead->created_by,
            'activity_type' => 'created',
            'title' => $creatorName . ' created this lead',
            'description' => ucfirst($lead->status ?? 'New'),
            'new_values' => $lead->toArray(),
            'created_by' => $lead->created_by
        ]);
        
        if (!$selfAssigned && $lead->assignedUser) {
            LeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => auth()->id() ?? $lead->created_by,
                'activity_type' => 'assigned',
                'title' => $creatorName . ' assigned to ' . $assignedName,
                'description' => '',
                'new_values' => ['assigned_to' => $lead->assigned_to],
                'created_by' => $lead->created_by
            ]);
        } elseif ($selfAssigned) {
            LeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => auth()->id() ?? $lead->created_by,
                'activity_type' => 'assigned',
                'title' => $creatorName . ' self-assigned this lead',
                'description' => '',
                'new_values' => ['assigned_to' => $lead->assigned_to],
                'created_by' => $lead->created_by
            ]);
        }
    }

    public function updated(Lead $lead): void
    {
        $changes = $lead->getChanges();
        $original = $lead->getOriginal();
        
        if (empty($changes)) return;
        
        $userName = auth()->user()->name ?? 'System';
        
        foreach ($changes as $field => $newValue) {
            if (in_array($field, ['updated_at'])) continue;
            
            $oldValue = $original[$field] ?? null;
            if ($field === 'is_converted') continue;
            
            $title = $this->getUpdateTitle($field, $oldValue, $newValue, $userName, $lead);
            $description = $this->getUpdateDescription($field, $oldValue, $newValue, $lead);
            
            $newValues = [$field => $newValue];
            
            // Store lead status colors for historical accuracy
            if ($field === 'lead_status_id') {
                if ($oldValue) {
                    $oldLeadStatus = \App\Models\LeadStatus::find($oldValue);
                    if ($oldLeadStatus) {
                        $newValues['old_lead_status_color'] = $oldLeadStatus->color;
                    }
                }
                if ($newValue) {
                    $newLeadStatus = \App\Models\LeadStatus::find($newValue);
                    if ($newLeadStatus) {
                        $newValues['lead_status_color'] = $newLeadStatus->color;
                    }
                }
            }
            
            LeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => auth()->id() ?? $lead->created_by,
                'activity_type' => 'updated',
                'title' => $title,
                'description' => $description,
                'field_changed' => $field,
                'old_values' => [$field => $oldValue],
                'new_values' => $newValues,
                'created_by' => $lead->created_by
            ]);
        }
        

    }
    
    private function getUpdateTitle($field, $oldValue, $newValue, $userName, $lead)
    {
        switch ($field) {
            case 'status':
                return $userName . ' updated status';
            case 'lead_status_id':
                return $userName . ' updated lead status';
            case 'lead_source_id':
                return $userName . ' updated lead source';
            case 'account_industry_id':
                return $userName . ' updated account industry';
            case 'campaign_id':
                return $userName . ' updated campaign';
            case 'name':
                return $userName . ' updated name';
            case 'assigned_to':
                $newUser = $newValue ? \App\Models\User::find($newValue)?->name : 'Unassigned';
                if ($newUser === $userName) {
                    return $userName . ' self-assigned this lead';
                }
                return $userName . ' assigned to ' . $newUser;
            case 'account_id':
                return $userName . ' updated account';
            default:
                $fieldName = str_replace(['_id', '_'], [' ', ' '], $field);
                return $userName . ' updated ' . $fieldName;
        }
    }
    
    private function getUpdateDescription($field, $oldValue, $newValue, $lead)
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
            case 'lead_status_id':
                $oldLeadStatus = $oldValue ? \App\Models\LeadStatus::find($oldValue) : null;
                $newLeadStatus = $newValue ? \App\Models\LeadStatus::find($newValue) : null;
                $oldPart = $oldLeadStatus ? '<div class="inline-block w-3 h-3 rounded-full mr-1" style="background-color: ' . ($oldLeadStatus->color ?? '#6b7280') . ';"></div><span class="font-bold">' . $oldLeadStatus->name . '</span>' : 'None';
                $newPart = $newLeadStatus ? '<div class="inline-block w-3 h-3 rounded-full mr-1" style="background-color: ' . ($newLeadStatus->color ?? '#6b7280') . ';"></div><span class="font-bold">' . $newLeadStatus->name . '</span>' : 'None';
                return $oldPart . ' into ' . $newPart;
            case 'name':
                return '<span class="font-bold text-base">' . ($oldValue ?? '') . '</span> into <span class="font-bold text-base">' . ($newValue ?? '') . '</span>';
            case 'assigned_to':
                $oldUser = $oldValue ? \App\Models\User::find($oldValue)?->name : 'Unassigned';
                $newUser = $newValue ? \App\Models\User::find($newValue)?->name : 'Unassigned';
                return '<span class="font-bold text-base">' . $oldUser . '</span> into <span class="font-bold text-base">' . $newUser . '</span>';
            case 'is_converted':
                $oldConverted = $oldValue ? 'Converted' : 'Not Converted';
                $newConverted = $newValue ? 'Converted' : 'Not Converted';
                return '<span class="font-bold text-base">' . $oldConverted . '</span> into <span class="font-bold text-base">' . $newConverted . '</span>';
            case 'account_industry_id':
                $oldIndustry = $oldValue ? \App\Models\AccountIndustry::find($oldValue)?->name : 'None';
                $newIndustry = $newValue ? \App\Models\AccountIndustry::find($newValue)?->name : 'None';
                return '<span class="font-bold text-base">' . ($oldIndustry ?? 'None') . '</span> into <span class="font-bold text-base">' . ($newIndustry ?? 'None') . '</span>';
            case 'campaign_id':
                $oldCampaign = $oldValue ? \App\Models\Campaign::find($oldValue)?->name : 'None';
                $newCampaign = $newValue ? \App\Models\Campaign::find($newValue)?->name : 'None';
                return '<span class="font-bold text-base">' . ($oldCampaign ?? 'None') . '</span> into <span class="font-bold text-base">' . ($newCampaign ?? 'None') . '</span>';
            case 'lead_source_id':
                $oldSource = $oldValue ? \App\Models\LeadSource::find($oldValue)?->name : 'None';
                $newSource = $newValue ? \App\Models\LeadSource::find($newValue)?->name : 'None';
                return '<span class="font-bold text-base">' . ($oldSource ?? 'None') . '</span> into <span class="font-bold text-base">' . ($newSource ?? 'None') . '</span>';
            case 'account_id':
                $oldAccount = $oldValue ? \App\Models\Account::find($oldValue)?->name : 'None';
                $newAccount = $newValue ? \App\Models\Account::find($newValue)?->name : 'None';
                return '<span class="font-bold text-base">' . ($oldAccount ?? 'None') . '</span> into <span class="font-bold text-base">' . ($newAccount ?? 'None') . '</span>';
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
