<?php

namespace App\Listeners;

use App\Events\QuoteCreated;
use App\Models\User;
use App\Services\TwilioService;
use Exception;

class TwilioQuoteCreateListener
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private TwilioService $twilioService,
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
        $account = $quote->account;
        if (isNotificationTemplateEnabled('Quote Create','twilio', createdBy()) && !empty($billingContact->phone)) {

            $variables = [
                '{quote_number}' => $quote->quote_number ?? '-',
                '{account_name}' => $account->name ?? '-',
                '{total_amount}' => $quote->total_amount ? number_format($quote->total_amount, 2) : '0.00',
                '{valid_until}' => $quote->valid_until ? date('Y-m-d', strtotime($quote->valid_until)) : '-',
                '{company_name}' => getCompanyName()
            ];

            try {
                // Clear any existing twilio error
                session()->forget('twilio_error');

                $twilio = getTwilioConfig();
                $sid = $twilio['twilio_sid'];
                $token = $twilio['twilio_token'];
                $from = $twilio['twilio_from'];

                if (filled($sid) && filled($token) && filled($from)) {
                    $createdByUser = User::find(createdBy());
                    $userLanguage = $createdByUser->lang ?? 'en';
                    $this->twilioService->sendTemplateMessageWithLanguage(
                        templateName: 'Quote Create',
                        variables: $variables,
                        toPhone: $billingContact->phone,
                        language: $userLanguage
                    );
                } else {
                    session()->flash('twilio_error', 'Twilio credentials are not set.');
                }
            } catch (Exception $e) {
                // Store error in session for frontend notification
                session()->flash('twilio_error', 'Failed to send quote Create notification: ' . $e->getMessage());
            }
        }
    }
}
