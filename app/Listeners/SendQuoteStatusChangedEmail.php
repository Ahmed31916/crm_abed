<?php

namespace App\Listeners;

use App\Events\QuoteStatusChanged;
use App\Models\User;
use App\Services\EmailTemplateService;
use App\Services\WebhookService;
use Exception;

class SendQuoteStatusChangedEmail
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
    public function handle(QuoteStatusChanged $event): void
    {
        $quote = $event->quote;
        $oldStatus = $event->oldStatus;
        $newStatus = $event->newStatus;
        $billingContact = $quote->billingContact;
        $assignedUser = $quote->assignedUser;
        $account = $quote->account;

        if (isEmailTemplateEnabled('Quote Status Changed', createdBy())) {
            // Prepare email variables
            $variables = [
                '{quote_number}' => $quote->quote_number ?? '-',
                '{quote_name}' => $quote->name ?? '-',
                '{billing_contact_name}' => $billingContact->name ?? '-',
                '{account_name}' => $account->name ?? '-',
                '{quote_total}' => $quote->total_amount ? '$' . number_format($quote->total_amount, 2) : '$0.00',
                '{quote_valid_until}' => $quote->valid_until ? date('Y-m-d', strtotime($quote->valid_until)) : '-',
                '{old_quote_status}' => ucfirst($oldStatus),
                '{new_quote_status}' => ucfirst($newStatus),
                '{assigned_user_name}' => $assignedUser->name ?? '-',
                '{assigned_user_email}' => $assignedUser->email ?? '-',
                '{company_name}' => getCompanyName(),
            ];

            try {
                $createdByUser = User::find(createdBy());
                $userLanguage = $createdByUser->lang ?? 'en';

                // Send email to billing contact if exists
                if ($billingContact && $billingContact->email) {
                    $this->emailService->sendTemplateEmailWithLanguage(
                        templateName: 'Quote Status Changed',
                        variables: $variables,
                        toEmail: $billingContact->email,
                        toName: $billingContact->name,
                        language: $userLanguage
                    );
                }

                // Send email to assigned user if exists and different from billing contact
                if ($assignedUser && $assignedUser->email && (!$billingContact || $assignedUser->email !== $billingContact->email)) {
                    $this->emailService->sendTemplateEmailWithLanguage(
                        templateName: 'Quote Status Changed',
                        variables: $variables,
                        toEmail: $assignedUser->email,
                        toName: $assignedUser->name,
                        language: $userLanguage
                    );
                }

                // Trigger webhooks for Quote Status Changed
                if ($assignedUser && $assignedUser->id) {
                    $this->webhookService->triggerWebhooks('Quote Status Changed', $quote->toArray(), $quote->created_by ?? $quote->id);
                }
            } catch (Exception $e) {
                // Store error in session for frontend notification
                session()->flash('email_error', 'Failed to send quote status changed email: ' . $e->getMessage());
            }
        }
    }
}
