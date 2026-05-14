<?php

namespace App\Listeners;

use App\Events\OpportunityCreated;
use App\Models\User;
use App\Services\EmailTemplateService;
use Exception;

class SendOpportunityCreatedEmail
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private EmailTemplateService $emailService,
    ) {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OpportunityCreated $event): void
    {
        if (isEmailTemplateEnabled('Opportunity Created', createdBy())) {
            $opportunity = $event->opportunity;
            $assignedUser = $opportunity->assignedUser;
            $account = $opportunity->account;
            $contact = $opportunity->contact;
            $stage = $opportunity->opportunityStage;

            $variables = [
                '{opportunity_name}' => $opportunity->name ?? '-',
                '{account_name}' => $account->name ?? '-',
                '{contact_name}' => $contact->name ?? '-',
                '{opportunity_stage}' => $stage->name ?? '-',
                '{opportunity_amount}' => $opportunity->amount ? '$' . number_format($opportunity->amount, 2) : '$0.00',
                '{opportunity_close_date}' => $opportunity->close_date ? date('Y-m-d', strtotime($opportunity->close_date)) : '-',
                '{opportunity_description}' => $opportunity->description ?? '-',
                '{assigned_user_name}' => $assignedUser->name ?? '-',
                '{assigned_user_email}' => $assignedUser->email ?? '-',
                '{company_name}' => getCompanyName(),
            ];

            try {
                session()->forget('email_error');
                $createdByUser = User::find(createdBy());
                $userLanguage = $createdByUser->lang ?? 'en';

                // Send email to contact if exists
                if ($contact && $contact->email) {
                    $this->emailService->sendTemplateEmailWithLanguage(
                        templateName: 'Opportunity Created',
                        variables: $variables,
                        toEmail: $contact->email,
                        toName: $contact->name,
                        language: $userLanguage
                    );
                }

                // Send email to assigned user if exists and different from contact
                if ($assignedUser && $assignedUser->email && (!$contact || $assignedUser->email !== $contact->email)) {
                    $this->emailService->sendTemplateEmailWithLanguage(
                        templateName: 'Opportunity Created',
                        variables: $variables,
                        toEmail: $assignedUser->email,
                        toName: $assignedUser->name,
                        language: $userLanguage
                    );
                }

            } catch (Exception $e) {
                $errorMessage = $e->getMessage();
                if (
                    !str_contains($errorMessage, 'Too many emails per second') &&
                    !str_contains($errorMessage, '550 5.7.0') &&
                    !str_contains($errorMessage, 'rate limit')
                ) {
                    session()->flash('email_error', 'Failed to send opportunity create email: ' . $errorMessage);
                }
            }
        }
    }
}
