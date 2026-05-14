<?php

namespace App\Listeners;

use App\Events\AccountCreate;
use App\Events\LeadAssigned;
use App\Models\User;
use App\Services\TwilioService;
use Exception;

class TwilioAccountCreateListener
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
    public function handle(AccountCreate $event): void
    {
        $account = $event->account;
        if (isNotificationTemplateEnabled('Account create','twilio', createdBy()) && $account->phone) {

            $variables = [
                '{account_name}' => $account->name,
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
                        templateName: 'Account create',
                        variables: $variables,
                        toPhone: $account->phone,
                        language: $userLanguage
                    );
                } else {
                    session()->flash('twilio_error', 'Twilio credentials are not set.');
                }
            } catch (Exception $e) {
                // Store error in session for frontend notification - error entry in LOG
                session()->flash('twilio_error', 'Failed to send account create notification: ' . $e->getMessage());
            }
        }
    }
}
