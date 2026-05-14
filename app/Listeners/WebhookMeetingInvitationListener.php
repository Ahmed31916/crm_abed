<?php

namespace App\Listeners;

use App\Events\MeetingInvitation;
use App\Services\WebhookService;

class WebhookMeetingInvitationListener
{
    private static array $processedMeeting = [];
    /**
     * Create the event listener.
     */
    public function __construct(
        private WebhookService $webhookService
    ) {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MeetingInvitation $event): void
    {
        $meeting = $event->meeting;

        // Prevent duplicate processing
        $meetingKey = $meeting->id . '_' . $meeting->updated_at->timestamp;
        if (in_array($meetingKey, self::$processedMeeting)) {
            return;
        }

        self::$processedMeeting[] = $meetingKey;

        // Trigger webhooks for Meeting Invitation
        $this->webhookService->triggerWebhooks('Meeting Invitation', $meeting->toArray(), $meeting->created_by ?? $meeting->id);
    }
}
