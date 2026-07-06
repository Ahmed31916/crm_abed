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
            ->with(['category', 'brand', 'tax', 'assignedUser', 'creator', 'media', 'tags', 'healthProduct'])
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

        // ⚡ فلتر الملكية (ownership) — للسوبر ادمن فقط
        if (auth()->user()->isSuperAdmin() && $request->filled('ownership') && $request->ownership !== 'all') {
            $superAdminId = getSuperAdminCompanyId();
            if ($request->ownership === 'super_admin') {
                $query->where('created_by', $superAdminId);
            } elseif ($request->ownership === 'company') {
                $query->where('created_by', '!=', $superAdminId);
            }
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

        if (!auth()->user()->isSuperAdmin()) {
            $products->each(function ($product) use ($currentCompanyId) {
                if (
                    isSuperAdminProduct($product)
                    && (int) $product->created_by !== (int) $currentCompanyId
                ) {
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
            'categories' => Category::whereIn('created_by', [$currentCompanyId, getSuperAdminCompanyId()])
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
            'brands' => Brand::whereIn('created_by', [$currentCompanyId, getSuperAdminCompanyId()])
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
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
            'filters' => $request->all([
                'search', 'category', 'brand', 'status', 'stock_status', 'tag',
                'company_id', 'ownership',
                'sort_field', 'sort_direction', 'per_page', 'page'
            ]),
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
        $visibleCompanyIds = [$currentCompanyId, $superAdminId];

        return Inertia::render('products/create', [
            'categories' => Category::whereIn('created_by', $visibleCompanyIds)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
            'brands' => Brand::whereIn('created_by', $visibleCompanyIds)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']),
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
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string',
            'specification'     => 'nullable|string',
            'detail'            => 'nullable|string',
            'price'             => 'required|numeric|min:0',
            'sale_price'        => 'nullable|numeric|min:0',
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
            'product_sku'       => 'nullable|string|max:255|unique:products,sku',
            'product_form'      => 'required|in:Liquid,Caps',
            'bottle_size'       => 'required|numeric|min:0',
            'bottle_size_unit'  => 'nullable|string|max:50',
            'product_image_url' => 'nullable|url|max:500',
            'full_name'         => 'nullable|string|max:500',
            'supports'          => 'nullable|string|max:1000',
            'useful_for'        => 'nullable|string|max:1000',
            'ingredients'       => 'nullable|string|max:1000',
            'contraindications' => 'nullable|string|max:2000',
            'research_links'    => 'nullable|string|max:2000',
            'primary_indications' => 'nullable|array',
            'primary_indications.*' => 'integer|exists:primary_indications,id',
            'dosing_upon_rising'     => 'nullable|string|max:255',
            'dosing_breakfast'       => 'nullable|string|max:255',
            'dosing_between_meals_am' => 'nullable|string|max:255',
            'dosing_lunch'           => 'nullable|string|max:255',
            'dosing_between_meals_pm' => 'nullable|string|max:255',
            'dosing_dinner'          => 'nullable|string|max:255',
            'dosing_before_sleep'    => 'nullable|string|max:255',
            'dosing_na'              => 'nullable|boolean',
            'frequency'          => 'nullable|string|max:255',  // ⚡ NEW
            'tag_id'            => 'required|array|min:1',
            'tag_id.*'          => 'exists:tags,id',
            'pairs_well_with'   => 'nullable|array',
            'pairs_well_with.*' => 'exists:products,id',
            'practitioner_notes'         => 'nullable|string|max:5000',
            'custom_primary_indications' => 'nullable|array',
            'custom_dosing_notes'        => 'nullable|string|max:5000',
        ]);

        try {
            DB::beginTransaction();
            $currentCompanyId = createdBy();

            // ⚡ توليد SKU تلقائياً لو فاضي
            $productSku = $validated['product_sku'] ?? null;
            if (empty($productSku)) {
                $productSku = $this->generateUniqueSku();
            }
            $validated['product_sku'] = $productSku;

            // ⚡ استخدام buildProductData المشترك
            $productData = $this->buildProductData($validated, $currentCompanyId, true);
            $productData['sku'] = $productSku;

            $product = new Product();
            $product->fill($productData);
            $product->save();

            if (!empty($validated['additional_image_ids'])) {
                $product->additional_image_ids = $validated['additional_image_ids'];
                $product->save();
            }

            // ===== Tags =====
            $validTagIds = $this->filterValidTagIds($validated['tag_id'], $currentCompanyId);
            $this->saveTagsToPivot($product->id, $validTagIds, $currentCompanyId);

            // ===== Health Product =====
            $healthData = $this->buildHealthProductData($validated, $currentCompanyId, $productSku);
            HealthProduct::updateOrCreate(
                ['product_id' => $product->id, 'created_by' => $currentCompanyId],
                $healthData
            );

            // ===== Primary Indications Pivot =====
            $product->syncPrimaryIndications($validated['primary_indications'] ?? []);

            // ===== Pairs Well With =====
            if (!empty($validated['pairs_well_with'])) {
                $this->syncPairsWellWith($product->id, $validated['pairs_well_with'], $currentCompanyId);
            }

            // ===== Override =====
            ProductCompanyOverride::updateOrCreate(
                ['product_id' => $product->id, 'company_id' => $currentCompanyId],
                ['is_visible' => true]
            );

            $createdProductId = $product->id;
            $companySlug = $this->getCurrentCompanySlug($currentCompanyId);
            DB::afterCommit(function () use ($createdProductId, $companySlug) {
                \App\Observers\ProductObserver::dispatchProductEvent(
                    $createdProductId, 'created',
                    ['company_slug' => $companySlug, 'override_mode' => false]
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
        $product = Product::with(['category', 'brand', 'tax', 'assignedUser', 'creator', 'media', 'tags', 'healthProduct', 'primaryIndications'])
            ->whereIn('created_by', getVisibleCompanyIds())
            ->findOrFail($id);

        $currentCompanyId = createdBy();
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
            'primaryIndications' => $product->primaryIndications,
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

        $product = Product::with(['category', 'brand', 'tax', 'assignedUser', 'creator', 'media', 'tags', 'healthProduct', 'primaryIndications'])
            ->whereIn('created_by', getVisibleCompanyIds())
            ->findOrFail($id);

        $isSuperAdminProduct = isSuperAdminProduct($product);

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
                'primary_indications_ids' => $product->primaryIndications->pluck('id')->toArray(),
            ]),
            'categories' => Category::whereIn('created_by', [$currentCompanyId, $superAdminId])->where('status', 'active')->get(['id', 'name']),
            'brands' => Brand::whereIn('created_by', [$currentCompanyId, $superAdminId])->where('status', 'active')->get(['id', 'name']),
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
        $isSuperAdminProduct = isSuperAdminProduct($product)
            && (int) $product->created_by !== (int) $currentCompanyId;

        if (!auth()->user()->isSuperAdmin() && $isSuperAdminProduct) {
            return $this->updateOverride($request, $product);
        }

        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string',
            'specification'     => 'nullable|string',
            'detail'            => 'nullable|string',
            'price'             => 'required|numeric|min:0',
            'sale_price'        => 'nullable|numeric|min:0',
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
            'product_sku'       => 'required|string|max:255|unique:products,sku,' . $productId,
            'product_form'      => 'required|in:Liquid,Caps',
            'bottle_size'       => 'required|numeric|min:0',
            'bottle_size_unit'  => 'nullable|string|max:50',
            'product_image_url' => 'nullable|url|max:500',
            'full_name'         => 'nullable|string|max:500',
            'supports'          => 'nullable|string|max:1000',
            'useful_for'        => 'nullable|string|max:1000',
            'ingredients'       => 'nullable|string|max:1000',
            'contraindications' => 'nullable|string|max:2000',
            'research_links'    => 'nullable|string|max:2000',
            'primary_indications' => 'nullable|array',
            'primary_indications.*' => 'integer|exists:primary_indications,id',
            'dosing_upon_rising'     => 'nullable|string|max:255',
            'dosing_breakfast'       => 'nullable|string|max:255',
            'dosing_between_meals_am' => 'nullable|string|max:255',
            'dosing_lunch'           => 'nullable|string|max:255',
            'dosing_between_meals_pm' => 'nullable|string|max:255',
            'dosing_dinner'          => 'nullable|string|max:255',
            'dosing_before_sleep'    => 'nullable|string|max:255',
            'dosing_na'              => 'nullable|boolean',
            'frequency'          => 'nullable|string|max:255',  // ⚡ NEW
            'tag_id'            => 'required|array|min:1',
            'tag_id.*'          => 'exists:tags,id',
            'pairs_well_with'   => 'nullable|array',
            'pairs_well_with.*' => 'exists:products,id',
            'practitioner_notes'         => 'nullable|string|max:5000',
            'custom_primary_indications' => 'nullable|array',
            'custom_dosing_notes'        => 'nullable|string|max:5000',
        ]);

        try {
            DB::beginTransaction();

            // ⚡ استخدام buildProductData المشترك (isNew = false)
            $productData = $this->buildProductData($validated, $currentCompanyId, false);

            // SKU لا يُعدّل بعد الإنشاء
            unset($productData['sku']);

            $product->fill($productData);
            $product->saveQuietly();

            // ===== Tags =====
            $rawTagIds = $validated['tag_id'] ?? [];
            $validTagIds = $this->filterValidTagIds($rawTagIds, $currentCompanyId);
            $this->saveTagsToPivot($product->id, $validTagIds, $currentCompanyId);

            // ===== Health Product =====
            $healthData = $this->buildHealthProductData($validated, $currentCompanyId, $validated['product_sku']);
            HealthProduct::updateOrCreate(
                ['product_id' => $product->id, 'created_by' => $currentCompanyId],
                $healthData
            );

            // ===== Primary Indications Pivot =====
            $product->syncPrimaryIndications($validated['primary_indications'] ?? []);

            // ===== Pairs Well With =====
            $pairIds = $validated['pairs_well_with'] ?? [];
            $this->syncPairsWellWith($product->id, $pairIds, $currentCompanyId);

            // ===== Override =====
            ProductCompanyOverride::updateOrCreate(
                ['product_id' => $product->id, 'company_id' => $currentCompanyId],
                ['is_visible' => true]
            );

            $updatedProductId = $product->id;
            $companySlug = $this->getCurrentCompanySlug($currentCompanyId);
            DB::afterCommit(function () use ($updatedProductId, $companySlug) {
                \App\Observers\ProductObserver::dispatchProductEvent(
                    $updatedProductId, 'updated',
                    ['company_slug' => $companySlug, 'override_mode' => false]
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
            'primary_indications.*'    => 'integer|exists:primary_indications,id',
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
            'tag_id'                   => 'nullable|array',
            'tag_id.*'                 => 'exists:tags,id',
            'practitioner_notes'       => 'nullable|string|max:5000',
            'custom_primary_indications' => 'nullable|array',
            'custom_dosing_notes'      => 'nullable|string|max:5000',
        ]);

        try {
            DB::beginTransaction();
            $currentCompanyId = createdBy();
            $dosingNa = $request->boolean('dosing_na');

            // Primary Indications override = array of NAMES (not IDs)
            $indicationIds = $validated['primary_indications'] ?? [];
            $indicationNames = [];
            if (!empty($indicationIds)) {
                $indicationNames = PrimaryIndication::whereIn('id', $indicationIds)
                    ->pluck('name')
                    ->toArray();
            }

            ProductCompanyOverride::updateOrCreate(
                ['product_id' => $product->id, 'company_id' => $currentCompanyId],
                [
                    'description'               => $validated['description'] ?? null,
                    'primary_indications'       => $indicationNames,
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
                    'is_visible' => true,
                ]
            );

            // ⚡ فلترة tag_id قبل الحفظ
            $rawTagIds = $validated['tag_id'] ?? [];
            $validTagIds = $this->filterValidTagIds($rawTagIds, $currentCompanyId);

            if (count($rawTagIds) !== count($validTagIds)) {
                Log::warning('ProductController@updateOverride: Filtered out invalid tag IDs', [
                    'product_id'      => $product->id,
                    'company_id'      => $currentCompanyId,
                    'sent_tag_ids'    => $rawTagIds,
                    'valid_tag_ids'   => $validTagIds,
                    'filtered_count'  => count($rawTagIds) - count($validTagIds),
                ]);
            }

            $this->saveTagsToPivot($product->id, $validTagIds, $currentCompanyId);

            // حفظ practitioner_notes و custom fields في health_products
            $superAdminId = getSuperAdminCompanyId();
            $healthProduct = HealthProduct::where('product_id', $product->id)
                ->where('created_by', $superAdminId)
                ->first();

            if ($healthProduct) {
                $healthProduct->practitioner_notes = $validated['practitioner_notes'] ?? null;
                $healthProduct->custom_primary_indications = $validated['custom_primary_indications'] ?? [];
                $healthProduct->custom_dosing_notes = $validated['custom_dosing_notes'] ?? null;
                $healthProduct->save();
            }

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
            DB::table('product_primary_indications')->where('product_id', $product->id)->delete();
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

        try {
            DB::beginTransaction();

            $product->status = $product->status === 'active' ? 'inactive' : 'active';
            $product->save();

            $toggledProductId = $product->id;
            $companySlug = $this->getCurrentCompanySlug($product->created_by);

            DB::afterCommit(function () use ($toggledProductId, $companySlug) {
                \App\Observers\ProductObserver::dispatchProductEvent(
                    $toggledProductId,
                    'updated',
                    [
                        'company_slug' => $companySlug,
                        'override_mode' => false,
                    ]
                );
            });

            DB::commit();

            return redirect()->back()->with('success', __('Product status updated.'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Product toggleStatus failed: " . $e->getMessage());
            return redirect()->back()->with('error', __('Failed to update product status: :error', ['error' => $e->getMessage()]));
        }
    }

    // =========================================================================
    // =========== EXPORT / IMPORT =============================================
    // =========================================================================

    public function fileExport()
    {
        if (!auth()->user()->can('export-products')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $currentCompanyId = createdBy();
        $superAdminId     = getSuperAdminCompanyId();

        // لو المستخدم سوبر ادمن → نمرّر null عشان يصدّر كل المنتجات
        // لو شركة → نمرّر companyId عشان يصدّر منتجاتها + منتجات السوبر ادمن فقط
        $companyIdForExport = auth()->user()->isSuperAdmin() ? null : $currentCompanyId;

        $fileName = 'products_export_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(
            new \App\Exports\ProductExport($companyIdForExport, $superAdminId),
            $fileName
        );
    }

        private function buildHealthProductData(array $data, int $companyId, string $sku): array
    {
        $dosingNa = !empty($data['dosing_na']);

        $productForm = $data['product_form'] ?? 'Caps';
        $bottleSizeUnit = !empty($data['bottle_size_unit'])
            ? $data['bottle_size_unit']
            : (($productForm === 'Liquid') ? 'oz' : 'caps');

        return [
            'sku'                        => $sku,
            'product_form'               => $productForm,
            'bottle_size'                => $data['bottle_size'] ?? 0,
            'bottle_size_unit'           => $bottleSizeUnit,
            'product_image_url'          => $data['product_image_url'] ?? null,
            'full_name'                  => $data['full_name'] ?? null,
            'ingredients'                => $data['ingredients'] ?? null,
            'contraindications'          => $data['contraindications'] ?? 'N/A',
            'research_links'             => $data['research_links'] ?? null,
            'supports'                   => $data['supports'] ?? null,
            'useful_for'                 => $data['useful_for'] ?? null,
            'practitioner_notes'         => $data['practitioner_notes'] ?? null,
            'custom_primary_indications' => $data['custom_primary_indications'] ?? [],
            'custom_dosing_notes'        => $data['custom_dosing_notes'] ?? null,
            'dosing_upon_rising'         => $dosingNa ? null : ($data['dosing_upon_rising'] ?? null),
            'dosing_breakfast'           => $dosingNa ? null : ($data['dosing_breakfast'] ?? null),
            'dosing_between_meals_am'    => $dosingNa ? null : ($data['dosing_between_meals_am'] ?? null),
            'dosing_lunch'               => $dosingNa ? null : ($data['dosing_lunch'] ?? null),
            'dosing_between_meals_pm'    => $dosingNa ? null : ($data['dosing_between_meals_pm'] ?? null),
            'dosing_dinner'              => $dosingNa ? null : ($data['dosing_dinner'] ?? null),
            'dosing_before_sleep'        => $dosingNa ? null : ($data['dosing_before_sleep'] ?? null),
            'dosing_na'                  => $dosingNa,
        ];
    }

        private function buildProductData(array $data, int $companyId, bool $isNew = true): array
    {
        $productData = [
            'name'           => $data['name'],
            'slug'           => \Illuminate\Support\Str::slug($data['name']),
            'description'    => $data['description'] ?? null,
            'specification'  => $data['specification'] ?? null,
            'detail'         => $data['detail'] ?? null,
            'price'          => $data['price'],
            'sale_price'     => !empty($data['sale_price']) ? $data['sale_price'] : ($data['price'] ?? 0),
            'stock_quantity' => $data['stock_quantity'] ?? 0,
            'stock_status'   => $data['stock_status'] ?? 'in_stock',
            'product_weight' => $data['product_weight'] ?? null,
            'category_id'    => $data['category_id'],
            'brand_id'       => $data['brand_id'] ?? null,
            'tax_id'         => $data['tax_id'] ?? null,
            'status'         => $data['status'] ?? 'active',
            'frequency'      => $data['frequency'] ?? null,
            'tax_status'     => 'taxable',
        ];

        if ($isNew) {
            $productData['sku'] = $data['sku'] ?? $data['product_sku'] ?? null;
            $productData['created_by'] = $companyId;
        }

        if (!empty($data['main_image_id'])) {
            $productData['main_image_id'] = $data['main_image_id'];
        }
        if (isset($data['additional_image_ids'])) {
            $productData['additional_image_ids'] = $data['additional_image_ids'];
        }

        return $productData;
    }

    public function downloadTemplate()
    {
        $filePath = public_path('templates/products-import-template.xlsx');

        if (!file_exists($filePath)) {
            abort(404, __('Import template file not found.'));
        }

        return response()->download(
            $filePath,
            'products-import-template.xlsx',
            [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="products-import-template.xlsx"',
            ]
        );
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
                'added' => $import->getAddedCount(),
                'skipped' => $import->getSkippedCount()
            ]));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Import failed: :error', ['error' => $e->getMessage()]));
        }
    }

    // =========================================================================
    // =========== IMPORT FROM EXCEL (Super Admin Only) ========================
    // =========================================================================

    public function importFromExcel(Request $request)
    {
        // ⚡ FIX: السماح للسوبر ادمن والشركة باستخدام الميزة
        if (!auth()->user()->isSuperAdmin() && auth()->user()->type !== 'company') {
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

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $worksheet   = $spreadsheet->getActiveSheet();
            $rows        = $worksheet->toArray(null, true, true, true);

            if (count($rows) < 2) {
                return response()->json([
                    'flag' => 'error',
                    'msg'  => __('The file is empty or has no data rows.')
                ]);
            }

            $rawHeaders = array_shift($rows);
            $headerMap  = [];
            foreach ($rawHeaders as $col => $header) {
                if ($header !== null && $header !== '') {
                    $normalizedKey           = strtolower(trim((string) $header));
                    $headerMap[$normalizedKey] = $col;
                }
            }

            Log::info("Excel Import: Started", [
                'user_type' => auth()->user()->type,
                'headers'    => array_keys($headerMap),
                'total_rows' => count($rows),
            ]);

            // Frequency-Only Mode detection (للكل المستخدمين)
            $headerKeys = array_keys($headerMap);
            $hasSku      = in_array('sku', $headerKeys, true);
            $hasFreq     = in_array('frequency', $headerKeys, true)
                        || in_array('dosing_frequency', $headerKeys, true)
                        || in_array('freq', $headerKeys, true);

            if ($hasSku && $hasFreq && count($headerKeys) === 2) {
                return $this->importFrequencyOnly($rows, $headerMap);
            }

            // ⚡ توجيه حسب نوع المستخدم
            if (auth()->user()->isSuperAdmin()) {
                return $this->importForSuperAdmin($rows, $headerMap);
            } else {
                return $this->importForCompany($rows, $headerMap);
            }

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
     * ════════════════════════════════════════════════════════════════════
     * استيراد للسوبر ادمن — المنطق القديم الكامل
     * ════════════════════════════════════════════════════════════════════
     */
    private function importForSuperAdmin(array $rows, array $headerMap)
    {
        $superAdminCompanyId = getSuperAdminCompanyId();
        $importedCount       = 0;
        $updatedCount        = 0;
        $skippedCount        = 0;
        $errorCount          = 0;
        $errorMessages       = [];

        foreach ($rows as $rowIndex => $row) {
            $displayRow = $rowIndex + 2;

            try {
                $item = [];
                foreach ($headerMap as $key => $col) {
                    $item[$key] = $row[$col] ?? null;
                }

                // ---- SKU ----
                $skuRaw = $item['sku'] ?? null;
                $sku = $this->cleanImportValue($skuRaw);
                if ($sku !== null && is_numeric($sku)) {
                    $sku = (string) intval(floatval($sku));
                }
                if (empty($sku)) {
                    $skippedCount++;
                    $errorMessages[] = "Row {$displayRow}: Missing SKU — skipped.";
                    continue;
                }

                $existingHealthProduct = HealthProduct::where('sku', $sku)->first();
                $isUpdate = !is_null($existingHealthProduct);

                // Parse جميع الحقول (نفس المنطق القديم)
                $parsed = $this->parseExcelRow($item, $superAdminCompanyId);
                if (!$parsed) {
                    $skippedCount++;
                    continue;
                }
                extract($parsed);

                // ====================================================
                // UPDATE OR CREATE
                // ====================================================
                if ($isUpdate) {
                    $product = Product::find($existingHealthProduct->product_id);
                    if (!$product) {
                        $skippedCount++;
                        continue;
                    }

                    // Build desired state + detect changes (نفس المنطق القديم)
                    $newProductName    = $productName;
                    $newDescription    = $description;
                    $newPrice          = $price;
                    $newSalePrice      = $salePrice ?: 0;
                    $newStatus         = $status;
                    $newCategoryId     = $categoryId ?? $product->category_id;
                    $newBrandId        = $brandId ?: $product->brand_id;
                    $newFrequency      = $frequency;

                    $newHealthData = [
                        'product_image_url'           => $imageUrl,
                        'bottle_size'                 => $bottleSize,
                        'bottle_size_unit'            => $bottleSizeUnit,
                        'ingredients'                 => $ingredients,
                        'contraindications'           => $contraindications,
                        'research_links'              => $researchLinks,
                        'supports'                    => $supports,
                        'useful_for'                  => $usefulFor,
                        'dosing_na'                   => $hasDosingData ? false : true,
                        'practitioner_notes'          => $practitionerNotes,
                        'custom_primary_indications'  => $customPrimaryIndicationsArray,
                        'custom_dosing_notes'         => $customDosingNotes,
                    ];
                    if ($fullName)         $newHealthData['full_name']    = $fullName;
                    if ($productForm)      $newHealthData['product_form'] = $productForm;
                    foreach ($dosingData as $field => $val) {
                        $newHealthData[$field] = $val;
                    }

                    $productChanges = [];
                    if ($this->isFieldDifferent($product->name, $newProductName))       $productChanges['name']         = $newProductName;
                    if ($this->isFieldDifferent($product->description, $newDescription)) $productChanges['description']  = $newDescription;
                    if ($this->isFieldDifferent($product->price, $newPrice))             $productChanges['price']        = $newPrice;
                    if ($this->isFieldDifferent($product->sale_price, $newSalePrice))    $productChanges['sale_price']   = $newSalePrice;
                    if ($this->isFieldDifferent($product->status, $newStatus))           $productChanges['status']       = $newStatus;
                    if ($this->isFieldDifferent($product->category_id, $newCategoryId))  $productChanges['category_id']  = $newCategoryId;
                    if ($this->isFieldDifferent($product->brand_id, $newBrandId))        $productChanges['brand_id']     = $newBrandId;
                    if ($this->isFieldDifferent($product->frequency, $newFrequency))     $productChanges['frequency']    = $newFrequency;

                    $healthChanges = [];
                    foreach ($newHealthData as $field => $val) {
                        $currentVal = $existingHealthProduct->{$field} ?? null;
                        if ($this->isFieldDifferent($currentVal, $val)) {
                            $healthChanges[$field] = $val;
                        }
                    }

                    $currentIndicationIds = $product->primaryIndications()->pluck('primary_indications.id')->toArray();
                    sort($currentIndicationIds);
                    $desiredIndicationIds = $indicationIds;
                    sort($desiredIndicationIds);
                    $indicationsChanged = ($currentIndicationIds !== $desiredIndicationIds);

                    $currentTagIds = DB::table('product_tags')
                        ->where('product_id', $product->id)
                        ->where('created_by', $superAdminCompanyId)
                        ->pluck('tag_id')
                        ->toArray();
                    sort($currentTagIds);
                    $desiredTagIdsSorted = $desiredTagIds;
                    sort($desiredTagIdsSorted);
                    $tagsChanged = ($currentTagIds !== $desiredTagIdsSorted);

                    if (empty($productChanges) && empty($healthChanges)
                        && !$tagsChanged && !$indicationsChanged) {
                        $skippedCount++;
                        continue;
                    }

                    // APPLY CHANGES
                    if (!empty($productChanges)) {
                        if (isset($productChanges['name'])) {
                            $product->name = $productChanges['name'];
                            $product->slug = Str::slug($productChanges['name']);
                            unset($productChanges['name']);
                        }
                        foreach ($productChanges as $field => $val) {
                            $product->{$field} = $val;
                        }
                        $product->saveQuietly();
                    }

                    if (!empty($healthChanges)) {
                        $existingHealthProduct->fill($healthChanges);
                        $existingHealthProduct->save();
                    }

                    if ($indicationsChanged) {
                        $product->syncPrimaryIndications($indicationIds);
                    }

                    if ($tagsChanged) {
                        $this->saveTagsToPivot($product->id, $desiredTagIds, $superAdminCompanyId);
                    }

                    \App\Observers\ProductObserver::dispatchProductEvent(
                        $product->id, 'updated',
                        ['company_slug' => $this->getCurrentCompanySlug($superAdminCompanyId), 'override_mode' => false]
                    );

                    $updatedCount++;
                } else {
                    // CREATE
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
                    $product->frequency    = $frequency;
                    $product->saveQuietly();

                    $healthData = [
                        'product_id'                 => $product->id,
                        'created_by'                 => $superAdminCompanyId,
                        'sku'                        => $sku,
                        'product_image_url'          => $imageUrl,
                        'bottle_size'                => $bottleSize,
                        'bottle_size_unit'           => $bottleSizeUnit,
                        'ingredients'                => $ingredients,
                        'contraindications'          => $contraindications,
                        'research_links'             => $researchLinks,
                        'supports'                   => $supports,
                        'useful_for'                 => $usefulFor,
                        'dosing_na'                  => $hasDosingData ? false : true,
                        'practitioner_notes'         => $practitionerNotes,
                        'custom_primary_indications' => $customPrimaryIndicationsArray,
                        'custom_dosing_notes'        => $customDosingNotes,
                    ];
                    if ($fullName)       $healthData['full_name']    = $fullName;
                    if ($productForm)    $healthData['product_form'] = $productForm;
                    foreach ($dosingData as $field => $val) {
                        $healthData[$field] = $val;
                    }
                    HealthProduct::create($healthData);

                    if (!empty($indicationIds)) {
                        $product->syncPrimaryIndications($indicationIds);
                    }

                    if (!empty($desiredTagIds)) {
                        $this->saveTagsToPivot($product->id, $desiredTagIds, $superAdminCompanyId);
                    }

                    \App\Observers\ProductObserver::dispatchProductEvent(
                        $product->id, 'created',
                        ['company_slug' => $this->getCurrentCompanySlug($superAdminCompanyId), 'override_mode' => false]
                    );

                    $importedCount++;
                }
            } catch (\Exception $e) {
                $errorCount++;
                $errorMessages[] = "Row {$displayRow}: " . $e->getMessage();
                Log::error("Excel Import [SuperAdmin]: Row failed", ['row' => $displayRow, 'error' => $e->getMessage()]);
            }
        }

        $msg = __('Imported: :count, Updated: :updated, Skipped: :skipped', [
            'count' => $importedCount, 'updated' => $updatedCount, 'skipped' => $skippedCount,
        ]);
        if ($errorCount > 0) {
            $msg .= ' ' . __('Errors: :errors', ['errors' => $errorCount]);
        }

        return response()->json([
            'flag'          => ($importedCount > 0 || $updatedCount > 0) ? 'success' : (($errorCount > 0) ? 'error' : 'warning'),
            'msg'           => $msg,
            'imported'      => $importedCount,
            'updated'       => $updatedCount,
            'skipped'       => $skippedCount,
            'errors'        => $errorCount,
            'error_details' => $errorMessages,
        ]);
    }

    /**
     * ════════════════════════════════════════════════════════════════════
     * ⚡ NEW: استيراد للشركة
     * ════════════════════════════════════════════════════════════════════
     *
     * 3 سيناريوهات:
     *   1) SKU في منتجات الشركة → تعديل كامل
     *   2) SKU في منتجات السوبر ادمن → تعديل override فقط (الحقول المسموح بها)
     *   3) SKU غير موجود → إضافة منتج جديد للشركة
     */
    private function importForCompany(array $rows, array $headerMap)
    {
        $currentCompanyId = createdBy();
        $superAdminId = getSuperAdminCompanyId();

        $importedCount  = 0;
        $updatedCount   = 0;
        $skippedCount   = 0;
        $errorCount     = 0;
        $errorMessages  = [];

        Log::info("Excel Import [Company]: Started", [
            'company_id' => $currentCompanyId,
            'total_rows' => count($rows),
        ]);

        foreach ($rows as $rowIndex => $row) {
            $displayRow = $rowIndex + 2;

            try {
                $item = [];
                foreach ($headerMap as $key => $col) {
                    $item[$key] = $row[$col] ?? null;
                }

                // ---- SKU ----
                $skuRaw = $item['sku'] ?? null;
                $sku = $this->cleanImportValue($skuRaw);
                if ($sku !== null && is_numeric($sku)) {
                    $sku = (string) intval(floatval($sku));
                }
                if (empty($sku)) {
                    $skippedCount++;
                    $errorMessages[] = "Row {$displayRow}: Missing SKU — skipped.";
                    continue;
                }

                // Parse جميع الحقول
                $parsed = $this->parseExcelRow($item, $currentCompanyId);
                if (!$parsed) {
                    $skippedCount++;
                    continue;
                }
                extract($parsed);

                // ====================================================
                // تحديد السيناريو حسب وجود SKU
                // ====================================================
                $existingProduct = Product::where('sku', $sku)->first();
                $existingHealthProduct = HealthProduct::where('sku', $sku)->first();

                // السيناريو 2: SKU موجود في منتج سوبر ادمن
                if ($existingProduct && (int) $existingProduct->created_by === (int) $superAdminId) {
                    $result = $this->importCompanyUpdateSuperAdminProduct(
                        $existingProduct, $item, $currentCompanyId, $displayRow
                    );
                    if ($result === 'updated') $updatedCount++;
                    elseif ($result === 'skipped') $skippedCount++;
                    continue;
                }

                // السيناريو 1: SKU موجود في منتج الشركة نفسها
                if ($existingProduct && (int) $existingProduct->created_by === (int) $currentCompanyId) {
                    $result = $this->importCompanyUpdateOwnProduct(
                        $existingProduct, $existingHealthProduct, $parsed, $currentCompanyId, $displayRow, $sku
                    );
                    if ($result === 'updated') $updatedCount++;
                    elseif ($result === 'skipped') $skippedCount++;
                    continue;
                }

                // SKU موجود لشركة تانية → skip (ممنوع التعديل)
                if ($existingProduct) {
                    $skippedCount++;
                    $errorMessages[] = "Row {$displayRow}: SKU {$sku} belongs to another company — skipped.";
                    continue;
                }

                // السيناريو 3: SKU غير موجود → CREATE
                if (hasReachedProductLimit()) {
                    $skippedCount++;
                    $errorMessages[] = "Row {$displayRow}: Product limit reached — skipped.";
                    continue;
                }

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
                $product->created_by   = $currentCompanyId;
                $product->tax_status   = 'taxable';
                $product->frequency    = $frequency;
                $product->saveQuietly();

                $healthData = [
                    'product_id'                 => $product->id,
                    'created_by'                 => $currentCompanyId,
                    'sku'                        => $sku,
                    'product_image_url'          => $imageUrl,
                    'bottle_size'                => $bottleSize,
                    'bottle_size_unit'           => $bottleSizeUnit,
                    'ingredients'                => $ingredients,
                    'contraindications'          => $contraindications,
                    'research_links'             => $researchLinks,
                    'supports'                   => $supports,
                    'useful_for'                 => $usefulFor,
                    'dosing_na'                  => $hasDosingData ? false : true,
                    'practitioner_notes'         => $practitionerNotes,
                    'custom_primary_indications' => $customPrimaryIndicationsArray,
                    'custom_dosing_notes'        => $customDosingNotes,
                ];
                if ($fullName)       $healthData['full_name']    = $fullName;
                if ($productForm)    $healthData['product_form'] = $productForm;
                foreach ($dosingData as $field => $val) {
                    $healthData[$field] = $val;
                }
                HealthProduct::create($healthData);

                if (!empty($indicationIds)) {
                    $product->syncPrimaryIndications($indicationIds);
                }

                if (!empty($desiredTagIds)) {
                    $this->saveTagsToPivot($product->id, $desiredTagIds, $currentCompanyId);
                }

                \App\Observers\ProductObserver::dispatchProductEvent(
                    $product->id, 'created',
                    ['company_slug' => $this->getCurrentCompanySlug($currentCompanyId), 'override_mode' => false]
                );

                $importedCount++;

                Log::info("Excel Import [Company]: Created product", [
                    'row' => $displayRow, 'sku' => $sku, 'product_id' => $product->id,
                ]);

            } catch (\Exception $e) {
                $errorCount++;
                $errorMessages[] = "Row {$displayRow}: " . $e->getMessage();
                Log::error("Excel Import [Company]: Row failed", ['row' => $displayRow, 'error' => $e->getMessage()]);
            }
        }

        $msg = __('Imported: :count, Updated: :updated, Skipped: :skipped', [
            'count' => $importedCount, 'updated' => $updatedCount, 'skipped' => $skippedCount,
        ]);
        if ($errorCount > 0) {
            $msg .= ' ' . __('Errors: :errors', ['errors' => $errorCount]);
        }

        Log::info("Excel Import [Company]: Completed", [
            'imported' => $importedCount, 'updated' => $updatedCount,
            'skipped' => $skippedCount, 'errors' => $errorCount,
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
    }

    /**
     * ⚡ NEW: السيناريو 1 — شركة تعدّل منتجها الخاص (تحديث كامل)
     */
    private function importCompanyUpdateOwnProduct(
        Product $product,
        ?HealthProduct $existingHealthProduct,
        array $parsed,
        int $currentCompanyId,
        int $displayRow,
        string $sku
    ): string {
        extract($parsed);

        if (!$existingHealthProduct) {
            return 'skipped';
        }

        $newProductName    = $productName;
        $newDescription    = $description;
        $newPrice          = $price;
        $newSalePrice      = $salePrice ?: 0;
        $newStatus         = $status;
        $newCategoryId     = $categoryId ?? $product->category_id;
        $newBrandId        = $brandId ?: $product->brand_id;
        $newFrequency      = $frequency;

        $newHealthData = [
            'product_image_url'           => $imageUrl,
            'bottle_size'                 => $bottleSize,
            'bottle_size_unit'            => $bottleSizeUnit,
            'ingredients'                 => $ingredients,
            'contraindications'           => $contraindications,
            'research_links'              => $researchLinks,
            'supports'                    => $supports,
            'useful_for'                  => $usefulFor,
            'dosing_na'                   => $hasDosingData ? false : true,
            'practitioner_notes'          => $practitionerNotes,
            'custom_primary_indications'  => $customPrimaryIndicationsArray,
            'custom_dosing_notes'         => $customDosingNotes,
        ];
        if ($fullName)         $newHealthData['full_name']    = $fullName;
        if ($productForm)      $newHealthData['product_form'] = $productForm;
        foreach ($dosingData as $field => $val) {
            $newHealthData[$field] = $val;
        }

        $productChanges = [];
        if ($this->isFieldDifferent($product->name, $newProductName))       $productChanges['name']         = $newProductName;
        if ($this->isFieldDifferent($product->description, $newDescription)) $productChanges['description']  = $newDescription;
        if ($this->isFieldDifferent($product->price, $newPrice))             $productChanges['price']        = $newPrice;
        if ($this->isFieldDifferent($product->sale_price, $newSalePrice))    $productChanges['sale_price']   = $newSalePrice;
        if ($this->isFieldDifferent($product->status, $newStatus))           $productChanges['status']       = $newStatus;
        if ($this->isFieldDifferent($product->category_id, $newCategoryId))  $productChanges['category_id']  = $newCategoryId;
        if ($this->isFieldDifferent($product->brand_id, $newBrandId))        $productChanges['brand_id']     = $newBrandId;
        if ($this->isFieldDifferent($product->frequency, $newFrequency))     $productChanges['frequency']    = $newFrequency;

        $healthChanges = [];
        foreach ($newHealthData as $field => $val) {
            $currentVal = $existingHealthProduct->{$field} ?? null;
            if ($this->isFieldDifferent($currentVal, $val)) {
                $healthChanges[$field] = $val;
            }
        }

        $currentIndicationIds = $product->primaryIndications()->pluck('primary_indications.id')->toArray();
        sort($currentIndicationIds);
        $desiredIndicationIds = $indicationIds;
        sort($desiredIndicationIds);
        $indicationsChanged = ($currentIndicationIds !== $desiredIndicationIds);

        $currentTagIds = DB::table('product_tags')
            ->where('product_id', $product->id)
            ->where('created_by', $currentCompanyId)
            ->pluck('tag_id')
            ->toArray();
        sort($currentTagIds);
        $desiredTagIdsSorted = $desiredTagIds;
        sort($desiredTagIdsSorted);
        $tagsChanged = ($currentTagIds !== $desiredTagIdsSorted);

        if (empty($productChanges) && empty($healthChanges) && !$tagsChanged && !$indicationsChanged) {
            return 'skipped';
        }

        if (!empty($productChanges)) {
            if (isset($productChanges['name'])) {
                $product->name = $productChanges['name'];
                $product->slug = Str::slug($productChanges['name']);
                unset($productChanges['name']);
            }
            foreach ($productChanges as $field => $val) {
                $product->{$field} = $val;
            }
            $product->saveQuietly();
        }

        if (!empty($healthChanges)) {
            $existingHealthProduct->fill($healthChanges);
            $existingHealthProduct->save();
        }

        if ($indicationsChanged) {
            $product->syncPrimaryIndications($indicationIds);
        }

        if ($tagsChanged) {
            $this->saveTagsToPivot($product->id, $desiredTagIds, $currentCompanyId);
        }

        \App\Observers\ProductObserver::dispatchProductEvent(
            $product->id, 'updated',
            ['company_slug' => $this->getCurrentCompanySlug($currentCompanyId), 'override_mode' => false]
        );

        return 'updated';
    }

    /**
     * ⚡ NEW: السيناريو 2 — شركة تعدّل منتج سوبر ادمن (override فقط)
     *
     * الحقول المسموح بتعديلها كـ override:
     *   - description, contraindications, research_links
     *   - primary_indications (كـ names array)
     *   - dosing_* (7 حقول) + dosing_na
     *   - category_id
     *   - sale_price (→ sale_price_override)
     *   - frequency (→ frequency_override)
     *   - practitioner_notes, custom_primary_indications, custom_dosing_notes
     *     (تُحفظ في health_products الخاصة بالشركة أو override)
     *   - tags (override عبر pivot بـ created_by = company_id)
     *
     * يتم تجاهل: name, sku, price, brand_id, status, stock_*, product_form,
     *             bottle_size, ingredients, supports, useful_for, image
     */
    private function importCompanyUpdateSuperAdminProduct(
        Product $product,
        array $item,
        int $currentCompanyId,
        int $displayRow
    ): string {
        $superAdminId = getSuperAdminCompanyId();

        // Parse الحقول من الـ item مباشرة (لا نحتاج كل الحقول هنا)
        $description        = $this->getHeaderValue($item, ['description', 'desc']);
        $contraindications  = $this->getHeaderValue($item, ['contraindications']);
        if ($contraindications && in_array(strtolower($contraindications), ['n/a', 'none'])) $contraindications = null;
        $researchLinks      = $this->getHeaderValue($item, ['research / studies / article links', 'research links', 'research']);
        if ($researchLinks && in_array(strtolower($researchLinks), ['n/a', 'none'])) $researchLinks = null;

        $salePriceStr = $this->getHeaderValue($item, ['sale price', 'regular price', 'discount price']);
        $salePriceOverride = $salePriceStr ? floatval(preg_replace('/[^0-9.]/', '', $salePriceStr)) : null;

        $frequency = $this->getHeaderValue($item, ['frequency', 'dosing frequency', 'freq']);
        if ($frequency && in_array(strtolower($frequency), ['n/a', 'none'])) $frequency = null;

        $categoryName = $this->getHeaderValue($item, ['category', 'category name', 'cat']);
        $categoryId = null;
        if ($categoryName) {
            $categoryName = html_entity_decode($categoryName, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $category = Category::where('name', $categoryName)
                ->where(function ($q) use ($currentCompanyId, $superAdminId) {
                    $q->where('created_by', $currentCompanyId)->orWhere('created_by', $superAdminId);
                })->first();
            if ($category) $categoryId = $category->id;
        }

        // Dosing
        $dosingMap = [
            ['upon rising', 'dosing_upon_rising'],
            ['breakfast', 'dosing_breakfast'],
            ['between meals (am)', 'dosing_between_meals_am'],
            ['lunch', 'dosing_lunch'],
            ['between meals (pm)', 'dosing_between_meals_pm'],
            ['dinner', 'dosing_dinner'],
            ['before sleep', 'dosing_before_sleep'],
        ];
        $dosingData = [];
        $hasDosingData = false;
        foreach ($dosingMap as [$excelKey, $dbField]) {
            $val = $this->cleanImportValue($item[$excelKey] ?? null);
            if ($val && strtolower($val) === 'none') $val = null;
            $dosingData[$dbField] = $val;
            if (!empty($val)) $hasDosingData = true;
        }
        $dosingNa = $hasDosingData ? false : true;

        // Primary Indications → names array
        $indicationsRaw = $this->getHeaderValue($item, [
            'primary indications', 'primary indication',
            'indications', 'indication',
            'primary_indications', 'primary_indication',
        ]);
        $indicationNames = [];
        if ($indicationsRaw && strtolower($indicationsRaw) !== 'none') {
            $indicationNames = array_filter(array_map('trim', preg_split('/[,|]/', $indicationsRaw)));
        }

        // Practitioner notes + custom (تُحفظ في health_products الخاصة بالسوبر ادمن الأصلية
        // أو يمكن للشركة إنشاء health_product خاص بها — نحفظها في override حالياً)
        $practitionerNotes = $this->getHeaderValue($item, ['practitioner notes']);
        if ($practitionerNotes && strtolower($practitionerNotes) === 'none') $practitionerNotes = null;

        $customPrimaryIndications = $this->getHeaderValue($item, ['custom primary indications']);
        if ($customPrimaryIndications && strtolower($customPrimaryIndications) === 'none') $customPrimaryIndications = null;
        $customPrimaryIndicationsArray = $customPrimaryIndications
            ? array_filter(array_map('trim', explode(',', $customPrimaryIndications)))
            : [];

        $customDosingNotes = $this->getHeaderValue($item, ['custom dosing notes']);
        if ($customDosingNotes && strtolower($customDosingNotes) === 'none') $customDosingNotes = null;

        // Tags (override)
        $tagsRaw = $this->getHeaderValue($item, ['tags', 'tag']);
        $desiredTagIds = [];
        if ($tagsRaw && strtolower($tagsRaw) !== 'none') {
            $tagNames = array_filter(array_map('trim', preg_split('/[,|]/', $tagsRaw)));
            foreach ($tagNames as $tagName) {
                if (empty($tagName)) continue;
                $tag = Tag::firstOrCreate(
                    ['name' => $tagName, 'company_id' => $currentCompanyId],
                    ['slug' => Str::slug($tagName), 'status' => 'active', 'color' => '#6366f1', 'created_by' => $currentCompanyId]
                );
                $desiredTagIds[] = $tag->id;
            }
        }
        $desiredTagIds = array_values(array_unique($desiredTagIds));

        // ====== اقرأ override الحالي للمقارنة ======
        $existingOverride = ProductCompanyOverride::where('product_id', $product->id)
            ->where('company_id', $currentCompanyId)
            ->first();

        // بناء override الجديد
        $newOverrideData = [
            'description'               => $description,
            'contraindications'         => $contraindications,
            'research_links'            => $researchLinks,
            'primary_indications'       => $indicationNames,
            'category_id'               => $categoryId,
            'sale_price_override'       => $salePriceOverride,
            'frequency_override'        => $frequency,
            'dosing_upon_rising'        => $dosingNa ? null : ($dosingData['dosing_upon_rising'] ?? null),
            'dosing_breakfast'          => $dosingNa ? null : ($dosingData['dosing_breakfast'] ?? null),
            'dosing_between_meals_am'   => $dosingNa ? null : ($dosingData['dosing_between_meals_am'] ?? null),
            'dosing_lunch'              => $dosingNa ? null : ($dosingData['dosing_lunch'] ?? null),
            'dosing_between_meals_pm'   => $dosingNa ? null : ($dosingData['dosing_between_meals_pm'] ?? null),
            'dosing_dinner'             => $dosingNa ? null : ($dosingData['dosing_dinner'] ?? null),
            'dosing_before_sleep'       => $dosingNa ? null : ($dosingData['dosing_before_sleep'] ?? null),
            'dosing_na'                 => $dosingNa,
            'practitioner_notes'        => $practitionerNotes,
            'custom_primary_indications'=> $customPrimaryIndicationsArray,
            'custom_dosing_notes'       => $customDosingNotes,
        ];

        // كشف التغييرات
        $overrideChanges = [];
        foreach ($newOverrideData as $field => $val) {
            $currentVal = $existingOverride?->{$field} ?? null;
            if ($this->isFieldDifferent($currentVal, $val)) {
                $overrideChanges[$field] = $val;
            }
        }

        // كشف تغيير الـ tags
        $currentTagIds = DB::table('product_tags')
            ->where('product_id', $product->id)
            ->where('created_by', $currentCompanyId)
            ->pluck('tag_id')
            ->toArray();
        sort($currentTagIds);
        $desiredTagIdsSorted = $desiredTagIds;
        sort($desiredTagIdsSorted);
        $tagsChanged = ($currentTagIds !== $desiredTagIdsSorted);

        // SKIP لو مفيش تغيير
        if (empty($overrideChanges) && !$tagsChanged) {
            return 'skipped';
        }

        // حفظ override
        if (!empty($overrideChanges)) {
            ProductCompanyOverride::updateOrCreate(
                ['product_id' => $product->id, 'company_id' => $currentCompanyId],
                array_merge(['is_visible' => true], $overrideChanges)
            );
        }

        // حفظ tags (override)
        if ($tagsChanged) {
            $this->saveTagsToPivot($product->id, $desiredTagIds, $currentCompanyId);
        }

        \App\Observers\ProductObserver::dispatchProductEvent(
            $product->id, 'updated',
            [
                'company_slug'        => $this->getCurrentCompanySlug($currentCompanyId),
                'override_company_id' => $currentCompanyId,
                'override_mode'       => true,
            ]
        );

        Log::info("Excel Import [Company]: Updated super admin product override", [
            'row' => $displayRow,
            'sku' => $product->sku,
            'product_id' => $product->id,
            'override_fields' => array_keys($overrideChanges),
            'tags_changed' => $tagsChanged,
        ]);

        return 'updated';
    }

    /**
     * ⚡ NEW: Helper — Parse جميع حقول صف الإكسل
     * يستخدم في importForSuperAdmin و importForCompany
     *
     * @return array|null  returns null لو SKU/Name غير موجودين
     */
    private function parseExcelRow(array $item, int $companyId): ?array
    {
        $superAdminId = getSuperAdminCompanyId();

        // SKU
        $skuRaw = $item['sku'] ?? null;
        $sku = $this->cleanImportValue($skuRaw);
        if ($sku !== null && is_numeric($sku)) {
            $sku = (string) intval(floatval($sku));
        }
        if (empty($sku)) return null;

        // Product Name
        $productName = $this->getHeaderValue($item, ['product name', 'name', 'title']) ?: 'Unnamed Product';
        $fullName    = $this->getHeaderValue($item, ['full name']);

        // Category
        $categoryId   = null;
        $categoryName = $this->getHeaderValue($item, ['category', 'category name', 'cat']);
        if ($categoryName) {
            $categoryName = html_entity_decode($categoryName, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $category = Category::where('name', $categoryName)
                ->where(function ($q) use ($companyId, $superAdminId) {
                    $q->where('created_by', $companyId)->orWhere('created_by', $superAdminId);
                })->first();
            if (!$category) {
                $categorySlug = Str::slug($categoryName);
                $origSlug = $categorySlug;
                $counter = 1;
                while (Category::where('slug', $categorySlug)->exists()) {
                    $categorySlug = $origSlug . '-' . $counter++;
                }
                $category = Category::create([
                    'name'       => $categoryName,
                    'slug'       => $categorySlug,
                    'created_by' => $companyId,
                    'company_id' => $companyId,
                    'status'     => 'active',
                ]);
            }
            $categoryId = $category->id;
        }

        // Prices
        $priceStr = $this->getHeaderValue($item, ['regular price', 'price']) ?? '0';
        $price    = floatval(preg_replace('/[^0-9.]/', '', $priceStr));
        $salePriceStr = $this->getHeaderValue($item, ['sale price', 'discount price']);
        $salePrice = $salePriceStr ? floatval(preg_replace('/[^0-9.]/', '', $salePriceStr)) : 0;

        // Supplier / Brand
        $supplierName = $this->getHeaderValue($item, ['supplier', 'brand', 'supplier name', 'brand name']);
        $brandId = null;
        if ($supplierName) {
            $brand = Brand::firstOrCreate(
                ['name' => $supplierName, 'created_by' => $companyId],
                ['status' => 'active']
            );
            $brandId = $brand->id;
        }

        // Other fields
        $description = $this->getHeaderValue($item, ['description', 'desc']) ?? '';
        $isActiveRaw = strtolower(trim((string) ($item['is active'] ?? '')));
        $status      = in_array($isActiveRaw, ['true', '1', 'yes']) ? 'active' : 'inactive';
        if ($isActiveRaw === '') $status = 'active';

        $frequency = $this->getHeaderValue($item, ['frequency', 'dosing frequency', 'freq']);
        if ($frequency && in_array(strtolower($frequency), ['n/a', 'none'])) $frequency = null;

        $imageUrl = $this->getHeaderValue($item, ['product image url', 'image url', 'image']);
        if ($imageUrl && in_array(strtolower($imageUrl), ['n/a', 'none'])) $imageUrl = null;

        // Bottle Size / Unit
        $bottleSizeRaw = $this->getHeaderValue($item, ['bottle size / unit count', 'bottle size', 'size']);
        $parsedBottle  = $this->parseBottleSizeAndUnit($bottleSizeRaw);
        $bottleSize    = $parsedBottle['bottle_size'];
        $bottleSizeUnit = $parsedBottle['bottle_size_unit'];

        // Product Form
        $productFormExcel = $this->getHeaderValue($item, ['product form', 'form']);
        if ($productFormExcel && in_array(strtolower($productFormExcel), ['n/a', 'none'])) $productFormExcel = null;
        $productForm = null;
        if ($productFormExcel) {
            $pfLower = strtolower(trim($productFormExcel));
            if (in_array($pfLower, ['liquid', 'tincture', 'drop', 'drops', 'oil', 'spray', 'liquid extract'])) {
                $productForm = 'Liquid'; if (!$bottleSizeUnit) $bottleSizeUnit = 'oz';
            } elseif (in_array($pfLower, ['caps', 'capsule', 'capsules', 'caplet', 'caplets', 'tablet', 'tablets', 'softgel', 'softgels'])) {
                $productForm = 'Caps'; if (!$bottleSizeUnit) $bottleSizeUnit = 'caps';
            } elseif (in_array($pfLower, ['powder', 'powders'])) {
                $productForm = 'Powder'; if (!$bottleSizeUnit) $bottleSizeUnit = 'g';
            } elseif (in_array($pfLower, ['gummy', 'gummies', 'chewable'])) {
                $productForm = 'Gummy'; if (!$bottleSizeUnit) $bottleSizeUnit = 'gummies';
            } elseif (in_array($pfLower, ['tea', 'herbal tea', 'loose tea', 'tea bags'])) {
                $productForm = 'Tea'; if (!$bottleSizeUnit) $bottleSizeUnit = 'bags';
            } elseif (in_array($pfLower, ['cream', 'topical', 'ointment', 'lotion', 'salve', 'balm', 'gel'])) {
                $productForm = 'Topical'; if (!$bottleSizeUnit) $bottleSizeUnit = 'oz';
            } else { $productForm = $productFormExcel; }
        } elseif ($bottleSizeRaw) {
            $bsLower = strtolower($bottleSizeRaw);
            if (preg_match('/cap|tablet|caplet|ct\\b|capsule/i', $bsLower)) $productForm = 'Caps';
            elseif (preg_match('/fl\\s*oz|ml|oz\\b|liquid|tincture|drop/i', $bsLower)) $productForm = 'Liquid';
        }

        // Health fields
        $contraindications = $this->getHeaderValue($item, ['contraindications']);
        if (empty($contraindications) || in_array(strtolower($contraindications ?? ''), ['n/a', 'none'])) {
            $contraindications = 'N/A';
        }
        $ingredients = $this->getHeaderValue($item, ['ingredients']);
        if ($ingredients && strtolower($ingredients) === 'none') $ingredients = null;
        $researchLinks = $this->getHeaderValue($item, ['research / studies / article links', 'research links', 'research']);
        if ($researchLinks && in_array(strtolower($researchLinks), ['n/a', 'none'])) $researchLinks = null;
        $supports = $this->getHeaderValue($item, ['supports']);
        if ($supports && in_array(strtolower($supports), ['n/a', 'none'])) $supports = null;
        $usefulFor = $this->getHeaderValue($item, ['useful for']);
        if ($usefulFor && in_array(strtolower($usefulFor), ['n/a', 'none'])) $usefulFor = null;

        // Dosing
        $dosingMap = [
            ['upon rising', 'dosing_upon_rising'],
            ['breakfast', 'dosing_breakfast'],
            ['between meals (am)', 'dosing_between_meals_am'],
            ['lunch', 'dosing_lunch'],
            ['between meals (pm)', 'dosing_between_meals_pm'],
            ['dinner', 'dosing_dinner'],
            ['before sleep', 'dosing_before_sleep'],
        ];
        $dosingData = [];
        $hasDosingData = false;
        foreach ($dosingMap as [$excelKey, $dbField]) {
            $val = $this->cleanImportValue($item[$excelKey] ?? null);
            if ($val && strtolower($val) === 'none') $val = null;
            $dosingData[$dbField] = $val;
            if (!empty($val)) $hasDosingData = true;
        }

        // Practitioner + custom fields
        $practitionerNotes = $this->getHeaderValue($item, ['practitioner notes']);
        if ($practitionerNotes && strtolower($practitionerNotes) === 'none') $practitionerNotes = null;

        $customPrimaryIndications = $this->getHeaderValue($item, ['custom primary indications']);
        if ($customPrimaryIndications && strtolower($customPrimaryIndications) === 'none') $customPrimaryIndications = null;
        $customPrimaryIndicationsArray = $customPrimaryIndications
            ? array_filter(array_map('trim', explode(',', $customPrimaryIndications)))
            : [];

        $customDosingNotes = $this->getHeaderValue($item, ['custom dosing notes']);
        if ($customDosingNotes && strtolower($customDosingNotes) === 'none') $customDosingNotes = null;

        // Primary Indications (find-or-create)
        $indicationsRaw = $this->getHeaderValue($item, [
            'primary indications', 'primary indication',
            'indications', 'indication',
            'primary_indications', 'primary_indication',
        ]);
        $primaryIndicationsNames = [];
        if ($indicationsRaw && strtolower($indicationsRaw) !== 'none') {
            $primaryIndicationsNames = array_filter(array_map('trim', preg_split('/[,|]/', $indicationsRaw)));
        }
        $indicationIds = [];
        foreach ($primaryIndicationsNames as $indicationName) {
            if (empty($indicationName)) continue;
            $indication = $this->findOrCreateIndicationRobust($indicationName);
            if ($indication) $indicationIds[] = $indication->id;
        }

        // Tags — ⚡ استخدام findOrCreateTagByName لمنع التكرار
        $tagsRaw = $this->getHeaderValue($item, ['tags', 'tag']);
        $desiredTagIds = [];
        if ($tagsRaw && strtolower($tagsRaw) !== 'none') {
            $tagNames = array_filter(array_map('trim', preg_split('/[,|]/', $tagsRaw)));
            foreach ($tagNames as $tagName) {
                if (empty($tagName)) continue;
                // ⚡ FIX: استخدم findOrCreateTagByName بدلاً من Tag::firstOrCreate
                // هذا يبحث بالاسم عبر كل شركات المستخدم، ويمنع إنشاء تاجات مكررة
                $tag = $this->findOrCreateTagByName($tagName, $companyId);
                if ($tag) {
                    $desiredTagIds[] = $tag->id;
                }
            }
        }
        $desiredTagIds = array_values(array_unique($desiredTagIds));

        return compact(
            'sku', 'productName', 'fullName', 'categoryId', 'price', 'salePrice',
            'brandId', 'description', 'status', 'frequency', 'imageUrl',
            'bottleSize', 'bottleSizeUnit', 'productForm',
            'contraindications', 'ingredients', 'researchLinks', 'supports', 'usefulFor',
            'dosingData', 'hasDosingData',
            'practitionerNotes', 'customPrimaryIndicationsArray', 'customDosingNotes',
            'indicationIds', 'desiredTagIds'
        );
    }

    /**
     * Frequency-Only Mode
     */
    private function importFrequencyOnly(array $rows, array $headerMap)
    {
        Log::info("Excel Import [Frequency-Only Mode]: Started", [
            'total_rows' => count($rows),
        ]);

        $superAdminCompanyId = getSuperAdminCompanyId();
        $updatedCount        = 0;
        $skippedCount        = 0;
        $errorCount          = 0;
        $errorMessages       = [];

        $frequencyCol = $headerMap['frequency']
                      ?? $headerMap['dosing_frequency']
                      ?? $headerMap['freq']
                      ?? null;
        $skuCol = $headerMap['sku'] ?? null;

        if (!$skuCol || !$frequencyCol) {
            return response()->json([
                'flag' => 'error',
                'msg'  => __('Frequency-only mode requires both "sku" and "frequency" columns.'),
            ]);
        }

        foreach ($rows as $rowIndex => $row) {
            $displayRow = $rowIndex + 2;

            try {
                $skuRaw = $row[$skuCol] ?? null;
                $sku = $this->cleanImportValue($skuRaw);

                if ($sku !== null && is_numeric($sku)) {
                    $sku = (string) intval(floatval($sku));
                }

                if (empty($sku)) {
                    $skippedCount++;
                    $errorMessages[] = "Row {$displayRow}: Missing SKU — skipped.";
                    continue;
                }

                $frequencyRaw = $this->cleanImportValue($row[$frequencyCol] ?? null);
                if ($frequencyRaw && in_array(strtolower($frequencyRaw), ['n/a', 'none', 'null'])) {
                    $frequencyRaw = null;
                }
                $newFrequency = $frequencyRaw;

                $product = Product::where('sku', $sku)->first();

                if (!$product) {
                    $healthProduct = HealthProduct::where('sku', $sku)->first();
                    if ($healthProduct) {
                        $product = Product::find($healthProduct->product_id);
                    }
                }

                if (!$product) {
                    $skippedCount++;
                    $errorMessages[] = "Row {$displayRow}: SKU {$sku} not found — skipped.";
                    continue;
                }

                $currentFrequency = $product->frequency;

                if (!$this->isFieldDifferent($currentFrequency, $newFrequency)) {
                    $skippedCount++;
                    continue;
                }

                $product->frequency = $newFrequency;
                $product->saveQuietly();

                \App\Observers\ProductObserver::dispatchProductEvent(
                    $product->id,
                    'updated',
                    [
                        'company_slug'  => $this->getCurrentCompanySlug($superAdminCompanyId),
                        'override_mode' => false,
                    ]
                );

                $updatedCount++;

            } catch (\Exception $e) {
                $errorCount++;
                $skuLabel = $sku ?? 'unknown';
                $errorMessages[] = "Row {$displayRow} (SKU: {$skuLabel}): " . $e->getMessage();
                Log::error("Excel Import [Frequency-Only]: Row failed", [
                    'row'   => $displayRow,
                    'sku'   => $skuLabel,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $msg = __('Frequency update — Updated: :updated, Skipped: :skipped', [
            'updated' => $updatedCount,
            'skipped' => $skippedCount,
        ]);
        if ($errorCount > 0) {
            $msg .= ' ' . __('Errors: :errors', ['errors' => $errorCount]);
        }

        return response()->json([
            'flag'          => ($updatedCount > 0) ? 'success' : (($errorCount > 0) ? 'error' : 'warning'),
            'msg'           => $msg,
            'imported'      => 0,
            'updated'       => $updatedCount,
            'skipped'       => $skippedCount,
            'errors'        => $errorCount,
            'error_details' => $errorMessages,
            'mode'          => 'frequency_only',
        ]);
    }

    // =========================================================================
    // =========== PRIVATE HELPERS =============================================
    // =========================================================================

    /**
     * Clean a value from the Excel import.
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

    /**
     * ════════════════════════════════════════════════════════════════════
     * ⚡ NEW: توليد SKU فريد تلقائياً
     * ════════════════════════════════════════════════════════════════════
     *
     * الصيغة: SKU-{Ymd}-{RANDOM6}
     * مثال: SKU-20260702-A3F9B2
     *
     * يضمن الفرادة عبر loop حتى يلاقي SKU غير مستخدم.
     *
     * @return string
     */
    private function generateUniqueSku(): string
    {
        $prefix = 'SKU-' . date('Ymd') . '-';

        do {
            $random = strtoupper(Str::random(6));
            $sku = $prefix . $random;
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }

    /**
     * ⚡ BULLETPROOF: Save tags to product_tags pivot table
     * لا تنشئ tags جديدة أبداً
     */
private function saveTagsToPivot(int $productId, array $tagIds, int $companyId): void

    {

        $tagIds = array_values(array_unique(

            array_filter($tagIds, fn($id) => !is_null($id) && $id !== '' && is_numeric($id))

        ));


        if (empty($tagIds)) {

            DB::table('product_tags')

                ->where('product_id', $productId)

                ->where('created_by', $companyId)

                ->delete();

            return;

        }


        $intTagIds = array_map('intval', $tagIds);


        $validTagIds = \App\Models\Tag::whereIn('id', $intTagIds)

            ->visibleTo($companyId)

            ->pluck('id')

            ->toArray();


        $invalidTagIds = array_diff($intTagIds, $validTagIds);


        if (!empty($invalidTagIds)) {

            Log::warning('ProductController: Filtered out invalid/inaccessible tag IDs', [

                'product_id'     => $productId,

                'company_id'     => $companyId,

                'sent_tag_ids'   => $intTagIds,

                'invalid_tag_ids'=> array_values($invalidTagIds),

                'valid_tag_ids'  => $validTagIds,

            ]);

        }


        // 1) حذف pivot entries الخاصة بالشركة الحالية فقط (override سابق)

        DB::table('product_tags')

            ->where('product_id', $productId)

            ->where('created_by', $companyId)

            ->delete();


        if (empty($validTagIds)) {

            return;

        }


        // 2) ⚡ FIX: جلب التاجز الموجودة فعلياً في pivot (عبر أي owner آخر)

        //    هذا يمنع إنشاء duplicate rows لما الشركة تعدّل منتج

        //    السوبر ادمن بدون تغيير التاجز.

        $existingTagIds = DB::table('product_tags')

            ->where('product_id', $productId)

            ->pluck('tag_id')

            ->map(fn($id) => (int) $id)

            ->toArray();


        // 3) الإدراج فقط للتاجز الجديدة فعلاً (اللي الشركة ضافتها بنفسها)

        $newTagIds = array_values(array_diff($validTagIds, $existingTagIds));


        if (empty($newTagIds)) {

            // كل التاجز موجودة بالفعل في pivot (مثلاً عبر السوبر ادمن)

            // → لا ننشئ duplicates

            Log::info('ProductController@saveTagsToPivot: No new tags to insert (all already exist in pivot)', [

                'product_id'         => $productId,

                'company_id'         => $companyId,

                'submitted_tag_ids'  => $validTagIds,

                'already_in_pivot'   => $existingTagIds,

            ]);

            return;

        }


        $insertData = [];

        $now = now();

        foreach ($newTagIds as $tagId) {

            $insertData[] = [

                'product_id' => $productId,

                'tag_id'     => (int) $tagId,

                'created_by' => $companyId,

                'created_at' => $now,

                'updated_at' => $now,

            ];

        }


        Log::info('ProductController@saveTagsToPivot: Inserting new tag pivot rows', [

            'product_id'         => $productId,

            'company_id'         => $companyId,

            'submitted_tag_ids'  => $validTagIds,

            'already_in_pivot'   => $existingTagIds,

            'new_tag_ids'        => $newTagIds,

        ]);


        try {

            DB::table('product_tags')->insert($insertData);

        } catch (\Illuminate\Database\QueryException $e) {

            if (str_contains($e->getMessage(), 'Duplicate entry')) {

                Log::warning('ProductController: Duplicate tag pivot entry, inserting one by one', [

                    'product_id' => $productId,

                    'company_id' => $companyId,

                ]);

                foreach ($insertData as $row) {

                    try {

                        DB::table('product_tags')->insert($row);

                    } catch (\Illuminate\Database\QueryException $e2) {

                        if (!str_contains($e2->getMessage(), 'Duplicate entry')) {

                            throw $e2;

                        }

                    }

                }

            } else {

                throw $e;

            }

        }

    }

    /**
     * ⚡ Helper: فلترة tag IDs صالحة فقط
     */
    private function filterValidTagIds(array $tagIds, int $companyId): array
    {
        $tagIds = array_values(array_filter($tagIds, fn($id) => !is_null($id) && $id !== '' && is_numeric($id)));

        if (empty($tagIds)) {
            return [];
        }

        $intTagIds = array_map('intval', $tagIds);

        return \App\Models\Tag::whereIn('id', $intTagIds)
            ->visibleTo($companyId)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Sync "Pairs Well With" products via product_pairs pivot table
     */
    private function syncPairsWellWith(int $productId, array $pairedIds, int $companyId): void
    {
        $pairedIds = array_values(array_unique(
            array_filter($pairedIds, fn($id) => !is_null($id) && $id !== '' && is_numeric($id))
        ));

        DB::table('product_pairs')
            ->where('product_id', $productId)
            ->where('created_by', $companyId)
            ->delete();

        if (empty($pairedIds)) {
            return;
        }

        $insertData = [];
        $now = now();
        foreach ($pairedIds as $pairedId) {
            if ((int) $pairedId === (int) $productId) {
                continue;
            }
            $insertData[] = [
                'product_id'        => $productId,
                'paired_product_id' => (int) $pairedId,
                'created_by'        => $companyId,
                'created_at'        => $now,
                'updated_at'        => $now,
            ];
        }
        if (!empty($insertData)) {
            DB::table('product_pairs')->insert($insertData);
        }
    }

    /**
     * Helper: استخراج قيمة من $item بدعم multiple aliases للـ header
     */
    private function getHeaderValue(array $item, array $possibleKeys): ?string
    {
        foreach ($possibleKeys as $key) {
            if (isset($item[$key]) && $item[$key] !== null && $item[$key] !== '') {
                return $this->cleanImportValue($item[$key]);
            }
        }
        return null;
    }

    /**
     * ⚡ FIX: Find-or-Create Primary Indication — يستخدم forceFill لتجاوز $fillable
     */
    private function findOrCreateIndicationRobust(string $name): ?\App\Models\PrimaryIndication
    {
        $trimmed = trim($name);
        if ($trimmed === '') return null;

        try {
            $existing = \App\Models\PrimaryIndication::whereRaw('LOWER(name) = ?', [mb_strtolower($trimmed)])->first();
            if ($existing) {
                return $existing;
            }

            $slug = Str::slug($trimmed);
            if (empty($slug)) {
                $slug = 'ind-' . substr(md5($trimmed), 0, 8);
            }

            $baseSlug = $slug;
            $counter = 1;
            while (\App\Models\PrimaryIndication::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter++;
            }

            $data = [
                'name' => $trimmed,
                'slug' => $slug,
            ];

            $columns = \Schema::getColumnListing('primary_indications');

            if (in_array('company_id', $columns)) {
                $data['company_id'] = getSuperAdminCompanyId();
            }
            if (in_array('created_by', $columns)) {
                $data['created_by'] = getSuperAdminCompanyId();
            }
            if (in_array('status', $columns)) {
                $data['status'] = 'active';
            }

            $indication = new \App\Models\PrimaryIndication();
            $indication->forceFill($data)->save();

            Log::info("Excel Import: Created new primary indication", [
                'name'       => $trimmed,
                'slug'       => $slug,
                'id'         => $indication->id,
                'company_id' => $data['company_id'] ?? null,
            ]);

            return $indication;

        } catch (\Illuminate\Database\QueryException $qe) {
            Log::warning("Excel Import: QueryException in findOrCreateIndication, retrying", [
                'name'  => $trimmed,
                'error' => $qe->getMessage(),
            ]);

            $existing = \App\Models\PrimaryIndication::whereRaw('LOWER(name) = ?', [mb_strtolower($trimmed)])->first();
            if ($existing) return $existing;

            Log::error("Excel Import: Failed to find-or-create primary indication after retry", [
                'name'  => $trimmed,
                'error' => $qe->getMessage(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error("Excel Import: Failed to find-or-create primary indication", [
                'name'  => $trimmed,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Helper: استخراج bottle_size و bottle_size_unit
     */
    private function parseBottleSizeAndUnit($rawValue): array
    {
        $result = ['bottle_size' => null, 'bottle_size_unit' => null];

        if (empty($rawValue)) return $result;

        $value = trim((string) $rawValue);
        $lower = strtolower($value);

        if ($value === '' || $lower === 'none' || $lower === 'n/a' || $lower === 'null') {
            return $result;
        }

        $pattern = '/^(\d+(?:\.\d+)?)\s*(fl\.?\s*oz|floz|oz|ml|milliliter|milliliters|caps|cap|capsule[s]?|caplet[s]?|tablet[s]?|softgel[s]?|gummies|gummy|g|grams|gram|bags|count|ct|pieces|pcs)\b/i';

        if (preg_match($pattern, $value, $matches)) {
            $result['bottle_size'] = (float) $matches[1];
            $unit = strtolower(trim(preg_replace('/\s+/', ' ', $matches[2])));

            if (in_array($unit, ['fl oz', 'fl. oz', 'fl.oz', 'floz', 'oz'])) {
                $result['bottle_size_unit'] = 'oz';
            } elseif (in_array($unit, ['ml', 'milliliter', 'milliliters'])) {
                $result['bottle_size_unit'] = 'ml';
            } elseif (in_array($unit, ['cap', 'caps', 'capsule', 'capsules'])) {
                $result['bottle_size_unit'] = 'caps';
            } elseif (in_array($unit, ['caplet', 'caplets', 'tablet', 'tablets'])) {
                $result['bottle_size_unit'] = 'tablets';
            } elseif (in_array($unit, ['softgel', 'softgels'])) {
                $result['bottle_size_unit'] = 'softgels';
            } elseif (in_array($unit, ['gummy', 'gummies'])) {
                $result['bottle_size_unit'] = 'gummies';
            } elseif (in_array($unit, ['g', 'gram', 'grams'])) {
                $result['bottle_size_unit'] = 'g';
            } elseif (in_array($unit, ['bags'])) {
                $result['bottle_size_unit'] = 'bags';
            } elseif (in_array($unit, ['count', 'ct', 'pieces', 'pcs'])) {
                $result['bottle_size_unit'] = 'count';
            } else {
                $result['bottle_size_unit'] = $unit;
            }
        } else {
            if (preg_match('/^(\d+(?:\.\d+)?)$/', $value)) {
                $result['bottle_size'] = (float) $value;
            } elseif (preg_match('/^[a-zA-Z\s\.]+$/', $value)) {
                $unit = strtolower(trim($value));
                if (in_array($unit, ['caps', 'cap', 'capsule'])) {
                    $result['bottle_size_unit'] = 'caps';
                } elseif (in_array($unit, ['oz', 'fl oz', 'fl.oz'])) {
                    $result['bottle_size_unit'] = 'oz';
                } elseif ($unit === 'ml') {
                    $result['bottle_size_unit'] = 'ml';
                } else {
                    $result['bottle_size_unit'] = $unit;
                }
            } else {
                $result['bottle_size'] = $value;
            }
        }

        return $result;
    }

    /**
     * مقارنة قيمتين لمعرفة هل تختلفان فعلاً (للاستيراد)
     */
    private function isFieldDifferent($current, $new): bool
    {
        return $this->normalizeForCompare($current)
            !== $this->normalizeForCompare($new);
    }

        private function normalizeForCompare($value)
    {
        // null أو فارغ → ''
        if ($value === null || $value === '' || $value === []) {
            return '';
        }

        // Boolean
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        // Array
        if (is_array($value)) {
            $normalized = array_map(fn($v) => is_string($v) ? trim($v) : $v, $value);
            $normalized = array_filter($normalized, fn($v) => $v !== '' && $v !== null);
            sort($normalized);
            return implode('|', $normalized);
        }

        // Numeric → float string
        if (is_numeric($value)) {
            $float = floatval($value);
            // لو رقم صحيح، رجّعه كـ int string (10.0 → "10" مو "10.00")
            if ($float == intval($float)) {
                return (string) intval($float);
            }
            return (string) $float;
        }

        // String
        $trimmed = trim((string) $value);
        // لو النص يمثل رقم، عالجه كرقم
        if (is_numeric($trimmed)) {
            $float = floatval($trimmed);
            if ($float == intval($float)) {
                return (string) intval($float);
            }
            return (string) $float;
        }
        return $trimmed;
    }

        private function findOrCreateTagByName(string $name, int $companyId): ?Tag
    {
        $trimmed = trim($name);
        if ($trimmed === '') return null;

        $superAdminId = getSuperAdminCompanyId();

        // ابحث بالاسم (case-insensitive) ضمن tags الشركة أو السوبر ادمن
        $existing = Tag::whereRaw('LOWER(name) = ?', [mb_strtolower($trimmed)])
            ->where(function ($q) use ($companyId, $superAdminId) {
                $q->where('company_id', $companyId)
                  ->orWhere('company_id', $superAdminId)
                  ->orWhereNull('company_id');
            })
            ->first();

        if ($existing) return $existing;

        return Tag::create([
            'name'       => $trimmed,
            'slug'       => Str::slug($trimmed),
            'color'      => '#6366f1',
            'status'     => 'active',
            'company_id' => $companyId,
            'created_by' => $companyId,
        ]);
    }


    /**
     * تطبيع القيمة للمقارنة في الاستيراد
     */
    private function normalizeForImportCompare($value)
    {
        if ($value === null || $value === '' || $value === []) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_string($value)) {
            $lower = strtolower(trim($value));
            if ($lower === 'true' || $lower === '1') {
                return '1';
            }
            if ($lower === 'false' || $lower === '0') {
                return '0';
            }
        }

        if (is_array($value)) {
            $normalized = array_map(function ($v) {
                return is_string($v) ? trim($v) : $v;
            }, $value);
            $normalized = array_filter($normalized, fn($v) => $v !== '' && $v !== null);
            sort($normalized);
            return implode('|', $normalized);
        }

        if (is_numeric($value)) {
            return (string) floatval($value);
        }

        return trim((string) $value);
    }

    // =========================================================================
    // =========== MERCHANT COMPARISON =========================================
    // =========================================================================

    public function merchantCompareResults()
    {
        if (auth()->user()->isSuperAdmin()) {
            return redirect()->route('products.index')->with('error', __('This feature is for company users only.'));
        }

        $currentCompanyId = createdBy();
        $superAdminId = getSuperAdminCompanyId();

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

            if ($override->price_override !== null) {
                $changes[] = [
                    'override_id' => $override->id,
                    'field_name'  => 'price_override',
                    'label'       => __('Price'),
                    'original_value' => number_format(floatval($product->price), 2),
                    'merchant_value' => number_format(floatval($override->price_override), 2),
                ];
            }

            if ($override->sale_price_override !== null) {
                $changes[] = [
                    'override_id' => $override->id,
                    'field_name'  => 'sale_price_override',
                    'label'       => __('Sale Price'),
                    'original_value' => $product->sale_price ? number_format(floatval($product->sale_price), 2) : '—',
                    'merchant_value' => number_format(floatval($override->sale_price_override), 2),
                ];
            }

            if ($override->stock_quantity_override !== null) {
                $changes[] = [
                    'override_id' => $override->id,
                    'field_name'  => 'stock_quantity_override',
                    'label'       => __('Stock Quantity'),
                    'original_value' => (string) ($product->stock_quantity ?? 0),
                    'merchant_value' => (string) $override->stock_quantity_override,
                ];
            }

            if ($override->stock_status_override !== null) {
                $changes[] = [
                    'override_id' => $override->id,
                    'field_name'  => 'stock_status_override',
                    'label'       => __('Stock Status'),
                    'original_value' => ucfirst(str_replace('_', ' ', $product->stock_status ?? '')),
                    'merchant_value' => ucfirst(str_replace('_', ' ', $override->stock_status_override)),
                ];
            }

            if ($override->description !== null) {
                $changes[] = [
                    'override_id' => $override->id,
                    'field_name'  => 'description',
                    'label'       => __('Description'),
                    'original_value' => $product->description ?? '—',
                    'merchant_value' => $override->description,
                ];
            }

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

            if ($override->contraindications !== null) {
                $changes[] = [
                    'override_id' => $override->id,
                    'field_name'  => 'contraindications',
                    'label'       => __('Contraindications'),
                    'original_value' => $baseHealthProduct?->contraindications ?? '—',
                    'merchant_value' => $override->contraindications,
                ];
            }

            if ($override->research_links !== null) {
                $changes[] = [
                    'override_id' => $override->id,
                    'field_name'  => 'research_links',
                    'label'       => __('Research Links'),
                    'original_value' => $baseHealthProduct?->research_links ?? '—',
                    'merchant_value' => $override->research_links,
                ];
            }

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

            if ($override->dosing_na !== null) {
                $changes[] = [
                    'override_id'    => $override->id,
                    'field_name'     => 'dosing_na',
                    'label'          => __('Dosing N/A'),
                    'original_value' => $baseHealthProduct?->dosing_na ? __('Yes') : __('No'),
                    'merchant_value' => $override->dosing_na ? __('Yes') : __('No'),
                ];
            }

            if ($baseHealthProduct?->practitioner_notes !== null) {
                $changes[] = [
                    'override_id'    => $override->id,
                    'field_name'     => 'practitioner_notes',
                    'label'          => __('Practitioner Notes'),
                    'original_value' => null,
                    'merchant_value' => $baseHealthProduct->practitioner_notes,
                    'is_exclusive'   => true,
                ];
            }

            if (!empty($baseHealthProduct?->custom_primary_indications)) {
                $merchantValue = is_array($baseHealthProduct->custom_primary_indications)
                    ? implode(', ', $baseHealthProduct->custom_primary_indications)
                    : $baseHealthProduct->custom_primary_indications;
                $changes[] = [
                    'override_id'    => $override->id,
                    'field_name'     => 'custom_primary_indications',
                    'label'          => __('Custom Primary Indications'),
                    'original_value' => null,
                    'merchant_value' => $merchantValue,
                    'is_exclusive'   => true,
                ];
            }

            if ($baseHealthProduct?->custom_dosing_notes !== null) {
                $changes[] = [
                    'override_id'    => $override->id,
                    'field_name'     => 'custom_dosing_notes',
                    'label'          => __('Custom Dosing Notes'),
                    'original_value' => null,
                    'merchant_value' => $baseHealthProduct->custom_dosing_notes,
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

    public function merchantRevertField(Request $request)
    {
        $request->validate([
            'id'         => 'required|exists:product_company_overrides,id',
            'field_name' => 'required|string',
        ]);

        $override = ProductCompanyOverride::findOrFail($request->id);

        if ($override->company_id !== createdBy()) {
            return response()->json([
                'flag' => 'error',
                'msg'  => __('Unauthorized.'),
            ], 403);
        }

        $fieldName = $request->field_name;

        $revertibleFields = [
            'price_override',
            'sale_price_override',
            'stock_quantity_override',
            'stock_status_override',
            'description',
            'contraindications',
            'research_links',
            'category_id',
            'primary_indications',
            'dosing_upon_rising',
            'dosing_breakfast',
            'dosing_between_meals_am',
            'dosing_lunch',
            'dosing_between_meals_pm',
            'dosing_dinner',
            'dosing_before_sleep',
            'dosing_na',
        ];

        if (!in_array($fieldName, $revertibleFields)) {
            return response()->json([
                'flag' => 'error',
                'msg'  => __('Invalid field name.'),
            ]);
        }

        $override->$fieldName = null;
        $override->save();

        return response()->json([
            'flag' => 'success',
            'msg'  => __('Field reverted to original value.'),
        ]);
    }

    // =========================================================================
    // =========== PROVIDER COMPARISON ========================================
    // =========================================================================

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

            $liveUrl = $providerApiUrl . '&t=' . time();
            $response = \Illuminate\Support\Facades\Http::timeout(120)->get($liveUrl);

            if (!$response->successful()) {
                return redirect()->back()->with('error', __('Failed to fetch data from provider.'));
            }

            $apiProducts = $response->json();

            if (empty($apiProducts) || !is_array($apiProducts)) {
                return redirect()->back()->with('error', __('No data found from provider.'));
            }

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
                if (Schema::hasColumn('products', $fieldName)) {
                    $product->$fieldName = $newValue;
                    $product->save();
                }
                break;
        }
    }

    private function cleanUpResolvedProduct($productId)
    {
        $pendingCount = ProductComparison::where('product_id', $productId)
            ->where('status', 'pending')
            ->count();

        if ($pendingCount === 0) {
            ProductComparison::where('product_id', $productId)->delete();
        }
    }

    private function isActuallyDifferent($oldValue, $newValue)
    {
        $oldNormalized = $this->normalizeForComparison($oldValue);
        $newNormalized = $this->normalizeForComparison($newValue);

        if (empty($oldNormalized) && empty($newNormalized)) {
            return false;
        }

        return $oldNormalized !== $newNormalized;
    }

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

    private function getCurrentCompanySlug(?int $currentCompanyId = null): ?string
    {
        $user = auth()->user();

        if ($user && isset($user->slug) && !empty($user->slug)) {
            return (string) $user->slug;
        }

        if ($user && method_exists($user, 'company') && $user->company && !empty($user->company->slug)) {
            return (string) $user->company->slug;
        }

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
