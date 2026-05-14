<?php

namespace App\Listeners;

use App\Events\LeadStatusChanged;
use App\Models\User;
use App\Services\EmailTemplateService;
use App\Services\WebhookService;
use Exception;

class SendLeadStatusChangedEmail
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private EmailTemplateService $emailService,
        private WebhookService $webhookService
    ) {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(LeadStatusChanged $event): void
    {
        $lead = $event->lead;
        $old_status = $event->oldStatus;
        $new_status = $event->newStatus;
        $assignedUser = $event->lead->assignedUser;

        if (isEmailTemplateEnabled('Lead Moved', createdBy()) && $assignedUser) {
            // Prepare email variables
            $variables = [
                '{lead_name}' => $lead->name ?? '-',
                '{assigned_user_name}' => $assignedUser->name ?? '-',
                '{old_lead_stage}' => $old_status ?? '-',
                '{new_lead_stage}' => $new_status ?? '-',
                '{lead_email}' => $lead->email ?? '-',
                '{lead_phone}' => $lead->phone ?? '-',
                '{lead_company}' => $lead->company ?? '-',
                '{company_name}' => getCompanyName(),
            ];

            try {
                // Send lead assignment email to the assigned user
                $createdByUser = User::find(createdBy());
                $userLanguage = $createdByUser->lang ?? 'en';
                $this->emailService->sendTemplateEmailWithLanguage(
                    templateName: 'Lead Moved',
                    variables: $variables,
                    toEmail: $assignedUser->email,
                    toName: $assignedUser->name,
                    language: $userLanguage
                );

                // Trigger webhooks for Lead Assignment
                $this->webhookService->triggerWebhooks('Lead Moved', $lead->toArray(), $lead->created_by ?? $lead->id);
            } catch (Exception $e) {
                // Store error in session for frontend notification
                session()->flash('email_error', 'Failed to send lead assignment email: ' . $e->getMessage());
            }
        }
    }
}
