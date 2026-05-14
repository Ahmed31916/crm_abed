<?php

namespace App\Exports;

use App\Models\ReturnOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ReturnOrderExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        $query = ReturnOrder::with(['salesOrder', 'account', 'contact', 'shippingProviderType', 'assignedUser'])
            ->where('created_by', createdBy());

        if (!auth()->user()->hasRole('company')) {
            $query->where('assigned_to', auth()->id());
        }

        return $query->get();
    }

    public function map($returnOrder): array
    {
        return [
            $returnOrder->return_number,
            $returnOrder->name,
            $returnOrder->description,
            $returnOrder->salesOrder?->order_number,
            $returnOrder->account?->name,
            $returnOrder->contact?->name,
            $returnOrder->shippingProviderType?->name,
            $returnOrder->tracking_number,
            $returnOrder->status,
            $returnOrder->reason,
            $returnOrder->reason_description,
            $returnOrder->return_date?->format('Y-m-d'),
            $returnOrder->subtotal,
            $returnOrder->tax_amount,
            $returnOrder->total_amount,
            $returnOrder->notes,
            $returnOrder->assignedUser?->name,
        ];
    }

    public function headings(): array
    {
        return [
            'Return Number',
            'Name',
            'Description',
            'Sales Order',
            'Account',
            'Contact',
            'Shipping Provider',
            'Tracking Number',
            'Status',
            'Reason',
            'Reason Description',
            'Return Date',
            'Subtotal',
            'Tax Amount',
            'Total Amount',
            'Notes',
            'Assigned User',
        ];
    }
}
