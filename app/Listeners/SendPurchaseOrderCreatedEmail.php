<?php

namespace App\Listeners;

use App\Events\PurchaseOrderCreated;
use App\Models\User;
use App\Services\EmailTemplateService;
use Exception;

class SendPurchaseOrderCreatedEmail
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
    public function handle(PurchaseOrderCreated $event): void
    {
        $purchaseOrder = $event->purchaseOrder;

        $billingContact = $purchaseOrder->billingContact;
        $assignedUser = $purchaseOrder->assignedUser;
        $account = $purchaseOrder->account;

        if (isEmailTemplateEnabled('Purchase Order Created', createdBy())) {
            // Prepare email variables
            $variables = [
                '{purchase_order_number}' => $purchaseOrder->order_number ?? '-',
                '{purchase_order_name}' => $purchaseOrder->name ?? '-',
                '{contact_name}' => $billingContact->name ?? '-',
                '{account_name}' => $account->name ?? '-',
                '{purchase_order_total}' => $purchaseOrder->total_amount ? '$' . number_format($purchaseOrder->total_amount, 2) : '$0.00',
                '{purchase_order_date}' => $purchaseOrder->order_date ? date('Y-m-d', strtotime($purchaseOrder->order_date)) : '-',
                '{expected_delivery_date}' => $purchaseOrder->expected_delivery_date ? date('Y-m-d', strtotime($purchaseOrder->expected_delivery_date)) : '-',
                '{purchase_order_status}' => ucfirst($purchaseOrder->status ?? 'draft'),
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
                        templateName: 'Purchase Order Created',
                        variables: $variables,
                        toEmail: $billingContact->email,
                        toName: $billingContact->name,
                        language: $userLanguage
                    );
                }

                // Send email to assigned user if exists and different from billing contact
                if ($assignedUser && $assignedUser->email && (!$billingContact || $assignedUser->email !== $billingContact->email)) {
                    $this->emailService->sendTemplateEmailWithLanguage(
                        templateName: 'Purchase Order Created',
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
                    session()->flash('email_error', 'Failed to send purchase order create email: ' . $errorMessage);
                }
            }
        }
    }
}
