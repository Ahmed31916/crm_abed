<?php

namespace App\Listeners;

use App\Events\UserCreated;
use App\Services\WebhookService;

class WebhookUserCreateListener
{
    private static array $processedUser = [];
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
    public function handle(UserCreated $event): void
    {
        $user = $event->user;

        // Prevent duplicate processing
        $userKey = $user->id . '_' . $user->updated_at->timestamp;
        if (in_array($userKey, self::$processedUser)) {
            return;
        }

        self::$processedUser[] = $userKey;

        // Trigger webhooks for New User
        $this->webhookService->triggerWebhooks('New User', $user->toArray(), $user->created_by ?? $user->id);
    }
}
