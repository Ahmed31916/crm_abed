<?php

namespace App\Listeners;

use App\Events\LeadAssigned;
use App\Models\User;
use App\Services\TwilioService;
use Exception;

class TwilioLeadCreateListener
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
    public function handle(LeadAssigned $event): void
    {
        $lead = $event->lead;
        if (isNotificationTemplateEnabled('Lead Create','twilio', createdBy()) && $lead->phone) {

            $variables = [
                '{lead_name}' => $lead->name ?? '-',
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
                        templateName: 'Lead Create',
                        variables: $variables,
                        toPhone: $lead->phone,
                        language: $userLanguage
                    );
                } else {
                    session()->flash('twilio_error', 'Twilio credentials are not set.');
                }
            } catch (Exception $e) {
                // Store error in session for frontend notification
                session()->flash('twilio_error', 'Failed to send lead create notification: ' . $e->getMessage());
            }
        }
    }
}
