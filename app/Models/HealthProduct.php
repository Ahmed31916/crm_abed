<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * HealthProduct - البيانات الصحية للمنتجات
 *
 * النسخة المتكيفة مع المشروع الجديد (CRM)
 *
 * التعديلات عن المشروع القديم:
 * ──────────────────────────────────────────────────────────────────────
 * | القديم                          | الجديد
 * | store_id                        | created_by
 * | store() → belongsTo(Store)      | creator() → belongsTo(User, 'created_by')
 * | merchantOverride() with store_id | companyOverride() with company_id
 * | getCurrentStore()               | createdBy()
 * ──────────────────────────────────────────────────────────────────────
 */
class HealthProduct extends Model
{
    use HasFactory;

    protected $table = 'health_products';

    protected $fillable = [
        'product_id',
        'created_by',      // ← القديم: store_id

        // General Product Info
        'sku',
        'product_form',
        'bottle_size',
        'bottle_size_unit',
        'product_image_url',

        // Content & Descriptions
        'primary_indications',
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
        'primary_indications' => 'array',
        'dosing_na' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =========================================================================
    // =========== Relationships ===============================================
    // =========================================================================

    /**
     * المنتج المرتبط
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * المستخدم الذي أنشأ السجل
     * القديم: store() → belongsTo(Store::class)
     * الجديد: creator() → belongsTo(User::class, 'created_by')
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Override الخاص بالشركة على هذا المنتج
     * القديم: merchantOverride() with store_id / getCurrentStore()
     * الجديد: companyOverride() with company_id / createdBy()
     */
    public function companyOverride()
    {
        return $this->hasOne(ProductCompanyOverride::class, 'product_id', 'product_id')
            ->where('company_id', createdBy());
    }

    // =========================================================================
    // =========== Accessors (Getters) ========================================
    // =========================================================================

    /**
     * جدول الجرعات منسق (snake_case keys مثل الـ API القديم)
     *
     * @return array|null
     */
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

    /**
     * الدلالات الرئيسية كنص مفصول بفواصل
     */
    public function getIndicationsStringAttribute(): string
    {
        return $this->primary_indications
            ? implode(', ', $this->primary_indications)
            : '';
    }

    /**
     * شكل المنتج من وحدة القياس
     * Caps أو Liquid
     */
    public function getProductFormAttribute(): string
    {
        if (isset($this->attributes['product_form']) && !empty($this->attributes['product_form'])) {
            return $this->attributes['product_form'];
        }

        return ($this->bottle_size_unit ?? '') === 'caps' ? 'Caps' : 'Liquid';
    }

    // =========================================================================
    // =========== Scopes (Queries) ============================================
    // =========================================================================

    /**
     * المنتجات اللي فيها موانع استعمال
     */
    public function scopeWithContraindications($query)
    {
        return $query->whereNotNull('contraindications')
                     ->where('contraindications', '!=', '')
                     ->where('contraindications', '!=', 'N/A');
    }

    /**
     * فلترة حسب المستخدم (الشركة أو السوبر أدمن)
     * القديم: scopeForStore($query, int $storeId)
     * الجديد: scopeForCompany($query, int $userId)
     */
    public function scopeForCompany($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * المنتجات اللي لها جدول جرعات
     */
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

    /**
     * بحث في المنتجات الصحية
     * يبحث في: sku, ingredients, supports, useful_for + اسم المنتج عبر relationship
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
                $q->where('sku', 'like', "%{$term}%")
                  ->orWhere('ingredients', 'like', "%{$term}%")
                  ->orWhere('supports', 'like', "%{$term}%")
                  ->orWhere('useful_for', 'like', "%{$term}%")
                  ->orWhereHas('product', function ($pq) use ($term) {
                      $pq->where('name', 'like', "%{$term}%");
                  });
            });
    }

    /**
     * البيانات الصحية المرئية لمستخدم معين
     * (بياناته + بيانات السوبر أدمن)
     */
    public function scopeVisibleTo($query, $userId)
    {
        $superAdminId = getSuperAdminCompanyId();

        return $query->where(function ($q) use ($userId, $superAdminId) {
            $q->where('created_by', $userId)
              ->orWhere('created_by', $superAdminId);
        });
    }

    // =========================================================================
    // =========== Helper Methods =============================================
    // =========================================================================

    /**
     * هل يوجد تحذير حمل؟
     */
    public function hasPregnancyWarning(): bool
    {
        return str_contains(strtolower($this->contraindications ?? ''), 'pregnancy');
    }

    /**
     * أوقات الجرعة الفعالة (غير الفارغة)
     */
    public function getActiveDosingTimes(): array
    {
        if ($this->dosing_na) {
            return [];
        }

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

    /**
     * عدد أوقات الجرعة الفعالة
     */
    public function getActiveDosingCountAttribute(): int
    {
        return count($this->getActiveDosingTimes());
    }

    /**
     * هل المنتج يحتوي على مكون معين؟
     */
    public function hasIngredient(string $ingredient): bool
    {
        if (!$this->ingredients) return false;

        $ingredientsList = array_map('trim', explode(',', strtolower($this->ingredients)));
        return in_array(strtolower($ingredient), $ingredientsList);
    }

    /**
     * هل يدعم نظام معين في الجسم؟
     */
    public function supportsSystem(string $system): bool
    {
        if (!$this->supports) return false;

        return str_contains(strtolower($this->supports), strtolower($system));
    }

    /**
     * هل هذا السجل تابع للسوبر أدمن؟
     */
    public function isSuperAdminRecord(): bool
    {
        return $this->created_by == getSuperAdminCompanyId();
    }

    /**
     * جلب البيانات الصحية لمنتج معين مع أولوية الشركة
     * الشركة تشوف بياناتها أولاً، إذا ما عندش يرجع لبيانات السوبر أدمن
     *
     * @param int $productId
     * @param int $companyId
     * @return HealthProduct|null
     */
    public static function getForProduct(int $productId, int $companyId): ?self
    {
        $superAdminId = getSuperAdminCompanyId();

        // الأولوية: بيانات الشركة
        $health = self::where('product_id', $productId)
            ->where('created_by', $companyId)
            ->first();

        if ($health) {
            return $health;
        }

        // Fallback: بيانات السوبر أدمن
        return self::where('product_id', $productId)
            ->where('created_by', $superAdminId)
            ->first();
    }
}
