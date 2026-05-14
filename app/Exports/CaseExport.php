<?php

namespace App\Exports;

use App\Models\CaseModel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CaseExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        $query = CaseModel::with(['account', 'contact', 'assignedUser'])
            ->where('created_by', createdBy());

        if (!auth()->user()->hasRole('company')) {
            $query->where('assigned_to', auth()->id());
        }

        return $query->get();
    }

    public function map($case): array
    {
        return [
            $case->subject,
            $case->description,
            $case->priority,
            $case->status,
            $case->case_type,
            $case->account?->name,
            $case->contact?->name,
            $case->assignedUser?->name,
        ];
    }

    public function headings(): array
    {
        return [
            'Subject',
            'Description',
            'Priority',
            'Status',
            'Case Type',
            'Account',
            'Contact',
            'Assigned To',
        ];
    }
}
