<?php

namespace App\Exports;

use App\Models\DeliveryOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DeliveryOrderExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        $query = DeliveryOrder::with(['salesOrder', 'account', 'contact', 'shippingProviderType', 'assignedUser'])
            ->where('created_by', createdBy());

        if (!auth()->user()->hasRole('company')) {
            $query->where('assigned_to', auth()->id());
        }

        return $query->get();
    }

    public function map($deliveryOrder): array
    {
        return [
            $deliveryOrder->delivery_number,
            $deliveryOrder->name,
            $deliveryOrder->description,
            $deliveryOrder->salesOrder?->order_number,
            $deliveryOrder->account?->name,
            $deliveryOrder->contact?->name,
            $deliveryOrder->shippingProviderType?->name,
            $deliveryOrder->delivery_address,
            $deliveryOrder->delivery_city,
            $deliveryOrder->delivery_state,
            $deliveryOrder->delivery_postal_code,
            $deliveryOrder->delivery_country,
            $deliveryOrder->delivery_date?->format('Y-m-d'),
            $deliveryOrder->expected_delivery_date?->format('Y-m-d'),
            $deliveryOrder->status,
            $deliveryOrder->tracking_number,
            $deliveryOrder->delivery_notes,
            $deliveryOrder->total_weight,
            $deliveryOrder->shipping_cost,
            $deliveryOrder->assignedUser?->name,
        ];
    }

    public function headings(): array
    {
        return [
            'Delivery Number',
            'Name',
            'Description',
            'Sales Order',
            'Account',
            'Contact',
            'Shipping Provider',
            'Delivery Address',
            'Delivery City',
            'Delivery State',
            'Delivery Postal Code',
            'Delivery Country',
            'Delivery Date',
            'Expected Delivery Date',
            'Status',
            'Tracking Number',
            'Delivery Notes',
            'Total Weight',
            'Shipping Cost',
            'Assigned User',
        ];
    }
}
