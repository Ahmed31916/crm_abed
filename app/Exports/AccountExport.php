<?php

namespace App\Exports;

use App\Models\Account;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AccountExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        $query = Account::with(['assignedUser', 'accountType', 'accountIndustry'])
            ->where('created_by', createdBy());

        if (!auth()->user()->hasRole('company')) {
            $query->where('assigned_to', auth()->id());
        }

        return $query->get();
    }

    public function map($account): array
    {
        return [
            $account->name,
            $account->email,
            $account->phone,
            $account->website,
            $account->accountType?->name,
            $account->accountIndustry?->name,
            $account->billing_address,
            $account->billing_city,
            $account->billing_state,
            $account->billing_postal_code,
            $account->billing_country,
            $account->shipping_address,
            $account->shipping_city,
            $account->shipping_state,
            $account->shipping_postal_code,
            $account->shipping_country,
            $account->status,
            $account->assignedUser?->name,
        ];
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Phone',
            'Website',
            'Account Type',
            'Account Industry',
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
            'Status',
            'Assigned User',
        ];
    }
}
