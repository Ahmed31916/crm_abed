<?php

namespace App\Listeners;

use App\Events\ReturnOrderCreated;
use App\Models\User;
use App\Services\EmailTemplateService;
use Exception;

class SendReturnOrderCreatedEmail
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
    public function handle(ReturnOrderCreated $event): void
    {
        $returnOrder = $event->returnOrder;

        $contact = $returnOrder->contact;
        $assignedUser = $returnOrder->assignedUser;
        $account = $returnOrder->account;

        if (isEmailTemplateEnabled('Return Order Created', createdBy())) {
            // Prepare email variables
            $variables = [
                '{return_number}' => $returnOrder->return_number ?? '-',
                '{return_name}' => $returnOrder->name ?? '-',
                '{contact_name}' => $contact->name ?? '-',
                '{account_name}' => $account->name ?? '-',
                '{return_total}' => $returnOrder->total_amount ? '$' . number_format($returnOrder->total_amount, 2) : '$0.00',
                '{return_date}' => $returnOrder->return_date ? date('Y-m-d', strtotime($returnOrder->return_date)) : '-',
                '{return_status}' => ucfirst($returnOrder->status ?? 'pending'),
                '{return_reason}' => ucfirst(str_replace('_', ' ', $returnOrder->reason ?? 'other')),
                '{tracking_number}' => $returnOrder->tracking_number ?? '-',
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
                        templateName: 'Return Order Created',
                        variables: $variables,
                        toEmail: $contact->email,
                        toName: $contact->name,
                        language: $userLanguage
                    );
                }

                // Send email to assigned user if exists and different from contact
                if ($assignedUser && $assignedUser->email && (!$contact || $assignedUser->email !== $contact->email)) {
                    $this->emailService->sendTemplateEmailWithLanguage(
                        templateName: 'Return Order Created',
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
                    session()->flash('email_error', 'Failed to send return order create email: ' . $errorMessage);
                }
            }
        }
    }
}
