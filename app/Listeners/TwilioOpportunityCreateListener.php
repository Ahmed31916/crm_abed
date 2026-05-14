<?php

namespace App\Listeners;

use App\Events\OpportunityCreated;
use App\Models\User;
use App\Services\TwilioService;
use Exception;
use PhpParser\Node\Expr\Empty_;

class TwilioOpportunityCreateListener
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
    public function handle(OpportunityCreated $event): void
    {
        $opportunity = $event->opportunity;
        $account = $opportunity->account;
        $contact = $opportunity->contact;
        if (isNotificationTemplateEnabled('Opportunity create','twilio', createdBy()) && !empty($contact->phone)) {
            $variables = [
                '{opportunity_name}' => $opportunity->name ?? '-',
                '{amount}' => $opportunity->amount ?? '-',
                '{account_name}' => $account->name ?? '-',
                '{close_date}' => $opportunity->close_date ?? '-',
                '{company_name}' => getCompanyName(),
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
                        templateName: 'Opportunity create',
                        variables: $variables,
                        toPhone: $contact->phone,
                        language: $userLanguage
                    );
                } else {
                    session()->flash('twilio_error', 'Twilio credentials are not set.');
                }
            } catch (Exception $e) {
                // Store error in session for frontend notification
                session()->flash('twilio_error', 'Failed to send Opportunity create notification: ' . $e->getMessage());
            }
        }
    }
}
