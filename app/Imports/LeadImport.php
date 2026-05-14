<?php

namespace App\Imports;

use App\Models\Lead;
use App\Models\User;
use App\Events\LeadAssigned;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;

class LeadImport implements ToModel, WithHeadingRow, WithEvents
{
    private $addedCount = 0;
    private $skippedCount = 0;
    private $leads = [];

    public function model(array $row)
    {
        // Skip if both name and email are empty
        if (empty($row['name']) && empty($row['email'])) {
            return null;
        }

        // Check for duplicate email
        if (!empty($row['email'])) {
            $leadByEmail = Lead::where('email', $row['email'])->where('created_by', createdBy())->first();
            if ($leadByEmail) {
                $this->skippedCount++;
                return null;
            }
        }

        $leadData = [
            'name' => $row['name'] ?? '',
            'email' => $row['email'] ?? '',
            'phone' => $row['phone'] ?? '',
            'company' => $row['company'] ?? '',
            'account_name' => $row['account_name'] ?? '',
            'website' => $row['website'] ?? '',
            'position' => $row['position'] ?? '',
            'address' => $row['address'] ?? '',
            'notes' => $row['notes'] ?? '',
            'value' => is_numeric($row['value'] ?? null) ? (float)($row['value']) : null,
            'status' => in_array($row['status'] ?? 'active', ['active', 'inactive']) ? ($row['status'] ?? 'active') : 'active',
            'is_converted' => false,
            'created_by' => createdBy(),
        ];

        // Lead Status
        $leadStatusValue = trim($row['lead_status'] ?? '');
        $leadStatus = !empty($leadStatusValue)
            ? \App\Models\LeadStatus::where('name', $leadStatusValue)->where('created_by', createdBy())->first()
            : null;
        $leadData['lead_status_id'] = $leadStatus?->id ?? \App\Models\LeadStatus::where('created_by', createdBy())->value('id');

        // Lead Source
        $leadSourceValue = trim($row['lead_source'] ?? '');
        $leadSource = !empty($leadSourceValue)
            ? \App\Models\LeadSource::where('name', $leadSourceValue)->where('created_by', createdBy())->first()
            : null;
        $leadData['lead_source_id'] = $leadSource?->id ?? \App\Models\LeadSource::where('created_by', createdBy())->value('id');

        // Account Industry
        $accountIndustryValue = trim($row['account_industry'] ?? '');
        $accountIndustry = !empty($accountIndustryValue)
            ? \App\Models\AccountIndustry::where('name', $accountIndustryValue)->where('created_by', createdBy())->first()
            : null;
        $leadData['account_industry_id'] = $accountIndustry?->id ?? \App\Models\AccountIndustry::where('created_by', createdBy())->value('id');

        // Campaign
        $campaignValue = trim($row['campaign'] ?? '');
        if (!empty($campaignValue)) {
            $campaign = \App\Models\Campaign::where('name', $campaignValue)->where('created_by', createdBy())->first();
            $leadData['campaign_id'] = $campaign?->id ?? \App\Models\Campaign::where('created_by', createdBy())->value('id');
        } else {
            $leadData['campaign_id'] = null;
        }

        if (!auth()->user()->hasRole('company')) {
            $leadData['assigned_to'] = auth()->id();
        }

        $this->addedCount++;
        array_push($this->leads, $leadData['email']);
        return new Lead($leadData);
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function(AfterImport $event) {
                // Fire LeadAssigned event for all imported leads
                Lead::where('created_by', createdBy())
                ->whereIn('email', $this->leads)
                ->orderBy('id', 'desc')
                ->take($this->addedCount)
                ->get()
                ->each(function($lead) {
                        event(new LeadAssigned($lead));
                    });
            },
        ];
    }

    public function getAddedCount()
    {
        return $this->addedCount;
    }

    public function getSkippedCount()
    {
        return $this->skippedCount;
    }
}
