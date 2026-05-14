<?php

namespace App\Observers;

use App\Models\Opportunity;
use App\Models\OpportunityActivity;

class OpportunityObserver
{
    public function created(Opportunity $opportunity): void
    {
        $creatorName = $opportunity->creator ? $opportunity->creator->name : 'System';
        $assignedName = $opportunity->assignedUser ? $opportunity->assignedUser->name : 'Unassigned';
        $selfAssigned = $opportunity->creator && $opportunity->assignedUser && $opportunity->creator->id === $opportunity->assignedUser->id;
        
        OpportunityActivity::create([
            'opportunity_id' => $opportunity->id,
            'user_id' => auth()->id() ?? $opportunity->created_by,
            'activity_type' => 'created',
            'title' => $creatorName . ' created this opportunity',
            'description' => ucfirst($opportunity->status ?? 'Active'),
            'new_values' => $opportunity->toArray(),
            'created_by' => $opportunity->created_by
        ]);
        
        if (!$selfAssigned && $opportunity->assignedUser) {
            OpportunityActivity::create([
                'opportunity_id' => $opportunity->id,
                'user_id' => auth()->id() ?? $opportunity->created_by,
                'activity_type' => 'assigned',
                'title' => $creatorName . ' assigned to ' . $assignedName,
                'description' => '',
                'new_values' => ['assigned_to' => $opportunity->assigned_to],
                'created_by' => $opportunity->created_by
            ]);
        } elseif ($selfAssigned) {
            OpportunityActivity::create([
                'opportunity_id' => $opportunity->id,
                'user_id' => auth()->id() ?? $opportunity->created_by,
                'activity_type' => 'assigned',
                'title' => $creatorName . ' self-assigned this opportunity',
                'description' => '',
                'new_values' => ['assigned_to' => $opportunity->assigned_to],
                'created_by' => $opportunity->created_by
            ]);
        }
    }

    public function updated(Opportunity $opportunity): void
    {
        $changes = $opportunity->getChanges();
        $original = $opportunity->getOriginal();
        
        if (empty($changes)) return;
        
        $userName = auth()->user()->name ?? 'System';
        
        foreach ($changes as $field => $newValue) {
            if (in_array($field, ['updated_at'])) continue;
            
            $oldValue = $original[$field] ?? null;
            
            $title = $this->getUpdateTitle($field, $oldValue, $newValue, $userName, $opportunity);
            $description = $this->getUpdateDescription($field, $oldValue, $newValue, $opportunity);
            
            $newValues = [$field => $newValue];
            
            // Store opportunity stage colors for historical accuracy
            if ($field === 'opportunity_stage_id') {
                if ($oldValue) {
                    $oldOpportunityStage = \App\Models\OpportunityStage::find($oldValue);
                    if ($oldOpportunityStage) {
                        $newValues['old_opportunity_stage_color'] = $oldOpportunityStage->color;
                    }
                }
                if ($newValue) {
                    $newOpportunityStage = \App\Models\OpportunityStage::find($newValue);
                    if ($newOpportunityStage) {
                        $newValues['opportunity_stage_color'] = $newOpportunityStage->color;
                    }
                }
            }
            
            OpportunityActivity::create([
                'opportunity_id' => $opportunity->id,
                'user_id' => auth()->id() ?? $opportunity->created_by,
                'activity_type' => 'updated',
                'title' => $title,
                'description' => $description,
                'field_changed' => $field,
                'old_values' => [$field => $oldValue],
                'new_values' => $newValues,
                'created_by' => $opportunity->created_by
            ]);
        }
    }
    
    private function getUpdateTitle($field, $oldValue, $newValue, $userName, $opportunity)
    {
        switch ($field) {
            case 'status':
                return $userName . ' updated status';
            case 'opportunity_stage_id':
                return $userName . ' updated opportunity stage';
            case 'opportunity_source_id':
                return $userName . ' updated opportunity source';
            case 'name':
                return $userName . ' updated name';
            case 'amount':
                return $userName . ' updated amount';
            case 'close_date':
                return $userName . ' updated close date';
            case 'assigned_to':
                $newUser = $newValue ? \App\Models\User::find($newValue)?->name : 'Unassigned';
                if ($newUser === $userName) {
                    return $userName . ' self-assigned this opportunity';
                }
                return $userName . ' assigned to ' . $newUser;
            default:
                $fieldName = str_replace(['_id', '_'], [' ', ' '], $field);
                return $userName . ' updated ' . $fieldName;
        }
    }
    
    private function getUpdateDescription($field, $oldValue, $newValue, $opportunity)
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
            case 'opportunity_stage_id':
                $oldOpportunityStage = $oldValue ? \App\Models\OpportunityStage::find($oldValue) : null;
                $newOpportunityStage = $newValue ? \App\Models\OpportunityStage::find($newValue) : null;
                $oldPart = $oldOpportunityStage ? '<div class="inline-block w-3 h-3 rounded-full mr-1" style="background-color: ' . ($oldOpportunityStage->color ?? '#6b7280') . ';"></div><span class="font-bold">' . $oldOpportunityStage->name . '</span>' : 'None';
                $newPart = $newOpportunityStage ? '<div class="inline-block w-3 h-3 rounded-full mr-1" style="background-color: ' . ($newOpportunityStage->color ?? '#6b7280') . ';"></div><span class="font-bold">' . $newOpportunityStage->name . '</span>' : 'None';
                return $oldPart . ' into ' . $newPart;
            case 'opportunity_source_id':
                $oldSource = $oldValue ? \App\Models\OpportunitySource::find($oldValue)?->name : 'None';
                $newSource = $newValue ? \App\Models\OpportunitySource::find($newValue)?->name : 'None';
                return '<span class="font-bold text-base">' . ($oldSource ?? 'None') . '</span> into <span class="font-bold text-base">' . ($newSource ?? 'None') . '</span>';
            case 'name':
                return '<span class="font-bold text-base">' . ($oldValue ?? '') . '</span> into <span class="font-bold text-base">' . ($newValue ?? '') . '</span>';
            case 'amount':
                $oldAmount = $oldValue ? '$' . number_format($oldValue, 2) : '$0.00';
                $newAmount = $newValue ? '$' . number_format($newValue, 2) : '$0.00';
                return '<span class="font-bold text-base">' . $oldAmount . '</span> into <span class="font-bold text-base">' . $newAmount . '</span>';
            case 'assigned_to':
                $oldUser = $oldValue ? \App\Models\User::find($oldValue)?->name : 'Unassigned';
                $newUser = $newValue ? \App\Models\User::find($newValue)?->name : 'Unassigned';
                return '<span class="font-bold text-base">' . $oldUser . '</span> into <span class="font-bold text-base">' . $newUser . '</span>';
            default:
                // Handle date fields
                if (str_contains($field, '_date') || str_contains($field, '_at') || in_array($field, ['close_date'])) {
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