<?php

namespace App\Observers;

use App\Models\Quote;
use App\Models\QuoteActivity;

class QuoteObserver
{
    private static $processing = [];
    public function created(Quote $quote): void
    {
        $key = 'created_' . $quote->id;
        if (isset(self::$processing[$key])) return;
        self::$processing[$key] = true;

        $creatorName = $quote->creator ? $quote->creator->name : 'System';
        $assignedName = $quote->assignedUser ? $quote->assignedUser->name : 'Unassigned';
        $selfAssigned = $quote->creator && $quote->assignedUser && $quote->creator->id === $quote->assignedUser->id;

        QuoteActivity::create([
            'quote_id' => $quote->id,
            'user_id' => auth()->id() ?? $quote->created_by,
            'activity_type' => 'created',
            'title' => $creatorName . ' created this quote',
            'description' => ucfirst($quote->status ?? 'Draft'),
            'new_values' => $quote->toArray(),
            'created_by' => $quote->created_by
        ]);

        if (!$selfAssigned && $quote->assignedUser) {
            QuoteActivity::create([
                'quote_id' => $quote->id,
                'user_id' => auth()->id() ?? $quote->created_by,
                'activity_type' => 'assigned',
                'title' => $creatorName . ' assigned to ' . $assignedName,
                'description' => '',
                'new_values' => ['assigned_to' => $quote->assigned_to],
                'created_by' => $quote->created_by
            ]);
        } elseif ($selfAssigned) {
            QuoteActivity::create([
                'quote_id' => $quote->id,
                'user_id' => auth()->id() ?? $quote->created_by,
                'activity_type' => 'assigned',
                'title' => $creatorName . ' self-assigned this quote',
                'description' => '',
                'new_values' => ['assigned_to' => $quote->assigned_to],
                'created_by' => $quote->created_by
            ]);
        }
    }

    public function updated(Quote $quote): void
    {
        $key = 'updated_' . $quote->id . '_' . md5(serialize($quote->getChanges()));
        if (isset(self::$processing[$key])) return;
        self::$processing[$key] = true;

        $changes = $quote->getChanges();
        $original = $quote->getOriginal();

        if (empty($changes)) return;

        $userName = auth()->user()->name ?? 'System';

        foreach ($changes as $field => $newValue) {
            if (in_array($field, ['updated_at'])) continue;

            $oldValue = $original[$field] ?? null;

            $title = $this->getUpdateTitle($field, $oldValue, $newValue, $userName, $quote);
            $description = $this->getUpdateDescription($field, $oldValue, $newValue, $quote);

            QuoteActivity::create([
                'quote_id' => $quote->id,
                'user_id' => auth()->id() ?? $quote->created_by,
                'activity_type' => 'updated',
                'title' => $title,
                'description' => $description,
                'field_changed' => $field,
                'old_values' => [$field => $oldValue],
                'new_values' => [$field => $newValue],
                'created_by' => $quote->created_by
            ]);
        }
    }

    private function getUpdateTitle($field, $oldValue, $newValue, $userName, $quote)
    {
        switch ($field) {
            case 'status':
                return $userName . ' updated status';
            case 'name':
                return $userName . ' updated name';
            case 'assigned_to':
                $newUser = $newValue ? \App\Models\User::find($newValue)?->name : 'Unassigned';
                if ($newUser === $userName) {
                    return $userName . ' self-assigned this quote';
                }
                return $userName . ' assigned to ' . $newUser;
            case 'account_id':
                return $userName . ' updated account';
            case 'opportunity_id':
                return $userName . ' updated opportunity';
            case 'billing_contact_id':
                return $userName . ' updated billing contact';
            case 'shipping_contact_id':
                return $userName . ' updated shipping contact';
            case 'shipping_provider_type_id':
                return $userName . ' updated shipping provider';
            default:
                $fieldName = str_replace(['_id', '_'], [' ', ' '], $field);
                return $userName . ' updated ' . $fieldName;
        }
    }

    private function getUpdateDescription($field, $oldValue, $newValue, $quote)
    {
        switch ($field) {
            case 'status':
                $statusColors = [
                    'draft' => 'bg-gray-50 text-gray-700 ring-gray-600/20',
                    'sent' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                    'accepted' => 'bg-green-50 text-green-700 ring-green-600/20',
                    'rejected' => 'bg-red-50 text-red-700 ring-red-600/20',
                    'expired' => 'bg-yellow-50 text-yellow-700 ring-yellow-600/20'
                ];
                $oldColor = $statusColors[$oldValue] ?? $statusColors['draft'];
                $newColor = $statusColors[$newValue] ?? $statusColors['draft'];
                $oldStatus = $oldValue ? '<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ' . $oldColor . '">' . ucfirst($oldValue) . '</span>' : 'None';
                $newStatus = $newValue ? '<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ' . $newColor . '">' . ucfirst($newValue) . '</span>' : 'None';
                return $oldStatus . ' into ' . $newStatus;
            case 'name':
                return '<span class="font-bold text-base">' . ($oldValue ?? '') . '</span> into <span class="font-bold text-base">' . ($newValue ?? '') . '</span>';
            case 'assigned_to':
                $oldUser = $oldValue ? \App\Models\User::find($oldValue)?->name : 'Unassigned';
                $newUser = $newValue ? \App\Models\User::find($newValue)?->name : 'Unassigned';
                return '<span class="font-bold text-base">' . $oldUser . '</span> into <span class="font-bold text-base">' . $newUser . '</span>';
            case 'account_id':
                $oldAccount = $oldValue ? \App\Models\Account::find($oldValue)?->name : 'None';
                $newAccount = $newValue ? \App\Models\Account::find($newValue)?->name : 'None';
                return '<span class="font-bold text-base">' . ($oldAccount ?? 'None') . '</span> into <span class="font-bold text-base">' . ($newAccount ?? 'None') . '</span>';
            case 'opportunity_id':
                $oldOpportunity = $oldValue ? \App\Models\Opportunity::find($oldValue)?->name : 'None';
                $newOpportunity = $newValue ? \App\Models\Opportunity::find($newValue)?->name : 'None';
                return '<span class="font-bold text-base">' . ($oldOpportunity ?? 'None') . '</span> into <span class="font-bold text-base">' . ($newOpportunity ?? 'None') . '</span>';
            case 'billing_contact_id':
            case 'shipping_contact_id':
                $oldContact = $oldValue ? \App\Models\Contact::find($oldValue)?->name : 'None';
                $newContact = $newValue ? \App\Models\Contact::find($newValue)?->name : 'None';
                return '<span class="font-bold text-base">' . ($oldContact ?? 'None') . '</span> into <span class="font-bold text-base">' . ($newContact ?? 'None') . '</span>';
            case 'shipping_provider_type_id':
                $oldProvider = $oldValue ? \App\Models\ShippingProviderType::find($oldValue)?->name : 'None';
                $newProvider = $newValue ? \App\Models\ShippingProviderType::find($newValue)?->name : 'None';
                return '<span class="font-bold text-base">' . ($oldProvider ?? 'None') . '</span> into <span class="font-bold text-base">' . ($newProvider ?? 'None') . '</span>';
            case 'valid_until':
                $oldDate = $oldValue ? date('Y-m-d', strtotime($oldValue)) : 'None';
                $newDate = $newValue ? date('Y-m-d', strtotime($newValue)) : 'None';
                return '<span class="font-bold text-base">' . $oldDate . '</span> into <span class="font-bold text-base">' . $newDate . '</span>';
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
