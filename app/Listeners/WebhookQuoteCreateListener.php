<?php

namespace App\Listeners;

use App\Events\QuoteCreated;
use App\Services\WebhookService;

class WebhookQuoteCreateListener
{
    private static array $processedQuote = [];
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
    public function handle(QuoteCreated $event): void
    {
        $quote = $event->quote;
        // Prevent duplicate processing
        $quoteKey = $quote->id . '_' . $quote->updated_at->timestamp;
        if (in_array($quoteKey, self::$processedQuote)) {
            return;
        }

        self::$processedQuote[] = $quoteKey;
        // Trigger webhooks for Quote Created
        $this->webhookService->triggerWebhooks('Quote Created', $quote->toArray(), $quote->created_by ?? $quote->id);
    }
}
