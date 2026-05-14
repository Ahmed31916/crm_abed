<?php

namespace App\Listeners;

use App\Events\CaseCreated;
use App\Services\TwilioService;
use App\Models\User;
use Exception;

class TwilioCaseCreateListener
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private TwilioService $twilioService
    ) {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CaseCreated $event): void
    {
        $case = $event->case;
        $assignedUser = $case->assignedUser;
        $contact = $case->contact;
        if (isNotificationTemplateEnabled('Case Create','twilio', createdBy()) && !empty($contact->phone)) {
            $variables = [
                '{case_subject}' => $case->subject ?? '-',
                '{company_name}' => 'Company Name'
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
                        templateName: 'Case Create',
                        variables: $variables,
                        toPhone: $contact->phone,
                        language: $userLanguage
                    );
                } else {
                    session()->flash('twilio_error', 'Twilio credentials are not set.');
                }
            } catch (Exception $e) {
                // Store error in session for frontend notification
                session()->flash('twilio_error', 'Failed to send case Create notification: ' . $e->getMessage());
            }
        }
    }
}
