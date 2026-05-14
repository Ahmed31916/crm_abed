<?php

namespace App\Listeners;

use App\Events\CaseCreated;
use App\Models\User;
use App\Services\EmailTemplateService;
use Exception;

class SendCaseCreatedEmail
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
    public function handle(CaseCreated $event): void
    {
        $case = $event->case;
        $assignedUser = $case->assignedUser;
        $contact = $case->contact;

        if (isEmailTemplateEnabled('Case Created', createdBy())) {
            // Prepare email variables
            $variables = [
                '{contact_name}' => $contact->name ?? '-',
                '{case_subject}' => $case->subject ?? '-',
                '{case_priority}' => $case->priority ?? '-',
                '{case_status}' => $case->status ?? '-',
                '{case_created_date}' => $case->created_at ? date('Y-m-d', strtotime($case->created_at)) : '-',
                '{case_description}' => $case->description ?? '-',
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
                        templateName: 'Case Created',
                        variables: $variables,
                        toEmail: $contact->email,
                        toName: $contact->name,
                        language: $userLanguage
                    );
                }

                // Send email to assigned user if exists and different from contact
                if ($assignedUser && $assignedUser->email && (!$contact || $assignedUser->email !== $contact->email)) {
                    $this->emailService->sendTemplateEmailWithLanguage(
                        templateName: 'Case Created',
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
                    session()->flash('email_error', 'Failed to send case create email: ' . $errorMessage);
                }
            }
        }
    }
}
