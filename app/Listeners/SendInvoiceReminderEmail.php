<?php

namespace App\Listeners;

use App\Events\InvoiceReminderSent;
use App\Models\User;
use App\Services\EmailTemplateService;
use Exception;

class SendInvoiceReminderEmail
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private EmailTemplateService $emailService
    ) {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(InvoiceReminderSent $event): void
    {
        $invoice = $event->invoice;
        $recipient = $event->recipientEmail;
        $recipientName = $event->recipientName;

        if (!$recipient || !isEmailTemplateEnabled('Invoice Payment Reminder', createdBy())) {
            session()->flash('email_error', 'Invoice Payment Reminder not enabled in company settings, Enable it to send Invoice Payment Reminder');
            return;
        }

        $encryptedId = encrypt($invoice->id);
        $paymentLink = route('invoices.public', $encryptedId);

        // Prepare email variables
        $variables = [
            '{billing_contact_name}' => $recipientName ?? 'Customer',
            '{invoice_number}' => $invoice->invoice_number,
            '{invoice_date}' => $invoice->invoice_date,
            '{invoice_due_date}' => $invoice->due_date,
            '{invoice_total}' => $this->formatCurrency($invoice->total_amount),
            '{invoice_amount_due}' => $this->formatCurrency($invoice->amount_due ?? $invoice->total_amount),
            '{invoice_payment_link}' => $paymentLink,
            '{company_name}' => getCompanyName(),
        ];

        try {
            $createdByUser = User::find(createdBy());
            $userLanguage = $createdByUser->lang ?? 'en';

            $this->emailService->sendTemplateEmailWithLanguage(
                templateName: 'Invoice Payment Reminder',
                variables: $variables,
                toEmail: $recipient,
                toName: $recipientName,
                language: $userLanguage
            );
        } catch (Exception $e) {
            // Store error in session for frontend notification
            $errorMessage = $e->getMessage();
            if (
                !str_contains($errorMessage, 'Too many emails per second') &&
                !str_contains($errorMessage, '550 5.7.0') &&
                !str_contains($errorMessage, 'rate limit')
            ) {
                session()->flash('email_error', 'Email template not enabled in company settings.');
            }
        }
    }

    private function formatCurrency($amount)
    {
        $currencySymbol = getSetting('currencySymbol', '$');
        return $currencySymbol . number_format($amount, 2);
    }
}
