<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * HealthProduct — UPDATED for Many-to-Many Primary Indications
 *
 * ────────────────────────────────────────────────────────────────────────
 * ما تغيّر:
 * ────────────────────────────────────────────────────────────────────────
 * - العمود `primary_indications` يبقى في قاعدة البيانات لأغراض التوافق،
 *   لكنه يُعتبر "deprecated" — لا تقرأ منه ولا تكتب إليه في الكود الجديد.
 * - تم حذفه من $fillable و $casts (لمنع الكتابة إليه عن طريق الخطأ).
 * - تم إضافة علاقة belongsToMany مع PrimaryIndication عبر المنتج المرتبط.
 * - تم إضافة accessor `primary_indications` (مُعاد تعريفه) يقرأ من العلاقة
 *   الجديدة بدلاً من الـ JSON القديم. هذا يحافظ على عمل أي كود قديم يصل
 *   عبر `$healthProduct->primary_indications` لكنه يرجع البيانات الصحيحة.
 *
 * ────────────────────────────────────────────────────────────────────────
 * مهم عند الدمج مع الكود الأصلي:
 * ────────────────────────────────────────────────────────────────────────
 *   1. احذف `'primary_indications'` من $fillable.
 *   2. احذف `'primary_indications' => 'array'` من $casts.
 *   3. أضِف الكود في القسم "NEW: Many-to-Many Primary Indications".
 *   4. احتفظ بجميع العلاقات والـ accessors الـ existing الأخرى كما هي.
 * ────────────────────────────────────────────────────────────────────────
 */
class HealthProduct extends Model
{
    use HasFactory;

    protected $table = 'health_products';

    protected $fillable = [
        'product_id',
        'created_by',

        // General Product Info
        'sku',
        'product_form',
        'bottle_size',
        'bottle_size_unit',
        'product_image_url',

        // Content & Descriptions
        // ⚠️ تم إزالة: 'primary_indications' — لم يعد يُستخدم
        'ingredients',
        'contraindications',
        'research_links',

        // Additional Info
        'supports',
        'useful_for',

        // Dosing Schedule
        'dosing_upon_rising',
        'dosing_breakfast',
        'dosing_between_meals_am',
        'dosing_lunch',
        'dosing_between_meals_pm',
        'dosing_dinner',
        'dosing_before_sleep',
        'dosing_na',

        // Full Name
        'full_name',
    ];

    protected $casts = [
        // ⚠️ تم إزالة: 'primary_indications' => 'array'
        'dosing_na'   => 'boolean',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    // =========================================================================
    // =========== Relationships (existing — keep as-is) =====================
    // =========================================================================

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function companyOverride()
    {
        return $this->hasOne(ProductCompanyOverride::class, 'product_id', 'product_id')
            ->where('company_id', createdBy());
    }

    // =========================================================================
    // =========== NEW: Many-to-Many Primary Indications =====================
    // =========================================================================

    /**
     * العلاقة Many-to-Many مع PrimaryIndication (عبر المنتج المرتبط).
     *
     * يستخدم هذا للتغلب على حقيقة أن الـ primary_indications مرتبطة
     * بالـ Product وليس بـ HealthProduct مباشرة.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function primaryIndications(): BelongsToMany
    {
        // نمر عبر المنتج للوصول إلى pivot
        return $this->product->primaryIndications();
    }

    /**
     * Accessor: primary_indications كـ array من الأسماء.
     *
     * هذا الـ accessor يحل محل قراءة الـ JSON القديم من قاعدة البيانات.
     * الكود القديم الذي يستخدم `$healthProduct->primary_indications`
     * سيستمر في العمل لكنه يجلب البيانات من العلاقة الجديدة.
     *
     * @return array<string>
     */
    public function getPrimaryIndicationsAttribute(): array
    {
        // لو الـ product غير محمّل، ارجع []
        if (!$this->product_id) {
            return [];
        }

        // استخدم العلاقة عبر المنتج
        if ($this->relationLoaded('product') && $this->product) {
            return $this->product->primaryIndications()
                ->pluck('name')
                ->toArray();
        }

        // fallback لو product غير محمّل — استعلم مباشرة
        return \App\Models\PrimaryIndication::whereHas('products', function ($q) {
            $q->where('products.id', $this->product_id);
        })->pluck('name')->toArray();
    }

    /**
     * Accessor (قديم - تم استبداله): نص الـ indications مفصول بفواصل.
     *
     * @return string
     */
    public function getIndicationsStringAttribute(): string
    {
        $names = $this->primary_indications; // يستخدم الـ accessor الجديد
        return !empty($names) ? implode(', ', $names) : '';
    }

    // =========================================================================
    // =========== Existing Accessors (keep as-is) ============================
    // =========================================================================

    public function getDosingScheduleAttribute(): ?array
    {
        if ($this->dosing_na) {
            return null;
        }

        $schedule = [];
        if (!empty($this->dosing_upon_rising))      $schedule['upon_rising']      = $this->dosing_upon_rising;
        if (!empty($this->dosing_breakfast))         $schedule['breakfast']        = $this->dosing_breakfast;
        if (!empty($this->dosing_between_meals_am))  $schedule['between_meals_am'] = $this->dosing_between_meals_am;
        if (!empty($this->dosing_lunch))             $schedule['lunch']            = $this->dosing_lunch;
        if (!empty($this->dosing_between_meals_pm))  $schedule['between_meals_pm'] = $this->dosing_between_meals_pm;
        if (!empty($this->dosing_dinner))            $schedule['dinner']           = $this->dosing_dinner;
        if (!empty($this->dosing_before_sleep))      $schedule['before_sleep']     = $this->dosing_before_sleep;

        return !empty($schedule) ? $schedule : null;
    }

    public function getProductFormAttribute(): string
    {
        if (isset($this->attributes['product_form']) && !empty($this->attributes['product_form'])) {
            return $this->attributes['product_form'];
        }
        return ($this->bottle_size_unit ?? '') === 'caps' ? 'Caps' : 'Liquid';
    }

    // =========================================================================
    // =========== Existing Scopes (keep as-is) ===============================
    // =========================================================================

    public function scopeWithContraindications($query)
    {
        return $query->whereNotNull('contraindications')
                     ->where('contraindications', '!=', '')
                     ->where('contraindications', '!=', 'N/A');
    }

    public function scopeForCompany($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }

    public function scopeWithDosingSchedule($query)
    {
        return $query->where('dosing_na', false)
                     ->where(function ($q) {
                         $q->whereNotNull('dosing_upon_rising')
                           ->orWhereNotNull('dosing_breakfast')
                           ->orWhereNotNull('dosing_lunch')
                           ->orWhereNotNull('dosing_dinner')
                           ->orWhereNotNull('dosing_before_sleep');
                     });
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('sku', 'like', "%{$term}%")
              ->orWhere('ingredients', 'like', "%{$term}%")
              ->orWhere('supports', 'like', "%{$term}%")
              ->orWhere('useful_for', 'like', "%{$term}%")
              ->orWhereHas('product', function ($pq) use ($term) {
                  $pq->where('name', 'like', "%{$term}%");
              })
              // NEW: بحث في الـ primary indications عبر العلاقة الجديدة
              ->orWhereHas('product.primaryIndications', function ($piq) use ($term) {
                  $piq->where('name', 'like', "%{$term}%");
              });
        });
    }

    public function scopeVisibleTo($query, $userId)
    {
        $superAdminId = getSuperAdminCompanyId();
        return $query->where(function ($q) use ($userId, $superAdminId) {
            $q->where('created_by', $userId)
              ->orWhere('created_by', $superAdminId);
        });
    }

    // =========================================================================
    // =========== Existing Helper Methods (keep as-is) =======================
    // =========================================================================

    public function hasPregnancyWarning(): bool
    {
        return str_contains(strtolower($this->contraindications ?? ''), 'pregnancy');
    }

    public function getActiveDosingTimes(): array
    {
        if ($this->dosing_na) return [];
        $schedule = [
            'upon_rising'      => $this->dosing_upon_rising,
            'breakfast'        => $this->dosing_breakfast,
            'between_meals_am' => $this->dosing_between_meals_am,
            'lunch'            => $this->dosing_lunch,
            'between_meals_pm' => $this->dosing_between_meals_pm,
            'dinner'           => $this->dosing_dinner,
            'before_sleep'     => $this->dosing_before_sleep,
        ];
        return array_filter($schedule, fn($value) => !empty($value));
    }

    public function getActiveDosingCountAttribute(): int
    {
        return count($this->getActiveDosingTimes());
    }

    public function hasIngredient(string $ingredient): bool
    {
        if (!$this->ingredients) return false;
        $ingredientsList = array_map('trim', explode(',', strtolower($this->ingredients)));
        return in_array(strtolower($ingredient), $ingredientsList);
    }

    public function supportsSystem(string $system): bool
    {
        if (!$this->supports) return false;
        return str_contains(strtolower($this->supports), strtolower($system));
    }

    public function isSuperAdminRecord(): bool
    {
        return $this->created_by == getSuperAdminCompanyId();
    }

    public static function getForProduct(int $productId, int $companyId): ?self
    {
        $superAdminId = getSuperAdminCompanyId();

        $health = self::where('product_id', $productId)
            ->where('created_by', $companyId)
            ->first();

        if ($health) return $health;

        return self::where('product_id', $productId)
            ->where('created_by', $superAdminId)
            ->first();
    }
}
