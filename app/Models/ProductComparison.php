<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ProductComparison Model
 *
 * يخزن نتائج المقارنة بين البيانات المحلية وبيانات المزود (Provider API)
 * يستخدم فقط من السوبر ادمن
 *
 * الحالات (status):
 * - pending: في انتظار المراجعة
 * - accepted: تم القبول والتطبيق
 * - rejected: تم الرفض
 */
class ProductComparison extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'product_name',
        'field_name',
        'old_value',
        'new_value',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the product this comparison belongs to
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope: only pending comparisons
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: only accepted comparisons
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope: only rejected comparisons
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
