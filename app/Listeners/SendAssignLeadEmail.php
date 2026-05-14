<?php

namespace App\Listeners;

use App\Events\LeadAssigned;
use App\Models\User;
use App\Services\EmailTemplateService;
use Exception;

class SendAssignLeadEmail
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
    public function handle(LeadAssigned $event): void
    {
        if (isEmailTemplateEnabled('Lead Assigned', createdBy()) && $event->lead->assigned_to) {
            $lead = $event->lead;
            $assignedUser = $event->lead->assignedUser;

            // Prepare email variables
            $variables = [
                '{lead_name}' => $lead->name ?? '-',
                '{lead_email}' => $lead->email ?? '-',
                '{assigned_user_name}' => $assignedUser->name ?? '-',
                '{lead_phone}' => $lead->phone ?? '-',
                '{lead_company}' => $lead->company ?? '-',
                '{company_name}' => getCompanyName(),
            ];

            try {
                // Clear any existing email error
                session()->forget('email_error');

                // Send lead assignment email to the assigned user
                $createdByUser = User::find(createdBy());
                $userLanguage = $createdByUser->lang ?? 'en';
                $this->emailService->sendTemplateEmailWithLanguage(
                    templateName: 'Lead Assigned',
                    variables: $variables,
                    toEmail: $assignedUser->email,
                    toName: $assignedUser->name,
                    language: $userLanguage
                );

            } catch (Exception $e) {
                // Only store error if it's not a rate limiting issue (email was likely sent successfully)
                $errorMessage = $e->getMessage();
                if (!str_contains($errorMessage, 'Too many emails per second') &&
                    !str_contains($errorMessage, '550 5.7.0') &&
                    !str_contains($errorMessage, 'rate limit')) {
                    session()->flash('email_error', 'Failed to send lead assignment email: ' . $errorMessage);
                }
            }
        }
    }
}
