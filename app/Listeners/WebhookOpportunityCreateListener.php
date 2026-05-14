<?php

namespace App\Listeners;

use App\Events\OpportunityCreated;
use App\Services\WebhookService;

class WebhookOpportunityCreateListener
{
    private static array $processedOpportunity = [];
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
    public function handle(OpportunityCreated $event): void
    {
        $opportunity = $event->opportunity;

        // Prevent duplicate processing
        $opportunityKey = $opportunity->id . '_' . $opportunity->updated_at->timestamp;
        if (in_array($opportunityKey, self::$processedOpportunity)) {
            return;
        }

        self::$processedOpportunity[] = $opportunityKey;
        // Trigger webhooks for Opportunity Created
        $this->webhookService->triggerWebhooks('Opportunity Created', $opportunity->toArray(), $opportunity->created_by ?? $opportunity->id);
    }
}
