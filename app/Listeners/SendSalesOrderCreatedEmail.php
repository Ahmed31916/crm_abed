<?php

namespace App\Listeners;

use App\Events\SalesOrderCreated;
use App\Models\User;
use App\Services\EmailTemplateService;
use Exception;

class SendSalesOrderCreatedEmail
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
    public function handle(SalesOrderCreated $event): void
    {
        $salesOrder = $event->salesOrder;

        $billingContact = $salesOrder->billingContact;
        $assignedUser = $salesOrder->assignedUser;
        $account = $salesOrder->account;

        if (isEmailTemplateEnabled('Sales Order Created', createdBy())) {
            // Prepare email variables
            $variables = [
                '{order_number}' => $salesOrder->order_number ?? '-',
                '{order_name}' => $salesOrder->name ?? '-',
                '{billing_contact_name}' => $billingContact->name ?? '-',
                '{account_name}' => $account->name ?? '-',
                '{order_total}' => $salesOrder->total_amount ? '$' . number_format($salesOrder->total_amount, 2) : '$0.00',
                '{order_date}' => $salesOrder->order_date ? date('Y-m-d', strtotime($salesOrder->order_date)) : '-',
                '{delivery_date}' => $salesOrder->delivery_date ? date('Y-m-d', strtotime($salesOrder->delivery_date)) : '-',
                '{order_status}' => ucfirst($salesOrder->status ?? 'draft'),
                '{assigned_user_name}' => $assignedUser->name ?? '-',
                '{assigned_user_email}' => $assignedUser->email ?? '-',
                '{company_name}' => getCompanyName(),
            ];

            try {
                session()->forget('email_error');
                $createdByUser = User::find(createdBy());
                $userLanguage = $createdByUser->lang ?? 'en';

                // Send email to billing contact if exists
                if ($billingContact && $billingContact->email) {
                    $this->emailService->sendTemplateEmailWithLanguage(
                        templateName: 'Sales Order Created',
                        variables: $variables,
                        toEmail: $billingContact->email,
                        toName: $billingContact->name,
                        language: $userLanguage
                    );
                }

                // Send email to assigned user if exists and different from billing contact
                if ($assignedUser && $assignedUser->email && (!$billingContact || $assignedUser->email !== $billingContact->email)) {
                    $this->emailService->sendTemplateEmailWithLanguage(
                        templateName: 'Sales Order Created',
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
                    session()->flash('email_error', 'Email template not enabled in company settings.');
                }
            }
        }
    }
}
