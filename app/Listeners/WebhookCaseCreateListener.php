<?php

namespace App\Listeners;

use App\Events\CaseCreated;
use App\Services\WebhookService;

class WebhookCaseCreateListener
{
    private static array $processedCase = [];
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
    public function handle(CaseCreated $event): void
    {
        $case = $event->case;
        // Prevent duplicate processing
        $caseKey = $case->id . '_' . $case->updated_at->timestamp;
        if (in_array($caseKey, self::$processedCase)) {
            return;
        }

        self::$processedCase[] = $caseKey;

        // Trigger webhooks for Case Created
        $this->webhookService->triggerWebhooks('Case Created', $case->toArray(), $case->created_by ?? $case->id);
    }
}
