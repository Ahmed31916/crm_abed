<?php

namespace App\Exports;

use App\Models\Lead;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LeadExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        $query = Lead::with(['leadStatus', 'leadSource', 'assignedUser', 'campaign', 'accountIndustry'])
            ->where('created_by', createdBy());

        if (!auth()->user()->hasRole('company')) {
            $query->where('assigned_to', auth()->id());
        }

        return $query->get();
    }

    public function map($lead): array
    {
        return [
            $lead->name,
            $lead->email,
            $lead->phone,
            $lead->company,
            $lead->account_name,
            $lead->accountIndustry?->name,
            $lead->website,
            $lead->position,
            $lead->address,
            $lead->notes,
            $lead->value,
            $lead->status,
            $lead->is_converted ? 'Yes' : 'No',
            $lead->leadStatus?->name,
            $lead->leadSource?->name,
            $lead->assignedUser?->name,
            $lead->campaign?->name,
        ];
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Phone',
            'Company',
            'Account Name',
            'Account Industry',
            'Website',
            'Position',
            'Address',
            'Notes',
            'Lead Value',
            'Status',
            'Is Converted',
            'Lead Status',
            'Lead Source',
            'Assigned User',
            'Campaign',
        ];
    }
}
