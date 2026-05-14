<?php

namespace App\Listeners;

use App\Events\ReceiptOrderCreated;
use App\Models\User;
use App\Services\EmailTemplateService;
use Exception;

class SendReceiptOrderCreatedEmail
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
    public function handle(ReceiptOrderCreated $event): void
    {
        $receiptOrder = $event->receiptOrder;

        $contact = $receiptOrder->contact;
        $assignedUser = $receiptOrder->assignedUser;
        $account = $receiptOrder->account;

        if (isEmailTemplateEnabled('Receipt Order Created', createdBy())) {
            // Prepare email variables
            $variables = [
                '{receipt_number}' => $receiptOrder->receipt_number ?? '-',
                '{receipt_name}' => $receiptOrder->name ?? '-',
                '{contact_name}' => $contact->name ?? '-',
                '{account_name}' => $account->name ?? '-',
                '{receipt_total}' => $receiptOrder->total_amount ? '$' . number_format($receiptOrder->total_amount, 2) : '$0.00',
                '{receipt_date}' => $receiptOrder->receipt_date ? date('Y-m-d', strtotime($receiptOrder->receipt_date)) : '-',
                '{expected_receipt_date}' => $receiptOrder->expected_date ? date('Y-m-d', strtotime($receiptOrder->expected_date)) : '-',
                '{receipt_status}' => ucfirst($receiptOrder->status ?? 'pending'),
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
                        templateName: 'Receipt Order Created',
                        variables: $variables,
                        toEmail: $contact->email,
                        toName: $contact->name,
                        language: $userLanguage
                    );
                }

                // Send email to assigned user if exists and different from contact
                if ($assignedUser && $assignedUser->email && (!$contact || $assignedUser->email !== $contact->email)) {
                    $this->emailService->sendTemplateEmailWithLanguage(
                        templateName: 'Receipt Order Created',
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
                    session()->flash('email_error', 'Failed to send receipt order create email: ' . $errorMessage);
                }
            }
        }
    }
}
