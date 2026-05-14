<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PurchaseOrderExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        $query = PurchaseOrder::with(['salesOrder', 'account', 'contact', 'billingContact', 'shippingContact', 'shippingProviderType', 'assignedUser'])
            ->where('created_by', createdBy());

        if (!auth()->user()->hasRole('company')) {
            $query->where('assigned_to', auth()->id());
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Order Number',
            'Name',
            'Description',
            'Sales Order',
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
            'Expected Delivery Date',
            'Status',
            'Subtotal',
            'Tax Amount',
            'Shipping Amount',
            'Discount Amount',
            'Total Amount',
            'Assigned To',
        ];
    }

    public function map($purchaseOrder): array
    {
        return [
            $purchaseOrder->order_number,
            $purchaseOrder->name,
            $purchaseOrder->description,
            $purchaseOrder->salesOrder?->order_number,
            $purchaseOrder->account?->name,
            $purchaseOrder->contact?->name,
            $purchaseOrder->billingContact?->name,
            $purchaseOrder->shippingContact?->name,
            $purchaseOrder->shippingProviderType?->name,
            $purchaseOrder->billing_address,
            $purchaseOrder->billing_city,
            $purchaseOrder->billing_state,
            $purchaseOrder->billing_postal_code,
            $purchaseOrder->billing_country,
            $purchaseOrder->shipping_address,
            $purchaseOrder->shipping_city,
            $purchaseOrder->shipping_state,
            $purchaseOrder->shipping_postal_code,
            $purchaseOrder->shipping_country,
            $purchaseOrder->order_date?->format('Y-m-d'),
            $purchaseOrder->expected_delivery_date?->format('Y-m-d'),
            $purchaseOrder->status,
            $purchaseOrder->subtotal,
            $purchaseOrder->tax_amount,
            $purchaseOrder->shipping_amount,
            $purchaseOrder->discount_amount,
            $purchaseOrder->total_amount,
            $purchaseOrder->assignedUser?->name,
        ];
    }
}
