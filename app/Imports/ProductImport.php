<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Tax;
use App\Models\Tag;
use App\Models\PrimaryIndication;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * ProductImport — UPDATED for Many-to-Many Tags, Category, Primary Indications
 *
 * ────────────────────────────────────────────────────────────────────────
 * ما الجديد في هذا التحديث:
 * ────────────────────────────────────────────────────────────────────────
 *
 * 1) Primary Indications (Many-to-Many via pivot `product_primary_indications`)
 *    - تقسيم القيمة على | , / أو سطر جديد (عبر PrimaryIndication::parseNames).
 *    - find-or-create لكل اسم في جدول primary_indications (case-insensitive).
 *    - ربط النتائج عبر $product->syncPrimaryIndications($ids).
 *
 * 2) Tags (Many-to-Many via `product_tags` مع created_by)
 *    - تقسيم القيمة على | , / أو سطر جديد.
 *    - find-or-create كل tag للـ user الحالي (case-insensitive lookup بالاسم).
 *    - ربط IDs عبر `product_tags` مع `created_by = $userId`.
 *    - التخزين بنفس نمط ProductController@store (DB::table('product_tags')->insert).
 *
 * 3) Category (BelongsTo — قيمة واحدة)
 *    - لو القيمة رقمية → نستخدمها مباشرة.
 *    - لو نصية → find-or-create في جدول categories (case-insensitive).
 *    - عند الإنشاء: نولّد slug ونضبط created_by = $userId.
 *    - لا يتم عمل ربط منفصل — كافٍ ضبط category_id على المنتج قبل Product::create.
 *
 * ملاحظات هامة:
 *   - جميع الحقول غير القابلة للتعبئة (primary_indication, tags) يتم استخراجها
 *     قبل Product::create لتجنب MassAssignmentException.
 *   - كل علاقة لها try/catch مستقل حتى لا يفشل الـ import كله لو فشل ربط واحد.
 *
 * الأعمدة المعروفة في headerMap (مع aliases):
 *   name, sku, description, price, stock_quantity, stock_status,
 *   category_id, brand_id, tax_id, status,
 *   primary_indication  (مفصول بـ | أو , أو /)
 *   tags                (مفصول بـ | أو , أو /)
 *
 * ────────────────────────────────────────────────────────────────────────
 */
class ProductImport implements ToCollection, WithHeadingRow
{
    protected $addedCount = 0;
    protected $skippedCount = 0;

    /**
     * Fuzzy header mapping: maps common header variations to database field names.
     * This makes the import resilient to different Excel file formats.
     */
    protected $headerMap = [
        'name' => ['name', 'product_name', 'product name', 'productname', 'title'],
        'sku' => ['sku', 'SKU', 'product_sku', 'product sku', 'item_code', 'code'],
        'description' => ['description', 'desc', 'product_description', 'details'],
        'price' => ['price', 'unit_price', 'unit price', 'selling_price', 'selling price', 'rate'],
        'stock_quantity' => ['stock_quantity', 'stock quantity', 'quantity', 'qty', 'stock', 'inventory'],
        'stock_status' => ['stock_status', 'stock status', 'availability'],
        'category_id' => ['category', 'category_id', 'category name', 'cat'],
        'brand_id' => ['brand', 'brand_id', 'brand name', 'supplier', 'supplier name'],
        'tax_id' => ['tax', 'tax_id', 'tax name', 'tax rate'],
        'status' => ['status', 'product_status', 'is_active', 'active'],
        // ─── Primary Indications (split by | , / or newline) ───
        'primary_indication' => [
            'primary_indication', 'primary indication',
            'primary_indications', 'primary indications',
            'indications', 'indication',
            'primary_indication_name', 'primary indication name',
        ],
        // ─── Tags (split by | , / or newline) ───
        'tags' => [
            'tags', 'tag', 'tag_name', 'tag name',
            'tag_names', 'tag names', 'product_tags', 'product tags',
        ],
    ];

    /**
     * Normalize a header string to match against known aliases.
     */
    protected function normalizeHeader($header)
    {
        $normalized = Str::lower(str_replace([' ', '_', '-'], '', $header));

        foreach ($this->headerMap as $field => $aliases) {
            foreach ($aliases as $alias) {
                if ($normalized === Str::lower(str_replace([' ', '_', '-'], '', $alias))) {
                    return $field;
                }
            }
        }

        return $header; // Return as-is if no match found
    }

    public function collection(Collection $rows)
    {
        $userId = $this->resolveUserId();

        foreach ($rows as $row) {
            $data = $row->toArray();

            // Remap headers using fuzzy matching
            $mapped = [];
            foreach ($data as $key => $value) {
                $mappedKey = $this->normalizeHeader($key);
                $mapped[$mappedKey] = $value;
            }

            // Validate required fields
            if (empty($mapped['name']) || empty($mapped['sku'])) {
                $this->skippedCount++;
                continue;
            }

            // Check for duplicate SKU
            if (Product::where('sku', $mapped['sku'])->exists()) {
                $this->skippedCount++;
                continue;
            }

            // Check product limit
            if (function_exists('hasReachedProductLimit') && hasReachedProductLimit()) {
                $this->skippedCount++;
                continue;
            }

            // ─────────────────────────────────────────────────────────────────────
            // Resolve Category — قيمة واحدة، find-or-create لو نصية
            // ─────────────────────────────────────────────────────────────────────
            $categoryId = $this->resolveCategory($mapped['category_id'] ?? null, $userId);
            $mapped['category_id'] = $categoryId;
            // ─────────────────────────────────────────────────────────────────────

            // Resolve brand by name
            if (isset($mapped['brand_id']) && !is_numeric($mapped['brand_id'])) {
                $brand = Brand::where('name', $mapped['brand_id'])
                    ->where('created_by', $userId)
                    ->first();
                $mapped['brand_id'] = $brand ? $brand->id : null;
            }

            // Resolve tax by name
            if (isset($mapped['tax_id']) && !is_numeric($mapped['tax_id'])) {
                $tax = Tax::where('name', $mapped['tax_id'])
                    ->where('created_by', $userId)
                    ->first();
                $mapped['tax_id'] = $tax ? $tax->id : null;
            }

            // Set defaults
            $mapped['created_by'] = $userId;
            $mapped['status'] = $mapped['status'] ?? 'active';
            $mapped['stock_quantity'] = $mapped['stock_quantity'] ?? 0;
            $mapped['stock_status'] = $mapped['stock_status'] ?? 'in_stock';

            // ─────────────────────────────────────────────────────────────────────
            // استخراج الحقول غير القابلة للتعبئة قبل Product::create
            // ─────────────────────────────────────────────────────────────────────
            $primaryIndicationRaw = null;
            if (isset($mapped['primary_indication'])) {
                $primaryIndicationRaw = $mapped['primary_indication'];
                unset($mapped['primary_indication']);
            }

            $tagsRaw = null;
            if (isset($mapped['tags'])) {
                $tagsRaw = $mapped['tags'];
                unset($mapped['tags']);
            }
            // ─────────────────────────────────────────────────────────────────────

            // Clean up non-fillable fields
            unset($mapped['category'], $mapped['brand'], $mapped['tax'], $mapped['supplier'], $mapped['supplier_name']);

            try {
                $product = Product::create($mapped);
                $this->addedCount++;

                // ═══════════════════════════════════════════════════════════════════════
                // ⚡ Primary Indications — parse + find-or-create + sync on pivot
                // ═══════════════════════════════════════════════════════════════════════
                $this->attachPrimaryIndications($product, $primaryIndicationRaw);
                // ═══════════════════════════════════════════════════════════════════════

                // ═══════════════════════════════════════════════════════════════════════
                // ⚡ Tags — parse + find-or-create + attach on product_tags pivot
                // ═══════════════════════════════════════════════════════════════════════
                $this->attachTags($product, $tagsRaw, $userId);
                // ═══════════════════════════════════════════════════════════════════════

            } catch (\Exception $e) {
                \Log::error('Product import failed for SKU: ' . $mapped['sku'], [
                    'error' => $e->getMessage(),
                ]);
                $this->skippedCount++;
            }
        }
    }

    // =========================================================================
    // =========== Helpers: User Resolution ===================================
    // =========================================================================

    /**
     * الحصول على معرّف المستخدم الحالي.
     * يفضّل auth()->id()، ولو ما فيش مستخدم مسجّل يستخدم createdBy().
     */
    private function resolveUserId(): int
    {
        $id = auth()->id();
        if ($id) return (int) $id;

        if (function_exists('createdBy')) {
            return (int) createdBy();
        }

        throw new \RuntimeException('ProductImport requires an authenticated user or createdBy() helper.');
    }

    // =========================================================================
    // =========== Helpers: Category (single value, find-or-create) ===========
    // =========================================================================

    /**
     * تحويل قيمة category إلى ID.
     *
     *  - لو null/فارغة → null
     *  - لو رقمية → (int) مباشرة
     *  - لو نصية:
     *      1. بحث case-insensitive ضمن فئات نفس المستخدم أو فئات السوبر ادمن
     *      2. لو موجودة → إرجاع id
     *      3. لو غير موجودة → إنشاء category جديد بـ slug و created_by
     *
     * @param mixed    $value
     * @param int      $userId
     * @return int|null
     */
    private function resolveCategory($value, int $userId): ?int
    {
        if (empty($value)) return null;

        // لو رقمي → استخدمه مباشرة
        if (is_numeric($value)) {
            $id = (int) $value;
            // تحقق من وجوده فعلاً (تجنّب ربط منتج بفئة محذوفة)
            return Category::where('id', $id)->exists() ? $id : null;
        }

        $name = trim((string) $value);
        if ($name === '') return null;

        // 1) بحث case-insensitive ضمن فئات المستخدم أو السوبر ادمن
        $superAdminId = function_exists('getSuperAdminCompanyId') ? getSuperAdminCompanyId() : null;

        $existing = Category::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->where(function ($q) use ($userId, $superAdminId) {
                $q->where('created_by', $userId);
                if ($superAdminId) {
                    $q->orWhere('created_by', $superAdminId);
                }
            })
            ->first();

        if ($existing) {
            return $existing->id;
        }

        // 2) إنشاء category جديد
        try {
            $slug = $this->generateUniqueSlug('categories', $name);

            $category = Category::create([
                'name'       => $name,
                'slug'       => $slug,
                'created_by' => $userId,
                'status'     => 'active',
            ]);

            Log::info('ProductImport: created new category', [
                'category_id' => $category->id,
                'name'        => $name,
                'created_by'  => $userId,
            ]);

            return $category->id;
        } catch (\Exception $e) {
            Log::warning('ProductImport: failed to create category, falling back to null', [
                'name'  => $name,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    // =========================================================================
    // =========== Helpers: Primary Indications (split + sync) ===============
    // =========================================================================

    /**
     * تقسيم القيمة ثم find-or-create كل indication ثم sync على الـ pivot.
     */
    private function attachPrimaryIndications(Product $product, $rawValue): void
    {
        if (empty($rawValue)) return;

        try {
            $names = PrimaryIndication::parseNames($rawValue);
            if (empty($names)) return;

            $indicationIds = [];
            foreach ($names as $name) {
                try {
                    $indication = PrimaryIndication::findOrCreateByName($name);
                    $indicationIds[] = $indication->id;
                } catch (\Exception $e) {
                    Log::warning('ProductImport: failed to find-or-create primary indication', [
                        'product_id' => $product->id,
                        'name'       => $name,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }

            if (!empty($indicationIds)) {
                $product->syncPrimaryIndications($indicationIds);

                Log::info('ProductImport: synced primary indications', [
                    'product_id'      => $product->id,
                    'sku'             => $product->sku,
                    'indications'     => $names,
                    'indications_ids' => $indicationIds,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('ProductImport: failed to sync primary indications', [
                'product_id' => $product->id,
                'sku'        => $product->sku,
                'raw_value'  => is_array($rawValue) ? json_encode($rawValue) : (string) $rawValue,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // =========== Helpers: Tags (split + find-or-create + attach) ============
    // =========================================================================

    /**
     * تقسيم القيمة على | , / أو سطر جديد، ثم find-or-create كل tag
     * ضمن نفس المستخدم، ثم ربط IDs بـ product_tags.
     *
     * يستخدم نفس نمط ProductController@store: DB::table('product_tags')->insert
     * مع created_by = $userId.
     */
    private function attachTags(Product $product, $rawValue, int $userId): void
    {
        if (empty($rawValue)) return;

        try {
            $names = $this->parseTagNames($rawValue);
            if (empty($names)) return;

            $tagIds = [];
            foreach ($names as $name) {
                try {
                    $tag = $this->findOrCreateTag($name, $userId);
                    if ($tag && !in_array($tag->id, $tagIds, true)) {
                        $tagIds[] = $tag->id;
                    }
                } catch (\Exception $e) {
                    Log::warning('ProductImport: failed to find-or-create tag', [
                        'product_id' => $product->id,
                        'name'       => $name,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }

            if (empty($tagIds)) return;

            // إدراج في product_tags مع تجنّب التكرار (unique combo)
            $now = now();
            foreach ($tagIds as $tagId) {
                $exists = DB::table('product_tags')
                    ->where('product_id', $product->id)
                    ->where('tag_id', $tagId)
                    ->where('created_by', $userId)
                    ->exists();

                if (!$exists) {
                    DB::table('product_tags')->insert([
                        'product_id'  => $product->id,
                        'tag_id'      => $tagId,
                        'created_by'  => $userId,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ]);
                }
            }

            Log::info('ProductImport: attached tags', [
                'product_id' => $product->id,
                'sku'        => $product->sku,
                'tags'       => $names,
                'tag_ids'    => $tagIds,
            ]);
        } catch (\Exception $e) {
            Log::warning('ProductImport: failed to attach tags', [
                'product_id' => $product->id,
                'sku'        => $product->sku,
                'raw_value'  => is_array($rawValue) ? json_encode($rawValue) : (string) $rawValue,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * تقسيم قيمة tags على | , / أو سطر جديد + تنظيف + إزالة التكرار.
     *
     * @param string|array|null $value
     * @return array<string>
     */
    private function parseTagNames($value): array
    {
        if (empty($value)) return [];

        if (is_array($value)) {
            $parts = array_map('strval', $value);
        } else {
            $string = (string) $value;
            // لو JSON
            if (in_array(substr($string, 0, 1), ['[', '{'])) {
                $decoded = json_decode($string, true);
                $parts = is_array($decoded) ? array_map('strval', $decoded) : [$string];
            } else {
                $parts = preg_split('/[|,\/\n\r]+/', $string) ?: [];
            }
        }

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

    /**
     * Find-or-create Tag بالاسم (case-insensitive) للمستخدم الحالي.
     *
     * نمط البحث: tag يخص نفس المستخدم أو السوبر ادمن.
     * عند الإنشاء: company_id = $userId, created_by = $userId, status = active.
     *
     * @param string $name
     * @param int    $userId
     * @return Tag|null
     */
    private function findOrCreateTag(string $name, int $userId): ?Tag
    {
        $trimmed = trim($name);
        if ($trimmed === '') return null;

        $superAdminId = function_exists('getSuperAdminCompanyId') ? getSuperAdminCompanyId() : null;

        // 1) بحث case-insensitive ضمن tags المستخدم أو السوبر ادمن
        $existing = Tag::whereRaw('LOWER(name) = ?', [mb_strtolower($trimmed)])
            ->where(function ($q) use ($userId, $superAdminId) {
                $q->where('company_id', $userId);
                if ($superAdminId) {
                    $q->orWhere('company_id', $superAdminId);
                }
                $q->orWhereNull('company_id'); // سجلات قديمة
            })
            ->first();

        if ($existing) {
            return $existing;
        }

        // 2) إنشاء tag جديد
        $slug = $this->generateUniqueSlug('tags', $trimmed);

        return Tag::create([
            'name'       => $trimmed,
            'slug'       => $slug,
            'color'      => '#6B7280',
            'status'     => 'active',
            'company_id' => $userId,
            'created_by' => $userId,
        ]);
    }

    // =========================================================================
    // =========== Helpers: Slug Generation ===================================
    // =========================================================================

    /**
     * توليد slug فريد لجدول معيّن (تجنّب تعارض الـ unique constraint).
     *
     * @param string $table
     * @param string $name
     * @return string
     */
    private function generateUniqueSlug(string $table, string $name): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'item-' . Str::random(6);
        }

        $slug = $base;
        $counter = 1;

        while (DB::table($table)->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter++;
        }

        return $slug;
    }

    // =========================================================================
    // =========== Counters ===================================================
    // =========================================================================

    public function getAddedCount()
    {
        return $this->addedCount;
    }

    public function getSkippedCount()
    {
        return $this->skippedCount;
    }
}
