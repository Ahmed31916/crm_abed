<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Jobs\ProcessProductEvent;

class ProductEventOutbox extends Model
{
    use HasFactory;

    protected $table = 'product_event_outbox';

    /**
     * الحقول القابلة للتعبئة
     */
    protected $fillable = [
        'product_id',
        'action',
        'payload',
        'status',
        'attempts',
        'max_attempts',
        'last_error',
        'sent_at',
        'next_retry_at',
    ];

    /**
     * تحويل أنواع الحقول
     */
    protected $casts = [
        'payload'       => 'array',
        'attempts'      => 'integer',
        'max_attempts'  => 'integer',
        'sent_at'       => 'datetime',
        'next_retry_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | حالات الأحداث
    |--------------------------------------------------------------------------
    */
    const STATUS_PENDING    = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SENT       = 'sent';
    const STATUS_FAILED     = 'failed';

    /*
    |--------------------------------------------------------------------------
    | أنواع الأحداث
    |--------------------------------------------------------------------------
    */
    const ACTION_CREATED = 'created';
    const ACTION_UPDATED = 'updated';
    const ACTION_DELETED = 'deleted';

    /*
    |--------------------------------------------------------------------------
    | العلاقات
    |--------------------------------------------------------------------------
    */

    /**
     * المنتج المرتبط بالحدث
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes للاستعلام
    |--------------------------------------------------------------------------
    */

    /**
     * الأحداث المعلقة الجاهزة للإرسال
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING)
                     ->where(function ($q) {
                         $q->whereNull('next_retry_at')
                           ->orWhere('next_retry_at', '<=', now());
                     });
    }

    /**
     * الأحداث الفاشلة التي يمكن إعادة محاولتها
     */
    public function scopeRetryable($query)
    {
        return $query->where('status', self::STATUS_FAILED)
                     ->where('attempts', '<', \DB::raw('max_attempts'))
                     ->where(function ($q) {
                         $q->whereNull('next_retry_at')
                           ->orWhere('next_retry_at', '<=', now());
                     });
    }

    /**
     * الأحداث العالقة في حالة processing لأكثر من دقيقتين
     * (يمكن أن تحدث إذا توقف الـ worker أثناء المعالجة)
     */
    public function scopeStuck($query)
    {
        return $query->where('status', self::STATUS_PROCESSING)
                     ->where('updated_at', '<', now()->subMinutes(2));
    }

    /*
    |--------------------------------------------------------------------------
    | طرق مساعدة
    |--------------------------------------------------------------------------
    */

    /**
     * إنشاء حدث جديد في الـ outbox وإرساله للـ queue
     *
     * @param int $productId
     * @param string $action
     * @param array $payload
     * @return self
     */
    public static function createAndDispatch(int $productId, string $action, array $payload): self
    {
        $outbox = self::create([
            'product_id'    => $productId,
            'action'        => $action,
            'payload'       => $payload,
            'status'        => self::STATUS_PENDING,
            'attempts'      => 0,
            'max_attempts'  => 5,
            'next_retry_at' => null,
        ]);

        // إرسال الـ job للـ queue
        ProcessProductEvent::dispatch($outbox->id)
            ->onQueue('product-events');

        return $outbox;
    }

    /**
     * حساب وقت المحاولة القادمة (Exponential Backoff)
     * المحاولة 1: 30 ثانية، المحاولة 2: دقيقة، المحاولة 3: 2 دقيقة، المحاولة 4: 5 دقائق، المحاولة 5: 10 دقائق
     *
     * @return \Carbon\Carbon
     */
    public function calculateNextRetryAt()
    {
        $delays = [30, 60, 120, 300, 600]; // بالثواني
        $delaySeconds = $delays[min($this->attempts, count($delays) - 1)];

        return now()->addSeconds($delaySeconds);
    }

    /**
     * هل وصل للحد الأقصى من المحاولات؟
     */
    public function hasExceededMaxAttempts(): bool
    {
        return $this->attempts >= $this->max_attempts;
    }

    /**
     * هل الحدث عالق في حالة processing؟
     */
    public function isStuck(): bool
    {
        return $this->status === self::STATUS_PROCESSING
            && $this->updated_at->lt(now()->subMinutes(2));
    }

    /**
     * تحديث حالة الحدث إلى "قيد المعالجة"
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status'   => self::STATUS_PROCESSING,
            'attempts' => $this->attempts + 1,
        ]);
    }

    /**
     * تحديث حالة الحدث إلى "تم الإرسال"
     */
    public function markAsSent(): void
    {
        $this->update([
            'status'  => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * تحديث حالة الحدث إلى "فاشل" مع تسجيل الخطأ
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status'        => self::STATUS_FAILED,
            'last_error'    => $errorMessage,
            'next_retry_at' => $this->hasExceededMaxAttempts() ? null : $this->calculateNextRetryAt(),
        ]);
    }

    /**
     * إعادة تعيين حدث عالق إلى pending
     */
    public function resetStuck(): void
    {
        $this->update([
            'status'        => self::STATUS_PENDING,
            'next_retry_at' => null,
        ]);
    }
}
