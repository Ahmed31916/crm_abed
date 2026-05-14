<?php

namespace App\Listeners;

use App\Events\LeadAssigned;
use App\Services\WebhookService;

class WebhookAssignLeadListener
{
    private static array $processedLead = [];
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
    public function handle(LeadAssigned $event): void
    {
        $lead = $event->lead;

        // Prevent duplicate processing
        $leadKey = $lead->id . '_' . $lead->updated_at->timestamp;
        if (in_array($leadKey, self::$processedLead)) {
            return;
        }

        self::$processedLead[] = $leadKey;

        // Trigger webhooks for Lead Assignment
        $this->webhookService->triggerWebhooks('Lead Assigned', $lead->toArray(), $lead->created_by ?? $lead->id);
    }
}
