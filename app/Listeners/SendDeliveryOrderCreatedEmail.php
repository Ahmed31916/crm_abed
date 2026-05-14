<?php

namespace App\Listeners;

use App\Events\DeliveryOrderCreated;
use App\Models\User;
use App\Services\EmailTemplateService;
use Exception;

class SendDeliveryOrderCreatedEmail
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
    public function handle(DeliveryOrderCreated $event): void
    {
        $deliveryOrder = $event->deliveryOrder;
        $contact = $deliveryOrder->contact;
        $assignedUser = $deliveryOrder->assignedUser;
        $account = $deliveryOrder->account;

        if (isEmailTemplateEnabled('Delivery Order Created', createdBy())) {
            // Prepare email variables
            $variables = [
                '{delivery_order_number}' => $deliveryOrder->delivery_number ?? '-',
                '{delivery_order_name}' => $deliveryOrder->name ?? '-',
                '{contact_name}' => $contact->name ?? '-',
                '{account_name}' => $account->name ?? '-',
                '{delivery_date}' => $deliveryOrder->delivery_date ? date('Y-m-d', strtotime($deliveryOrder->delivery_date)) : '-',
                '{expected_delivery_date}' => $deliveryOrder->expected_delivery_date ? date('Y-m-d', strtotime($deliveryOrder->expected_delivery_date)) : '-',
                '{delivery_status}' => ucfirst($deliveryOrder->status ?? 'pending'),
                '{tracking_number}' => $deliveryOrder->tracking_number ?? '-',
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
                        templateName: 'Delivery Order Created',
                        variables: $variables,
                        toEmail: $contact->email,
                        toName: $contact->name,
                        language: $userLanguage
                    );
                }

                // Send email to assigned user if exists and different from contact
                if ($assignedUser && $assignedUser->email && (!$contact || $assignedUser->email !== $contact->email)) {
                    $this->emailService->sendTemplateEmailWithLanguage(
                        templateName: 'Delivery Order Created',
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
