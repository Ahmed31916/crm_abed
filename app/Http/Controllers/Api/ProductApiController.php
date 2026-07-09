<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HealthProduct;
use App\Models\Product;
use App\Models\User;
use App\Models\ProductCompanyOverride;
use App\Models\Tag;
use App\Models\PrimaryIndication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * ProductApiController — UPDATED for Many-to-Many Primary Indications
 *
 * ────────────────────────────────────────────────────────────────────────
 * ما تغيّر:
 * ────────────────────────────────────────────────────────────────────────
 * - في formatProductData: قراءة primary_indications صارت من:
 *     1. override->primary_indications (لو الشركة عندها override)
 *     2. العلاقة belongsToMany عبر $product->primaryIndications
 *   بدلاً من قراءة JSON من health_products.primary_indications.
 * - أضفنا eager-load للعلاقة في queries الرئيسية.
 * - أضفنا فلتر جديد ?primary_indication_id= للبحث بالـ indication.
 * - باقي المنطق (search, tags, sort, pagination) ما تغيّر.
 *
 * كيفية الدمج مع الكود الأصلي:
 *   فقط استبدل قسم "Primary Indications" في formatProductData + أضِف
 *   `->with([... 'primaryIndications'])` لكل query رئيسي.
 * ────────────────────────────────────────────────────────────────────────
 */
class ProductApiController extends Controller
{
    public function vitalProductList(Request $request, $slug)
    {
        try {
            $company = User::where('slug', $slug)->where('type', 'company')->first();
            if (empty($company)) {
                return response()->json(['message' => 'Company not found!'], 404);
            }

            $superAdminId = getSuperAdminCompanyId();
            $perPage = min($request->input('per_page', 15), 100);

            // ─── NEW: eager-load primaryIndications لتحسين الأداء ───
            $query = Product::with(['healthProduct', 'brand', 'category', 'media', 'primaryIndications'])
                ->where('status', 'active')
                ->where(function ($q) use ($company, $superAdminId) {
                    $q->where('created_by', $company->id)
                      ->orWhere('created_by', $superAdminId);
                });

            // استبعاد المنتجات المخفية
            $query->whereNotExists(function ($q) use ($company) {
                $q->select(DB::raw(1))
                  ->from('product_company_overrides')
                  ->whereColumn('product_company_overrides.product_id', 'products.id')
                  ->where('product_company_overrides.company_id', $company->id)
                  ->where('product_company_overrides.is_visible', false);
            });

            // فلتر البحث باستخدام التاج
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

            // ═══════════════════════════════════════════════════════════════════════
            // ⚡ NEW: فلتر البحث بالـ Primary Indication
            // ═══════════════════════════════════════════════════════════════════════
            if ($request->filled('primary_indication_id')) {
                $indicationIds = is_array($request->primary_indication_id)
                    ? $request->primary_indication_id
                    : [$request->primary_indication_id];
                $indicationIds = array_map('intval', $indicationIds);

                $query->whereHas('primaryIndications', function ($q) use ($indicationIds) {
                    $q->whereIn('primary_indications.id', $indicationIds);
                });
            }

            // فلتر بالاسم (بحث نصي)
            if ($request->filled('primary_indication')) {
                $indicationName = $request->input('primary_indication');
                $query->whereHas('primaryIndications', function ($q) use ($indicationName) {
                    $q->where('name', 'LIKE', "%{$indicationName}%");
                });
            }
            // ═══════════════════════════════════════════════════════════════════════

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
                      })
                      // NEW: بحث في اسم الـ indication عبر العلاقة
                      ->orWhereHas('primaryIndications', function ($piq) use ($searchTerm) {
                          $piq->where('name', 'LIKE', "%{$searchTerm}%");
                      });
                });
            }

            if ($request->filled('category_id')) {
                $query->where('category_id', $request->input('category_id'));
            }

            if ($request->filled('has_discount') && $request->boolean('has_discount')) {
                $query->whereRaw('sale_price > 0 AND sale_price < price');
            }

            if ($request->filled('stock_status')) {
                $query->where('stock_status', $request->input('stock_status'));
            }

            if ($request->filled('price_min')) {
                $query->where('price', '>=', $request->input('price_min'));
            }
            if ($request->filled('price_max')) {
                $query->where('price', '<=', $request->input('price_max'));
            }

            if ($request->filled('updated_since')) {
                $date = $request->input('updated_since');
                try {
                    $parsedDate = \Carbon\Carbon::parse($date);
                    $query->where('updated_at', '>=', $parsedDate);
                } catch (\Exception $e) {
                    return response()->json([
                        'error'   => 'Invalid date format',
                        'message' => 'The updated_since parameter must be a valid date.',
                        'received' => $date,
                    ], 422);
                }
            }

            $sortBy = $request->input('sort_by', 'id');
            $sortDir = $request->input('sort_dir', 'desc');
            $allowedSortFields = ['id', 'name', 'price', 'created_at', 'updated_at'];
            if (!in_array($sortBy, $allowedSortFields)) $sortBy = 'id';
            if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'desc';

            $query->orderByRaw('CASE WHEN created_by = ? THEN 0 ELSE 1 END', [$company->id])
                  ->orderBy($sortBy, $sortDir);

            $products = $query->paginate($perPage);

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
                    'tag'                     => $request->input('tag'),
                    'tag_id'                  => $request->input('tag_id'),
                    'search'                  => $request->input('search'),
                    'category_id'             => $request->input('category_id'),
                    'has_discount'            => $request->input('has_discount'),
                    'updated_since'           => $request->input('updated_since'),
                    'primary_indication_id'   => $request->input('primary_indication_id'),
                    'primary_indication'      => $request->input('primary_indication'),
                    'sort_by'                 => $sortBy,
                    'sort_dir'                => $sortDir,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function vitalTagsList(Request $request, $slug)
    {
        // نفس الكود القديم بدون تغيير
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

            if ($request->filled('updated_since')) {
                $date = $request->input('updated_since');
                try {
                    $parsedDate = \Carbon\Carbon::parse($date);
                    $query->where('updated_at', '>=', $parsedDate);
                } catch (\Exception $e) {
                    return response()->json([
                        'error'    => 'Invalid date format',
                        'message'  => 'The updated_since parameter must be a valid date.',
                        'received' => $date,
                    ], 422);
                }
            }

            $sortBy = $request->input('sort_by', 'name');
            $sortDir = $request->input('sort_dir', 'asc');
            $allowedSortFields = ['id', 'name', 'slug', 'created_at', 'updated_at'];
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
                'id'         => $tag->id,
                'name'       => $tag->name,
                'slug'       => $tag->slug ?? null,
                'updated_at' => $tag->updated_at?->toIso8601String(),
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
     * ⚡ NEW: API لجلب كل الـ Primary Indications المتاحة.
     * Route: GET /api/{slug}/primary-indications
     */
    public function vitalPrimaryIndicationsList(Request $request, $slug)
    {
        try {
            $company = User::where('slug', $slug)->where('type', 'company')->first();
            if (empty($company)) {
                return response()->json(['message' => 'Company not found!'], 404);
            }

            $query = PrimaryIndication::query();

            if ($request->filled('search')) {
                $query->where('name', 'LIKE', "%{$request->search}%");
            }

            $sortBy = $request->input('sort_by', 'name');
            $sortDir = $request->input('sort_dir', 'asc');
            $query->orderBy($sortBy, $sortDir);

            if ($request->boolean('all')) {
                $indications = $query->get();
                return response()->json([
                    'data'  => $indications->map(fn($i) => [
                        'id'   => $i->id,
                        'name' => $i->name,
                        'slug' => $i->slug,
                    ])->values(),
                    'total' => $indications->count(),
                ], 200);
            }

            $perPage = min($request->input('per_page', 100), 500);
            $indications = $query->paginate($perPage);

            return response()->json([
                'data' => $indications->map(fn($i) => [
                    'id'   => $i->id,
                    'name' => $i->name,
                    'slug' => $i->slug,
                ]),
                'meta' => [
                    'current_page' => $indications->currentPage(),
                    'last_page'    => $indications->lastPage(),
                    'per_page'     => $indications->perPage(),
                    'total'        => $indications->total(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Something went wrong',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function vitalProductShow(Request $request, $slug, $productId)
    {
        try {
            $company = User::where('slug', $slug)->where('type', 'company')->first();
            if (empty($company)) {
                return response()->json(['message' => 'Company not found!'], 404);
            }

            $superAdminId = getSuperAdminCompanyId();

            // ─── NEW: eager-load primaryIndications ───
            $product = Product::with(['healthProduct', 'brand', 'category', 'tax', 'assignedUser', 'media', 'primaryIndications'])
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

    private function formatProductData(Product $product, User $company, int $superAdminId, bool $includeDetails = false): array
    {
        $health = HealthProduct::getForProduct($product->id, $company->id);
        $healthObj = $health ?? new \stdClass();

        $override = null;
        if ($product->created_by == $superAdminId) {
            $override = ProductCompanyOverride::where('product_id', $product->id)
                ->where('company_id', $company->id)
                ->first();
        }

        $merged = $this->mergeProductData($product, $health, $override);

        $price = (float) $merged->price;
        $salePrice = (float) ($merged->sale_price ?? 0);
        $hasDiscount = ($salePrice > 0 && $salePrice < $price);
        $discountPercentage = $hasDiscount ? round((($price - $salePrice) / $price) * 100, 2) : 0;
        $finalPrice = $hasDiscount ? $salePrice : $price;

        $imageUrl = !empty($health?->product_image_url)
            ? $health->product_image_url
            : ($product->getFirstMediaUrl('main') ?: null);

        $tags = $product->getTagNames($company->id);

        // Dosing Schedule
        $dosingSchedule = null;
        $dosingNa = false;
        if ($override && $override->dosing_na) {
            $dosingSchedule = null;
            $dosingNa = true;
        } elseif ($override) {
            $dosingSchedule = $override->getEffectiveDosingSchedule();
            $dosingNa = $override->getEffectiveDosingNa();
        } elseif ($health && !$health->dosing_na) {
            $dosingSchedule = $health->dosing_schedule;
            $dosingNa = false;
        } elseif ($health && $health->dosing_na) {
            $dosingSchedule = null;
            $dosingNa = true;
        }

        // ═══════════════════════════════════════════════════════════════════════
        // ⚡ UPDATED: Primary Indications - من العلاقة belongsToMany أو override
        // ═══════════════════════════════════════════════════════════════════════
        // القديم: كان يجيبهم من health_product.primary_indications أو override
        // الجديد: يجيبهم من override أو من العلاقة belongsToMany
        $primaryIndicationNames = [];
        if ($override && $override->primary_indications !== null) {
            $primaryIndicationNames = is_array($override->primary_indications)
                ? array_values($override->primary_indications)
                : [];
        } else {
            // اقرأ من العلاقة belongsToMany (تم eager-loadها في الاستعلام)
            $primaryIndicationNames = $product->primaryIndications
                ->pluck('name')
                ->toArray();
        }
        // ═══════════════════════════════════════════════════════════════════════

        $data = [
            'id'                  => $product->id,
            'sku'                 => $health?->sku ?? $product->sku,
            'name'                => $merged->name,
            'full_name'           => $health?->full_name ?? null,
            'slug'                => $product->slug ?? null,
            'practitioner'        => $company->name,
            'practitioner_slug'   => $company->slug,
            'description'         => strip_tags($merged->description ?? ''),

            'regular_price'       => $price,
            'sale_price'          => $salePrice > 0 ? $salePrice : null,
            'price'               => $finalPrice,
            'has_discount'        => $hasDiscount,
            'discount_percentage' => $discountPercentage,

            'category'            => $merged->category->name ?? ($merged->category_name ?? 'other'),
            'category_id'         => $merged->category_id ?? null,

            'image_url'           => $imageUrl,
            'is_active'           => ($merged->status === 'active'),
            'stock_status'        => $merged->stock_status ?? 'in_stock',
            'tags'                => array_values($tags),

            'frequency'           => $product->frequency ?? null,
            'supplier'            => $product->brand?->name ?? null,
            'pairs_well_with'     => [],

            'ingredients'         => $health?->ingredients ?? null,
            'bottle_size'         => $health?->bottle_size ?? null,
            'bottle_size_unit'    => $health?->bottle_size_unit ?? null,
            'product_form'        => ($health?->bottle_size_unit ?? '') === 'caps' ? 'Caps' : 'Liquid',
            'primary_indications' => $primaryIndicationNames,
            'supports'            => $health?->supports ?? null,
            'useful_for'          => $health?->useful_for ?? null,
            'contraindications'   => $override?->getEffectiveContraindications() ?? $health?->contraindications ?? null,
            'research'            => $override?->getEffectiveResearchLinks() ?? $health?->research_links ?? null,

            'practitioner_notes'           => $override?->practitioner_notes ?? ($health?->practitioner_notes ?? null),
            'custom_primary_indications'   => $override?->custom_primary_indications ?? [],
            'custom_dosing_notes'          => $override?->custom_dosing_notes ?? ($health?->custom_dosing_notes ?? null),

            'dosing_schedule'     => $dosingSchedule,
            'dosing_na'           => $dosingNa,

            'message_id'          => $product->message_id,
            'updated_at'          => $product->updated_at?->toIso8601String(),
        ];

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
            // ⚡ NEW: IDs للـ indications (مفيد للـ desktop app)
            $data['primary_indication_ids'] = $product->primaryIndications->pluck('id')->toArray();
        }

        return $data;
    }

    private function mergeProductData(Product $product, ?HealthProduct $health, ?ProductCompanyOverride $override): object
    {
        $mergedProduct = $product->toArray();
        $mergedProduct['category'] = $product->category;

        if ($override) {
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
                $category = \App\Models\Category::find($override->category_id);
                if ($category) {
                    $mergedProduct['category'] = $category;
                }
            }
        } else {
            if ($health && $health->description !== null && $product->created_by == getSuperAdminCompanyId()) {
                // للمنتجات المشتركة، الوصف يأتي من المنتج نفسه
            }
        }

        return (object) $mergedProduct;
    }
}
