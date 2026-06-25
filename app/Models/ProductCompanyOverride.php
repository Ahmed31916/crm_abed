<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCompanyOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'company_id',

        // Product overrides
        'price_override',
        'sale_price_override',
        'stock_quantity_override',
        'stock_status_override',
        'is_visible',

        // Health Product overrides (من القديم ProductMerchantOverride)
        'description',
        'contraindications',
        'research_links',
        'category_id',

        // Practitioner exclusive fields
        'practitioner_notes',
        'custom_primary_indications',
        'custom_dosing_notes',

        // Primary indications override
        'primary_indications',

        // Dosing schedule override
        'dosing_upon_rising',
        'dosing_breakfast',
        'dosing_between_meals_am',
        'dosing_lunch',
        'dosing_between_meals_pm',
        'dosing_dinner',
        'dosing_before_sleep',
        'dosing_na',
    ];

    protected $casts = [
        'is_visible'                  => 'boolean',
        'price_override'              => 'decimal:2',
        'sale_price_override'         => 'decimal:2',
        'stock_quantity_override'     => 'integer',
        'dosing_na'                   => 'boolean',
        'primary_indications'         => 'array',
        'custom_primary_indications'  => 'array',
    ];

    // =========================================================================
    // =========== Relationships ===============================================
    // =========================================================================

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function company()
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    // =========================================================================
    // =========== Product Override Getters ====================================
    // =========================================================================

    /**
     * Get the effective price (override or original)
     */
    public function getEffectivePrice()
    {
        return $this->price_override ?? $this->product->price;
    }

    /**
     * Get the effective sale price (override or original)
     */
    public function getEffectiveSalePrice()
    {
        return $this->sale_price_override ?? $this->product->sale_price;
    }

    /**
     * Get the effective stock quantity (override or original)
     */
    public function getEffectiveStock()
    {
        return $this->stock_quantity_override ?? $this->product->stock_quantity;
    }

    /**
     * Get the effective stock status (override or original)
     */
    public function getEffectiveStockStatus()
    {
        return $this->stock_status_override ?? $this->product->stock_status;
    }

    // =========================================================================
    // =========== Health Override Getters =====================================
    // =========================================================================

    /**
     * Get the effective description (override or original health product)
     */
    public function getEffectiveDescription(): ?string
    {
        if ($this->description !== null) {
            return $this->description;
        }

        return $this->product->healthProduct?->description
            ?? $this->product->description;
    }

    /**
     * Get the effective contraindications (override or original)
     */
    public function getEffectiveContraindications(): ?string
    {
        if ($this->contraindications !== null) {
            return $this->contraindications;
        }

        return $this->product->healthProduct?->contraindications;
    }

    /**
     * Get the effective research links (override or original)
     */
    public function getEffectiveResearchLinks(): ?string
    {
        if ($this->research_links !== null) {
            return $this->research_links;
        }

        return $this->product->healthProduct?->research_links;
    }

    /**
     * Get the effective category (override or original)
     */
    public function getEffectiveCategoryId(): ?int
    {
        if ($this->category_id !== null) {
            return $this->category_id;
        }

        return $this->product->category_id;
    }

    /**
     * Get the effective dosing schedule (override or original)
     * يرجع null إذا dosing_na = true
     */
    public function getEffectiveDosingSchedule(): ?array
    {
        $health = $this->product->healthProduct;

        // إذا الـ override يعطل الجرعات
        if ($this->dosing_na) {
            return null;
        }

        $schedule = [];

        // Override أولاً ثم fallback للـ health product
        $fields = [
            'upon_rising'      => 'dosing_upon_rising',
            'breakfast'        => 'dosing_breakfast',
            'between_meals_am' => 'dosing_between_meals_am',
            'lunch'            => 'dosing_lunch',
            'between_meals_pm' => 'dosing_between_meals_pm',
            'dinner'           => 'dosing_dinner',
            'before_sleep'     => 'dosing_before_sleep',
        ];

        foreach ($fields as $key => $field) {
            $value = $this->$field ?? $health?->$field;
            if (!empty($value)) {
                $schedule[$key] = $value;
            }
        }

        return !empty($schedule) ? $schedule : null;
    }

    /**
     * Get the effective dosing_na status
     */
    public function getEffectiveDosingNa(): bool
    {
        if ($this->dosing_na !== null) {
            return $this->dosing_na;
        }

        return $this->product->healthProduct?->dosing_na ?? false;
    }

    /**
     * Get the effective primary indications (override or original)
     */
    public function getEffectivePrimaryIndications(): ?array
    {
        if ($this->primary_indications !== null) {
            return $this->primary_indications;
        }

        return $this->product->healthProduct?->primary_indications;
    }

    /**
     * Get the effective practitioner notes (override only - لا يوجد fallback)
     * هذا حقل حصري للشركة
     */
    public function getEffectivePractitionerNotes(): ?string
    {
        return $this->practitioner_notes;
    }

    /**
     * Get the effective custom primary indications (override only)
     */
    public function getEffectiveCustomPrimaryIndications(): ?array
    {
        return $this->custom_primary_indications;
    }

    /**
     * Get the effective custom dosing notes (override only)
     */
    public function getEffectiveCustomDosingNotes(): ?string
    {
        return $this->custom_dosing_notes;
    }
}
