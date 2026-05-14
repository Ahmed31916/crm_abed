<?php

namespace App\Exports;

use App\Models\ReceiptOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ReceiptOrderExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        $query = ReceiptOrder::with(['purchaseOrder', 'account', 'returnOrder', 'contact', 'assignedUser'])
            ->where('created_by', createdBy());

        if (!auth()->user()->hasRole('company')) {
            $query->where('assigned_to', auth()->id());
        }

        return $query->get();
    }

    public function map($receiptOrder): array
    {
        return [
            $receiptOrder->receipt_number,
            $receiptOrder->name,
            $receiptOrder->description,
            $receiptOrder->purchaseOrder?->order_number,
            $receiptOrder->account?->name,
            $receiptOrder->returnOrder?->return_number,
            $receiptOrder->contact?->name,
            $receiptOrder->receipt_date?->format('Y-m-d'),
            $receiptOrder->expected_date?->format('Y-m-d'),
            $receiptOrder->status,
            $receiptOrder->notes,
            $receiptOrder->subtotal,
            $receiptOrder->tax_amount,
            $receiptOrder->shipping_amount,
            $receiptOrder->discount_amount,
            $receiptOrder->total_amount,
            $receiptOrder->assignedUser?->name,
        ];
    }

    public function headings(): array
    {
        return [
            'Receipt Number',
            'Name',
            'Description',
            'Purchase Order',
            'Account',
            'Return Order',
            'Contact',
            'Receipt Date',
            'Expected Date',
            'Status',
            'Notes',
            'Subtotal',
            'Tax Amount',
            'Shipping Amount',
            'Discount Amount',
            'Total Amount',
            'Assigned User',
        ];
    }
}
