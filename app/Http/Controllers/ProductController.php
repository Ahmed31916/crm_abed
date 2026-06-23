<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Tax;
use App\Models\Tag;
use App\Models\PrimaryIndication;
use App\Models\HealthProduct;
use App\Models\ProductCompanyOverride;
use App\Models\ProductComparison;
use App\Exports\ProductExport;
use App\Imports\ProductImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

/**
 * ProductController - Updated with HealthProduct, Tags, Dosing, Override support
 *
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║  FIELD STORAGE MAP - أي حقل يروح على أي جدول                      ║
 * ╠══════════════════════════════════════════════════════════════════════╣
 * ║                                                                    ║
 * ║  TABLE: products                                                   ║
 * ║  ├── name, slug, sku, description, specification, detail          ║
 * ║  ├── price, sale_price                                            ║
 * ║  ├── product_weight, tax_id, tax_status                            ║
 * ║  ├── category_id, brand_id, frequency                              ║
 * ║  ├── status                                                        ║
 * ║  ├── created_by, assigned_to                                       ║
 * ║  └── main_image_id, additional_image_ids (Spatie MediaLibrary)     ║
 * ║                                                                    ║
 * ║  TABLE: health_products                                            ║
 * ║  ├── product_id, created_by                                        ║
 * ║  ├── sku (product_sku in form), product_form, bottle_size          ║
 * ║  ├── bottle_size_unit (auto: Liquid→oz, Caps→caps)                ║
 * ║  ├── product_image_url, full_name                                  ║
 * ║  ├── ingredients, contraindications, research_links                ║
 * ║  ├── supports, useful_for, primary_indications (JSON)              ║
 * ║  ├── dosing_upon_rising..dosing_before_sleep (7 fields)            ║
 * ║  └── dosing_na (boolean)                                           ║
 * ║                                                                    ║
 * ║  TABLE: tags                                                       ║
 * ║  └── id, name, slug, color, status, company_id                     ║
 * ║                                                                    ║
 * ║  TABLE: primary_indications                                        ║
 * ║  └── id, name, company_id                                          ║
 * ║                                                                    ║
 * ║  TABLE: product_tags (pivot)                                       ║
 * ║  └── product_id, tag_id, created_by                                ║
 * ║                                                                    ║
 * ║  TABLE: product_pairs (pivot)                                      ║
 * ║  └── product_id, paired_product_id, created_by                     ║
 * ║                                                                    ║
 * ║  TABLE: product_company_overrides                                  ║
 * ║  ├── product_id, company_id, is_visible                            ║
 * ║  ├── price_override, sale_price_override                           ║
 * ║  ├── stock_quantity_override, stock_status_override                 ║
 * ║  ├── description, contraindications, research_links                ║
 * ║  ├── category_id, primary_indications (JSON)                       ║
 * ║  ├── dosing_upon_rising..dosing_before_sleep, dosing_na            ║
 * ║  ├── practitioner_notes, custom_primary_indications (JSON)         ║
 * ║  └── custom_dosing_notes                                           ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 *
 * REQUIRED MIGRATION: 2026_01_01_000010_add_missing_product_fields_and_pairs_table.php
 * This migration adds: specification, detail,
 * product_weight, tax_status, frequency, slug
 * to the products table + creates product_pairs table.
 *
 * REQUIRED MIGRATIONS (added):
 *  - 2026_06_16_000001_create_tags_table.php (with company_id)
 *  - 2026_06_16_000002_create_primary_indications_table.php (with company_id)
 */
class ProductController extends Controller
{
    // =========================================================================
    // =========== INDEX =======================================================
    // =========================================================================

    public function index(Request $request)
    {
        $currentCompanyId = createdBy();
        $visibleCompanyIds = getVisibleCompanyIds();

        $query = Product::query()
            ->with(['category', 'brand', 'tax', 'assignedUser', 'media', 'tags', 'healthProduct'])
            ->whereIn('created_by', $visibleCompanyIds);

        if (!auth()->user()->isSuperAdmin()) {
            $query->orderByRaw('CASE WHEN created_by = ? THEN 0 ELSE 1 END', [$currentCompanyId]);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('sku', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('brand') && $request->brand !== 'all') {
            $query->where('brand_id', $request->brand);
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('stock_status') && $request->stock_status !== 'all') {
            $query->where('stock_status', $request->stock_status);
        }
        if ($request->filled('tag') && $request->tag !== 'all') {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('tags.id', $request->tag);
            });
        }
        if (auth()->user()->isSuperAdmin() && $request->filled('company_id') && $request->company_id !== 'all') {
            $query->where('created_by', $request->company_id);
        }

        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');
        if (in_array($sortField, ['id', 'name', 'price', 'stock_quantity', 'created_at']) && in_array($sortDirection, ['asc', 'desc'])) {
            $query->orderBy($sortField, $sortDirection);
        }

        $products = $query->paginate($request->get('per_page', 10));

        // Apply company overrides for non-super-admin users
        if (!auth()->user()->isSuperAdmin()) {
            $products->each(function ($product) use ($currentCompanyId) {
                // FIX: نتجاهل منتجات الشركة الحالية لأنها لا تحتاج override.
                if (isSuperAdminProduct($product)
                    && (int) $product->created_by !== (int) $currentCompanyId) {
                    $override = ProductCompanyOverride::where('product_id', $product->id)
                        ->where('company_id', $currentCompanyId)->first();
                    if ($override) {
                        $product->price = $override->getEffectivePrice();
                        $product->sale_price = $override->sale_price_override;
                        $product->stock_quantity = $override->getEffectiveStock();
                        $product->stock_status = $override->getEffectiveStockStatus();
                    }
                }
            });
        }

        return Inertia::render('products/index', [
            'products' => $products,
            'categories' => Category::where('created_by', $currentCompanyId)->where('status', 'active')->get(['id', 'name']),
            'brands' => Brand::where('created_by', $currentCompanyId)->where('status', 'active')->get(['id', 'name']),
            'taxes' => Tax::where('created_by', $currentCompanyId)->where('status', 'active')->get(['id', 'name', 'rate']),
            'tags' => Tag::visibleTo($currentCompanyId)->orderBy('name')->get(['id', 'name', 'color']),
            'primaryIndications' => PrimaryIndication::visibleTo($currentCompanyId)->orderBy('name')->get(['id', 'name']),
            'users' => auth()->user()->type === 'company'
                ? \App\Models\User::where('created_by', $currentCompanyId)->select('id', 'name', 'email')->get()
                : [],
            'companies' => auth()->user()->isSuperAdmin()
                ? \App\Models\User::where('type', 'company')->select('id', 'name', 'email')->get()
                : [],
            'productLimitInfo' => auth()->user()->type === 'company' ? [
                'current' => Product::where('created_by', $currentCompanyId)->count(),
                'limit' => auth()->user()->product_limit ?? 10,
                'can_create' => Product::where('created_by', $currentCompanyId)->count() < (auth()->user()->product_limit ?? 10),
            ] : null,
            'isSuperAdmin' => auth()->user()->isSuperAdmin(),
            'superAdminCompanyId' => getSuperAdminCompanyId(),
            'filters' => $request->all(['search', 'category', 'brand', 'status', 'stock_status', 'tag', 'company_id', 'sort_field', 'sort_direction', 'per_page', 'page']),
        ]);
    }

    // =========================================================================
    // =========== CREATE ======================================================
    // =========================================================================

    public function create()
    {
        if (hasReachedProductLimit()) {
            return redirect()->route('products.index')->with('error', __('Product limit reached.'));
        }

        $currentCompanyId = createdBy();
        $superAdminId = getSuperAdminCompanyId();

        return Inertia::render('products/create', [
            'categories' => Category::where('created_by', $currentCompanyId)->where('status', 'active')->get(['id', 'name']),
            'brands' => Brand::where('created_by', $currentCompanyId)->where('status', 'active')->get(['id', 'name']),
            'taxes' => Tax::where('created_by', $currentCompanyId)->where('status', 'active')->get(['id', 'name', 'rate']),
            'tags' => Tag::visibleTo($currentCompanyId)->orderBy('name')->get(['id', 'name', 'color']),
            'primaryIndications' => PrimaryIndication::visibleTo($currentCompanyId)->orderBy('name')->get(['id', 'name']),
            'availableProducts' => Product::where('status', 'active')
                ->where(function ($q) use ($currentCompanyId, $superAdminId) {
                    $q->where('created_by', $currentCompanyId)->orWhere('created_by', $superAdminId);
                })->select('id', 'name')->orderBy('name')->get(),
            'users' => auth()->user()->type === 'company'
                ? \App\Models\User::where('created_by', $currentCompanyId)->select('id', 'name', 'email')->get()
                : [],
        ]);
    }

    // =========================================================================
    // =========== STORE (Create Product) ======================================
    // =========================================================================

    public function store(Request $request)
    {
        if (hasReachedProductLimit()) {
            return redirect()->back()->with('error', __('Product limit reached.'));
        }

        $validated = $request->validate([
            // ===== TABLE: products =====
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string',
            'specification'     => 'nullable|string',     // ← products.specification
            'detail'            => 'nullable|string',     // ← products.detail
            'price'             => 'required|numeric|min:0',
            'sale_price'        => 'nullable|numeric|min:0|lt:price',
            'category_id'       => 'required|exists:categories,id',
            'brand_id'          => 'nullable|exists:brands,id',
            'tax_id'            => 'nullable|exists:taxes,id',
            'status'            => 'nullable|in:active,inactive',
            'stock_status'      => 'nullable|in:in_stock,out_of_stock,on_backorder',
            'stock_quantity'    => 'nullable|integer|min:0',
            'product_weight'    => 'nullable|numeric|min:0',
            'main_image_id'     => 'nullable|exists:media,id',
            'additional_image_ids' => 'nullable|array',
            'additional_image_ids.*' => 'exists:media,id',

            // ===== TABLE: health_products (via product_sku) =====
            'product_sku'       => 'required|string|max:255',

            // ===== TABLE: health_products =====
            'product_form'      => 'required|in:Liquid,Caps',
            'bottle_size'       => 'required|numeric|min:0',
            'product_image_url' => 'nullable|url|max:500',
            'full_name'         => 'nullable|string|max:500',
            'supports'          => 'nullable|string|max:1000',
            'useful_for'        => 'nullable|string|max:1000',
            'ingredients'       => 'nullable|string|max:1000',
            'contraindications' => 'nullable|string|max:2000',
            'research_links'    => 'nullable|string|max:2000',
            'primary_indications' => 'nullable|array',
            'dosing_upon_rising'     => 'nullable|string|max:255',
            'dosing_breakfast'       => 'nullable|string|max:255',
            'dosing_between_meals_am'=> 'nullable|string|max:255',
            'dosing_lunch'           => 'nullable|string|max:255',
            'dosing_between_meals_pm'=> 'nullable|string|max:255',
            'dosing_dinner'          => 'nullable|string|max:255',
            'dosing_before_sleep'    => 'nullable|string|max:255',
            'dosing_na'              => 'nullable|boolean',

            // ===== TABLE: product_tags (pivot) =====
            'tag_id'            => 'required|array|min:1',
            'tag_id.*'          => 'exists:tags,id',

            // ===== TABLE: product_pairs (pivot) =====
            'pairs_well_with'   => 'nullable|array',
            'pairs_well_with.*' => 'exists:products,id',

            // ===== TABLE: product_company_overrides =====
            'practitioner_notes'         => 'nullable|string|max:5000',
            'custom_primary_indications' => 'nullable|array',
            'custom_dosing_notes'        => 'nullable|string|max:5000',
        ]);

        try {
            DB::beginTransaction();

            $currentCompanyId = createdBy();
            $dosingNa = $request->boolean('dosing_na');

            // ==================== TABLE: products ====================
            $product = new Product();
            $product->name = $validated['name'];
            $product->slug = \Illuminate\Support\Str::slug($validated['name']);
            $product->sku = $validated['product_sku'];
            $product->description = $validated['description'] ?? null;
            $product->specification = $validated['specification'] ?? null;
            $product->detail = $validated['detail'] ?? null;
            $product->price = $validated['price'];
            $product->sale_price = $validated['sale_price'] ?? 0;
            $product->stock_quantity = $validated['stock_quantity'] ?? 0;
            $product->stock_status = $validated['stock_status'] ?? 'in_stock';
            $product->product_weight = $validated['product_weight'] ?? null;
            $product->category_id = $validated['category_id'];
            $product->brand_id = $validated['brand_id'] ?? null;
            $product->tax_id = $validated['tax_id'] ?? null;
            $product->status = $validated['status'] ?? 'active';
            $product->created_by = $currentCompanyId;

            if (!empty($validated['main_image_id'])) {
                $product->main_image_id = $validated['main_image_id'];
            }

            $product->save();

            if (!empty($validated['additional_image_ids'])) {
                $product->additional_image_ids = $validated['additional_image_ids'];
                $product->save();
            }

            // ==================== TABLE: product_tags (pivot) ====================
            $this->saveTagsToPivot($product->id, $validated['tag_id'], $currentCompanyId);

            // ==================== TABLE: health_products ====================
            HealthProduct::updateOrCreate(
                ['product_id' => $product->id, 'created_by' => $currentCompanyId],
                [
                    'sku'                     => $validated['product_sku'],
                    'product_form'            => $validated['product_form'],
                    'bottle_size'             => $validated['bottle_size'],
                    'bottle_size_unit'        => ($validated['product_form'] == 'Liquid') ? 'oz' : 'caps',
                    'product_image_url'       => $validated['product_image_url'] ?? null,
                    'full_name'               => $validated['full_name'] ?? null,
                    'ingredients'             => $validated['ingredients'] ?? null,
                    'contraindications'       => $validated['contraindications'] ?? 'N/A',
                    'research_links'          => $validated['research_links'] ?? null,
                    'supports'                => $validated['supports'] ?? null,
                    'useful_for'              => $validated['useful_for'] ?? null,
                    'primary_indications'     => $validated['primary_indications'] ?? [],
                    'dosing_upon_rising'      => $dosingNa ? null : ($validated['dosing_upon_rising'] ?? null),
                    'dosing_breakfast'        => $dosingNa ? null : ($validated['dosing_breakfast'] ?? null),
                    'dosing_between_meals_am' => $dosingNa ? null : ($validated['dosing_between_meals_am'] ?? null),
                    'dosing_lunch'            => $dosingNa ? null : ($validated['dosing_lunch'] ?? null),
                    'dosing_between_meals_pm' => $dosingNa ? null : ($validated['dosing_between_meals_pm'] ?? null),
                    'dosing_dinner'           => $dosingNa ? null : ($validated['dosing_dinner'] ?? null),
                    'dosing_before_sleep'     => $dosingNa ? null : ($validated['dosing_before_sleep'] ?? null),
                    'dosing_na'               => $dosingNa,
                ]
            );

            // ==================== TABLE: product_pairs (pivot) ====================
            if (!empty($validated['pairs_well_with'])) {
                $this->syncPairsWellWith($product->id, $validated['pairs_well_with'], $currentCompanyId);
            }

            // ==================== TABLE: product_company_overrides ====================
            ProductCompanyOverride::updateOrCreate(
                ['product_id' => $product->id, 'company_id' => $currentCompanyId],
                [
                    'practitioner_notes'         => $validated['practitioner_notes'] ?? null,
                    'custom_primary_indications' => $validated['custom_primary_indications'] ?? [],
                    'custom_dosing_notes'        => $validated['custom_dosing_notes'] ?? null,
                    'is_visible'                 => true,
                ]
            );

            // ==================== Dispatch Product Event ====================
            // FIX: نُرفق slug الشركة التي أنشأت المنتج.
            $createdProductId = $product->id;
            $companySlug = $this->getCurrentCompanySlug($currentCompanyId);
            DB::afterCommit(function () use ($createdProductId, $companySlug) {
                \App\Observers\ProductObserver::dispatchProductEvent(
                    $createdProductId,
                    'created',
                    [
                        'company_slug' => $companySlug,
                        'override_mode' => false,
                    ]
                );
            });

            DB::commit();

            return redirect()->route('products.index')->with('success', __('Product created successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Product store failed: " . $e->getMessage());
            return redirect()->back()->with('error', __('Failed to create product: :error', ['error' => $e->getMessage()]))->withInput();
        }
    }

    // =========================================================================
    // =========== SHOW ========================================================
    // =========================================================================

    public function show($id)
    {
        $product = Product::with(['category', 'brand', 'tax', 'assignedUser', 'creator', 'media', 'tags', 'healthProduct'])
            ->whereIn('created_by', getVisibleCompanyIds())
            ->findOrFail($id);

        $currentCompanyId = createdBy();
        // FIX: نطبّق نفس منطق edit()/update() هنا لتجنب اعتبار منتجات الشركة
        // نفسها كمنتجات سوبر ادمن.
        $isSuperAdminProduct = isSuperAdminProduct($product)
            && (int) $product->created_by !== (int) $currentCompanyId;

        if (!auth()->user()->isSuperAdmin() && $isSuperAdminProduct) {
            $override = ProductCompanyOverride::where('product_id', $product->id)
                ->where('company_id', $currentCompanyId)->first();
            if ($override) {
                $product->price = $override->getEffectivePrice();
                $product->sale_price = $override->sale_price_override;
                $product->stock_quantity = $override->getEffectiveStock();
                $product->stock_status = $override->getEffectiveStockStatus();
            }
        }

        return Inertia::render('products/show', [
            'product' => $product,
            'mainImage' => $product->main_image_url,
            'additionalImages' => $product->additional_image_urls,
            'healthProduct' => $product->healthProduct,
            'canEdit' => canEditProduct($product),
            'canDelete' => canDeleteProduct($product),
            'isSuperAdminProduct' => $isSuperAdminProduct,
        ]);
    }

    // =========================================================================
    // =========== EDIT ========================================================
    // =========================================================================

    public function edit($id)
    {
        $currentCompanyId = createdBy();
        $superAdminId = getSuperAdminCompanyId();

        $product = Product::with(['category', 'brand', 'tax', 'assignedUser', 'creator', 'media', 'tags', 'healthProduct'])
            ->whereIn('created_by', getVisibleCompanyIds())
            ->findOrFail($id);

        // ════════════════════════════════════════════════════════════════════
        // FIX: تعتبر المنتجات "منتجات سوبر ادمن" فقط إذا كانت منشأة من السوبر
        // ادمن الحقيقي وليست منشأة من الشركة الحالية. بدون هذا الشرط، إذا كانت
        // الشركة الحالية هي نفسها getSuperAdminCompanyId() (مثلاً عندما يكون
        // المالك هو السوبر ادمن بنفسه لكنه يسجّل الدخول كـ "company")، فإن
        // كل منتجاته الخاصة ستظهر بشكل خاطئ على أنها "منتجات سوبر ادمن"
        // وتظهر رسالة "You are editing a Super Admin product" بالخطأ.
        // ════════════════════════════════════════════════════════════════════
        $isSuperAdminProduct = isSuperAdminProduct($product)
            && (int) $product->created_by !== (int) $currentCompanyId;

        // Load override data
        $override = ProductCompanyOverride::where('product_id', $product->id)
            ->where('company_id', $currentCompanyId)
            ->first();

        if (!auth()->user()->isSuperAdmin() && $isSuperAdminProduct && $override) {
            $product->price = $override->getEffectivePrice();
            $product->sale_price = $override->sale_price_override;
            $product->stock_quantity = $override->getEffectiveStock();
            $product->stock_status = $override->getEffectiveStockStatus();
        }

        return Inertia::render('products/edit', [
            'product' => array_merge($product->toArray(), [
                'main_image_id' => $product->main_image_id,
                'additional_image_ids' => $product->additional_image_ids ?: [],
                'tags' => $product->tags->map(fn($tag) => ['id' => $tag->id, 'name' => $tag->name])->toArray(),
                'pairs_well_with_ids' => $product->pairsWellWith->pluck('id')->toArray(),
            ]),
            'categories' => Category::where('created_by', $currentCompanyId)->where('status', 'active')->get(['id', 'name']),
            'brands' => Brand::where('created_by', $currentCompanyId)->where('status', 'active')->get(['id', 'name']),
            'taxes' => Tax::where('created_by', $currentCompanyId)->where('status', 'active')->get(['id', 'name', 'rate']),
            'tags' => Tag::visibleTo($currentCompanyId)->orderBy('name')->get(['id', 'name', 'color']),
            'primaryIndications' => PrimaryIndication::visibleTo($currentCompanyId)->orderBy('name')->get(['id', 'name']),
            'availableProducts' => Product::where('status', 'active')
                ->where(function ($q) use ($currentCompanyId, $superAdminId) {
                    $q->where('created_by', $currentCompanyId)->orWhere('created_by', $superAdminId);
                })
                ->where('id', '!=', $product->id)
                ->select('id', 'name')->orderBy('name')->get(),
            'users' => auth()->user()->type === 'company'
                ? \App\Models\User::where('created_by', $currentCompanyId)->select('id', 'name', 'email')->get()
                : [],
            'mainImage' => $product->main_image_url,
            'additionalImages' => $product->additional_image_urls,
            'healthProduct' => $product->healthProduct,
            'override' => $override,
            'isSuperAdminProduct' => $isSuperAdminProduct,
            'canEditOriginal' => canEditProduct($product),
        ]);
    }

    // =========================================================================
    // =========== UPDATE ======================================================
    // =========================================================================

    public function update(Request $request, $productId)
    {
        $product = Product::whereIn('created_by', getVisibleCompanyIds())->find($productId);
        if (!$product) {
            return redirect()->back()->with('error', __('Product not found.'));
        }

        $currentCompanyId = createdBy();
        // ════════════════════════════════════════════════════════════════════
        // FIX: نطبّق نفس منطق دالة edit() — المنتج يُعتبر "سوبر ادمن" فقط
        // إذا كان منشأ من شركة أخرى غير الشركة الحالية. هذا يمنع توجيه
        // تحديث منتج الشركة نفسه إلى مسار updateOverride() بالخطأ.
        // ════════════════════════════════════════════════════════════════════
        $isSuperAdminProduct = isSuperAdminProduct($product)
            && (int) $product->created_by !== (int) $currentCompanyId;

        // Company user editing super admin product → update override only
        if (!auth()->user()->isSuperAdmin() && $isSuperAdminProduct) {
            return $this->updateOverride($request, $product);
        }

        // ===== Full update (own product or super admin editing) =====
        $validated = $request->validate([
            // TABLE: products
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string',
            'specification'     => 'nullable|string',
            'detail'            => 'nullable|string',
            'price'             => 'required|numeric|min:0',
            'sale_price'        => 'nullable|numeric|min:0|lt:price',
            'category_id'       => 'required|exists:categories,id',
            'brand_id'          => 'nullable|exists:brands,id',
            'tax_id'            => 'nullable|exists:taxes,id',
            'status'            => 'nullable|in:active,inactive',
            'stock_status'      => 'nullable|in:in_stock,out_of_stock,on_backorder',
            'stock_quantity'    => 'nullable|integer|min:0',
            'product_weight'    => 'nullable|numeric|min:0',
            'main_image_id'     => 'nullable|exists:media,id',
            'additional_image_ids' => 'nullable|array',
            'additional_image_ids.*' => 'exists:media,id',
            // TABLE: health_products
            'product_sku'       => 'required|string|max:255',
            'product_form'      => 'required|in:Liquid,Caps',
            'bottle_size'       => 'required|numeric|min:0',
            'product_image_url' => 'nullable|url|max:500',
            'full_name'         => 'nullable|string|max:500',
            'supports'          => 'nullable|string|max:1000',
            'useful_for'        => 'nullable|string|max:1000',
            'ingredients'       => 'nullable|string|max:1000',
            'contraindications' => 'nullable|string|max:2000',
            'research_links'    => 'nullable|string|max:2000',
            'primary_indications' => 'nullable|array',
            'dosing_upon_rising'     => 'nullable|string|max:255',
            'dosing_breakfast'       => 'nullable|string|max:255',
            'dosing_between_meals_am'=> 'nullable|string|max:255',
            'dosing_lunch'           => 'nullable|string|max:255',
            'dosing_between_meals_pm'=> 'nullable|string|max:255',
            'dosing_dinner'          => 'nullable|string|max:255',
            'dosing_before_sleep'    => 'nullable|string|max:255',
            'dosing_na'              => 'nullable|boolean',
            // TABLE: product_tags
            'tag_id'            => 'required|array|min:1',
            'tag_id.*'          => 'exists:tags,id',
            // TABLE: product_pairs
            'pairs_well_with'   => 'nullable|array',
            'pairs_well_with.*' => 'exists:products,id',
            // TABLE: product_company_overrides
            'practitioner_notes'         => 'nullable|string|max:5000',
            'custom_primary_indications' => 'nullable|array',
            'custom_dosing_notes'        => 'nullable|string|max:5000',
        ]);

        try {
            DB::beginTransaction();
            $dosingNa = $request->boolean('dosing_na');

            // ===== TABLE: products =====
            $product->name = $validated['name'];
            $product->slug = \Illuminate\Support\Str::slug($validated['name']);
            // SKU NOT updated on products table (read-only after creation)
            $product->description = $validated['description'] ?? null;
            $product->specification = $validated['specification'] ?? null;
            $product->detail = $validated['detail'] ?? null;
            $product->price = $validated['price'];
            $product->sale_price = $validated['sale_price'] ?? 0;
            $product->stock_quantity = $validated['stock_quantity'] ?? 0;
            $product->stock_status = $validated['stock_status'] ?? 'in_stock';
            $product->product_weight = $validated['product_weight'] ?? null;
            $product->category_id = $validated['category_id'];
            $product->brand_id = $validated['brand_id'] ?? null;
            $product->tax_id = $validated['tax_id'] ?? null;
            $product->status = $validated['status'] ?? 'active';

            if (!empty($validated['main_image_id'])) {
                $product->main_image_id = $validated['main_image_id'];
            }
            if (isset($validated['additional_image_ids'])) {
                $product->additional_image_ids = $validated['additional_image_ids'];
            }

            $product->saveQuietly();

            // ===== TABLE: product_tags =====
            $this->saveTagsToPivot($product->id, $validated['tag_id'], $currentCompanyId);

            // ===== TABLE: health_products =====
            HealthProduct::updateOrCreate(
                ['product_id' => $product->id, 'created_by' => $currentCompanyId],
                [
                    'sku'                     => $validated['product_sku'],
                    'product_form'            => $validated['product_form'],
                    'bottle_size'             => $validated['bottle_size'],
                    'bottle_size_unit'        => ($validated['product_form'] == 'Liquid') ? 'oz' : 'caps',
                    'product_image_url'       => $validated['product_image_url'] ?? null,
                    'full_name'               => $validated['full_name'] ?? null,
                    'ingredients'             => $validated['ingredients'] ?? null,
                    'contraindications'       => $validated['contraindications'] ?? 'N/A',
                    'research_links'          => $validated['research_links'] ?? null,
                    'supports'                => $validated['supports'] ?? null,
                    'useful_for'              => $validated['useful_for'] ?? null,
                    'primary_indications'     => $validated['primary_indications'] ?? [],
                    'dosing_upon_rising'      => $dosingNa ? null : ($validated['dosing_upon_rising'] ?? null),
                    'dosing_breakfast'        => $dosingNa ? null : ($validated['dosing_breakfast'] ?? null),
                    'dosing_between_meals_am' => $dosingNa ? null : ($validated['dosing_between_meals_am'] ?? null),
                    'dosing_lunch'            => $dosingNa ? null : ($validated['dosing_lunch'] ?? null),
                    'dosing_between_meals_pm' => $dosingNa ? null : ($validated['dosing_between_meals_pm'] ?? null),
                    'dosing_dinner'           => $dosingNa ? null : ($validated['dosing_dinner'] ?? null),
                    'dosing_before_sleep'     => $dosingNa ? null : ($validated['dosing_before_sleep'] ?? null),
                    'dosing_na'               => $dosingNa,
                ]
            );

            // ===== TABLE: product_pairs =====
            $pairIds = $validated['pairs_well_with'] ?? [];
            $this->syncPairsWellWith($product->id, $pairIds, $currentCompanyId);

            // ===== TABLE: product_company_overrides =====
            ProductCompanyOverride::updateOrCreate(
                ['product_id' => $product->id, 'company_id' => $currentCompanyId],
                [
                    'practitioner_notes'         => $validated['practitioner_notes'] ?? null,
                    'custom_primary_indications' => $validated['custom_primary_indications'] ?? [],
                    'custom_dosing_notes'        => $validated['custom_dosing_notes'] ?? null,
                ]
            );

            // ==================== Dispatch Product Event ====================
            // Note: saveQuietly() above does NOT trigger observer, so we dispatch manually.
            // FIX: نُرفق slug الشركة التي قامت بالتعديل (سواء كانت شركة عادية أو
            // السوبر ادمن نفسه) لكي يستطيع الـ desktop app معرفة أي شركة يجب
            // أن تستقبل/تحديث نسختها من المنتج.
            $updatedProductId = $product->id;
            $companySlug = $this->getCurrentCompanySlug($currentCompanyId);

            DB::afterCommit(function () use ($updatedProductId, $companySlug) {
                \App\Observers\ProductObserver::dispatchProductEvent(
                    $updatedProductId,
                    'updated',
                    [
                        'company_slug' => $companySlug,
                        'override_mode' => false,
                    ]
                );
            });

            DB::commit();
            return redirect()->back()->with('success', __('Product updated successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Product update failed: " . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update product.'));
        }
    }

    // =========================================================================
    // =========== UPDATE OVERRIDE (Company editing Super Admin product) =======
    // =========================================================================

    private function updateOverride(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id'              => 'required|exists:categories,id',
            'description'              => 'nullable|string',
            'sale_price'               => 'nullable|numeric|min:0',
            'primary_indications'      => 'nullable|array',
            'contraindications'        => 'nullable|string|max:2000',
            'research_links'           => 'nullable|string|max:2000',
            'dosing_upon_rising'       => 'nullable|string|max:255',
            'dosing_breakfast'         => 'nullable|string|max:255',
            'dosing_between_meals_am'  => 'nullable|string|max:255',
            'dosing_lunch'             => 'nullable|string|max:255',
            'dosing_between_meals_pm'  => 'nullable|string|max:255',
            'dosing_dinner'            => 'nullable|string|max:255',
            'dosing_before_sleep'      => 'nullable|string|max:255',
            'dosing_na'                => 'nullable|boolean',
            'tag_id'                   => 'required|array|min:1',
            'tag_id.*'                 => 'exists:tags,id',
            'practitioner_notes'       => 'nullable|string|max:5000',
            'custom_primary_indications'=> 'nullable|array',
            'custom_dosing_notes'      => 'nullable|string|max:5000',
        ]);

        try {
            DB::beginTransaction();
            $currentCompanyId = createdBy();
            $dosingNa = $request->boolean('dosing_na');

            ProductCompanyOverride::updateOrCreate(
                ['product_id' => $product->id, 'company_id' => $currentCompanyId],
                [
                    'description'               => $validated['description'] ?? null,
                    'primary_indications'       => $validated['primary_indications'] ?? [],
                    'contraindications'         => $validated['contraindications'] ?? null,
                    'research_links'            => $validated['research_links'] ?? null,
                    'category_id'               => $validated['category_id'],
                    'sale_price_override'       => $validated['sale_price'] ?? null,
                    'dosing_upon_rising'        => $dosingNa ? null : ($validated['dosing_upon_rising'] ?? null),
                    'dosing_breakfast'          => $dosingNa ? null : ($validated['dosing_breakfast'] ?? null),
                    'dosing_between_meals_am'   => $dosingNa ? null : ($validated['dosing_between_meals_am'] ?? null),
                    'dosing_lunch'              => $dosingNa ? null : ($validated['dosing_lunch'] ?? null),
                    'dosing_between_meals_pm'   => $dosingNa ? null : ($validated['dosing_between_meals_pm'] ?? null),
                    'dosing_dinner'             => $dosingNa ? null : ($validated['dosing_dinner'] ?? null),
                    'dosing_before_sleep'       => $dosingNa ? null : ($validated['dosing_before_sleep'] ?? null),
                    'dosing_na'                 => $dosingNa,
                    'practitioner_notes'         => $validated['practitioner_notes'] ?? null,
                    'custom_primary_indications' => $validated['custom_primary_indications'] ?? [],
                    'custom_dosing_notes'        => $validated['custom_dosing_notes'] ?? null,
                    'is_visible' => true,
                ]
            );

            $this->saveTagsToPivot($product->id, $validated['tag_id'], $currentCompanyId);

            // ════════════════════════════════════════════════════════════════════
            // FIX: عند تعديل منتج من السوبر ادمن من قبل صاحب الشركة، يجب أن
            // تحتوي رسالة الـ RabbitMQ على الـ slug الخاص بصاحب الشركة (وليس
            // slug السوبر ادمن) لكي يستطيع الـ desktop app للشركة تحديث نسخته
            // المحلية من المنتج بناءً على override الخاص به.
            //
            // نمرر override_company_id لكي يبني الـ Observer الـ payload من
            // منظور الشركة المُعدِّلة (practitioner, tags, override, dosing).
            // ════════════════════════════════════════════════════════════════════
            $overrideProductId = $product->id;
            $companySlug = $this->getCurrentCompanySlug($currentCompanyId);

            DB::afterCommit(function () use ($overrideProductId, $companySlug, $currentCompanyId) {
                \App\Observers\ProductObserver::dispatchProductEvent(
                    $overrideProductId,
                    'updated',
                    [
                        'company_slug'        => $companySlug,
                        'override_company_id' => $currentCompanyId,
                        'override_mode'       => true,
                    ]
                );
            });

            DB::commit();

            return redirect()->back()->with('success', __('Product override updated successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Product override update failed: " . $e->getMessage());
            return redirect()->back()->with('error', __('Failed to update product override: :error', ['error' => $e->getMessage()]));
        }
    }

    // =========================================================================
    // =========== DESTROY =====================================================
    // =========================================================================

    public function destroy($productId)
    {
        $product = Product::whereIn('created_by', getVisibleCompanyIds())->find($productId);
        if (!$product) {
            return redirect()->back()->with('error', __('Product not found.'));
        }
        if (!canDeleteProduct($product)) {
            return redirect()->back()->with('error', __('You cannot delete this product.'));
        }

        try {
            DB::beginTransaction();
            HealthProduct::where('product_id', $product->id)->delete();
            ProductCompanyOverride::where('product_id', $product->id)->delete();
            DB::table('product_tags')->where('product_id', $product->id)->delete();
            DB::table('product_pairs')->where('product_id', $product->id)->delete();
            $product->delete();
            DB::commit();
            return redirect()->back()->with('success', __('Product deleted successfully.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete product.'));
        }
    }

    // =========================================================================
    // =========== TOGGLE STATUS ===============================================
    // =========================================================================

    public function toggleStatus($productId)
    {
        $product = Product::whereIn('created_by', getVisibleCompanyIds())->find($productId);
        if (!$product || !canEditProduct($product)) {
            return redirect()->back()->with('error', __('Cannot edit this product.'));
        }
        $product->status = $product->status === 'active' ? 'inactive' : 'active';
        $product->save();
        return redirect()->back()->with('success', __('Product status updated.'));
    }

    // =========================================================================
    // =========== EXPORT / IMPORT =============================================
    // =========================================================================

    public function fileExport()
    {
        if (!auth()->user()->can('export-products')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
        return Excel::download(new ProductExport(), 'product_' . date('Y-m-d') . '.xlsx');
    }

    public function downloadTemplate()
    {
        $filePath = storage_path('uploads/sample/sample-product.xlsx');
        if (!file_exists($filePath)) {
            return response()->json(['error' => __('Template not available')], 404);
        }
        return response()->download($filePath, 'sample-product.xlsx');
    }

    public function parseFile(Request $request)
    {
        $request->validate(['file' => 'required|mimes:csv,txt,xlsx']);
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($request->file('file')->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $headers = [];
            for ($col = 'A'; $col <= $worksheet->getHighestColumn(); $col++) {
                if ($val = $worksheet->getCell($col . '1')->getValue()) $headers[] = (string)$val;
            }
            $previewData = [];
            for ($row = 2; $row <= $worksheet->getHighestRow(); $row++) {
                $rowData = [];
                $colIndex = 0;
                for ($col = 'A'; $col <= $worksheet->getHighestColumn(); $col++) {
                    if ($colIndex < count($headers)) {
                        $rowData[$headers[$colIndex]] = (string)$worksheet->getCell($col . $row)->getValue();
                    }
                    $colIndex++;
                }
                $previewData[] = $rowData;
            }
            return response()->json(['excelColumns' => $headers, 'previewData' => $previewData]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to parse file.'));
        }
    }

    public function fileImport(Request $request)
    {
        if (!auth()->user()->can('import-products') || hasReachedProductLimit()) {
            return redirect()->back()->with('error', __('Permission denied or limit reached.'));
        }
        $request->validate(['data' => 'required|array']);
        try {
            $tempFile = storage_path('tmp/import_' . time() . '.csv');
            if (!file_exists(dirname($tempFile))) mkdir(dirname($tempFile), 0755, true);
            $handle = fopen($tempFile, 'w');
            fputcsv($handle, array_keys($request->data[0] ?? []));
            foreach ($request->data as $row) fputcsv($handle, $row);
            fclose($handle);
            $import = new ProductImport();
            Excel::import($import, $tempFile);
            unlink($tempFile);
            return redirect()->back()->with('success', __('Import completed: :added added, :skipped skipped', [
                'added' => $import->getAddedCount(), 'skipped' => $import->getSkippedCount()
            ]));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Import failed: :error', ['error' => $e->getMessage()]));
        }
    }

    // =========================================================================
    // =========== IMPORT FROM EXCEL (Super Admin Only) ========================
    // =========================================================================

    /**
     * استيراد المنتجات من ملف Excel
     * فقط السوبر ادمن يقدر يستخدم هاد الميثود
     *
     * Mapping from old project:
     * - isSuperAdmin() → auth()->user()->isSuperAdmin()
     * - getCurrentStore() → getSuperAdminCompanyId()
     * - store_id → created_by
     * - ProductMerchantOverride → ProductCompanyOverride
     * - ProductBrand → Brand
     * - status 1/0 → 'active'/'inactive'
     * - PrimaryIndication pivot → primary_indications JSON on health_products
     * - cover_image_url → Spatie MediaLibrary (not set here, use product_image_url on health_products)
     * - variant_product, trending, custom_field_status → REMOVED
     *
     * NOTE: tags table uses company_id (not created_by).
     *       product_tags pivot still uses created_by.
     */
    public function importFromExcel(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json([
                'flag' => 'error',
                'msg'  => __('Permission denied.')
            ]);
        }

        try {
            $request->validate([
                'import_file' => 'required|file|mimes:xlsx,xls,csv|max:20480',
            ]);

            $file = $request->file('import_file');

            // Load the spreadsheet
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $worksheet   = $spreadsheet->getActiveSheet();
            $rows        = $worksheet->toArray(null, true, true, true);

            if (count($rows) < 2) {
                return response()->json([
                    'flag' => 'error',
                    'msg'  => __('The file is empty or has no data rows.')
                ]);
            }

            // ---- Build header map (normalize to lowercase trimmed keys) ----
            $rawHeaders = array_shift($rows);
            $headerMap  = [];
            foreach ($rawHeaders as $col => $header) {
                if ($header !== null && $header !== '') {
                    $normalizedKey           = strtolower(trim((string) $header));
                    $headerMap[$normalizedKey] = $col;
                }
            }

            Log::info("Excel Import: Started", [
                'headers' => array_keys($headerMap),
                'total_rows' => count($rows),
            ]);

            $superAdminCompanyId = getSuperAdminCompanyId();
            $importedCount       = 0;
            $updatedCount        = 0;
            $skippedCount        = 0;
            $errorCount          = 0;
            $errorMessages       = [];

            foreach ($rows as $rowIndex => $row) {
                $displayRow = $rowIndex + 2;

                try {
                    // Normalize row data into an associative array keyed by header
                    $item = [];
                    foreach ($headerMap as $key => $col) {
                        $item[$key] = $row[$col] ?? null;
                    }

                    // ---- SKU (required) - Fix numeric SKUs ----
                    $skuRaw = $item['sku'] ?? null;
                    $sku = $this->cleanImportValue($skuRaw);

                    // Fix: PhpSpreadsheet may read numeric SKUs as float (e.g., 99000202.0)
                    if ($sku !== null && is_numeric($sku)) {
                        $sku = (string) intval(floatval($sku));
                    }

                    if (empty($sku)) {
                        $skippedCount++;
                        $errorMessages[] = "Row {$displayRow}: Missing SKU — skipped.";
                        continue;
                    }

                    // ---- Check if SKU already exists ----
                    $existingHealthProduct = HealthProduct::where('sku', $sku)->first();
                    $isUpdate = !is_null($existingHealthProduct);

                    // ---- Category ----
                    $categoryId   = null;
                    $categoryName = $this->cleanImportValue($item['category'] ?? null);
                    if ($categoryName) {
                        $categoryName = html_entity_decode($categoryName, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                        $category = Category::where('name', $categoryName)
                            ->where('created_by', $superAdminCompanyId)
                            ->first();

                        if (!$category) {
                            $categorySlug  = Str::slug($categoryName);
                            $originalSlug  = $categorySlug;
                            $counter       = 1;
                            while (Category::where('slug', $categorySlug)->exists()) {
                                $categorySlug = $originalSlug . '-' . $counter++;
                            }

                            $category = Category::create([
                                'name'       => $categoryName,
                                'slug'       => $categorySlug,
                                'created_by' => $superAdminCompanyId,
                                'company_id' => $superAdminCompanyId,
                                'status'     => 'active',
                            ]);
                        }
                        $categoryId = $category->id;
                    }

                    // ---- Price (strip $ and any non-numeric chars except dot) ----
                    $priceStr = $item['regular price'] ?? '0';
                    $price    = floatval(preg_replace('/[^0-9.]/', '', $priceStr));

                    // ---- Sale Price ----
                    $salePriceStr = $item['sale price'] ?? null;
                    $salePrice = $salePriceStr ? floatval(preg_replace('/[^0-9.]/', '', $salePriceStr)) : 0;

                    // ---- Product Name & Slug ----
                    $productName = $this->cleanImportValue($item['product name'] ?? null) ?: 'Unnamed Product';

                    // ---- Full Name ----
                    $fullName = $this->cleanImportValue($item['full name'] ?? null);

                    // ---- Supplier / Brand ----
                    $supplierName = $this->cleanImportValue($item['supplier'] ?? $item['brand'] ?? null);
                    $brandId = null;
                    if ($supplierName) {
                        $brand = Brand::firstOrCreate(
                            ['name' => $supplierName, 'created_by' => $superAdminCompanyId],
                            ['status' => 'active']
                        );
                        $brandId = $brand->id;
                    }

                    // ---- Description ----
                    $description = $this->cleanImportValue($item['description'] ?? null) ?? '';

                    // ---- Is Active → status ----
                    $isActiveRaw  = strtolower(trim((string) ($item['is active'] ?? '')));
                    $status       = in_array($isActiveRaw, ['true', '1', 'yes']) ? 'active' : 'inactive';
                    if ($isActiveRaw === '') {
                        $status = 'active';
                    }

                    // ---- Image URL ----
                    $imageUrl = $this->cleanImportValue($item['product image url'] ?? null);
                    if ($imageUrl && in_array(strtolower($imageUrl), ['n/a', 'none'])) {
                        $imageUrl = null;
                    }

                    // ---- Bottle Size / Unit Count ----
                    $bottleSize = $this->cleanImportValue($item['bottle size / unit count'] ?? null);
                    if ($bottleSize && strtolower($bottleSize) === 'none') {
                        $bottleSize = null;
                    }

                    // ---- Product Form (from Excel column, or auto-detect from bottle_size) ----
                    $productFormExcel = $this->cleanImportValue($item['product form'] ?? null);
                    if ($productFormExcel && in_array(strtolower($productFormExcel), ['n/a', 'none'])) {
                        $productFormExcel = null;
                    }

                    $productForm    = null;
                    $bottleSizeUnit = null;

                    if ($productFormExcel) {
                        $pfLower = strtolower(trim($productFormExcel));
                        if (in_array($pfLower, ['liquid', 'tincture', 'drop', 'drops', 'oil', 'spray', 'liquid extract'])) {
                            $productForm    = 'Liquid';
                            $bottleSizeUnit = 'oz';
                        } elseif (in_array($pfLower, ['caps', 'capsule', 'capsules', 'caplet', 'caplets', 'tablet', 'tablets', 'softgel', 'softgels'])) {
                            $productForm    = 'Caps';
                            $bottleSizeUnit = 'caps';
                        } elseif (in_array($pfLower, ['powder', 'powders'])) {
                            $productForm    = 'Powder';
                            $bottleSizeUnit = 'g';
                        } elseif (in_array($pfLower, ['gummy', 'gummies', 'chewable'])) {
                            $productForm    = 'Gummy';
                            $bottleSizeUnit = 'gummies';
                        } elseif (in_array($pfLower, ['tea', 'herbal tea', 'loose tea', 'tea bags'])) {
                            $productForm    = 'Tea';
                            $bottleSizeUnit = 'bags';
                        } elseif (in_array($pfLower, ['cream', 'topical', 'ointment', 'lotion', 'salve', 'balm', 'gel'])) {
                            $productForm    = 'Topical';
                            $bottleSizeUnit = 'oz';
                        } else {
                            $productForm = $productFormExcel;
                        }
                    } elseif ($bottleSize) {
                        // Auto-detect from bottle_size (fallback)
                        if (preg_match('/fl\\s*oz|ml|oz\\b|liquid|tincture|drop/i', $bottleSize)) {
                            $productForm    = 'Liquid';
                            $bottleSizeUnit = 'oz';
                        } elseif (preg_match('/cap|tablet|caplet|ct\\b|capsule/i', $bottleSize)) {
                            $productForm    = 'Caps';
                            $bottleSizeUnit = 'caps';
                        }
                    }

                    // ---- Contraindications ----
                    $contraindications = $this->cleanImportValue($item['contraindications'] ?? null);
                    if (empty($contraindications) || in_array(strtolower($contraindications ?? ''), ['n/a', 'none'])) {
                        $contraindications = 'N/A';
                    }

                    // ---- Ingredients ----
                    $ingredients = $this->cleanImportValue($item['ingredients'] ?? null);
                    if ($ingredients && strtolower($ingredients) === 'none') {
                        $ingredients = null;
                    }

                    // ---- Research Links ----
                    $researchLinks = $this->cleanImportValue($item['research / studies / article links'] ?? null);
                    if ($researchLinks && in_array(strtolower($researchLinks), ['n/a', 'none'])) {
                        $researchLinks = null;
                    }

                    // ---- Supports ----
                    $supports = $this->cleanImportValue($item['supports'] ?? null);
                    if ($supports && in_array(strtolower($supports), ['n/a', 'none'])) {
                        $supports = null;
                    }

                    // ---- Useful For ----
                    $usefulFor = $this->cleanImportValue($item['useful for'] ?? null);
                    if ($usefulFor && in_array(strtolower($usefulFor), ['n/a', 'none'])) {
                        $usefulFor = null;
                    }

                    // ---- Dosing Fields ----
                    $dosingMap = [
                        'upon rising'        => 'dosing_upon_rising',
                        'breakfast'          => 'dosing_breakfast',
                        'between meals (am)' => 'dosing_between_meals_am',
                        'lunch'              => 'dosing_lunch',
                        'between meals (pm)' => 'dosing_between_meals_pm',
                        'dinner'             => 'dosing_dinner',
                        'before sleep'       => 'dosing_before_sleep',
                    ];

                    $dosingData    = [];
                    $hasDosingData = false;
                    foreach ($dosingMap as $excelKey => $dbField) {
                        $val = $this->cleanImportValue($item[$excelKey] ?? null);
                        if ($val && strtolower($val) === 'none') {
                            $val = null;
                        }
                        $dosingData[$dbField] = $val;
                        if (!empty($val)) {
                            $hasDosingData = true;
                        }
                    }

                    // ---- Practitioner Notes ----
                    $practitionerNotes = $this->cleanImportValue($item['practitioner notes'] ?? null);
                    if ($practitionerNotes && strtolower($practitionerNotes) === 'none') {
                        $practitionerNotes = null;
                    }

                    // ---- Custom Primary Indications ----
                    $customPrimaryIndications = $this->cleanImportValue($item['custom primary indications'] ?? null);
                    if ($customPrimaryIndications && strtolower($customPrimaryIndications) === 'none') {
                        $customPrimaryIndications = null;
                    }
                    $customPrimaryIndicationsArray = $customPrimaryIndications
                        ? array_filter(array_map('trim', explode(',', $customPrimaryIndications)))
                        : [];

                    // ---- Custom Dosing Notes ----
                    $customDosingNotes = $this->cleanImportValue($item['custom dosing notes'] ?? null);
                    if ($customDosingNotes && strtolower($customDosingNotes) === 'none') {
                        $customDosingNotes = null;
                    }

                    // ---- Primary Indications (JSON) ----
                    // القديم: جدول product_primary_indications منفصل مع PrimaryIndication model
                    // الجديد: primary_indications كـ JSON array في health_products
                    $indicationsRaw = $this->cleanImportValue($item['primary indications'] ?? null);
                    $primaryIndications = [];
                    if ($indicationsRaw && strtolower($indicationsRaw) !== 'none') {
                        $primaryIndications = array_filter(
                            array_map('trim', preg_split('/[,|]/', $indicationsRaw))
                        );
                    }

                    // ====================================================
                    // UPDATE OR CREATE PRODUCT
                    // ====================================================
                    if ($isUpdate) {
                        // ============ UPDATE EXISTING PRODUCT ============
                        $product = Product::find($existingHealthProduct->product_id);

                        if (!$product) {
                            $skippedCount++;
                            $errorMessages[] = "Row {$displayRow}: SKU {$sku} exists in health_products but product_id {$existingHealthProduct->product_id} not found — skipped.";
                            continue;
                        }

                        // Update product fields
                        $product->name         = $productName;
                        $product->slug         = Str::slug($productName);
                        $product->description  = $description;
                        $product->price        = $price;
                        $product->sale_price   = $salePrice ?: 0;
                        $product->status       = $status;
                        $product->category_id  = $categoryId ?? $product->category_id;

                        if ($brandId) {
                            $product->brand_id = $brandId;
                        }

                        $product->saveQuietly();

                        // Update health product
                        $healthUpdateData = [
                            'product_image_url'  => $imageUrl,
                            'bottle_size'        => $bottleSize,
                            'ingredients'        => $ingredients,
                            'contraindications'  => $contraindications,
                            'research_links'     => $researchLinks,
                            'supports'           => $supports,
                            'useful_for'         => $usefulFor,
                            'primary_indications'=> $primaryIndications,
                            'dosing_na'          => $hasDosingData ? false : true,
                        ];

                        if ($fullName) {
                            $healthUpdateData['full_name'] = $fullName;
                        }

                        // Dosing fields
                        foreach ($dosingData as $field => $val) {
                            $healthUpdateData[$field] = $val;
                        }

                        // Product form & bottle_size_unit
                        if ($productForm) {
                            $healthUpdateData['product_form'] = $productForm;
                        }
                        if ($bottleSizeUnit) {
                            $healthUpdateData['bottle_size_unit'] = $bottleSizeUnit;
                        }

                        $existingHealthProduct->fill($healthUpdateData);
                        $existingHealthProduct->save();

                        // Update company override (custom fields)
                        $overrideData = [];
                        if ($practitionerNotes) {
                            $overrideData['practitioner_notes'] = $practitionerNotes;
                        }
                        if (!empty($customPrimaryIndicationsArray)) {
                            $overrideData['custom_primary_indications'] = $customPrimaryIndicationsArray;
                        }
                        if ($customDosingNotes) {
                            $overrideData['custom_dosing_notes'] = $customDosingNotes;
                        }
                        if (!empty($overrideData)) {
                            ProductCompanyOverride::updateOrCreate(
                                ['product_id' => $product->id, 'company_id' => $superAdminCompanyId],
                                $overrideData
                            );
                        }

                        // Tags - support both comma and pipe separator
                        // NOTE: tags table uses company_id (not created_by)
                        $tagsRaw = $this->cleanImportValue($item['tags'] ?? null);
                        if ($tagsRaw && strtolower($tagsRaw) !== 'none') {
                            $tagNames = array_filter(
                                array_map('trim', preg_split('/[,|]/', $tagsRaw))
                            );
                            $tagIds   = [];
                            foreach ($tagNames as $tagName) {
                                if (empty($tagName)) continue;
                                $tag = Tag::firstOrCreate(
                                    ['name' => $tagName, 'company_id' => $superAdminCompanyId],
                                    ['slug' => Str::slug($tagName), 'status' => 'active', 'color' => '#6366f1', 'created_by' => $superAdminCompanyId]
                                );
                                $tagIds[] = $tag->id;
                            }
                            if (!empty($tagIds)) {
                                $this->saveTagsToPivot($product->id, $tagIds, $superAdminCompanyId);
                            }
                        }

                        // Dispatch event after update
                        // FIX: نُرفق slug الشركة (السوبر ادمن هنا لأن الاستيراد يتم باسمه)
                        \App\Observers\ProductObserver::dispatchProductEvent(
                            $product->id,
                            'updated',
                            [
                                'company_slug'  => $this->getCurrentCompanySlug($superAdminCompanyId),
                                'override_mode' => false,
                            ]
                        );

                        $updatedCount++;

                        Log::info("Excel Import: Updated product", [
                            'row' => $displayRow,
                            'sku' => $sku,
                            'product_id' => $product->id,
                        ]);
                    } else {
                        // ============ CREATE NEW PRODUCT ============
                        $productSlug = Str::slug($productName);
                        $origSlug    = $productSlug;
                        $pCounter    = 1;
                        while (Product::where('slug', $productSlug)->exists()) {
                            $productSlug = $origSlug . '-' . $pCounter++;
                        }

                        $product = new Product();
                        $product->name         = $productName;
                        $product->slug         = $productSlug;
                        $product->sku          = $sku;
                        $product->category_id  = $categoryId;
                        $product->brand_id     = $brandId;
                        $product->price        = $price;
                        $product->sale_price   = $salePrice ?: 0;
                        $product->stock_status = 'in_stock';
                        $product->description  = $description;
                        $product->status       = $status;
                        $product->created_by   = $superAdminCompanyId;
                        $product->tax_status   = 'taxable';
                        $product->saveQuietly();

                        // Create Health Product
                        $healthData = [
                            'product_id'        => $product->id,
                            'created_by'        => $superAdminCompanyId,
                            'sku'               => $sku,
                            'product_image_url' => $imageUrl,
                            'bottle_size'       => $bottleSize,
                            'ingredients'       => $ingredients,
                            'contraindications' => $contraindications,
                            'research_links'    => $researchLinks,
                            'supports'          => $supports,
                            'useful_for'        => $usefulFor,
                            'primary_indications' => $primaryIndications,
                            'dosing_na'         => $hasDosingData ? false : true,
                        ];

                        if ($fullName) {
                            $healthData['full_name'] = $fullName;
                        }

                        // Dosing fields
                        foreach ($dosingData as $field => $val) {
                            $healthData[$field] = $val;
                        }

                        if ($productForm) {
                            $healthData['product_form'] = $productForm;
                        }
                        if ($bottleSizeUnit) {
                            $healthData['bottle_size_unit'] = $bottleSizeUnit;
                        }

                        HealthProduct::create($healthData);

                        // Create Company Override
                        $overrideData = [];
                        if ($practitionerNotes) {
                            $overrideData['practitioner_notes'] = $practitionerNotes;
                        }
                        if (!empty($customPrimaryIndicationsArray)) {
                            $overrideData['custom_primary_indications'] = $customPrimaryIndicationsArray;
                        }
                        if ($customDosingNotes) {
                            $overrideData['custom_dosing_notes'] = $customDosingNotes;
                        }
                        if (!empty($overrideData)) {
                            ProductCompanyOverride::create([
                                'product_id' => $product->id,
                                'company_id' => $superAdminCompanyId,
                                'is_visible' => true,
                            ] + $overrideData);
                        }

                        // Tags - support both comma and pipe separator
                        // NOTE: tags table uses company_id (not created_by)
                        $tagsRaw = $this->cleanImportValue($item['tags'] ?? null);
                        if ($tagsRaw && strtolower($tagsRaw) !== 'none') {
                            $tagNames = array_filter(
                                array_map('trim', preg_split('/[,|]/', $tagsRaw))
                            );
                            $tagIds   = [];
                            foreach ($tagNames as $tagName) {
                                if (empty($tagName)) continue;
                                $tag = Tag::firstOrCreate(
                                    ['name' => $tagName, 'company_id' => $superAdminCompanyId],
                                    ['slug' => Str::slug($tagName), 'status' => 'active', 'color' => '#6366f1', 'created_by' => $superAdminCompanyId]
                                );
                                $tagIds[] = $tag->id;
                            }
                            if (!empty($tagIds)) {
                                $this->saveTagsToPivot($product->id, $tagIds, $superAdminCompanyId);
                            }
                        }

                        // Dispatch event after create
                        // FIX: نُرفق slug الشركة (السوبر ادمن هنا لأن الاستيراد يتم باسمه)
                        \App\Observers\ProductObserver::dispatchProductEvent(
                            $product->id,
                            'created',
                            [
                                'company_slug'  => $this->getCurrentCompanySlug($superAdminCompanyId),
                                'override_mode' => false,
                            ]
                        );

                        $importedCount++;

                        Log::info("Excel Import: Created product", [
                            'row' => $displayRow,
                            'sku' => $sku,
                            'product_id' => $product->id,
                        ]);
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $skuLabel = $sku ?? 'unknown';
                    $errorMessages[] = "Row {$displayRow} (SKU: {$skuLabel}): " . $e->getMessage();
                    Log::error("Excel Import: Row failed", [
                        'row' => $displayRow,
                        'sku' => $skuLabel,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Build the result message
            $msg  = __('Imported: :count, Updated: :updated, Skipped: :skipped', [
                'count'   => $importedCount,
                'updated' => $updatedCount,
                'skipped' => $skippedCount,
            ]);
            if ($errorCount > 0) {
                $msg .= ' ' . __('Errors: :errors', ['errors' => $errorCount]);
            }

            Log::info("Excel Import: Completed", [
                'imported' => $importedCount,
                'updated'  => $updatedCount,
                'skipped'  => $skippedCount,
                'errors'   => $errorCount,
            ]);

            return response()->json([
                'flag'          => ($importedCount > 0 || $updatedCount > 0) ? 'success' : (($errorCount > 0) ? 'error' : 'warning'),
                'msg'           => $msg,
                'imported'      => $importedCount,
                'updated'       => $updatedCount,
                'skipped'       => $skippedCount,
                'errors'        => $errorCount,
                'error_details' => $errorMessages,
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'flag' => 'error',
                'msg'  => __('Please upload a valid Excel file (.xlsx, .xls, .csv)')
            ]);
        } catch (\Exception $e) {
            Log::error("Excel Import failed: " . $e->getMessage());
            return response()->json(['flag' => 'error', 'msg' => $e->getMessage()]);
        }
    }

    /**
     * Clean a value from the Excel import.
     * Returns null for empty strings, literal "null", "None", etc.
     */
    private function cleanImportValue($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $val = trim((string) $value);
        if ($val === '' || strtolower($val) === 'null') {
            return null;
        }
        return $val;
    }

    // =========================================================================
    // =========== PRIVATE HELPERS =============================================
    // =========================================================================

    /**
     * Save tags to product_tags pivot table
     * NOTE: product_tags pivot still uses created_by (the company who attached the tag),
     *       while the tags table itself uses company_id (the company that owns the tag definition).
     */
    private function saveTagsToPivot(int $productId, array $tagIds, int $companyId): void
    {
        DB::table('product_tags')
            ->where('product_id', $productId)
            ->where('created_by', $companyId)
            ->delete();

        $insertData = [];
        foreach ($tagIds as $tagId) {
            $insertData[] = [
                'product_id' => $productId,
                'tag_id'     => $tagId,
                'created_by' => $companyId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        if (!empty($insertData)) {
            DB::table('product_tags')->insert($insertData);
        }
    }

    /**
     * Sync "Pairs Well With" products via product_pairs pivot table
     */
    private function syncPairsWellWith(int $productId, array $pairedIds, int $companyId): void
    {
        // Remove existing pairs for this company
        DB::table('product_pairs')
            ->where('product_id', $productId)
            ->where('created_by', $companyId)
            ->delete();

        // Insert new pairs
        $insertData = [];
        foreach ($pairedIds as $pairedId) {
            $insertData[] = [
                'product_id'       => $productId,
                'paired_product_id' => $pairedId,
                'created_by'       => $companyId,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
        }
        if (!empty($insertData)) {
            DB::table('product_pairs')->insert($insertData);
        }
    }

    // =========================================================================
    // =========== MERCHANT COMPARISON (Company User) ==========================
    // =========================================================================

    /**
     * مقارنة بيانات الشركة مع بيانات السوبر ادمن
     * يظهر الفروقات بين الـ Override والبيانات الأصلية
     */
    public function merchantCompareResults()
    {
        // فقط صاحب الشركة يقدر يشوف المقارنة
        if (auth()->user()->isSuperAdmin()) {
            return redirect()->route('products.index')->with('error', __('This feature is for company users only.'));
        }

        $currentCompanyId = createdBy();
        $superAdminId = getSuperAdminCompanyId();

        // جلب كل الـ overrides لهذه الشركة على منتجات السوبر ادمن
        $overrides = ProductCompanyOverride::where('company_id', $currentCompanyId)
            ->whereHas('product', function ($q) use ($superAdminId) {
                $q->where('created_by', $superAdminId);
            })
            ->with(['product.category', 'product.healthProduct'])
            ->get();

        $allDifferences = [];

        foreach ($overrides as $override) {
            $product = $override->product;
            if (!$product) continue;

            $healthProduct = HealthProduct::getForProduct($product->id, $currentCompanyId);
            $baseHealthProduct = $product->healthProduct()->where('created_by', $superAdminId)->first();
            $changes = [];

            // ============ مقارنة حقول المنتج (products table) ============

            // السعر
            if ($override->price_override !== null) {
                $changes[] = [
                    'override_id' => $override->id,
                    'field_name'  => 'price_override',
                    'label'       => __('Price'),
                    'original_value' => number_format(floatval($product->price), 2),
                    'merchant_value' => number_format(floatval($override->price_override), 2),
                ];
            }

            // سعر التخفيض
            if ($override->sale_price_override !== null) {
                $changes[] = [
                    'override_id' => $override->id,
                    'field_name'  => 'sale_price_override',
                    'label'       => __('Sale Price'),
                    'original_value' => $product->sale_price ? number_format(floatval($product->sale_price), 2) : '—',
                    'merchant_value' => number_format(floatval($override->sale_price_override), 2),
                ];
            }

            // كمية المخزون
            if ($override->stock_quantity_override !== null) {
                $changes[] = [
                    'override_id' => $override->id,
                    'field_name'  => 'stock_quantity_override',
                    'label'       => __('Stock Quantity'),
                    'original_value' => (string) ($product->stock_quantity ?? 0),
                    'merchant_value' => (string) $override->stock_quantity_override,
                ];
            }

            // حالة المخزون
            if ($override->stock_status_override !== null) {
                $changes[] = [
                    'override_id' => $override->id,
                    'field_name'  => 'stock_status_override',
                    'label'       => __('Stock Status'),
                    'original_value' => ucfirst(str_replace('_', ' ', $product->stock_status ?? '')),
                    'merchant_value' => ucfirst(str_replace('_', ' ', $override->stock_status_override)),
                ];
            }

            // الوصف
            if ($override->description !== null) {
                $changes[] = [
                    'override_id' => $override->id,
                    'field_name'  => 'description',
                    'label'       => __('Description'),
                    'original_value' => $product->description ?? '—',
                    'merchant_value' => $override->description,
                ];
            }

            // التصنيف
            if ($override->category_id !== null) {
                $originalCategory = $product->category?->name ?? '—';
                $merchantCategory = \App\Models\Category::find($override->category_id)?->name ?? '—';
                $changes[] = [
                    'override_id' => $override->id,
                    'field_name'  => 'category_id',
                    'label'       => __('Category'),
                    'original_value' => $originalCategory,
                    'merchant_value' => $merchantCategory,
                ];
            }

            // ============ مقارنة حقول الصحة (health_products table) ============

            // موانع الاستعمال
            if ($override->contraindications !== null) {
                $changes[] = [
                    'override_id' => $override->id,
                    'field_name'  => 'contraindications',
                    'label'       => __('Contraindications'),
                    'original_value' => $baseHealthProduct?->contraindications ?? '—',
                    'merchant_value' => $override->contraindications,
                ];
            }

            // روابط الأبحاث
            if ($override->research_links !== null) {
                $changes[] = [
                    'override_id' => $override->id,
                    'field_name'  => 'research_links',
                    'label'       => __('Research Links'),
                    'original_value' => $baseHealthProduct?->research_links ?? '—',
                    'merchant_value' => $override->research_links,
                ];
            }

            // المؤشرات الرئيسية
            if ($override->primary_indications !== null && !empty($override->primary_indications)) {
                $originalIndications = $baseHealthProduct?->primary_indications
                    ? (is_array($baseHealthProduct->primary_indications)
                        ? implode(', ', $baseHealthProduct->primary_indications)
                        : $baseHealthProduct->primary_indications)
                    : '—';
                $merchantIndications = is_array($override->primary_indications)
                    ? implode(', ', $override->primary_indications)
                    : $override->primary_indications;
                $changes[] = [
                    'override_id' => $override->id,
                    'field_name'  => 'primary_indications',
                    'label'       => __('Primary Indications'),
                    'original_value' => $originalIndications,
                    'merchant_value' => $merchantIndications,
                ];
            }

            // حقول الجرعات
            $dosingFields = [
                'dosing_upon_rising'      => __('Upon Rising'),
                'dosing_breakfast'        => __('Breakfast'),
                'dosing_between_meals_am' => __('Between Meals (AM)'),
                'dosing_lunch'            => __('Lunch'),
                'dosing_between_meals_pm' => __('Between Meals (PM)'),
                'dosing_dinner'           => __('Dinner'),
                'dosing_before_sleep'     => __('Before Sleep'),
            ];

            foreach ($dosingFields as $field => $label) {
                if ($override->$field !== null) {
                    $changes[] = [
                        'override_id'    => $override->id,
                        'field_name'     => $field,
                        'label'          => $label,
                        'original_value' => $baseHealthProduct?->$field ?? '—',
                        'merchant_value' => $override->$field,
                    ];
                }
            }

            // Dosing N/A
            if ($override->dosing_na !== null) {
                $changes[] = [
                    'override_id'    => $override->id,
                    'field_name'     => 'dosing_na',
                    'label'          => __('Dosing N/A'),
                    'original_value' => $baseHealthProduct?->dosing_na ? __('Yes') : __('No'),
                    'merchant_value' => $override->dosing_na ? __('Yes') : __('No'),
                ];
            }

            // ============ حقول حصرية للشركة (لا توجد في بيانات السوبر ادمن) ============

            // ملاحظات الممارس
            if ($override->practitioner_notes !== null) {
                $changes[] = [
                    'override_id'    => $override->id,
                    'field_name'     => 'practitioner_notes',
                    'label'          => __('Practitioner Notes'),
                    'original_value' => null,
                    'merchant_value' => $override->practitioner_notes,
                    'is_exclusive'   => true,
                ];
            }

            // مؤشرات رئيسية مخصصة
            if ($override->custom_primary_indications !== null && !empty($override->custom_primary_indications)) {
                $merchantValue = is_array($override->custom_primary_indications)
                    ? implode(', ', $override->custom_primary_indications)
                    : $override->custom_primary_indications;
                $changes[] = [
                    'override_id'    => $override->id,
                    'field_name'     => 'custom_primary_indications',
                    'label'          => __('Custom Primary Indications'),
                    'original_value' => null,
                    'merchant_value' => $merchantValue,
                    'is_exclusive'   => true,
                ];
            }

            // ملاحظات الجرعات المخصصة
            if ($override->custom_dosing_notes !== null) {
                $changes[] = [
                    'override_id'    => $override->id,
                    'field_name'     => 'custom_dosing_notes',
                    'label'          => __('Custom Dosing Notes'),
                    'original_value' => null,
                    'merchant_value' => $override->custom_dosing_notes,
                    'is_exclusive'   => true,
                ];
            }

            if (!empty($changes)) {
                $allDifferences[] = [
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'sku'          => $baseHealthProduct?->sku ?? $product->sku,
                    'changes'      => $changes,
                ];
            }
        }

        return Inertia::render('products/comparison', [
            'allDifferences' => $allDifferences,
            'totalProducts'  => count($allDifferences),
            'totalChanges'   => array_sum(array_map(fn($d) => count($d['changes']), $allDifferences)),
        ]);
    }

    /**
     * إرجاع حقل معين من الـ Override لقيمته الأصلية
     * يعمل null للحقل = الرجوع لقيمة السوبر ادمن
     */
    public function merchantRevertField(Request $request)
    {
        $request->validate([
            'id'         => 'required|exists:product_company_overrides,id',
            'field_name' => 'required|string',
        ]);

        $override = ProductCompanyOverride::findOrFail($request->id);

        // التحقق إن الـ Override يخص الشركة الحالية
        if ($override->company_id !== createdBy()) {
            return response()->json([
                'flag' => 'error',
                'msg'  => __('Unauthorized.'),
            ], 403);
        }

        $fieldName = $request->field_name;

        // قائمة الحقول المسموح بإرجاعها
        $revertibleFields = [
            'price_override', 'sale_price_override', 'stock_quantity_override',
            'stock_status_override', 'description', 'contraindications',
            'research_links', 'category_id', 'primary_indications',
            'dosing_upon_rising', 'dosing_breakfast', 'dosing_between_meals_am',
            'dosing_lunch', 'dosing_between_meals_pm', 'dosing_dinner',
            'dosing_before_sleep', 'dosing_na',
            'practitioner_notes', 'custom_primary_indications', 'custom_dosing_notes',
        ];

        if (!in_array($fieldName, $revertibleFields)) {
            return response()->json([
                'flag' => 'error',
                'msg'  => __('Invalid field name.'),
            ]);
        }

        // إرجاع الحقل لـ null = استخدام قيمة السوبر ادمن الأصلية
        $override->$fieldName = null;
        $override->save();

        return response()->json([
            'flag' => 'success',
            'msg'  => __('Field reverted to original value.'),
        ]);
    }

    // =========================================================================
    // =========== PROVIDER COMPARISON (Super Admin) ==========================
    // =========================================================================

    /**
     * مقارنة البيانات المحلية مع بيانات المزود الخارجي
     * فقط السوبر ادمن
     */
    public function compareWithProvider()
    {
        if (!auth()->user()->isSuperAdmin()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        try {
            $providerApiUrl = config('services.provider_api_url');
            if (empty($providerApiUrl)) {
                return redirect()->back()->with('error', __('Provider API URL is not configured.'));
            }

            // إضافة timestamp لمنع التخزين المؤقت
            $liveUrl = $providerApiUrl . '&t=' . time();
            $response = \Illuminate\Support\Facades\Http::timeout(120)->get($liveUrl);

            if (!$response->successful()) {
                return redirect()->back()->with('error', __('Failed to fetch data from provider.'));
            }

            $apiProducts = $response->json();

            if (empty($apiProducts) || !is_array($apiProducts)) {
                return redirect()->back()->with('error', __('No data found from provider.'));
            }

            // مسح بيانات المقارنة القديمة
            ProductComparison::truncate();

            $changesDetected  = 0;
            $processedProducts = 0;
            $skippedCount     = 0;

            foreach ($apiProducts as $apiProduct) {
                $sku = $apiProduct['sku'] ?? null;

                if (empty($sku)) {
                    continue;
                }

                $healthProduct = HealthProduct::where('sku', $sku)->first();

                if (!$healthProduct) {
                    $skippedCount++;
                    continue;
                }

                $product = $healthProduct->product;
                if (!$product) {
                    $skippedCount++;
                    continue;
                }

                $processedProducts++;
                $productName = $product->name;

                // 1. مقارنة السعر
                $apiPrice = floatval($apiProduct['regular_price'] ?? 0);
                $localPrice = floatval($product->price ?? 0);

                if ($apiPrice != $localPrice) {
                    ProductComparison::create([
                        'product_id'   => $product->id,
                        'sku'          => $sku,
                        'product_name' => $productName,
                        'field_name'   => 'price',
                        'old_value'    => $localPrice,
                        'new_value'    => $apiPrice,
                        'status'       => 'pending',
                    ]);
                    $changesDetected++;
                }

                // 2. مقارنة الوزن
                $apiWeight = floatval($apiProduct['weight'] ?? 0);
                $localWeight = floatval($product->product_weight ?? 0);

                if ($apiWeight != $localWeight) {
                    ProductComparison::create([
                        'product_id'   => $product->id,
                        'sku'          => $sku,
                        'product_name' => $productName,
                        'field_name'   => 'product_weight',
                        'old_value'    => $localWeight,
                        'new_value'    => $apiWeight,
                        'status'       => 'pending',
                    ]);
                    $changesDetected++;
                }

                // 3. مقارنة الاسم
                $apiName = trim($apiProduct['name'] ?? '');
                $localName = trim($product->name ?? '');

                if ($apiName !== $localName && $apiName !== '') {
                    ProductComparison::create([
                        'product_id'   => $product->id,
                        'sku'          => $sku,
                        'product_name' => $productName,
                        'field_name'   => 'name',
                        'old_value'    => $localName,
                        'new_value'    => $apiName,
                        'status'       => 'pending',
                    ]);
                    $changesDetected++;
                }

                // 4. مقارنة حالة المخزون
                $stockStatusMap = [
                    'instock'     => 'in_stock',
                    'outofstock'  => 'out_of_stock',
                    'onbackorder' => 'on_backorder',
                ];
                $apiStockStatus = $stockStatusMap[$apiProduct['stock_status'] ?? ''] ?? 'in_stock';
                $localStockStatus = $product->stock_status ?? '';

                if ($apiStockStatus !== $localStockStatus) {
                    ProductComparison::create([
                        'product_id'   => $product->id,
                        'sku'          => $sku,
                        'product_name' => $productName,
                        'field_name'   => 'stock_status',
                        'old_value'    => $localStockStatus,
                        'new_value'    => $apiStockStatus,
                        'status'       => 'pending',
                    ]);
                    $changesDetected++;
                }

                // 5. مقارنة الوصف
                $apiDescription = trim($apiProduct['description'] ?? '');
                $localDescription = trim($product->description ?? '');

                if ($apiDescription !== $localDescription && $apiDescription !== '') {
                    ProductComparison::create([
                        'product_id'   => $product->id,
                        'sku'          => $sku,
                        'product_name' => $productName,
                        'field_name'   => 'description',
                        'old_value'    => $localDescription,
                        'new_value'    => $apiDescription,
                        'status'       => 'pending',
                    ]);
                    $changesDetected++;
                }

                // 6. مقارنة الصورة
                $apiImageUrl = null;
                if (!empty($apiProduct['images']) && is_array($apiProduct['images'])) {
                    $firstImage = $apiProduct['images'][0] ?? null;
                    if ($firstImage) {
                        $apiImageUrl = $firstImage['src'] ?? null;
                    }
                }
                $localImageUrl = $healthProduct->product_image_url ?? null;

                if ($apiImageUrl !== $localImageUrl && $apiImageUrl !== null) {
                    ProductComparison::create([
                        'product_id'   => $product->id,
                        'sku'          => $sku,
                        'product_name' => $productName,
                        'field_name'   => 'product_image_url',
                        'old_value'    => $localImageUrl,
                        'new_value'    => $apiImageUrl,
                        'status'       => 'pending',
                    ]);
                    $changesDetected++;
                }
            }

            if ($changesDetected === 0) {
                $msg = __('No changes detected. Checked :count products.', ['count' => $processedProducts]);
                if ($skippedCount > 0) {
                    $msg .= ' ' . __('Skipped (Not found locally): :skipped', ['skipped' => $skippedCount]);
                }
                return redirect()->back()->with('warning', $msg);
            }

            return redirect()->route('products.provider-comparison')
                ->with('success', __(':count changes detected in :products products.', [
                    'count'    => $changesDetected,
                    'products' => $processedProducts,
                ]));
        } catch (\Exception $e) {
            Log::error("Comparison Error: " . $e->getMessage());
            return redirect()->back()->with('error', __('An error occurred: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * عرض نتائج مقارنة المزود
     */
    public function providerComparisonResults()
    {
        if (!auth()->user()->isSuperAdmin()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $comparisons = ProductComparison::where('status', 'pending')
            ->orderBy('product_id')
            ->orderBy('field_name')
            ->get()
            ->groupBy('product_id');

        $totalChanges = ProductComparison::where('status', 'pending')->count();

        return Inertia::render('products/provider-comparison', [
            'comparisons'  => $comparisons,
            'totalChanges' => $totalChanges,
        ]);
    }

    /**
     * قبول تغيير واحد من المقارنة
     */
    public function acceptComparison($id)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json(['flag' => 'error', 'msg' => __('Permission denied.')]);
        }

        try {
            $comparison = ProductComparison::findOrFail($id);

            $product = Product::find($comparison->product_id);
            $healthProduct = HealthProduct::where('product_id', $comparison->product_id)
                ->where('created_by', getSuperAdminCompanyId())
                ->first();

            if (!$product) {
                return response()->json(['flag' => 'error', 'msg' => __('Product not found.')]);
            }

            $this->applyFieldChange($product, $healthProduct, $comparison->field_name, $comparison->new_value);

            $comparison->status = 'accepted';
            $comparison->save();

            $this->cleanUpResolvedProduct($comparison->product_id);

            return response()->json(['flag' => 'success', 'msg' => __('Change accepted and applied successfully.')]);
        } catch (\Exception $e) {
            Log::error("Accept comparison failed: " . $e->getMessage());
            return response()->json(['flag' => 'error', 'msg' => __('An error occurred.')]);
        }
    }

    /**
     * رفض تغيير واحد من المقارنة
     */
    public function rejectComparison($id)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json(['flag' => 'error', 'msg' => __('Permission denied.')]);
        }

        try {
            $comparison = ProductComparison::findOrFail($id);

            $comparison->status = 'rejected';
            $comparison->save();

            $this->cleanUpResolvedProduct($comparison->product_id);

            return response()->json(['flag' => 'success', 'msg' => __('Change rejected.')]);
        } catch (\Exception $e) {
            Log::error("Reject comparison failed: " . $e->getMessage());
            return response()->json(['flag' => 'error', 'msg' => __('An error occurred.')]);
        }
    }

    /**
     * قبول كل التغييرات لمنتج معين
     */
    public function acceptAllProductChanges($productId)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json(['flag' => 'error', 'msg' => __('Permission denied.')]);
        }

        try {
            $comparisons = ProductComparison::where('product_id', $productId)
                ->where('status', 'pending')
                ->get();

            $product = Product::find($productId);
            $healthProduct = HealthProduct::where('product_id', $productId)
                ->where('created_by', getSuperAdminCompanyId())
                ->first();

            if (!$product) {
                return response()->json(['flag' => 'error', 'msg' => __('Product not found.')]);
            }

            foreach ($comparisons as $comparison) {
                $this->applyFieldChange($product, $healthProduct, $comparison->field_name, $comparison->new_value);
                $comparison->status = 'accepted';
                $comparison->save();
            }

            ProductComparison::where('product_id', $productId)->delete();

            return response()->json(['flag' => 'success', 'msg' => __('All changes accepted and applied successfully.')]);
        } catch (\Exception $e) {
            Log::error("Accept all changes failed: " . $e->getMessage());
            return response()->json(['flag' => 'error', 'msg' => __('An error occurred.')]);
        }
    }

    /**
     * رفض كل التغييرات لمنتج معين
     */
    public function rejectAllProductChanges($productId)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json(['flag' => 'error', 'msg' => __('Permission denied.')]);
        }

        try {
            ProductComparison::where('product_id', $productId)
                ->where('status', 'pending')
                ->update(['status' => 'rejected']);

            ProductComparison::where('product_id', $productId)->delete();

            return response()->json(['flag' => 'success', 'msg' => __('All changes rejected.')]);
        } catch (\Exception $e) {
            Log::error("Reject all changes failed: " . $e->getMessage());
            return response()->json(['flag' => 'error', 'msg' => __('An error occurred.')]);
        }
    }

    // =========================================================================
    // =========== COMPARISON HELPER METHODS ===================================
    // =========================================================================

    /**
     * تطبيق تغيير حقل على المنتج / منتج الصحة
     */
    private function applyFieldChange($product, $healthProduct, $fieldName, $newValue)
    {
        switch ($fieldName) {
            case 'product_image_url':
                if ($healthProduct) {
                    $healthProduct->product_image_url = $newValue;
                    $healthProduct->save();
                }
                break;

            case 'stock_status':
                $product->stock_status = $newValue;
                $product->save();
                break;

            case 'product_weight':
                $product->product_weight = floatval($newValue);
                $product->save();
                break;

            case 'status':
                $product->status = $newValue;
                $product->save();
                break;

            default:
                // لباقي الحقول (name, price, sale_price, description)
                if (Schema::hasColumn('products', $fieldName)) {
                    $product->$fieldName = $newValue;
                    $product->save();
                }
                break;
        }
    }

    /**
     * حذف المقارنات المحلولة لمنتج معين
     */
    private function cleanUpResolvedProduct($productId)
    {
        $pendingCount = ProductComparison::where('product_id', $productId)
            ->where('status', 'pending')
            ->count();

        if ($pendingCount === 0) {
            ProductComparison::where('product_id', $productId)->delete();
        }
    }

    /**
     * فحص هل القيمتين مختلفتين فعلاً
     */
    private function isActuallyDifferent($oldValue, $newValue)
    {
        $oldNormalized = $this->normalizeForComparison($oldValue);
        $newNormalized = $this->normalizeForComparison($newValue);

        if (empty($oldNormalized) && empty($newNormalized)) {
            return false;
        }

        return $oldNormalized !== $newNormalized;
    }

    /**
     * تطبيع القيمة للمقارنة
     */
    private function normalizeForComparison($value)
    {
        if (is_null($value)) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return trim($value);
        }

        if (is_numeric($value)) {
            return floatval($value);
        }

        return trim(strip_tags((string) $value));
    }

    // =========================================================================
    // =========== getCurrentCompanySlug ======================================
    // =========================================================================
    // FIX: يرجع slug الشركة صاحبة التعديل. يُستخدم في رسائل الـ RabbitMQ
    // لكي يعرف الـ desktop app أي شركة قامت بالتعديل/الإنشاء.
    //
    // ترتيب البحث:
    //   1) auth()->user()->slug           (إذا كان للمستخدم نفسه slug مباشر)
    //   2) auth()->user()->company->slug  (إذا كان للمستخدم علاقة company)
    //   3) Company::find($currentCompanyId)->slug  (بحث مباشر في جدول companies)
    //   4) auth()->user()->username / store_slug  (حقول احتياطية شائعة)
    //   5) null                            (إذا فشل كل ما سبق)
    // =========================================================================
    private function getCurrentCompanySlug(?int $currentCompanyId = null): ?string
    {
        $user = auth()->user();

        // (1) slug على المستخدم نفسه
        if ($user && isset($user->slug) && !empty($user->slug)) {
            return (string) $user->slug;
        }

        // (2) علاقة company على المستخدم
        if ($user && method_exists($user, 'company') && $user->company && !empty($user->company->slug)) {
            return (string) $user->company->slug;
        }

        // (3) بحث مباشر في جدول companies باستخدام created_by الحالي
        if ($currentCompanyId) {
            try {
                $companyRow = \DB::table('companies')->where('id', $currentCompanyId)->first();
                if ($companyRow && !empty($companyRow->slug)) {
                    return (string) $companyRow->slug;
                }
            } catch (\Throwable $e) {
                Log::warning("getCurrentCompanySlug: companies table lookup failed: " . $e->getMessage());
            }
        }

        // (4) حقول احتياطية شائعة على جدول users
        if ($user) {
            foreach (['username', 'store_slug', 'company_slug'] as $fallbackField) {
                if (isset($user->{$fallbackField}) && !empty($user->{$fallbackField})) {
                    return (string) $user->{$fallbackField};
                }
            }
        }

        Log::warning("getCurrentCompanySlug: could not resolve company slug", [
            'user_id' => $user->id ?? null,
            'current_company_id' => $currentCompanyId,
        ]);

        return null;
    }
}
