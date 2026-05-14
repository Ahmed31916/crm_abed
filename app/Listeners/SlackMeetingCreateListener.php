<?php

namespace App\Listeners;

use App\Events\MeetingInvitation;
use App\Services\SlackService;
use App\Models\User;
use Exception;

class SlackMeetingCreateListener
{
    public function __construct(
        private SlackService $slackService
    ) {
        //
    }

    public function handle(MeetingInvitation $event): void
    {
        $meeting = $event->meeting;
        if (isNotificationTemplateEnabled('Meeting Create', 'slack', createdBy())) {
            $variables = [
                '{meeting_subject}' => $meeting->title ?? '-',
                '{meeting_date}' => date('Y-m-d', strtotime($meeting->start_date)) ?? '-',
                '{meeting_time}' => $meeting->start_time ?? '-',
                '{attendee_count}' => count($meeting->attendees),
                '{company_name}' => getCompanyName(),
            ];

            try {
                session()->forget('slack_error');

                $webhookUrl = getSetting('slack_webhook_url', '', createdBy());

                if (filled($webhookUrl)) {
                    $createdByUser = User::find(createdBy());
                    $userLanguage = $createdByUser->lang ?? 'en';
                    $this->slackService->sendTemplateMessageWithLanguage(
                        templateName: 'Meeting Create',
                        variables: $variables,
                        language: $userLanguage
                    );
                } else {
                    session()->flash('slack_error', 'Slack webhook URL is not set.');
                }
            } catch (Exception $e) {
                session()->flash('slack_error', 'Failed to send meeting create notification: ' . $e->getMessage());
            }
        }
    }
}
