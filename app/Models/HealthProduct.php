<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * HealthProduct — UPDATED
 *
 * ────────────────────────────────────────────────────────────────────────
 * آخر تحديث:
 * ────────────────────────────────────────────────────────────────────────
 * - تمت إضافة حقل `practitioner_notes` إلى $fillable (نُقل من ProductCompanyOverride).
 * - تمت إضافة حقلَي `custom_primary_indications` و `custom_dosing_notes`
 *   (نُقلا من ProductCompanyOverride إلى هنا).
 * - العمود `primary_indications` القديم ما يزال deprecated — استخدم العلاقة belongsToMany.
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
        'ingredients',
        'contraindications',
        'research_links',

        // Additional Info
        'supports',
        'useful_for',

        // ⚡ NEW: Practitioner Notes (نُقل من ProductCompanyOverride)
        'practitioner_notes',

        // ⚡ NEW: Custom fields (نُقلا من ProductCompanyOverride)
        'custom_primary_indications',
        'custom_dosing_notes',

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
        'dosing_na'                    => 'boolean',
        'custom_primary_indications'   => 'array',
        'created_at'                   => 'datetime',
        'updated_at'                   => 'datetime',
    ];

    // =========================================================================
    // =========== Relationships =============================================
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
    // =========== Many-to-Many Primary Indications ==========================
    // =========================================================================

    public function primaryIndications(): BelongsToMany
    {
        return $this->product->primaryIndications();
    }

    public function getPrimaryIndicationsAttribute(): array
    {
        if (!$this->product_id) {
            return [];
        }
        if ($this->relationLoaded('product') && $this->product) {
            return $this->product->primaryIndications()
                ->pluck('name')
                ->toArray();
        }
        return \App\Models\PrimaryIndication::whereHas('products', function ($q) {
            $q->where('products.id', $this->product_id);
        })->pluck('name')->toArray();
    }

    public function getIndicationsStringAttribute(): string
    {
        $names = $this->primary_indications;
        return !empty($names) ? implode(', ', $names) : '';
    }

    // =========================================================================
    // =========== Accessors =================================================
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
    // =========== Scopes ====================================================
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
              ->orWhere('practitioner_notes', 'like', "%{$term}%")
              ->orWhere('custom_dosing_notes', 'like', "%{$term}%")
              ->orWhereHas('product', function ($pq) use ($term) {
                  $pq->where('name', 'like', "%{$term}%");
              })
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
    // =========== Helper Methods ============================================
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
