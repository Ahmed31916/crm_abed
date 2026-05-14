<?php

namespace App\Console\Commands;

use App\Models\ProductEventOutbox;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessProductEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'outbox:process-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending product events from outbox and dispatch to message broker';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $events = ProductEventOutbox::pending()
            ->orderBy('created_at')
            ->get();

        if ($events->isEmpty()) {
            $this->info('No pending product events to process.');
            return 0;
        }

        $this->info("Processing {$events->count()} product events...");

        $processed = 0;
        $failed = 0;

        foreach ($events as $event) {
            try {
                // =============================================
                // DISPATCH TO MESSAGE BROKER (RabbitMQ, etc.)
                // Replace this with your actual dispatch logic
                // =============================================
                // Example for RabbitMQ:
                // Amqp::publish('product_events', json_encode([
                //     'event_type' => $event->event_type,
                //     'product_id' => $event->product_id,
                //     'payload' => $event->payload,
                //     'timestamp' => now()->toIso8601String(),
                // ]));

                $event->markAsProcessed();
                $processed++;

                $this->line("  [OK] Event #{$event->id}: {$event->event_type} for product #{$event->product_id}");
            } catch (\Exception $e) {
                $event->markAsFailed();

                Log::error('Product outbox event failed', [
                    'event_id' => $event->id,
                    'product_id' => $event->product_id,
                    'event_type' => $event->event_type,
                    'attempts' => $event->attempts,
                    'error' => $e->getMessage(),
                ]);

                $failed++;
                $this->error("  [FAIL] Event #{$event->id}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Results: {$processed} processed, {$failed} failed");

        return $failed > 0 ? 1 : 0;
    }
}
