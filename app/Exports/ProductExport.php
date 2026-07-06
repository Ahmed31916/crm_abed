<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * ProductExport — تصدير المنتجات بنفس أعمدة ملف الاستيراد
 *
 * ────────────────────────────────────────────────────────────────────────
 * الميزات:
 * ────────────────────────────────────────────────────────────────────────
 *   - يصدّر كل الأعمدة الموجودة في ملف الاستيراد (نفس الـ headers)
 *   - يدعم السياق: شركة معينة (منتجاتها + منتجات السوبر ادمن)
 *     أو سوبر ادمن (كل المنتجات)
 *   - تنسيق Bottle Size / Unit Count كحقل واحد مدمج (مثل الاستيراد)
 *   - تصدير tags + primary_indications + custom_primary_indications
 *     كنص مفصول بفواصل
 *
 * ────────────────────────────────────────────────────────────────────────
 * الأعمدة المُصدّرة (بنفس ترتيب ملف الاستيراد):
 * ────────────────────────────────────────────────────────────────────────
 *   1.  SKU
 *   2.  Product Name
 *   3.  Full Name
 *   4.  Category
 *   5.  Supplier
 *   6.  Regular Price
 *   7.  Sale Price
 *   8.  Description
 *   9.  Is Active
 *   10. Product Image URL
 *   11. Bottle Size / Unit Count
 *   12. Product Form
 *   13. Ingredients
 *   14. Contraindications
 *   15. Research / Studies / Article Links
 *   16. Supports
 *   17. Useful For
 *   18. Practitioner Notes
 *   19. Upon Rising
 *   20. Breakfast
 *   21. Between Meals (AM)
 *   22. Lunch
 *   23. Between Meals (PM)
 *   24. Dinner
 *   25. Before Sleep
 *   26. Tags
 *   27. Primary Indications
 *   28. Custom Primary Indications
 *   29. Custom Dosing Notes
 *   30. Frequency
 *
 * ────────────────────────────────────────────────────────────────────────
 */
class ProductExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    /**
     * معرّف الشركة المُصدّرة لها.
     * - null → السوبر ادمن (يصدّر كل المنتجات)
     * - int  → شركة (يصدّر منتجات الشركة + منتجات السوبر ادمن)
     */
    protected ?int $companyId;

    /**
     * معرّف السوبر ادمن (لعرض منتجاته للشركات)
     */
    protected ?int $superAdminId;

    public function __construct(?int $companyId = null, ?int $superAdminId = null)
    {
        $this->companyId    = $companyId;
        $this->superAdminId = $superAdminId;
    }

    /**
     * جلب المنتجات المطلوب تصديرها.
     */
    public function collection(): Collection
    {
        $query = Product::with([
            'category',
            'brand',
            'healthProduct',
            'tags',
            'primaryIndications',
            'creator',
        ]);

        // لو شركة معينة: نصدّر منتجاتها + منتجات السوبر ادمن فقط
        if ($this->companyId && $this->superAdminId) {
            $query->whereIn('created_by', [$this->companyId, $this->superAdminId]);
        }

        // ترتيب: منتجات الشركة أولاً (لو موجودة) ثم منتجات السوبر ادمن
        if ($this->companyId) {
            $query->orderByRaw('CASE WHEN created_by = ? THEN 0 ELSE 1 END', [$this->companyId]);
        }

        $query->orderBy('id', 'desc');

        return $query->get();
    }

    /**
     * عناوين الأعمدة — مطابقة لـ mلف الاستيراد.
     */
    public function headings(): array
    {
        return [
            'SKU',
            'Product Name',
            'Full Name',
            'Category',
            'Supplier',
            'Regular Price',
            'Sale Price',
            'Description',
            'Is Active',
            'Product Image URL',
            'Bottle Size / Unit Count',
            'Product Form',
            'Ingredients',
            'Contraindications',
            'Research / Studies / Article Links',
            'Supports',
            'Useful For',
            'Practitioner Notes',
            'Upon Rising',
            'Breakfast',
            'Between Meals (AM)',
            'Lunch',
            'Between Meals (PM)',
            'Dinner',
            'Before Sleep',
            'Tags',
            'Primary Indications',
            'Custom Primary Indications',
            'Custom Dosing Notes',
            'Frequency',
        ];
    }

    /**
     * map: تحويل كل منتج إلى صف في الإكسل.
     */
    public function map($product): array
    {
        $health = $product->healthProduct;

        // Bottle Size / Unit Count مدموج كحقل واحد
        $bottleSizeUnit = '';
        if ($health && $health->bottle_size) {
            $unit = $health->bottle_size_unit ?? '';
            $bottleSizeUnit = $unit
                ? $health->bottle_size . ' ' . $unit
                : (string) $health->bottle_size;
        }

        // Tags (كـ comma-separated) — نستخدم tags المرئية للمستخدم الحالي
        $tags = '';
        if ($this->companyId && method_exists($product, 'getTagNames')) {
            $tagNames = $product->getTagNames($this->companyId);
            $tags = !empty($tagNames) ? implode(', ', $tagNames) : '';
        } else {
            $tags = $product->tags->isNotEmpty()
                ? $product->tags->pluck('name')->implode(', ')
                : '';
        }

        // Primary Indications (كـ comma-separated)
        $primaryIndications = $product->primaryIndications && $product->primaryIndications->isNotEmpty()
            ? $product->primaryIndications->pluck('name')->implode(', ')
            : '';

        // Custom Primary Indications (كـ comma-separated)
        $customPrimaryIndications = '';
        if ($health && !empty($health->custom_primary_indications)) {
            $arr = $health->custom_primary_indications;
            $customPrimaryIndications = is_array($arr)
                ? implode(', ', $arr)
                : (string) $arr;
        }

        return [
            // 1. SKU
            $product->sku ?? '',

            // 2. Product Name
            $product->name ?? '',

            // 3. Full Name
            $health?->full_name ?? '',

            // 4. Category
            $product->category?->name ?? '',

            // 5. Supplier / Brand
            $product->brand?->name ?? '',

            // 6. Regular Price (price)
            $product->price ?? '',

            // 7. Sale Price
            $product->sale_price ?? '',

            // 8. Description
            $product->description ?? '',

            // 9. Is Active
            $product->status === 'active' ? 'true' : 'false',

            // 10. Product Image URL
            $health?->product_image_url ?? '',

            // 11. Bottle Size / Unit Count
            $bottleSizeUnit,

            // 12. Product Form
            $health?->product_form ?? '',

            // 13. Ingredients
            $health?->ingredients ?? '',

            // 14. Contraindications
            $health?->contraindications ?? '',

            // 15. Research / Studies / Article Links
            $health?->research_links ?? '',

            // 16. Supports
            $health?->supports ?? '',

            // 17. Useful For
            $health?->useful_for ?? '',

            // 18. Practitioner Notes
            $health?->practitioner_notes ?? '',

            // 19-25. Dosing fields
            $health?->dosing_upon_rising ?? '',
            $health?->dosing_breakfast ?? '',
            $health?->dosing_between_meals_am ?? '',
            $health?->dosing_lunch ?? '',
            $health?->dosing_between_meals_pm ?? '',
            $health?->dosing_dinner ?? '',
            $health?->dosing_before_sleep ?? '',

            // 26. Tags
            $tags,

            // 27. Primary Indications
            $primaryIndications,

            // 28. Custom Primary Indications
            $customPrimaryIndications,

            // 29. Custom Dosing Notes
            $health?->custom_dosing_notes ?? '',

            // 30. Frequency
            $product->frequency ?? '',
        ];
    }

    /**
     * تنسيق الـ styles — عرض عريض للعناوين + لون خلفية.
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFD3E3FF'],
                    'endColor'   => ['argb' => 'FFD3E3FF'],
                ],
            ],
        ];
    }

    /**
     * عرض الأعمدة — لتحسين القراءة.
     */
    public function columnWidths(): array
    {
        return [
            'A' => 18,   // SKU
            'B' => 30,   // Product Name
            'C' => 30,   // Full Name
            'D' => 20,   // Category
            'E' => 20,   // Supplier
            'F' => 14,   // Regular Price
            'G' => 14,   // Sale Price
            'H' => 40,   // Description
            'I' => 10,   // Is Active
            'J' => 30,   // Product Image URL
            'K' => 18,   // Bottle Size / Unit Count
            'L' => 14,   // Product Form
            'M' => 35,   // Ingredients
            'N' => 35,   // Contraindications
            'O' => 40,   // Research Links
            'P' => 25,   // Supports
            'Q' => 25,   // Useful For
            'R' => 35,   // Practitioner Notes
            'S' => 18,   // Upon Rising
            'T' => 18,   // Breakfast
            'U' => 18,   // Between Meals AM
            'V' => 18,   // Lunch
            'W' => 18,   // Between Meals PM
            'X' => 18,   // Dinner
            'Y' => 18,   // Before Sleep
            'Z' => 30,   // Tags
            'AA' => 35,  // Primary Indications
            'AB' => 35,  // Custom Primary Indications
            'AC' => 35,  // Custom Dosing Notes
            'AD' => 20,  // Frequency
        ];
    }
}
