<?php

namespace App\Listeners;

use App\Events\MeetingInvitation;
use App\Models\User;
use App\Services\TwilioService;
use Exception;

class TwilioMettingCreateListener
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
    public function handle(MeetingInvitation $event): void
    {
        // Meeting Create
        // Meeting Create

        $meeting = $event->meeting;
        $creator = $meeting->creator;
        $assignedUser = $meeting->assignedUser;

        if (isNotificationTemplateEnabled('Meeting Create','twilio', createdBy()) && !empty($meeting->attendees)) {
            $variables = [
                '{meeting_subject}' => $meeting->title ?? '-',
                '{meeting_date}' => date('Y-m-d', strtotime($meeting->start_date)) ?? '-',
                '{meeting_time}' => $meeting->start_time ?? '-',
                '{attendee_count}' => count($meeting->attendees),
                '{company_name}' => getCompanyName(),
            ];

            try {
                // Clear any existing twilio error
                session()->forget('twilio_error');

                $twilio = getTwilioConfig();
                $sid = $twilio['twilio_sid'];
                $token = $twilio['twilio_token'];
                $from = $twilio['twilio_from'];

                // Send email to attendees
                if ($meeting->attendees) {
                    $createdByUser = User::find(createdBy());
                    $userLanguage = $createdByUser->lang ?? 'en';
                    foreach ($meeting->attendees as $attendee) {
                        $attendeeRecord = null;

                        switch ($attendee->attendee_type) {
                            case 'contact':
                                $attendeeRecord = \App\Models\Contact::find($attendee->attendee_id);
                                break;
                            case 'lead':
                                $attendeeRecord = \App\Models\Lead::find($attendee->attendee_id);
                                break;
                        }

                        if ($attendeeRecord && $attendeeRecord->email) {
                            if (filled($sid) && filled($token) && filled($from)) {
                                $this->twilioService->sendTemplateMessageWithLanguage(
                                    templateName: 'Meeting Create',
                                    variables: $variables,
                                    toPhone: $attendeeRecord->phone,
                                    language: $userLanguage
                                );
                            } else {
                                session()->flash('twilio_error', 'Twilio credentials are not set.');
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                // Store error in session for frontend notification
                session()->flash('twilio_error', 'Failed to send meeting Create notification: ' . $e->getMessage());
            }
        }
    }
}
