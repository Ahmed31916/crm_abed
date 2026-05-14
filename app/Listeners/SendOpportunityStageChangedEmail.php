<?php

namespace App\Listeners;

use App\Events\OpportunityStageChanged;
use App\Models\User;
use App\Services\EmailTemplateService;
use App\Services\WebhookService;
use Exception;

class SendOpportunityStageChangedEmail
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
    public function handle(OpportunityStageChanged $event): void
    {
        $opportunity = $event->opportunity;
        $assignedUser = $opportunity->assignedUser;
        $account = $opportunity->account;
        $contact = $opportunity->contact;

        $oldStage = $event->oldStage;
        $newStage = $event->newStage;

        if (isEmailTemplateEnabled('Opportunity Status Changed', createdBy())) {
            // Prepare email variables
            $variables = [
                '{opportunity_name}' => $opportunity->name ?? '-',
                '{account_name}' => $account->name ?? '-',
                '{contact_name}' => $contact->name ?? '-',
                '{old_opportunity_stage}' => $oldStage ?? '-',
                '{new_opportunity_stage}' => $newStage ?? '-',
                '{opportunity_amount}' => $opportunity->amount ? '$' . number_format($opportunity->amount, 2) : '$0.00',
                '{opportunity_close_date}' => $opportunity->close_date ? date('Y-m-d', strtotime($opportunity->close_date)) : '-',
                '{opportunity_description}' => $opportunity->description ?? '-',
                '{assigned_user_name}' => $assignedUser->name ?? '-',
                '{assigned_user_email}' => $assignedUser->email ?? '-',
                '{company_name}' => getCompanyName(),
            ];

            try {
                $createdByUser = User::find(createdBy());
                $userLanguage = $createdByUser->lang ?? 'en';

                // Send email to contact if exists
                if ($contact && $contact->email) {
                    $this->emailService->sendTemplateEmailWithLanguage(
                        templateName: 'Opportunity Status Changed',
                        variables: $variables,
                        toEmail: $contact->email,
                        toName: $contact->name,
                        language: $userLanguage
                    );
                }

                // Send email to assigned user if exists and different from contact
                if ($assignedUser && $assignedUser->email && (!$contact || $assignedUser->email !== $contact->email)) {
                    $this->emailService->sendTemplateEmailWithLanguage(
                        templateName: 'Opportunity Status Changed',
                        variables: $variables,
                        toEmail: $assignedUser->email,
                        toName: $assignedUser->name,
                        language: $userLanguage
                    );
                }

                // Trigger webhooks for Opportunity Status Changed
                $this->webhookService->triggerWebhooks('Opportunity Status Changed', $opportunity->toArray(), $opportunity->created_by ?? $opportunity->id);
            } catch (Exception $e) {
                // Store error in session for frontend notification
                session()->flash('email_error', 'Failed to send Opportunity Status Changed email: ' . $e->getMessage());
            }
        }
    }
}
