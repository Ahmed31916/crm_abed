<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * PrimaryIndication Model — UPDATED for Many-to-Many with Product
 *
 * ────────────────────────────────────────────────────────────────────────
 * ما الجديد:
 * ────────────────────────────────────────────────────────────────────────
 * - إضافة علاقة belongsToMany مع Product عبر product_primary_indications.
 * - إضافة helpers للبحث/الإنشاء عن indications.
 *
 * إذا كان عندك ملف Model أصلي يحتوي على fillable/casts/relationships أخرى،
 * فقط أضِف methods جديدة للـ Product relationship + helpers الثابتة.
 * ────────────────────────────────────────────────────────────────────────
 */
class PrimaryIndication extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =========================================================================
    // =========== Boot: auto-slug on create ==================================
    // =========================================================================

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('name') && empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    // =========================================================================
    // =========== Relationships ==============================================
    // =========================================================================

    /**
     * العلاقة Many-to-Many مع Product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_primary_indications',
            'primary_indication_id',
            'product_id'
        )
        ->withTimestamps();
    }

    // =========================================================================
    // =========== Static Helpers (find-or-create) ============================
    // =========================================================================

    /**
     * Find-or-create by name (case-insensitive).
     *
     * يستخدم في:
     *   - ProductImport لربط الـ indications القادمة من Excel/CSV.
     *   - ProductController@store/update لو الـ form يرسل أسماء جديدة.
     *
     * @param string $name
     * @return self
     */
    public static function findOrCreateByName(string $name): self
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            throw new \InvalidArgumentException('Primary Indication name cannot be empty.');
        }

        // Case-insensitive lookup
        $existing = self::whereRaw('LOWER(name) = ?', [mb_strtolower($trimmed)])->first();
        if ($existing) {
            return $existing;
        }

        return self::create([
            'name' => $trimmed,
            'slug' => Str::slug($trimmed),
        ]);
    }

    /**
     * Parse string مفصول بـ | , / أو سطور جديدة إلى array من الأسماء النظيفة.
     *
     * يستخدم في ProductImport لتقسيم حقل Excel اللي قد يحتوي على عدة قيم.
     *
     * @param string|array|null $value
     * @return array<string>  أسماء فريدة بدون تكرار (case-insensitive deduplication)
     */
    public static function parseNames($value): array
    {
        if (empty($value)) return [];

        // لو array
        if (is_array($value)) {
            $parts = array_map('strval', $value);
        } else {
            $string = (string) $value;
            // لو JSON
            if (in_array(substr($string, 0, 1), ['[', '{'])) {
                $decoded = json_decode($string, true);
                if (is_array($decoded)) {
                    $parts = array_map('strval', $decoded);
                } else {
                    $parts = [$string];
                }
            } else {
                // تقسيم على | , / أو سطر جديد
                $parts = preg_split('/[|,\/\n\r]+/', $string) ?: [];
            }
        }

        // تنظيف + إزالة التكرار
        $cleaned = [];
        foreach ($parts as $part) {
            $trimmed = trim($part);
            if ($trimmed === '') continue;
            $key = mb_strtolower($trimmed);
            if (!isset($cleaned[$key])) {
                $cleaned[$key] = $trimmed;
            }
        }

        return array_values($cleaned);
    }

    public function scopeVisibleTo($query, $userId = null)
    {
        return $query;
    }
}
