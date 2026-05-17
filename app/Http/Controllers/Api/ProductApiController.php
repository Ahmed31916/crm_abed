<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HealthProduct;
use App\Models\Product;
use App\Models\User;
use App\Models\ProductCompanyOverride;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * API Controller: Public Product & Tag Listing
 *
 * النسخة المتكيفة من الـ API مع المشروع الجديد (CRM)
 *
 * التعديلات الرئيسية من المشروع القديم:
 * ─────────────────────────────────────────────────────────────────────
 * | المشروع القديم              → المشروع الجديد (CRM)
 * | Store::where('slug', $slug) → User::where('slug', $slug)->where('type', 'company')
 * | store_id                    → created_by
 * | $superAdminStoreId          → getSuperAdminCompanyId()
 * | ProductMerchantOverride     → ProductCompanyOverride (company_id بدل store_id)
 * | healthProduct relationship  → لا يوجد (البيانات مباشرة على Product)
 * | pairsWellWith               → لا يوجد في المشروع الجديد
 * | Tag / PrimaryIndication     → يدعم إذا Models موجودة (اختياري)
 * | cover_image_url/path        → Spatie MediaLibrary (main collection)
 * | sale_price على Product      → حقل منفصل على products table
 * ─────────────────────────────────────────────────────────────────────
 *
 * Route: GET /api/{slug}/products
 * Route: GET /api/{slug}/tags
 */
class ProductApiController extends Controller
{
    /**
     * API: Product List for a specific company (by slug)
     * Route: GET /api/{slug}/products
     *
     * مطابق تماماً لـ API القديم مع كل بيانات HealthProduct
     */
    public function vitalProductList(Request $request, $slug)
    {
        try {
            // ========================================================================
            // التحقق من الشركة عبر الـ slug
            // ========================================================================
            $company = User::where('slug', $slug)->where('type', 'company')->first();
            if (empty($company)) {
                return response()->json(['message' => 'Company not found!'], 404);
            }

            $superAdminId = getSuperAdminCompanyId();
            $perPage = min($request->input('per_page', 15), 100);

            // ========================================================================
            // بناء الاستعلام الأساسي
            // ========================================================================
            $query = Product::with(['healthProduct', 'brand', 'category', 'media'])
                ->where('status', 'active')
                ->where(function ($q) use ($company, $superAdminId) {
                    $q->where('created_by', $company->id)
                      ->orWhere('created_by', $superAdminId);
                });

            // ========================================================================
            // استبعاد المنتجات المخفية
            // ========================================================================
            $query->whereNotExists(function ($q) use ($company) {
                $q->select(DB::raw(1))
                  ->from('product_company_overrides')
                  ->whereColumn('product_company_overrides.product_id', 'products.id')
                  ->where('product_company_overrides.company_id', $company->id)
                  ->where('product_company_overrides.is_visible', false);
            });

            // ========================================================================
            // فلتر البحث باستخدام التاج
            // ========================================================================
            if ($request->filled('tag_id') || $request->filled('tag')) {
                $tagIds = [];

                if ($request->filled('tag_id')) {
                    $inputTagIds = is_array($request->tag_id) ? $request->tag_id : [$request->tag_id];
                    $tagIds = array_map('intval', $inputTagIds);
                }

                if ($request->filled('tag')) {
                    $tagNames = is_array($request->tag) ? $request->tag : [$request->tag];
                    $foundTagIds = Tag::where(function ($q) use ($tagNames) {
                        foreach ($tagNames as $name) {
                            $q->orWhere('name', 'LIKE', '%' . $name . '%');
                        }
                    })->pluck('id')->toArray();
                    $tagIds = array_merge($tagIds, $foundTagIds);
                }

                $tagIds = array_unique($tagIds);

                if (!empty($tagIds)) {
                    $query->whereHas('tags', function ($q) use ($tagIds) {
                        $q->whereIn('tags.id', $tagIds);
                    });
                }
            }

            // ========================================================================
            // فلتر البحث بالنص - يبحث في Product + HealthProduct
            // مطابق للقديم: name, description, sku, ingredients, supports, useful_for
            // ========================================================================
            if ($request->filled('search')) {
                $searchTerm = $request->input('search');
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('sku', 'LIKE', "%{$searchTerm}%")
                      ->orWhereHas('healthProduct', function ($hq) use ($searchTerm) {
                          $hq->where('sku', 'LIKE', "%{$searchTerm}%")
                             ->orWhere('ingredients', 'LIKE', "%{$searchTerm}%")
                             ->orWhere('supports', 'LIKE', "%{$searchTerm}%")
                             ->orWhere('useful_for', 'LIKE', "%{$searchTerm}%");
                      });
                });
            }

            // ========================================================================
            // فلترة حسب التصنيف
            // ========================================================================
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->input('category_id'));
            }

            // ========================================================================
            // فلترة حسب الخصم
            // ========================================================================
            if ($request->filled('has_discount') && $request->boolean('has_discount')) {
                $query->whereRaw('sale_price > 0 AND sale_price < price');
            }

            // ========================================================================
            // فلترة حسب المخزون
            // ========================================================================
            if ($request->filled('stock_status')) {
                $query->where('stock_status', $request->input('stock_status'));
            }

            // ========================================================================
            // فلترة حسب السعر
            // ========================================================================
            if ($request->filled('price_min')) {
                $query->where('price', '>=', $request->input('price_min'));
            }
            if ($request->filled('price_max')) {
                $query->where('price', '<=', $request->input('price_max'));
            }

            // ========================================================================
            // الترتيب
            // ========================================================================
            $sortBy = $request->input('sort_by', 'id');
            $sortDir = $request->input('sort_dir', 'desc');

            $allowedSortFields = ['id', 'name', 'price', 'created_at'];
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'id';
            }
            if (!in_array($sortDir, ['asc', 'desc'])) {
                $sortDir = 'desc';
            }

            // منتجات الشركة أولاً
            $query->orderByRaw('CASE WHEN created_by = ? THEN 0 ELSE 1 END', [$company->id])
                  ->orderBy($sortBy, $sortDir);

            // ========================================================================
            // التصفح
            // ========================================================================
            $products = $query->paginate($perPage);

            // ========================================================================
            // تنسيق البيانات - مطابق تماماً لـ API القديم
            // ========================================================================
            $formattedProducts = $products->map(function ($product) use ($company, $superAdminId) {
                return $this->formatProductData($product, $company, $superAdminId);
            });

            return response()->json([
                'data' => $formattedProducts,
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'last_page'    => $products->lastPage(),
                    'per_page'     => $products->perPage(),
                    'total'        => $products->total(),
                ],
                'filters' => [
                    'tag'          => $request->input('tag'),
                    'tag_id'       => $request->input('tag_id'),
                    'search'       => $request->input('search'),
                    'category_id'  => $request->input('category_id'),
                    'has_discount' => $request->input('has_discount'),
                    'sort_by'      => $sortBy,
                    'sort_dir'     => $sortDir,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Tags List
     * Route: GET /api/{slug}/tags
     */
    public function vitalTagsList(Request $request, $slug)
    {
        try {
            $company = User::where('slug', $slug)->where('type', 'company')->first();
            if (empty($company)) {
                return response()->json(['message' => 'Company not found!'], 404);
            }

            $superAdminId = getSuperAdminCompanyId();

            $query = Tag::where(function ($q) use ($superAdminId, $company) {
                $q->where('created_by', $superAdminId)
                  ->orWhere('created_by', $company->id);
            });

            if ($request->filled('search')) {
                $query->where('name', 'LIKE', "%{$request->search}%");
            }

            $sortBy = $request->input('sort_by', 'name');
            $sortDir = $request->input('sort_dir', 'asc');
            $allowedSortFields = ['id', 'name', 'slug', 'created_at'];
            if (!in_array($sortBy, $allowedSortFields)) $sortBy = 'name';
            if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'asc';
            $query->orderBy($sortBy, $sortDir);

            $perPage = min($request->input('per_page', 50), 200);

            if ($request->boolean('all')) {
                $tags = $query->get();
            } else {
                $tags = $query->paginate($perPage);
            }

            $formatTag = fn($tag) => [
                'id'   => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug ?? null,
            ];

            if ($request->boolean('all')) {
                return response()->json([
                    'data'  => $tags->map($formatTag)->values(),
                    'total' => $tags->count(),
                ], 200);
            }

            return response()->json([
                'data' => $tags->map($formatTag),
                'meta' => [
                    'current_page' => $tags->currentPage(),
                    'last_page'    => $tags->lastPage(),
                    'per_page'     => $tags->perPage(),
                    'total'        => $tags->total(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Single Product Details
     * Route: GET /api/{slug}/products/{productId}
     */
    public function vitalProductShow(Request $request, $slug, $productId)
    {
        try {
            $company = User::where('slug', $slug)->where('type', 'company')->first();
            if (empty($company)) {
                return response()->json(['message' => 'Company not found!'], 404);
            }

            $superAdminId = getSuperAdminCompanyId();

            $product = Product::with(['healthProduct', 'brand', 'category', 'tax', 'assignedUser', 'media'])
                ->where('id', $productId)
                ->where('status', 'active')
                ->where(function ($q) use ($company, $superAdminId) {
                    $q->where('created_by', $company->id)
                      ->orWhere('created_by', $superAdminId);
                })
                ->first();

            if (!$product) {
                return response()->json(['message' => 'Product not found!'], 404);
            }

            $hiddenOverride = ProductCompanyOverride::where('product_id', $product->id)
                ->where('company_id', $company->id)
                ->where('is_visible', false)
                ->exists();

            if ($hiddenOverride) {
                return response()->json(['message' => 'Product not available'], 404);
            }

            $formatted = $this->formatProductData($product, $company, $superAdminId, true);

            return response()->json(['data' => $formatted], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Company Info
     * Route: GET /api/{slug}/info
     */
    public function vitalCompanyInfo(Request $request, $slug)
    {
        try {
            $company = User::where('slug', $slug)->where('type', 'company')->first();
            if (empty($company)) {
                return response()->json(['message' => 'Company not found!'], 404);
            }

            return response()->json([
                'data' => [
                    'id'            => $company->id,
                    'name'          => $company->name,
                    'slug'          => $company->slug,
                    'email'         => $company->email,
                    'avatar'        => $company->avatar ? get_file($company->avatar) : null,
                    'settings'      => settings($company->id),
                    'product_count' => Product::where('created_by', $company->id)
                        ->where('status', 'active')
                        ->count(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // =========== Private Helpers =============================================
    // =========================================================================

    /**
     * تنسيق بيانات المنتج - مطابق تماماً لـ API القديم
     *
     * هذه الدالة هي قلب الـ API وتُرجع نفس الحقول بالضبط
     * مثل vitalProductList في المشروع القديم
     *
     * @param Product $product
     * @param User $company
     * @param int $superAdminId
     * @param bool $includeDetails بيانات إضافية لصفحة التفاصيل
     * @return array
     */
    private function formatProductData(Product $product, User $company, int $superAdminId, bool $includeDetails = false): array
    {
        // ──── جلب HealthProduct مع أولوية الشركة ────
        $health = HealthProduct::getForProduct($product->id, $company->id);
        $healthObj = $health ?? new \stdClass();

        // ───ـ جلب Override للشركة ────
        $override = null;
        if ($product->created_by == $superAdminId) {
            $override = ProductCompanyOverride::where('product_id', $product->id)
                ->where('company_id', $company->id)
                ->first();
        }

        // ──── دمج البيانات (مطابق لـ getMergedProductData القديم) ────
        $merged = $this->mergeProductData($product, $health, $override);

        // ──── التسعير ────
        $price = (float) $merged->price;
        $salePrice = (float) ($merged->sale_price ?? 0);
        $hasDiscount = ($salePrice > 0 && $salePrice < $price);
        $discountPercentage = $hasDiscount ? round((($price - $salePrice) / $price) * 100, 2) : 0;
        $finalPrice = $hasDiscount ? $salePrice : $price;

        // ──── الصورة ────
        // الأولوية: product_image_url من healthProduct → Spatie MediaLibrary
        $imageUrl = !empty($health?->product_image_url)
            ? $health->product_image_url
            : ($product->getFirstMediaUrl('main') ?: null);

        // ──── Tags ────
        $tags = $product->getTagNames($company->id);

        // ──── Dosing Schedule ────
        $dosingSchedule = null;
        $dosingNa = false;
        if ($override && $override->dosing_na) {
            // Override يعطل الجرعات
            $dosingSchedule = null;
            $dosingNa = true;
        } elseif ($override) {
            // Override به جرعات
            $dosingSchedule = $override->getEffectiveDosingSchedule();
            $dosingNa = $override->getEffectiveDosingNa();
        } elseif ($health && !$health->dosing_na) {
            // من HealthProduct
            $dosingSchedule = $health->dosing_schedule;
            $dosingNa = false;
        } elseif ($health && $health->dosing_na) {
            $dosingSchedule = null;
            $dosingNa = true;
        }

        // ──── Primary Indications من Pivot ────
        // القديم: كان يجيبهم من product_primary_indications pivot
        // الجديد: يجيبهم من health_product.primary_indications أو override
        $primaryIndicationNames = [];
        if ($override && $override->primary_indications !== null) {
            $primaryIndicationNames = $override->primary_indications;
        } elseif ($health && $health->primary_indications) {
            $primaryIndicationNames = $health->primary_indications;
        }

        // ──── بناء الـ response ────
        $data = [
            'id'                  => $product->id,
            'sku'                 => $health?->sku ?? $product->sku,
            'name'                => $merged->name,
            'full_name'           => $health?->full_name ?? null,
            'slug'                => $product->slug ?? null,
            'practitioner'        => $company->name,
            'practitioner_slug'   => $company->slug,
            'description'         => strip_tags($merged->description ?? ''),

            // التسعير
            'regular_price'       => $price,
            'sale_price'          => $salePrice > 0 ? $salePrice : null,
            'price'               => $finalPrice,
            'has_discount'        => $hasDiscount,
            'discount_percentage' => $discountPercentage,

            // التصنيف
            'category'            => $merged->category->name ?? ($merged->category_name ?? 'other'),
            'category_id'         => $merged->category_id ?? null,

            // الصورة
            'image_url'           => $imageUrl,

            // الحالة
            'is_active'           => ($merged->status === 'active'),
            'stock_status'        => $merged->stock_status ?? 'in_stock',

            // التاجات
            'tags'                => array_values($tags),

            // معلومات إضافية
            'frequency'           => $product->frequency ?? null,
            'supplier'            => $product->brand?->name ?? null,
            'pairs_well_with'     => [],  // غير مدعوم حالياً - يُضاف لاحقاً

            // بيانات المنتج الصحي (مطابق للقديم)
            'ingredients'         => $health?->ingredients ?? null,
            'bottle_size'         => $health?->bottle_size ?? null,
            'bottle_size_unit'    => $health?->bottle_size_unit ?? null,
            'product_form'        => ($health?->bottle_size_unit ?? '') === 'caps' ? 'Caps' : 'Liquid',
            'primary_indications' => $primaryIndicationNames,
            'supports'            => $health?->supports ?? null,
            'useful_for'          => $health?->useful_for ?? null,
            'contraindications'   => $override?->getEffectiveContraindications() ?? $health?->contraindications ?? null,
            'research'            => $override?->getEffectiveResearchLinks() ?? $health?->research_links ?? null,

            // ملاحظات الممارس (حصري للشركة)
            'practitioner_notes'           => $override?->practitioner_notes ?? ($health?->practitioner_notes ?? null),
            'custom_primary_indications'   => $override?->custom_primary_indications ?? [],
            'custom_dosing_notes'          => $override?->custom_dosing_notes ?? ($health?->custom_dosing_notes ?? null),

            // جدول الجرعات
            'dosing_schedule'     => $dosingSchedule,
            'dosing_na'           => $dosingNa,

            // معرف الرسالة
            'message_id'          => $product->message_id
        ];

        // ──── بيانات إضافية لصفحة التفاصيل ────
        if ($includeDetails) {
            $data['stock_quantity'] = $override?->getEffectiveStock() ?? $product->stock_quantity;
            $data['additional_images'] = $product->getMedia('additional')
                ->map(fn($m) => $m->getUrl())
                ->toArray();
            $data['is_shared_product'] = $product->created_by == $superAdminId;
            $data['has_override'] = $override !== null;
            $data['brand'] = $product->brand?->name ?? null;
            $data['tax_rate'] = $product->tax?->rate ?? null;
            $data['full_description'] = $product->description;
        }

        return $data;
    }

    /**
     * دمج بيانات المنتج مع Override
     * مطابق لـ getMergedProductData في المشروع القديم
     *
     * @param Product $product
     * @param HealthProduct|null $health
     * @param ProductCompanyOverride|null $override
     * @return object
     */
    private function mergeProductData(Product $product, ?HealthProduct $health, ?ProductCompanyOverride $override): object
    {
        // نبدأ من بيانات المنتج الأساسية
        $mergedProduct = $product->toArray();

        // نضيف علاقة التصنيف ككائن
        $mergedProduct['category'] = $product->category;

        if ($override) {
            // ──── Override: وصف المنتج ────
            if ($override->description !== null) {
                $mergedProduct['description'] = $override->description;
            }
            if ($override->sale_price_override !== null) {
                $mergedProduct['sale_price'] = $override->sale_price_override;
            }
            if ($override->price_override !== null) {
                $mergedProduct['price'] = $override->price_override;
            }
            if ($override->stock_quantity_override !== null) {
                $mergedProduct['stock_quantity'] = $override->stock_quantity_override;
            }
            if ($override->stock_status_override !== null) {
                $mergedProduct['stock_status'] = $override->stock_status_override;
            }
            if ($override->category_id !== null) {
                $mergedProduct['category_id'] = $override->category_id;
                // تحديث كائن التصنيف
                $category = \App\Models\Category::find($override->category_id);
                if ($category) {
                    $mergedProduct['category'] = $category;
                }
            }
        } else {
            // بدون override - نستخدم بيانات المنتج الأصلية
            // إذا في healthProduct، نستخدم وصفه إذا مختلف
            if ($health && $health->description !== null && $product->created_by == getSuperAdminCompanyId()) {
                // للمنتجات المشتركة، الوصف يأتي من المنتج نفسه
            }
        }

        return (object) $mergedProduct;
    }
}
