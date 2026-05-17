<?php

namespace App\Console\Commands;

use App\Jobs\ProcessProductEvent;
use App\Models\ProductEventOutbox;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RetryFailedProductEvents extends Command
{
    /**
     * اسم الأمر
     */
    protected $signature = 'product-events:retry
                            {--max-age=60 : أقصى عمر للحدث بالدقائق (افتراضي 60)}
                            {--limit=50 : أقصى عدد أحداث لمعالجتها دفعة واحدة}
                            {--force : إعادة محاولة جميع الأحداث الفاشلة حتى لو تجاوزت max_attempts}';

    /**
     * وصف الأمر
     */
    protected $description = 'إعادة محاولة إرسال أحداث المنتجات الفاشلة أو العالقة إلى RabbitMQ';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $maxAge = (int) $this->option('max-age');
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        $this->info('جاري البحث عن أحداث تحتاج لإعادة المحاولة...');

        // 1. معالجة الأحداث العالقة في حالة processing (توقف الـ worker أثناء المعالجة)
        $stuckCount = $this->processStuckEvents();
        $this->info("تم إعادة تعيين {$stuckCount} حدث عالق");

        // 2. معالجة الأحداث الفاشلة
        $failedCount = $this->processFailedEvents($maxAge, $limit, $force);
        $this->info("تم إعادة جدولة {$failedCount} حدث فاشل");

        // 3. التحقق من وجود أحداث وصلت للحد الأقصى من المحاولات
        $permanentlyFailed = ProductEventOutbox::where('status', ProductEventOutbox::STATUS_FAILED)
            ->whereColumn('attempts', '>=', 'max_attempts')
            ->count();

        if ($permanentlyFailed > 0) {
            $this->warn("يوجد {$permanentlyFailed} حدث فشل نهائياً بعد تجاوز الحد الأقصى من المحاولات");
            $this->warn("   يمكنك استخدام --force لإعادة محاولتها، أو تحقق من سجل الأخطاء");
        }

        $totalProcessed = $stuckCount + $failedCount;

        if ($totalProcessed > 0) {
            $this->info("تمت معالجة {$totalProcessed} حدث بنجاح");
            Log::info("RetryFailedProductEvents: Processed {$totalProcessed} events", [
                'stuck' => $stuckCount,
                'failed' => $failedCount,
            ]);
        } else {
            $this->info('لا توجد أحداث تحتاج لإعادة المحاولة');
        }

        return Command::SUCCESS;
    }

    /**
     * معالجة الأحداث العالقة في حالة processing
     * يمكن أن تحدث إذا توقف الـ worker أثناء معالجة حدث
     */
    private function processStuckEvents(): int
    {
        $stuckEvents = ProductEventOutbox::stuck()->get();

        foreach ($stuckEvents as $event) {
            $event->resetStuck();

            // إعادة إرسال الـ job
            ProcessProductEvent::dispatch($event->id)
                ->onQueue('product-events')
                ->delay(now()->addSeconds(5)); // تأخير بسيط

            Log::info("RetryFailedProductEvents: Reset stuck event", [
                'outbox_id' => $event->id,
                'product_id' => $event->product_id,
            ]);
        }

        return $stuckEvents->count();
    }

    /**
     * معالجة الأحداث الفاشلة
     */
    private function processFailedEvents(int $maxAge, int $limit, bool $force): int
    {
        $query = ProductEventOutbox::where('status', ProductEventOutbox::STATUS_FAILED);

        // تصفية حسب العمر
        if ($maxAge > 0) {
            $query->where('created_at', '>=', now()->subMinutes($maxAge));
        }

        // إذا لم يتم تحديد --force، لا نعيد المحاولة للأحداث التي تجاوزت max_attempts
        if (!$force) {
            $query->whereColumn('attempts', '<', 'max_attempts');
        }

        // فقط الأحداث التي حان وقت المحاولة
        $query->where(function ($q) {
            $q->whereNull('next_retry_at')
              ->orWhere('next_retry_at', '<=', now());
        });

        $failedEvents = $query->limit($limit)->get();

        foreach ($failedEvents as $event) {
            // إعادة تعيين الحالة إلى pending
            $event->update([
                'status'        => ProductEventOutbox::STATUS_PENDING,
                'next_retry_at' => null,
            ]);

            // إرسال job جديد
            ProcessProductEvent::dispatch($event->id)
                ->onQueue('product-events');

            Log::info("RetryFailedProductEvents: Retrying failed event", [
                'outbox_id' => $event->id,
                'product_id' => $event->product_id,
                'attempt'    => $event->attempts,
            ]);
        }

        return $failedEvents->count();
    }
}
