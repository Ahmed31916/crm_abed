<?php

namespace App\Listeners;

use App\Events\MeetingInvitation;
use App\Models\User;
use App\Services\EmailTemplateService;
use Exception;

class SendMeetingInvitationEmail
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
    public function handle(MeetingInvitation $event): void
    {
        $meeting = $event->meeting;
        $creator = $meeting->creator;
        $assignedUser = $meeting->assignedUser;

        if (isEmailTemplateEnabled('Meeting Invitation', createdBy()) && isset($meeting['attendees'])) {
            // Prepare email variables
            $variables = [
                '{meeting_title}' => $meeting->title ?? '-',
                '{meeting_description}' => $meeting->description ?? '-',
                '{meeting_location}' => $meeting->location ?? '-',
                '{meeting_date}' => $meeting->start_date ?? '-',
                '{meeting_start_time}' => $meeting->start_time ?? '-',
                '{meeting_end_time}' => $meeting->end_time ?? '-',
                '{meeting_status}' => $meeting->status ?? '-',
                '{company_name}' => getCompanyName(),
            ];

            try {
                // Clear any existing email error
                session()->forget('email_error');
                $createdByUser = User::find(createdBy());
                $userLanguage = $createdByUser->lang ?? 'en';

                // Send email to attendees
                if ($meeting->attendees) {
                    foreach ($meeting->attendees as $attendee) {
                        $attendeeRecord = null;
                        $attendeeEmail = null;
                        $attendeeName = null;

                        switch ($attendee->attendee_type) {
                            case 'user':
                                $attendeeRecord = \App\Models\User::find($attendee->attendee_id);
                                break;
                            case 'contact':
                                $attendeeRecord = \App\Models\Contact::find($attendee->attendee_id);
                                break;
                            case 'lead':
                                $attendeeRecord = \App\Models\Lead::find($attendee->attendee_id);
                                break;
                        }

                        if ($attendeeRecord && $attendeeRecord->email) {
                            $variables['{attendee_name}'] = $attendeeRecord->name ?? '-';
                            $this->emailService->sendTemplateEmailWithLanguage(
                                templateName: 'Meeting Invitation',
                                variables: $variables,
                                toEmail: $attendeeRecord->email,
                                toName: $attendeeRecord->name,
                                language: $userLanguage
                            );
                        }
                    }
                }

            } catch (Exception $e) {
                // Only store error if it's not a rate limiting issue (email was likely sent successfully)
                $errorMessage = $e->getMessage();
                if (
                    !str_contains($errorMessage, 'Too many emails per second') &&
                    !str_contains($errorMessage, '550 5.7.0') &&
                    !str_contains($errorMessage, 'rate limit')
                ) {
                    session()->flash('email_error', 'Failed to send meeting create email: ' . $errorMessage);
                }
            }
        }
    }
}
