<?php

namespace App\Exports;

use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InvoiceExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        $query = Invoice::with(['salesOrder', 'quote', 'opportunity', 'account', 'contact', 'assignedUser'])
            ->where('created_by', createdBy());
        
        if (!auth()->user()->hasRole('company')) {
            $query->where('assigned_to', auth()->id());
        }

        return $query->get();
    }

    public function map($invoice): array
    {
        return [
            $invoice->invoice_number,
            $invoice->name,
            $invoice->description,
            $invoice->salesOrder?->order_number,
            $invoice->quote?->name,
            $invoice->opportunity?->name,
            $invoice->account?->name,
            $invoice->contact?->name,
            $invoice->billing_address,
            $invoice->billing_city,
            $invoice->billing_state,
            $invoice->billing_postal_code,
            $invoice->billing_country,
            $invoice->subtotal,
            $invoice->tax_amount,
            $invoice->discount_amount,
            $invoice->total_amount,
            $invoice->status,
            $invoice->payment_method,
            $invoice->notes,
            $invoice->terms,
            $invoice->assignedUser?->name,
            $invoice->invoice_date?->format('Y-m-d'),
            $invoice->due_date?->format('Y-m-d'),
        ];
    }

    public function headings(): array
    {
        return [
            'Invoice Number',
            'Name',
            'Description',
            'Sales Order',
            'Quote',
            'Opportunity',
            'Account',
            'Contact',
            'Billing Address',
            'Billing City',
            'Billing State',
            'Billing Postal Code',
            'Billing Country',
            'Subtotal',
            'Tax Amount',
            'Discount Amount',
            'Total Amount',
            'Status',
            'Payment Method',
            'Notes',
            'Terms',
            'Assigned User',
            'Invoice Date',
            'Due Date',
        ];
    }
}
