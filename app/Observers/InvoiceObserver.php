<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\InvoiceActivity;

class InvoiceObserver
{
    public function created(Invoice $invoice): void
    {
        $creatorName = $invoice->creator ? $invoice->creator->name : 'System';
        $assignedName = $invoice->assignedUser ? $invoice->assignedUser->name : 'Unassigned';
        $selfAssigned = $invoice->creator && $invoice->assignedUser && $invoice->creator->id === $invoice->assignedUser->id;

        InvoiceActivity::create([
            'invoice_id' => $invoice->id,
            'user_id' => auth()->id() ?? $invoice->created_by,
            'activity_type' => 'created',
            'title' => $creatorName . ' created this invoice',
            'description' => ucfirst($invoice->status ?? 'Draft'),
            'new_values' => $invoice->toArray(),
            'created_by' => $invoice->created_by
        ]);

        if (!$selfAssigned && $invoice->assignedUser) {
            InvoiceActivity::create([
                'invoice_id' => $invoice->id,
                'user_id' => auth()->id() ?? $invoice->created_by,
                'activity_type' => 'assigned',
                'title' => $creatorName . ' assigned to ' . $assignedName,
                'description' => '',
                'new_values' => ['assigned_to' => $invoice->assigned_to],
                'created_by' => $invoice->created_by
            ]);
        } elseif ($selfAssigned) {
            InvoiceActivity::create([
                'invoice_id' => $invoice->id,
                'user_id' => auth()->id() ?? $invoice->created_by,
                'activity_type' => 'assigned',
                'title' => $creatorName . ' self-assigned this invoice',
                'description' => '',
                'new_values' => ['assigned_to' => $invoice->assigned_to],
                'created_by' => $invoice->created_by
            ]);
        }
    }

    public function updated(Invoice $invoice): void
    {
        $changes = $invoice->getChanges();
        $original = $invoice->getOriginal();

        if (empty($changes)) return;

        $userName = auth()->user()->name ?? 'System';

        foreach ($changes as $field => $newValue) {
            if (in_array($field, ['updated_at'])) continue;

            $oldValue = $original[$field] ?? null;

            $title = $this->getUpdateTitle($field, $oldValue, $newValue, $userName, $invoice);
            $description = $this->getUpdateDescription($field, $oldValue, $newValue, $invoice);

            InvoiceActivity::create([
                'invoice_id' => $invoice->id,
                'user_id' => auth()->id() ?? $invoice->created_by,
                'activity_type' => 'updated',
                'title' => $title,
                'description' => $description,
                'field_changed' => $field,
                'old_values' => [$field => $oldValue],
                'new_values' => [$field => $newValue],
                'created_by' => $invoice->created_by
            ]);
        }
    }

    private function getUpdateTitle($field, $oldValue, $newValue, $userName, $invoice)
    {
        switch ($field) {
            case 'status':
                return $userName . ' updated status';
            case 'invoice_number':
                return $userName . ' updated invoice number';
            case 'assigned_to':
                $newUser = $newValue ? \App\Models\User::find($newValue)?->name : 'Unassigned';
                if ($newUser === $userName) {
                    return $userName . ' self-assigned this invoice';
                }
                return $userName . ' assigned to ' . $newUser;
            case 'sales_order_id':
                return $userName . ' updated sales order';
            case 'billing_contact_id':
                return $userName . ' updated billing contact';
            case 'shipping_contact_id':
                return $userName . ' updated shipping contact';
            case 'due_date':
                return $userName . ' updated due date';
            case 'payment_method':
                return $userName . ' updated payment method';
            default:
                $fieldName = str_replace(['_id', '_'], [' ', ' '], $field);
                return $userName . ' updated ' . $fieldName;
        }
    }

    private function getUpdateDescription($field, $oldValue, $newValue, $invoice)
    {
        switch ($field) {
            case 'status':
                $statusColors = [
                    'draft' => 'bg-gray-50 text-gray-700 ring-gray-600/20',
                    'sent' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                    'pending' => 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
                    'paid' => 'bg-green-50 text-green-700 ring-green-600/20',
                    'partially_paid' => 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
                    'overdue' => 'bg-red-50 text-red-700 ring-red-600/20',
                    'cancelled' => 'bg-gray-50 text-gray-700 ring-gray-600/20'
                ];
                $oldColor = $statusColors[$oldValue] ?? $statusColors['draft'];
                $newColor = $statusColors[$newValue] ?? $statusColors['draft'];
                $oldStatus = $oldValue ? '<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ' . $oldColor . '">' . ucwords(str_replace('_', ' ', $oldValue)) . '</span>' : 'None';
                $newStatus = $newValue ? '<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ' . $newColor . '">' . ucwords(str_replace('_', ' ', $newValue)) . '</span>' : 'None';
                return $oldStatus . ' into ' . $newStatus;
            case 'invoice_number':
                return '<span class="font-bold text-base">' . ($oldValue ?? '') . '</span> into <span class="font-bold text-base">' . ($newValue ?? '') . '</span>';
            case 'assigned_to':
                $oldUser = $oldValue ? \App\Models\User::find($oldValue)?->name : 'Unassigned';
                $newUser = $newValue ? \App\Models\User::find($newValue)?->name : 'Unassigned';
                return '<span class="font-bold text-base">' . $oldUser . '</span> into <span class="font-bold text-base">' . $newUser . '</span>';
            case 'sales_order_id':
                $oldSalesOrder = $oldValue ? \App\Models\SalesOrder::find($oldValue)?->sales_order_number : 'None';
                $newSalesOrder = $newValue ? \App\Models\SalesOrder::find($newValue)?->sales_order_number : 'None';
                return '<span class="font-bold text-base">' . ($oldSalesOrder ?? 'None') . '</span> into <span class="font-bold text-base">' . ($newSalesOrder ?? 'None') . '</span>';
            case 'billing_contact_id':
            case 'shipping_contact_id':
                $oldContact = $oldValue ? \App\Models\Contact::find($oldValue)?->name : 'None';
                $newContact = $newValue ? \App\Models\Contact::find($newValue)?->name : 'None';
                return '<span class="font-bold text-base">' . ($oldContact ?? 'None') . '</span> into <span class="font-bold text-base">' . ($newContact ?? 'None') . '</span>';
            case 'due_date':
                $oldDate = $oldValue ? date('Y-m-d', strtotime($oldValue)) : 'None';
                $newDate = $newValue ? date('Y-m-d', strtotime($newValue)) : 'None';
                return '<span class="font-bold text-base">' . $oldDate . '</span> into <span class="font-bold text-base">' . $newDate . '</span>';
            case 'payment_method':
                return '<span class="font-bold text-base">' . ($oldValue ?? 'None') . '</span> into <span class="font-bold text-base">' . ($newValue ?? 'None') . '</span>';
            default:
                // Handle date fields
                if (str_contains($field, '_date') || str_contains($field, '_at') || in_array($field, ['due_date'])) {
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
