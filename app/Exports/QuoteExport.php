<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Models\Quote;

class QuoteExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        $query = Quote::with(['opportunity', 'account', 'billingContact', 'shippingContact', 'shippingProviderType', 'assignedUser', 'creator'])
            ->where('created_by', createdBy());
        
        if (!auth()->user()->hasRole('company')) {
            $query->where('assigned_to', auth()->id());
        }

        return $query->get();
    }

    public function map($quote): array
    {
        return [
            $quote->quote_number,
            $quote->name,
            $quote->description,
            $quote->opportunity?->name,
            $quote->account?->name,
            $quote->billingContact?->name,
            $quote->shippingContact?->name,
            $quote->shippingProviderType?->name,
            $quote->billing_address,
            $quote->billing_city,
            $quote->billing_state,
            $quote->billing_postal_code,
            $quote->billing_country,
            $quote->shipping_address,
            $quote->shipping_city,
            $quote->shipping_state,
            $quote->shipping_postal_code,
            $quote->shipping_country,
            $quote->subtotal,
            $quote->discount_amount,
            $quote->total_amount,
            $quote->status,
            $quote->valid_until,
            $quote->assignedUser?->name,
        ];
    }

    public function headings(): array
    {
        return [
            "Quote Number",
            "Name",
            "Description",
            "Opportunity",
            "Account",
            "Billing Contact",
            "Shipping Contact",
            "Shipping Provider Type",
            "Billing Address",
            "Billing City",
            "Billing State",
            "Billing Postal Code",
            "Billing Country",
            "Shipping Address",
            "Shipping City",
            "Shipping State",
            "Shipping Postal Code",
            "Shipping Country",
            "Subtotal",
            "Discount Amount",
            "Total Amount",
            "Status",
            "Valid Until",
            "Assigned User"
        ];
    }
}
