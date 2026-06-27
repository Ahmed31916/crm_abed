<?php

namespace App\Jobs;

use App\Models\ProductEventOutbox;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class ProcessProductEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * معرف سجل الـ outbox
     */
    public int $outboxId;

    /**
     * عدد المحاولات القصوى للـ job نفسه (قبل ما Laravel يعتبره failed job)
     * هذا مختلف عن max_attempts في الـ outbox
     */
    public int $tries = 3;

    /**
     * الوقت الأقصى للـ job قبل اعتباره timeout (بالثواني)
     */
    public int $timeout = 30;

    /**
     * التأخير بين محاولات الـ job (Exponential Backoff)
     * المحاولة 1: 10 ثواني، المحاولة 2: 30 ثانية، المحاولة 3: 60 ثانية
     */
    public array $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(int $outboxId)
    {
        $this->outboxId = $outboxId;
        $this->onQueue('product-events');
    }

    /**
     * Execute the job.
     *
     * هذه الدالة تقرأ الحدث من جدول الـ outbox وترسله لـ RabbitMQ
     * إذا فشل الإرسال، يتم تسجيل الخطأ والـ job سيعيد المحاولة تلقائياً
     */
    public function handle(): void
    {
        // 1. قراءة سجل الـ outbox
        $outbox = ProductEventOutbox::find($this->outboxId);

        if (!$outbox) {
            Log::warning("ProcessProductEvent: Outbox record not found", [
                'outbox_id' => $this->outboxId
            ]);
            return;
        }

        // 2. إذا تم إرساله بالفعل، لا حاجة لإعادة الإرسال
        if ($outbox->status === ProductEventOutbox::STATUS_SENT) {
            Log::info("ProcessProductEvent: Event already sent, skipping", [
                'outbox_id' => $this->outboxId,
                'product_id' => $outbox->product_id
            ]);
            return;
        }

        // 3. إذا وصل للحد الأقصى من المحاولات، لا تحاول مرة أخرى
        if ($outbox->hasExceededMaxAttempts()) {
            Log::error("ProcessProductEvent: Max attempts exceeded, giving up", [
                'outbox_id'  => $this->outboxId,
                'product_id' => $outbox->product_id,
                'attempts'   => $outbox->attempts,
            ]);
            return;
        }

        // 4. تحديث الحالة إلى "قيد المعالجة"
        $outbox->markAsProcessing();

        // 5. إرسال البيانات إلى RabbitMQ
        try {
            // تسجيل الـ JSON الكامل قبل الإرسال
            Log::info("ProcessProductEvent: Sending payload to RabbitMQ", [
                'outbox_id'  => $this->outboxId,
                'product_id' => $outbox->product_id,
                'action'     => $outbox->action,
                'attempt'    => $outbox->attempts,
                'payload'    => $outbox->payload,
            ]);

            $this->publishToRabbitMQ($outbox->payload);

            // 6. تحديث الحالة إلى "تم الإرسال"
            $outbox->markAsSent();

            Log::info("ProcessProductEvent: Successfully sent event to RabbitMQ", [
                'outbox_id'  => $this->outboxId,
                'product_id' => $outbox->product_id,
                'action'     => $outbox->action,
                'attempt'    => $outbox->attempts,
            ]);

        } catch (\Exception $e) {
            // 7. تسجيل الخطأ وتحديث الحالة إلى "فاشل"
            $outbox->markAsFailed($e->getMessage());

            Log::error("ProcessProductEvent: Failed to send event to RabbitMQ", [
                'outbox_id'  => $this->outboxId,
                'product_id' => $outbox->product_id,
                'action'     => $outbox->action,
                'attempt'    => $outbox->attempts,
                'error'      => $e->getMessage(),
            ]);

            // 8. رمي الاستثناء عشان Laravel يعيد المحاولة حسب الإعدادات
            // إذا وصل للحد الأقصى من المحاولات في الـ outbox، لا ترمي الاستثناء
            if (!$outbox->hasExceededMaxAttempts()) {
                throw $e;
            }
        }
    }

    /**
     * إرسال الرسالة إلى RabbitMQ
     *
     * @param array $payload
     * @throws \Exception
     */
    private function publishToRabbitMQ(array $payload): void
    {
        // تعريف ثوابت السوكت لبيئة ويندوز
        if (!defined('SOCKET_EAGAIN')) define('SOCKET_EAGAIN', 11);
        if (!defined('SOCKET_EINTR')) define('SOCKET_EINTR', 4);

        $connection = null;
        $channel = null;

        try {
            // إنشاء اتصال RabbitMQ
            $connection = new AMQPStreamConnection(
                config('services.rabbitmq.host', env('RABBITMQ_HOST', 'vitalexperts.co')),
                config('services.rabbitmq.port', env('RABBITMQ_PORT', 5672)),
                config('services.rabbitmq.user', env('RABBITMQ_USER', 'ahmed_admin')),
                config('services.rabbitmq.password', env('RABBITMQ_PASS', 'P@ssword123')),
                config('services.rabbitmq.vhost', env('RABBITMQ_VHOST', 'test')),
                false,       // insist
                'AMQPLAIN',  // login_method
                null,        // login_response
                'en_US',     // locale
                5.0,         // connection_timeout
                10.0,        // read_write_timeout - لازم يكون 2x heartbeat على الأقل
                null,        // context
                false,       // keepalive
                5            // heartbeat
            );

            $channel = $connection->channel();

            $exchangeName = 'product.updates';

            // إرسال الرسالة
            $msg = new AMQPMessage(json_encode($payload), [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'content_type'  => 'application/json',
                'message_id'    => uniqid('product_', true), // معرف فريد للرسالة
            ]);

            Log::info([
    'host'  => config('services.rabbitmq.host'),
    'vhost' => config('services.rabbitmq.vhost'),
]);

            $channel->basic_publish($msg, $exchangeName);

        } finally {
            // إغلاق الاتصال بأمان دائماً
            try {
                if ($channel) $channel->close();
            } catch (\Exception $e) {
                Log::warning("ProcessProductEvent: Error closing RabbitMQ channel", [
                    'error' => $e->getMessage()
                ]);
            }

            try {
                if ($connection) $connection->close();
            } catch (\Exception $e) {
                Log::warning("ProcessProductEvent: Error closing RabbitMQ connection", [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * التعامل مع فشل الـ job نهائياً (بعد كل المحاولات)
     * هذه الدالة يتم استدعاؤها عندما يفشل الـ job بعد كل الـ tries
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical("ProcessProductEvent: Job permanently failed", [
            'outbox_id' => $this->outboxId,
            'error'     => $exception->getMessage(),
        ]);

        $outbox = ProductEventOutbox::find($this->outboxId);
        if ($outbox && $outbox->status !== ProductEventOutbox::STATUS_SENT) {
            $outbox->update([
                'status'     => ProductEventOutbox::STATUS_FAILED,
                'last_error' => 'Job permanently failed: ' . $exception->getMessage(),
            ]);
        }
    }
}
