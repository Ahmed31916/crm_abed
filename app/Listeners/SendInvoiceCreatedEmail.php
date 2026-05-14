<?php

namespace App\Listeners;

use App\Events\InvoiceCreated;
use App\Models\User;
use App\Services\EmailTemplateService;
use Exception;

class SendInvoiceCreatedEmail
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
    public function handle(InvoiceCreated $event): void
    {
        $invoice = $event->invoice;

        $contact = $invoice->contact;
        $assignedUser = $invoice->assignedUser;
        $account = $invoice->account;

        if (isEmailTemplateEnabled('Invoice Created', createdBy())) {
            // Prepare email variables
            $variables = [
                '{invoice_number}' => $invoice->invoice_number ?? '-',
                '{invoice_name}' => $invoice->name ?? '-',
                '{contact_name}' => $contact->name ?? '-',
                '{account_name}' => $account->name ?? '-',
                '{invoice_total}' => $invoice->total_amount ? '$' . number_format($invoice->total_amount, 2) : '$0.00',
                '{invoice_date}' => $invoice->invoice_date ? date('Y-m-d', strtotime($invoice->invoice_date)) : '-',
                '{due_date}' => $invoice->due_date ? date('Y-m-d', strtotime($invoice->due_date)) : '-',
                '{invoice_status}' => ucfirst($invoice->status ?? 'draft'),
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
                        templateName: 'Invoice Created',
                        variables: $variables,
                        toEmail: $contact->email,
                        toName: $contact->name,
                        language: $userLanguage
                    );
                }

                // Send email to assigned user if exists and different from contact
                if ($assignedUser && $assignedUser->email && (!$contact || $assignedUser->email !== $contact->email)) {
                    $this->emailService->sendTemplateEmailWithLanguage(
                        templateName: 'Invoice Created',
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
                    session()->flash('email_error', 'Email template not enabled in company settings');
                }
            }
        }
    }
}
