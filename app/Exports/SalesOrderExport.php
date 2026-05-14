<?php

namespace App\Exports;

use App\Models\SalesOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalesOrderExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        $query = SalesOrder::with(['quote', 'account', 'contact', 'billingContact', 'shippingContact', 'shippingProviderType', 'assignedUser', 'creator'])
            ->where('created_by', createdBy());

        if (!auth()->user()->hasRole('company')) {
            $query->where('assigned_to', auth()->id());
        }

        return $query->get();
    }

    public function map($salesOrder): array
    {
        return [
            $salesOrder->order_number,
            $salesOrder->name,
            $salesOrder->description,
            $salesOrder->quote?->name,
            $salesOrder->account?->name,
            $salesOrder->contact?->name,
            $salesOrder->billingContact?->name,
            $salesOrder->shippingContact?->name,
            $salesOrder->shippingProviderType?->name,
            $salesOrder->billing_address,
            $salesOrder->billing_city,
            $salesOrder->billing_state,
            $salesOrder->billing_postal_code,
            $salesOrder->billing_country,
            $salesOrder->shipping_address,
            $salesOrder->shipping_city,
            $salesOrder->shipping_state,
            $salesOrder->shipping_postal_code,
            $salesOrder->shipping_country,
            $salesOrder->order_date?->format('Y-m-d'),
            $salesOrder->delivery_date?->format('Y-m-d'),
            $salesOrder->status,
            $salesOrder->subtotal,
            $salesOrder->tax_amount,
            $salesOrder->shipping_amount,
            $salesOrder->discount_amount,
            $salesOrder->total_amount,
            $salesOrder->assignedUser?->name,
        ];
    }

    public function headings(): array
    {
        return [
            'Order Number',
            'Name',
            'Description',
            'Quote',
            'Account',
            'Contact',
            'Billing Contact',
            'Shipping Contact',
            'Shipping Provider Type',
            'Billing Address',
            'Billing City',
            'Billing State',
            'Billing Postal Code',
            'Billing Country',
            'Shipping Address',
            'Shipping City',
            'Shipping State',
            'Shipping Postal Code',
            'Shipping Country',
            'Order Date',
            'Delivery Date',
            'Status',
            'Subtotal',
            'Tax Amount',
            'Shipping Amount',
            'Discount Amount',
            'Total Amount',
            'Assigned User',
        ];
    }
}
