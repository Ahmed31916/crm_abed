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
 * ProductImport — UPDATED: Add + Update + Skip logic, with `frequency` field
 *
 * ────────────────────────────────────────────────────────────────────────
 * ما الجديد في هذا التحديث:
 * ────────────────────────────────────────────────────────────────────────
 *
 * 1) عند وجود SKU مسبقاً في قاعدة البيانات:
 *    - يتم مقارنة كل الحقول القابلة للتعديل في الإكسل مع الحالية.
 *    - لو في أي اختلاف (ولو بحقل واحد) → UPDATE + updatedCount++
 *    - لو كل الحقول متطابقة (بما فيها tags و primary_indications) → SKIP + skippedCount++
 *
 * 2) تمت إضافة `frequency` إلى headerMap (للإضافة والتعديل).
 *
 * 3) تمت إضافة عدّاد جديد: $updatedCount + getUpdatedCount()
 *    كذلك getAddedCount() و getSkippedCount() كما هما.
 *
 * 4) المنطق القديم للإضافة (الـ Product::create + sync tags + sync indications)
 *    يبقى كما هو — فقط تم فصله في handleAdd() للوضوح.
 *
 * ────────────────────────────────────────────────────────────────────────
 */
class ProductImport implements ToCollection, WithHeadingRow
{
    protected $addedCount   = 0;
    protected $updatedCount = 0;
    protected $skippedCount = 0;

    /**
     * الحقول القابلة للمقارنة والتحديث عند وجود المنتج مسبقاً.
     * (لا تشمل sku لأنه المفتاح، ولا created_by/assigned_to لأنها تخص الملكية)
     */
    protected $updatableFields = [
        'name',
        'description',
        'price',
        'sale_price',
        'stock_quantity',
        'stock_status',
        'category_id',
        'brand_id',
        'tax_id',
        'tax_status',
        'product_weight',
        'status',
        'frequency',
    ];

    /**
     * Fuzzy header mapping: maps common header variations to database field names.
     */
    protected $headerMap = [
        'name'        => ['name', 'product_name', 'product name', 'productname', 'title'],
        'sku'         => ['sku', 'SKU', 'product_sku', 'product sku', 'item_code', 'code'],
        'description' => ['description', 'desc', 'product_description', 'details'],
        'price'       => ['price', 'unit_price', 'unit price', 'selling_price', 'selling price', 'rate'],
        'sale_price'  => ['sale_price', 'sale price', 'discount_price', 'discount price', 'special_price', 'special price'],
        'stock_quantity' => ['stock_quantity', 'stock quantity', 'quantity', 'qty', 'stock', 'inventory'],
        'stock_status'   => ['stock_status', 'stock status', 'availability'],
        'category_id'    => ['category', 'category_id', 'category name', 'cat'],
        'brand_id'       => ['brand', 'brand_id', 'brand name', 'supplier', 'supplier name'],
        'tax_id'         => ['tax', 'tax_id', 'tax name', 'tax rate'],
        'tax_status'     => ['tax_status', 'tax status', 'taxable'],
        'product_weight' => ['product_weight', 'product weight', 'weight'],
        'status'         => ['status', 'product_status', 'is_active', 'active'],

        // ─── NEW: frequency (dosing frequency) ───
        'frequency' => [
            'frequency', 'dosing_frequency', 'dosing frequency',
            'freq', 'product_frequency', 'product frequency',
        ],

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

    /**
     * ════════════════════════════════════════════════════════════════════
     *  MAIN ENTRY POINT
     * ════════════════════════════════════════════════════════════════════
     */
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

            // Normalize SKU to string
            $sku = (string) $mapped['sku'];

            // ─────────────────────────────────────────────────────────────
            // التحقق إن كان المنتج موجوداً مسبقاً عبر SKU
            // ─────────────────────────────────────────────────────────────
            $existingProduct = Product::where('sku', $sku)->first();

            if ($existingProduct) {
                // مسار التعديل
                $this->handleUpdate($existingProduct, $mapped, $userId);
            } else {
                // مسار الإضافة (نفس المنطق القديم)
                if (function_exists('hasReachedProductLimit') && hasReachedProductLimit()) {
                    $this->skippedCount++;
                    continue;
                }
                $this->handleAdd($mapped, $userId);
            }
        }
    }

    // ════════════════════════════════════════════════════════════════════
    // =========== ADD PATH (نفس المنطق القديم) ===========================
    // ════════════════════════════════════════════════════════════════════

    private function handleAdd(array $mapped, int $userId): void
    {
        // Resolve Category
        $mapped['category_id'] = $this->resolveCategory($mapped['category_id'] ?? null, $userId);

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

        // Defaults
        $mapped['created_by']     = $userId;
        $mapped['status']         = $mapped['status'] ?? 'active';
        $mapped['stock_quantity'] = $mapped['stock_quantity'] ?? 0;
        $mapped['stock_status']   = $mapped['stock_status'] ?? 'in_stock';

        // استخراج الحقول غير القابلة للتعبئة
        $primaryIndicationRaw = $mapped['primary_indication'] ?? null;
        unset($mapped['primary_indication']);
        $tagsRaw = $mapped['tags'] ?? null;
        unset($mapped['tags']);

        // تنظيف الحقول غير الموجودة في fillable
        unset(
            $mapped['category'], $mapped['brand'], $mapped['tax'],
            $mapped['supplier'], $mapped['supplier_name']
        );

        try {
            $product = Product::create($mapped);
            $this->addedCount++;

            $this->attachPrimaryIndications($product, $primaryIndicationRaw);
            $this->attachTags($product, $tagsRaw, $userId);

        } catch (\Exception $e) {
            Log::error('Product import (add) failed for SKU: ' . ($mapped['sku'] ?? 'unknown'), [
                'error' => $e->getMessage(),
            ]);
            $this->skippedCount++;
        }
    }

    // ════════════════════════════════════════════════════════════════════
    // =========== UPDATE PATH (المنطق الجديد) ============================
    // ════════════════════════════════════════════════════════════════════

    /**
     * عند وجود المنتج: نقارن الحقول، نحدّث لو في اختلاف، وإلا نتخطى.
     */
    private function handleUpdate(Product $existingProduct, array $mapped, int $userId): void
    {
        // ─── 1) بناء قيم الحقول القادمة من الإكسل (بعد resolve العلاقات) ───
        $incoming = [];

        // category
        if (array_key_exists('category_id', $mapped)) {
            $incoming['category_id'] = $this->resolveCategory($mapped['category_id'], $userId);
        }

        // brand
        if (array_key_exists('brand_id', $mapped)) {
            $rawBrand = $mapped['brand_id'];
            if (is_numeric($rawBrand)) {
                $incoming['brand_id'] = (int) $rawBrand;
            } else {
                $brand = Brand::where('name', $rawBrand)
                    ->where('created_by', $userId)
                    ->first();
                $incoming['brand_id'] = $brand ? $brand->id : null;
            }
        }

        // tax
        if (array_key_exists('tax_id', $mapped)) {
            $rawTax = $mapped['tax_id'];
            if (is_numeric($rawTax)) {
                $incoming['tax_id'] = (int) $rawTax;
            } else {
                $tax = Tax::where('name', $rawTax)
                    ->where('created_by', $userId)
                    ->first();
                $incoming['tax_id'] = $tax ? $tax->id : null;
            }
        }

        // باقي الحقول السكالار القابلة للتعديل
        foreach ($this->updatableFields as $field) {
            if (in_array($field, ['category_id', 'brand_id', 'tax_id'], true)) {
                continue; // تمت معالجتها أعلاه
            }
            if (array_key_exists($field, $mapped)) {
                $incoming[$field] = $mapped[$field];
            }
        }

        // ─── 2) تطبيع القيم (cast + trim + null for empty) ───
        $incoming = $this->normalizeIncomingValues($incoming);

        // ─── 3) كشف الاختلافات في الحقول العادية ───
        $changes = $this->detectFieldChanges($existingProduct, $incoming);

        // ─── 4) كشف الاختلافات في tags و primary_indications ───
        $tagsRaw        = $mapped['tags'] ?? null;
        $indicationsRaw = $mapped['primary_indication'] ?? null;

        $tagsChanged        = $this->tagsChanged($existingProduct, $tagsRaw, $userId);
        $indicationsChanged = $this->indicationsChanged($existingProduct, $indicationsRaw);

        // ─── 5) لو مفيش أي تغيير → SKIP ───
        if (empty($changes) && !$tagsChanged && !$indicationsChanged) {
            $this->skippedCount++;
            return;
        }

        // ─── 6) تنفيذ التحديث ───
        try {
            if (!empty($changes)) {
                $existingProduct->update($changes);
            }
            if ($tagsChanged) {
                $this->syncTagsForUpdate($existingProduct, $tagsRaw, $userId);
            }
            if ($indicationsChanged) {
                $this->attachPrimaryIndications($existingProduct, $indicationsRaw);
            }
            $this->updatedCount++;

            Log::info('ProductImport: updated existing product', [
                'product_id' => $existingProduct->id,
                'sku'        => $existingProduct->sku,
                'changes'    => array_keys($changes),
                'tags_changed'        => $tagsChanged,
                'indications_changed' => $indicationsChanged,
            ]);

        } catch (\Exception $e) {
            Log::error('Product import (update) failed for SKU: ' . $existingProduct->sku, [
                'error' => $e->getMessage(),
            ]);
            $this->skippedCount++;
        }
    }

    /**
     * تطبيع قيم الإكسل لتتناسب مع أنواع الأعمدة في الداتابيز قبل المقارنة.
     */
    private function normalizeIncomingValues(array $values): array
    {
        foreach ($values as $key => $value) {
            // null أو نص فارغ → null
            if ($value === null || $value === '') {
                $values[$key] = null;
                continue;
            }

            if (in_array($key, ['price', 'sale_price', 'product_weight'], true)) {
                $values[$key] = (float) $value;
            } elseif (in_array($key, ['stock_quantity', 'category_id', 'brand_id', 'tax_id'], true)) {
                $values[$key] = (int) $value;
            } elseif (is_string($value)) {
                $values[$key] = trim($value);
            }
        }

        return $values;
    }

    /**
     * مقارنة الحقول بين المنتج الحالي والقيم القادمة من الإكسل.
     * يُرجع array بالحقول التي تغيّرت فقط (لتمريرها مباشرة إلى update()).
     */
    private function detectFieldChanges(Product $product, array $incoming): array
    {
        $changes = [];

        foreach ($incoming as $field => $newValue) {
            $current = $product->{$field};

            // تطبيع القيمة الحالية بنفس نمط newValue
            if ($current === null) {
                $currentNorm = null;
            } elseif (in_array($field, ['price', 'sale_price', 'product_weight'], true)) {
                $currentNorm = (float) $current;
            } elseif (in_array($field, ['stock_quantity', 'category_id', 'brand_id', 'tax_id'], true)) {
                $currentNorm = (int) $current;
            } elseif (is_string($current)) {
                $currentNorm = trim($current);
            } else {
                $currentNorm = $current;
            }

            // مقارنة نصية بعد cast (تتعامل مع null vs '')
            if ((string) $currentNorm !== (string) $newValue) {
                $changes[$field] = $newValue;
            }
        }

        return $changes;
    }

    /**
     * هل تختلف الـ tags القادمة من الإكسل عن الـ tags الحالية للمنتج؟
     */
    private function tagsChanged(Product $product, $rawValue, int $userId): bool
    {
        if (empty($rawValue)) {
            // ما في tags في الإكسل → لا نعتبره تغيير (لا نمسح التاجات الموجودة)
            return false;
        }

        $incomingNames = $this->parseTagNames($rawValue);
        $currentNames  = $product->getTagNames($userId);

        sort($incomingNames);
        sort($currentNames);

        return $incomingNames !== $currentNames;
    }

    /**
     * هل تختلف الـ primary indications القادمة من الإكسل عن الحالية؟
     */
    private function indicationsChanged(Product $product, $rawValue): bool
    {
        if (empty($rawValue)) {
            return false;
        }

        $incomingNames = PrimaryIndication::parseNames($rawValue);
        $currentNames  = $product->getPrimaryIndicationNames();

        sort($incomingNames);
        sort($currentNames);

        return $incomingNames !== $currentNames;
    }

    /**
     * عند التحديث: نعمل sync كامل للـ tags (نحذف القديم ونربط الجديد).
     * مختلف عن attachTags (الذي للإضافة فقط ولا يحذف القديم).
     */
    private function syncTagsForUpdate(Product $product, $rawValue, int $userId): void
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
                    Log::warning('ProductImport: failed to find-or-create tag (update)', [
                        'product_id' => $product->id,
                        'name'       => $name,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }

            if (empty($tagIds)) return;

            // حذف تاجات المستخدم الحالية لهذا المنتج (للحفاظ على تاجات شركات/مستخدمين آخرين)
            DB::table('product_tags')
                ->where('product_id', $product->id)
                ->where('created_by', $userId)
                ->delete();

            // إدراج التاجات الجديدة
            $now = now();
            foreach ($tagIds as $tagId) {
                DB::table('product_tags')->insert([
                    'product_id'  => $product->id,
                    'tag_id'      => $tagId,
                    'created_by'  => $userId,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('ProductImport: failed to sync tags (update)', [
                'product_id' => $product->id,
                'sku'        => $product->sku,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    // ════════════════════════════════════════════════════════════════════
    // =========== Helpers: User Resolution ===================================
    // ════════════════════════════════════════════════════════════════════

    private function resolveUserId(): int
    {
        $id = auth()->id();
        if ($id) return (int) $id;

        if (function_exists('createdBy')) {
            return (int) createdBy();
        }

        throw new \RuntimeException('ProductImport requires an authenticated user or createdBy() helper.');
    }

    // ════════════════════════════════════════════════════════════════════
    // =========== Helpers: Category (single value, find-or-create) ===========
    // ════════════════════════════════════════════════════════════════════

    private function resolveCategory($value, int $userId): ?int
    {
        if (empty($value)) return null;

        if (is_numeric($value)) {
            $id = (int) $value;
            return Category::where('id', $id)->exists() ? $id : null;
        }

        $name = trim((string) $value);
        if ($name === '') return null;

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

    // ════════════════════════════════════════════════════════════════════
    // =========== Helpers: Primary Indications (split + sync) ===============
    // ════════════════════════════════════════════════════════════════════

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

    // ════════════════════════════════════════════════════════════════════
    // =========== Helpers: Tags (split + find-or-create + attach) ============
    // ════════════════════════════════════════════════════════════════════

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

    private function parseTagNames($value): array
    {
        if (empty($value)) return [];

        if (is_array($value)) {
            $parts = array_map('strval', $value);
        } else {
            $string = (string) $value;
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

    private function findOrCreateTag(string $name, int $userId): ?Tag
    {
        $trimmed = trim($name);
        if ($trimmed === '') return null;

        $superAdminId = function_exists('getSuperAdminCompanyId') ? getSuperAdminCompanyId() : null;

        $existing = Tag::whereRaw('LOWER(name) = ?', [mb_strtolower($trimmed)])
            ->where(function ($q) use ($userId, $superAdminId) {
                $q->where('company_id', $userId);
                if ($superAdminId) {
                    $q->orWhere('company_id', $superAdminId);
                }
                $q->orWhereNull('company_id');
            })
            ->first();

        if ($existing) {
            return $existing;
        }

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

    // ════════════════════════════════════════════════════════════════════
    // =========== Helpers: Slug Generation ===================================
    // ════════════════════════════════════════════════════════════════════

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

    // ════════════════════════════════════════════════════════════════════
    // =========== Counters ===================================================
    // ════════════════════════════════════════════════════════════════════

    public function getAddedCount(): int
    {
        return $this->addedCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    /**
     * Array summary مفيد للـ controller لإرجاعه للـ frontend.
     */
    public function getSummary(): array
    {
        return [
            'added'   => $this->addedCount,
            'updated' => $this->updatedCount,
            'skipped' => $this->skippedCount,
            'total'   => $this->addedCount + $this->updatedCount + $this->skippedCount,
        ];
    }
}
