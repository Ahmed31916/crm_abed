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

        // Health Product overrides
        'description',
        'contraindications',
        'research_links',
        'category_id',

        // Practitioner exclusive fields
        'practitioner_notes',
        'custom_primary_indications',
        'custom_dosing_notes',

        // ⚠️ Primary indications override — يبقى كـ JSON array من الأسماء
        // (وليس IDs) لأنه قد يحتوي على indications مخصصة للشركة
        // غير موجودة في جدول primary_indications.
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
    // =========== Getters (Existing — keep as-is) ============================
    // =========================================================================

    public function getEffectivePrice()
    {
        return $this->price_override ?? $this->product->price;
    }

    public function getEffectiveSalePrice()
    {
        return $this->sale_price_override ?? $this->product->sale_price;
    }

    public function getEffectiveStock()
    {
        return $this->stock_quantity_override ?? $this->product->stock_quantity;
    }

    public function getEffectiveStockStatus()
    {
        return $this->stock_status_override ?? $this->product->stock_status;
    }

    public function getEffectiveDescription(): ?string
    {
        if ($this->description !== null) {
            return $this->description;
        }
        return $this->product->healthProduct?->description
            ?? $this->product->description;
    }

    public function getEffectiveContraindications(): ?string
    {
        if ($this->contraindications !== null) {
            return $this->contraindications;
        }
        return $this->product->healthProduct?->contraindications;
    }

    public function getEffectiveResearchLinks(): ?string
    {
        if ($this->research_links !== null) {
            return $this->research_links;
        }
        return $this->product->healthProduct?->research_links;
    }

    public function getEffectiveCategoryId(): ?int
    {
        if ($this->category_id !== null) {
            return $this->category_id;
        }
        return $this->product->category_id;
    }

    public function getEffectiveDosingSchedule(): ?array
    {
        $health = $this->product->healthProduct;
        if ($this->dosing_na) return null;

        $schedule = [];
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

    public function getEffectiveDosingNa(): bool
    {
        if ($this->dosing_na !== null) {
            return $this->dosing_na;
        }
        return $this->product->healthProduct?->dosing_na ?? false;
    }

    /**
     * Get the effective primary indications (override or original).
     *
     * الـ override يخزّن array من الأسماء (strings).
     * الـ original (لو ما في override) يقرأ من belongsToMany على المنتج.
     *
     * @return array<string>
     */
    public function getEffectivePrimaryIndications(): array
    {
        if ($this->primary_indications !== null) {
            $indications = $this->primary_indications;
            return is_array($indications) ? array_values($indications) : [];
        }

        // fallback: اقرأ من العلاقة الجديدة على المنتج
        return $this->product?->primaryIndications()
            ->pluck('name')
            ->toArray() ?? [];
    }

    public function getEffectivePractitionerNotes(): ?string
    {
        return $this->practitioner_notes;
    }

    public function getEffectiveCustomPrimaryIndications(): ?array
    {
        return $this->custom_primary_indications;
    }

    public function getEffectiveCustomDosingNotes(): ?string
    {
        return $this->custom_dosing_notes;
    }
}
