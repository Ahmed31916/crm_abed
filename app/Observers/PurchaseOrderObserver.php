<?php

namespace App\Observers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderActivity;

class PurchaseOrderObserver
{
    public function created(PurchaseOrder $purchaseOrder): void
    {
        $creatorName = $purchaseOrder->creator ? $purchaseOrder->creator->name : 'System';
        $assignedName = $purchaseOrder->assignedUser ? $purchaseOrder->assignedUser->name : 'Unassigned';
        $selfAssigned = $purchaseOrder->creator && $purchaseOrder->assignedUser && $purchaseOrder->creator->id === $purchaseOrder->assignedUser->id;
        
        PurchaseOrderActivity::create([
            'purchase_order_id' => $purchaseOrder->id,
            'user_id' => auth()->id() ?? $purchaseOrder->created_by,
            'activity_type' => 'created',
            'title' => $creatorName . ' created this purchase order',
            'description' => ucfirst($purchaseOrder->status ?? 'Draft'),
            'new_values' => $purchaseOrder->toArray(),
            'created_by' => $purchaseOrder->created_by
        ]);
        
        if (!$selfAssigned && $purchaseOrder->assignedUser) {
            PurchaseOrderActivity::create([
                'purchase_order_id' => $purchaseOrder->id,
                'user_id' => auth()->id() ?? $purchaseOrder->created_by,
                'activity_type' => 'assigned',
                'title' => $creatorName . ' assigned to ' . $assignedName,
                'description' => '',
                'new_values' => ['assigned_to' => $purchaseOrder->assigned_to],
                'created_by' => $purchaseOrder->created_by
            ]);
        } elseif ($selfAssigned) {
            PurchaseOrderActivity::create([
                'purchase_order_id' => $purchaseOrder->id,
                'user_id' => auth()->id() ?? $purchaseOrder->created_by,
                'activity_type' => 'assigned',
                'title' => $creatorName . ' self-assigned this purchase order',
                'description' => '',
                'new_values' => ['assigned_to' => $purchaseOrder->assigned_to],
                'created_by' => $purchaseOrder->created_by
            ]);
        }
    }

    public function updated(PurchaseOrder $purchaseOrder): void
    {
        $changes = $purchaseOrder->getChanges();
        $original = $purchaseOrder->getOriginal();
        
        if (empty($changes)) return;
        
        $userName = auth()->user()->name ?? 'System';
        
        foreach ($changes as $field => $newValue) {
            if (in_array($field, ['updated_at'])) continue;
            
            $oldValue = $original[$field] ?? null;
            
            $title = $this->getUpdateTitle($field, $oldValue, $newValue, $userName, $purchaseOrder);
            $description = $this->getUpdateDescription($field, $oldValue, $newValue, $purchaseOrder);
            
            PurchaseOrderActivity::create([
                'purchase_order_id' => $purchaseOrder->id,
                'user_id' => auth()->id() ?? $purchaseOrder->created_by,
                'activity_type' => 'updated',
                'title' => $title,
                'description' => $description,
                'field_changed' => $field,
                'old_values' => [$field => $oldValue],
                'new_values' => [$field => $newValue],
                'created_by' => $purchaseOrder->created_by
            ]);
        }
    }
    
    private function getUpdateTitle($field, $oldValue, $newValue, $userName, $purchaseOrder)
    {
        switch ($field) {
            case 'status':
                return $userName . ' updated status';
            case 'name':
                return $userName . ' updated name';
            case 'assigned_to':
                $newUser = $newValue ? \App\Models\User::find($newValue)?->name : 'Unassigned';
                if ($newUser === $userName) {
                    return $userName . ' self-assigned this purchase order';
                }
                return $userName . ' assigned to ' . $newUser;
            case 'account_id':
                return $userName . ' updated account';
            case 'sales_order_id':
                return $userName . ' updated sales order';
            case 'billing_contact_id':
                return $userName . ' updated billing contact';
            case 'shipping_contact_id':
                return $userName . ' updated shipping contact';
            case 'shipping_provider_type_id':
                return $userName . ' updated shipping provider';
            case 'expected_delivery_date':
                return $userName . ' updated expected delivery date';
            default:
                $fieldName = str_replace(['_id', '_'], [' ', ' '], $field);
                return $userName . ' updated ' . $fieldName;
        }
    }
    
    private function getUpdateDescription($field, $oldValue, $newValue, $purchaseOrder)
    {
        switch ($field) {
            case 'status':
                $statusColors = [
                    'draft' => 'bg-gray-50 text-gray-700 ring-gray-600/20',
                    'sent' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                    'confirmed' => 'bg-green-50 text-green-700 ring-green-600/20',
                    'received' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
                    'cancelled' => 'bg-red-50 text-red-700 ring-red-600/20'
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
            case 'sales_order_id':
                $oldSalesOrder = $oldValue ? \App\Models\SalesOrder::find($oldValue)?->order_number : 'None';
                $newSalesOrder = $newValue ? \App\Models\SalesOrder::find($newValue)?->order_number : 'None';
                return '<span class="font-bold text-base">' . ($oldSalesOrder ?? 'None') . '</span> into <span class="font-bold text-base">' . ($newSalesOrder ?? 'None') . '</span>';
            case 'billing_contact_id':
            case 'shipping_contact_id':
                $oldContact = $oldValue ? \App\Models\Contact::find($oldValue)?->name : 'None';
                $newContact = $newValue ? \App\Models\Contact::find($newValue)?->name : 'None';
                return '<span class="font-bold text-base">' . ($oldContact ?? 'None') . '</span> into <span class="font-bold text-base">' . ($newContact ?? 'None') . '</span>';
            case 'shipping_provider_type_id':
                $oldProvider = $oldValue ? \App\Models\ShippingProviderType::find($oldValue)?->name : 'None';
                $newProvider = $newValue ? \App\Models\ShippingProviderType::find($newValue)?->name : 'None';
                return '<span class="font-bold text-base">' . ($oldProvider ?? 'None') . '</span> into <span class="font-bold text-base">' . ($newProvider ?? 'None') . '</span>';
            case 'expected_delivery_date':
                $oldDate = $oldValue ? date('Y-m-d', strtotime($oldValue)) : 'None';
                $newDate = $newValue ? date('Y-m-d', strtotime($newValue)) : 'None';
                return '<span class="font-bold text-base">' . $oldDate . '</span> into <span class="font-bold text-base">' . $newDate . '</span>';
            default:
                if (str_contains($field, '_date') || str_contains($field, '_at')) {
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