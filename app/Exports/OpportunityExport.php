<?php

namespace App\Exports;

use App\Models\Opportunity;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OpportunityExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        $query = Opportunity::with(['account', 'opportunityStage', 'opportunitySource', 'assignedUser'])
            ->where('created_by', createdBy());

        if (!auth()->user()->hasRole('company')) {
            $query->where('assigned_to', auth()->id());
        }

        return $query->get();
    }

    public function map($opportunity): array
    {
        return [
            $opportunity->name,
            $opportunity->account?->name,
            $opportunity->amount,
            $opportunity->close_date?->format('Y-m-d'),
            $opportunity->opportunityStage?->name,
            $opportunity->opportunitySource?->name,
            $opportunity->description,
            $opportunity->status,
            $opportunity->assignedUser?->name,
        ];
    }

    public function headings(): array
    {
        return [
            'Name',
            'Account',
            'Amount',
            'Close Date',
            'Stage',
            'Source',
            'Description',
            'Status',
            'Assigned User',
        ];
    }
}
