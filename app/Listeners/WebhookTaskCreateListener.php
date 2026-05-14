<?php

namespace App\Listeners;

use App\Events\TaskAssigned;
use App\Services\WebhookService;

class WebhookTaskCreateListener
{
    private static array $processedTask = [];
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
    public function handle(TaskAssigned $event): void
    {
        $task = $event->task;
        // Prevent duplicate processing
        $taskKey = $task->id . '_' . $task->updated_at->timestamp;
        if (in_array($taskKey, self::$processedTask)) {
            return;
        }

        self::$processedTask[] = $taskKey;
        // Trigger webhooks for Task Assigned
        $this->webhookService->triggerWebhooks('Task Assigned', $task->toArray(), $task->created_by ?? $task->id);
    }
}
