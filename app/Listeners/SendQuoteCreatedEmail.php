<?php

namespace App\Listeners;

use App\Events\QuoteCreated;
use App\Models\User;
use App\Services\EmailTemplateService;
use Exception;

class SendQuoteCreatedEmail
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
    public function handle(QuoteCreated $event): void
    {
        $quote = $event->quote;
        $billingContact = $quote->billingContact;
        $assignedUser = $quote->assignedUser;
        $account = $quote->account;

        if (isEmailTemplateEnabled('Quote Created', createdBy())) {
            // Prepare email variables
            $variables = [
                '{quote_number}' => $quote->quote_number ?? '-',
                '{quote_name}' => $quote->name ?? '-',
                '{billing_contact_name}' => $billingContact->name ?? '-',
                '{account_name}' => $account->name ?? '-',
                '{quote_total}' => $quote->total_amount ? '$' . number_format($quote->total_amount, 2) : '$0.00',
                '{quote_valid_until}' => $quote->valid_until ? date('Y-m-d', strtotime($quote->valid_until)) : '-',
                '{quote_status}' => ucfirst($quote->status ?? 'draft'),
                '{assigned_user_name}' => $assignedUser->name ?? '-',
                '{assigned_user_email}' => $assignedUser->email ?? '-',
                '{company_name}' => getCompanyName(),
            ];

            try {
                // Clear any existing email error
                session()->forget('email_error');
                $createdByUser = User::find(createdBy());
                $userLanguage = $createdByUser->lang ?? 'en';

                // Send email to billing contact if exists
                if ($billingContact && $billingContact->email && $quote->billing_contact_id) {
                    $this->emailService->sendTemplateEmailWithLanguage(
                        templateName: 'Quote Created',
                        variables: $variables,
                        toEmail: $billingContact->email,
                        toName: $billingContact->name,
                        language: $userLanguage
                    );
                }

                // Send email to assigned user if exists
                if ($assignedUser && $assignedUser->email &&  (!$billingContact || $assignedUser->email !== $billingContact->email)) {
                    $this->emailService->sendTemplateEmailWithLanguage(
                        templateName: 'Quote Created',
                        variables: $variables,
                        toEmail: $assignedUser->email,
                        toName: $assignedUser->name,
                        language: $userLanguage
                    );
                }
            } catch (Exception $e) {
                // Only store error if it's not a rate limiting issue (email was likely sent successfully)
                $errorMessage = $e->getMessage();
                if (
                    !str_contains($errorMessage, 'Too many emails per second') &&
                    !str_contains($errorMessage, '550 5.7.0') &&
                    !str_contains($errorMessage, 'rate limit')
                ) {
                    session()->flash('email_error', 'Failed to send quote create email: ' . $errorMessage);
                }
            }
        }
    }
}
