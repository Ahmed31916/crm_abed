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
                if (isSuperAdminProduct($product) && (int) $product->created_by !== (int) $currentCompanyId) {
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
                ->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::whereIn('created_by', [$currentCompanyId, getSuperAdminCompanyId()])
                ->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'taxes' => Tax::where('created_by', $currentCompanyId)->where('status', 'active')->get(['id', 'name', 'rate']),
            'tags' => Tag::visibleTo($currentCompanyId)->orderBy('name')->get(['id', 'name', 'color']),
            'primaryIndications' => PrimaryIndication::visibleTo($currentCompanyId)->orderBy('name')->get(['id', 'name']),
            'users' => auth()->user()->type === 'company'
                ? \App\Models\User::where('created_by', $currentCompanyId)->select('id', 'name', 'email')->get() : [],
            'companies' => auth()->user()->isSuperAdmin()
                ? \App\Models\User::where('type', 'company')->select('id', 'name', 'email')->get() : [],
            'productLimitInfo' => auth()->user()->type === 'company' ? [
                'current' => Product::where('created_by', $currentCompanyId)->count(),
                'limit' => auth()->user()->product_limit ?? 10,
                'can_create' => Product::where('created_by', $currentCompanyId)->count() < (auth()->user()->product_limit ?? 10),
            ] : null,
            'isSuperAdmin' => auth()->user()->isSuperAdmin(),
            'superAdminCompanyId' => getSuperAdminCompanyId(),
            'filters' => $request->all([
                'search', 'category', 'brand', 'status', 'stock_status', 'tag',
                'company_id', 'ownership', 'sort_field', 'sort_direction', 'per_page', 'page'
            ]),
        ]);
    }

    // =========================================================================
    // =========== CREATE / STORE =============================================
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
            'categories' => Category::whereIn('created_by', $visibleCompanyIds)->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::whereIn('created_by', $visibleCompanyIds)->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'taxes' => Tax::where('created_by', $currentCompanyId)->where('status', 'active')->get(['id', 'name', 'rate']),
            'tags' => Tag::visibleTo($currentCompanyId)->orderBy('name')->get(['id', 'name', 'color']),
            'primaryIndications' => PrimaryIndication::visibleTo($currentCompanyId)->orderBy('name')->get(['id', 'name']),
            'availableProducts' => Product::where('status', 'active')
                ->where(function ($q) use ($currentCompanyId, $superAdminId) {
                    $q->where('created_by', $currentCompanyId)->orWhere('created_by', $superAdminId);
                })->select('id', 'name')->orderBy('name')->get(),
            'users' => auth()->user()->type === 'company'
                ? \App\Models\User::where('created_by', $currentCompanyId)->select('id', 'name', 'email')->get() : [],
        ]);
    }

    public function store(Request $request)
    {
        if (hasReachedProductLimit()) {
            return redirect()->back()->with('error', __('Product limit reached.'));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'specification' => 'nullable|string',
            'detail' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'status' => 'nullable|in:active,inactive',
            'stock_status' => 'nullable|in:in_stock,out_of_stock,on_backorder',
            'stock_quantity' => 'nullable|integer|min:0',
            'product_weight' => 'nullable|numeric|min:0',
            'main_image_id' => 'nullable|exists:media,id',
            'additional_image_ids' => 'nullable|array',
            'additional_image_ids.*' => 'exists:media,id',
            'product_sku' => 'nullable|string|max:255|unique:products,sku',
            'product_form' => 'required|in:Liquid,Caps',
            'bottle_size' => 'required|numeric|min:0',
            'bottle_size_unit' => 'nullable|string|max:50',
            'product_image_url' => 'nullable|url|max:500',
            'full_name' => 'nullable|string|max:500',
            'supports' => 'nullable|string|max:1000',
            'useful_for' => 'nullable|string|max:1000',
            'ingredients' => 'nullable|string|max:1000',
            'contraindications' => 'nullable|string|max:2000',
            'research_links' => 'nullable|string|max:2000',
            'primary_indications' => 'nullable|array',
            'primary_indications.*' => 'integer|exists:primary_indications,id',
            'dosing_upon_rising' => 'nullable|string|max:255',
            'dosing_breakfast' => 'nullable|string|max:255',
            'dosing_between_meals_am' => 'nullable|string|max:255',
            'dosing_lunch' => 'nullable|string|max:255',
            'dosing_between_meals_pm' => 'nullable|string|max:255',
            'dosing_dinner' => 'nullable|string|max:255',
            'dosing_before_sleep' => 'nullable|string|max:255',
            'dosing_na' => 'nullable|boolean',
            'frequency' => 'nullable|string|max:255',
            'tag_id' => 'required|array|min:1',
            'tag_id.*' => 'exists:tags,id',
            'pairs_well_with' => 'nullable|array',
            'pairs_well_with.*' => 'exists:products,id',
            'practitioner_notes' => 'nullable|string|max:5000',
            'custom_primary_indications' => 'nullable|array',
            'custom_dosing_notes' => 'nullable|string|max:5000',
        ]);

        try {
            DB::beginTransaction();
            $currentCompanyId = createdBy();

            $productSku = $validated['product_sku'] ?? null;
            if (empty($productSku)) {
                $productSku = $this->generateUniqueSku();
            }
            $validated['product_sku'] = $productSku;

            $productData = $this->buildProductData($validated, $currentCompanyId, true);
            $productData['sku'] = $productSku;

            $product = new Product();
            $product->fill($productData);
            $product->save();

            if (!empty($validated['additional_image_ids'])) {
                $product->additional_image_ids = $validated['additional_image_ids'];
                $product->save();
            }

            $validTagIds = $this->filterValidTagIds($validated['tag_id'], $currentCompanyId);
            $this->saveTagsToPivot($product->id, $validTagIds, $currentCompanyId);

            $healthData = $this->buildHealthProductData($validated, $currentCompanyId, $productSku);
            HealthProduct::updateOrCreate(
                ['product_id' => $product->id, 'created_by' => $currentCompanyId],
                $healthData
            );

            $product->syncPrimaryIndications($validated['primary_indications'] ?? []);

            if (!empty($validated['pairs_well_with'])) {
                $this->syncPairsWellWith($product->id, $validated['pairs_well_with'], $currentCompanyId);
            }

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
    // =========== SHOW / EDIT / UPDATE =======================================
    // =========================================================================

    public function show($id)
    {
        $product = Product::with(['category', 'brand', 'tax', 'assignedUser', 'creator', 'media', 'tags', 'healthProduct', 'primaryIndications'])
            ->whereIn('created_by', getVisibleCompanyIds())->findOrFail($id);

        $currentCompanyId = createdBy();
        $isSuperAdminProduct = isSuperAdminProduct($product) && (int) $product->created_by !== (int) $currentCompanyId;

        if (!auth()->user()->isSuperAdmin() && $isSuperAdminProduct) {
            $override = ProductCompanyOverride::where('product_id', $product->id)->where('company_id', $currentCompanyId)->first();
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

    public function edit($id)
    {
        $currentCompanyId = createdBy();
        $superAdminId = getSuperAdminCompanyId();

        $product = Product::with(['category', 'brand', 'tax', 'assignedUser', 'creator', 'media', 'tags', 'healthProduct', 'primaryIndications'])
            ->whereIn('created_by', getVisibleCompanyIds())->findOrFail($id);

        $isSuperAdminProduct = isSuperAdminProduct($product);
        $override = ProductCompanyOverride::where('product_id', $product->id)->where('company_id', $currentCompanyId)->first();

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
                })->where('id', '!=', $product->id)->select('id', 'name')->orderBy('name')->get(),
            'users' => auth()->user()->type === 'company'
                ? \App\Models\User::where('created_by', $currentCompanyId)->select('id', 'name', 'email')->get() : [],
            'mainImage' => $product->main_image_url,
            'additionalImages' => $product->additional_image_urls,
            'healthProduct' => $product->healthProduct,
            'override' => $override,
            'isSuperAdminProduct' => $isSuperAdminProduct,
            'canEditOriginal' => canEditProduct($product),
        ]);
    }

    public function update(Request $request, $productId)
    {
        $product = Product::whereIn('created_by', getVisibleCompanyIds())->find($productId);
        if (!$product) {
            return redirect()->back()->with('error', __('Product not found.'));
        }

        $currentCompanyId = createdBy();
        $isSuperAdminProduct = isSuperAdminProduct($product) && (int) $product->created_by !== (int) $currentCompanyId;

        if (!auth()->user()->isSuperAdmin() && $isSuperAdminProduct) {
            return $this->updateOverride($request, $product);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'specification' => 'nullable|string',
            'detail' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'status' => 'nullable|in:active,inactive',
            'stock_status' => 'nullable|in:in_stock,out_of_stock,on_backorder',
            'stock_quantity' => 'nullable|integer|min:0',
            'product_weight' => 'nullable|numeric|min:0',
            'main_image_id' => 'nullable|exists:media,id',
            'additional_image_ids' => 'nullable|array',
            'additional_image_ids.*' => 'exists:media,id',
            'product_sku' => 'required|string|max:255|unique:products,sku,' . $productId,
            'product_form' => 'required|in:Liquid,Caps',
            'bottle_size' => 'required|numeric|min:0',
            'bottle_size_unit' => 'nullable|string|max:50',
            'product_image_url' => 'nullable|url|max:500',
            'full_name' => 'nullable|string|max:500',
            'supports' => 'nullable|string|max:1000',
            'useful_for' => 'nullable|string|max:1000',
            'ingredients' => 'nullable|string|max:1000',
            'contraindications' => 'nullable|string|max:2000',
            'research_links' => 'nullable|string|max:2000',
            'primary_indications' => 'nullable|array',
            'primary_indications.*' => 'integer|exists:primary_indications,id',
            'dosing_upon_rising' => 'nullable|string|max:255',
            'dosing_breakfast' => 'nullable|string|max:255',
            'dosing_between_meals_am' => 'nullable|string|max:255',
            'dosing_lunch' => 'nullable|string|max:255',
            'dosing_between_meals_pm' => 'nullable|string|max:255',
            'dosing_dinner' => 'nullable|string|max:255',
            'dosing_before_sleep' => 'nullable|string|max:255',
            'dosing_na' => 'nullable|boolean',
            'frequency' => 'nullable|string|max:255',
            'tag_id' => 'required|array|min:1',
            'tag_id.*' => 'exists:tags,id',
            'pairs_well_with' => 'nullable|array',
            'pairs_well_with.*' => 'exists:products,id',
            'practitioner_notes' => 'nullable|string|max:5000',
            'custom_primary_indications' => 'nullable|array',
            'custom_dosing_notes' => 'nullable|string|max:5000',
        ]);

        try {
            DB::beginTransaction();

            $productData = $this->buildProductData($validated, $currentCompanyId, false);
            unset($productData['sku']);
            unset($productData['slug']); // ⚡ FIX: لا تدع fill يضبط الـ slug مباشرة
            $product->fill($productData);

            // ⚡ FIX: اضبط الـ slug مع فحص الفرادة لو الاسم تغيّر
            if ($product->isDirty('name')) {
                $newSlug = Str::slug($product->name);
                if ($newSlug) {
                    $baseSlug = $newSlug;
                    $counter = 1;
                    while (Product::where('slug', $newSlug)->where('id', '!=', $product->id)->exists()) {
                        $newSlug = $baseSlug . '-' . $counter++;
                    }
                    $product->slug = $newSlug;
                }
            }

            $product->saveQuietly();

            $rawTagIds = $validated['tag_id'] ?? [];
            $validTagIds = $this->filterValidTagIds($rawTagIds, $currentCompanyId);
            $this->saveTagsToPivot($product->id, $validTagIds, $currentCompanyId);

            $healthData = $this->buildHealthProductData($validated, $currentCompanyId, $validated['product_sku']);
            HealthProduct::updateOrCreate(
                ['product_id' => $product->id, 'created_by' => $currentCompanyId],
                $healthData
            );

            $product->syncPrimaryIndications($validated['primary_indications'] ?? []);

            $pairIds = $validated['pairs_well_with'] ?? [];
            $this->syncPairsWellWith($product->id, $pairIds, $currentCompanyId);

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

    private function updateOverride(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'sale_price' => 'nullable|numeric|min:0',
            'primary_indications' => 'nullable|array',
            'primary_indications.*' => 'integer|exists:primary_indications,id',
            'contraindications' => 'nullable|string|max:2000',
            'research_links' => 'nullable|string|max:2000',
            'dosing_upon_rising' => 'nullable|string|max:255',
            'dosing_breakfast' => 'nullable|string|max:255',
            'dosing_between_meals_am' => 'nullable|string|max:255',
            'dosing_lunch' => 'nullable|string|max:255',
            'dosing_between_meals_pm' => 'nullable|string|max:255',
            'dosing_dinner' => 'nullable|string|max:255',
            'dosing_before_sleep' => 'nullable|string|max:255',
            'dosing_na' => 'nullable|boolean',
            'frequency' => 'nullable|string|max:255',
            'tag_id' => 'nullable|array',
            'tag_id.*' => 'exists:tags,id',
            'practitioner_notes' => 'nullable|string|max:5000',
            'custom_primary_indications' => 'nullable|array',
            'custom_dosing_notes' => 'nullable|string|max:5000',
        ]);

        try {
            DB::beginTransaction();
            $currentCompanyId = createdBy();
            $dosingNa = $request->boolean('dosing_na');

            $indicationIds = $validated['primary_indications'] ?? [];
            $indicationNames = [];
            if (!empty($indicationIds)) {
                $indicationNames = PrimaryIndication::whereIn('id', $indicationIds)->pluck('name')->toArray();
            }

            ProductCompanyOverride::updateOrCreate(
                ['product_id' => $product->id, 'company_id' => $currentCompanyId],
                [
                    'description' => $validated['description'] ?? null,
                    'primary_indications' => $indicationNames,
                    'contraindications' => $validated['contraindications'] ?? null,
                    'research_links' => $validated['research_links'] ?? null,
                    'category_id' => $validated['category_id'],
                    'sale_price_override' => $validated['sale_price'] ?? null,
                    'frequency_override' => $validated['frequency'] ?? null,
                    'dosing_upon_rising' => $dosingNa ? null : ($validated['dosing_upon_rising'] ?? null),
                    'dosing_breakfast' => $dosingNa ? null : ($validated['dosing_breakfast'] ?? null),
                    'dosing_between_meals_am' => $dosingNa ? null : ($validated['dosing_between_meals_am'] ?? null),
                    'dosing_lunch' => $dosingNa ? null : ($validated['dosing_lunch'] ?? null),
                    'dosing_between_meals_pm' => $dosingNa ? null : ($validated['dosing_between_meals_pm'] ?? null),
                    'dosing_dinner' => $dosingNa ? null : ($validated['dosing_dinner'] ?? null),
                    'dosing_before_sleep' => $dosingNa ? null : ($validated['dosing_before_sleep'] ?? null),
                    'dosing_na' => $dosingNa,
                    'practitioner_notes' => $validated['practitioner_notes'] ?? null,
                    'custom_primary_indications' => $validated['custom_primary_indications'] ?? [],
                    'custom_dosing_notes' => $validated['custom_dosing_notes'] ?? null,
                    'is_visible' => true,
                ]
            );

            $rawTagIds = $validated['tag_id'] ?? [];
            $validTagIds = $this->filterValidTagIds($rawTagIds, $currentCompanyId);
            $this->saveTagsToPivot($product->id, $validTagIds, $currentCompanyId);

            $superAdminId = getSuperAdminCompanyId();
            $healthProduct = HealthProduct::where('product_id', $product->id)->where('created_by', $superAdminId)->first();
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
                    $overrideProductId, 'updated',
                    ['company_slug' => $companySlug, 'override_company_id' => $currentCompanyId, 'override_mode' => true]
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
    // =========== DESTROY / TOGGLE STATUS ====================================
    // =========================================================================

    public function destroy($productId)
    {
        $product = Product::whereIn('created_by', getVisibleCompanyIds())->find($productId);
        if (!$product) return redirect()->back()->with('error', __('Product not found.'));
        if (!canDeleteProduct($product)) return redirect()->back()->with('error', __('You cannot delete this product.'));

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
                    $toggledProductId, 'updated',
                    ['company_slug' => $companySlug, 'override_mode' => false]
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
    // =========== EXPORT / TEMPLATE / PARSE ==================================
    // =========================================================================

    public function fileExport()
    {
        if (!auth()->user()->can('export-products')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
        $currentCompanyId = createdBy();
        $superAdminId = getSuperAdminCompanyId();
        $companyIdForExport = auth()->user()->isSuperAdmin() ? null : $currentCompanyId;
        $fileName = 'products_export_' . date('Y-m-d_His') . '.xlsx';
        return Excel::download(new \App\Exports\ProductExport($companyIdForExport, $superAdminId), $fileName);
    }

    public function downloadTemplate()
    {
        $filePath = public_path('templates/products-import-template.xlsx');
        if (!file_exists($filePath)) abort(404, __('Import template file not found.'));
        return response()->download($filePath, 'products-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="products-import-template.xlsx"',
        ]);
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
    // ═══════════════════════════════════════════════════════════════════════
    //  IMPORT FROM EXCEL — SUPER ADMIN + COMPANY
    // ═══════════════════════════════════════════════════════════════════════
    //
    //  القواعد:
    //    1) "N/A" أو خلية فارغة = احذف القيمة من الداتابيز
    //    2) قيمة فعلية = حدّث القيمة
    //    3) العمود غير موجود في الملف = لا تعدّل الحقل
    //
    //  الصلاحيات:
    //    - SUPER ADMIN: ينشئ/يعدّل منتجاته فقط. لا يعدّل منتجات الشركات (يظهر خطأ).
    //    - COMPANY: ينشئ منتجاتها، يعدّل منتجاتها بالكامل، يعدّل override فقط
    //               على منتجات السوبر ادمن. لا يعدّل منتجات شركات أخرى (يظهر خطأ).
    // ═══════════════════════════════════════════════════════════════════════

    public function importFromExcel(Request $request)
    {
        if (!auth()->user()->isSuperAdmin() && auth()->user()->type !== 'company') {
            return response()->json(['flag' => 'error', 'msg' => __('Permission denied.')]);
        }

        try {
            $request->validate(['import_file' => 'required|file|mimes:xlsx,xls,csv|max:20480']);

            $file = $request->file('import_file');
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray(null, true, true, true);

            if (count($rows) < 2) {
                return response()->json(['flag' => 'error', 'msg' => __('The file is empty or has no data rows.')]);
            }

            $rawHeaders = array_shift($rows);
            $headerMap = [];
            foreach ($rawHeaders as $col => $header) {
                if ($header !== null && $header !== '') {
                    $headerMap[strtolower(trim((string) $header))] = $col;
                }
            }

            Log::info("Excel Import: Started", [
                'user_type' => auth()->user()->type,
                'headers' => array_keys($headerMap),
                'total_rows' => count($rows),
            ]);

            // Frequency-Only Mode
            $headerKeys = array_keys($headerMap);
            $hasSku = in_array('sku', $headerKeys, true);
            $hasFreq = in_array('frequency', $headerKeys, true)
                    || in_array('dosing_frequency', $headerKeys, true)
                    || in_array('freq', $headerKeys, true);

            if ($hasSku && $hasFreq && count($headerKeys) === 2) {
                return $this->importFrequencyOnly($rows, $headerMap);
            }

            if (auth()->user()->isSuperAdmin()) {
                return $this->importForSuperAdmin($rows, $headerMap);
            } else {
                return $this->importForCompany($rows, $headerMap);
            }
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json(['flag' => 'error', 'msg' => __('Please upload a valid Excel file (.xlsx, .xls, .csv)')]);
        } catch (\Exception $e) {
            Log::error("Excel Import failed: " . $e->getMessage());
            return response()->json(['flag' => 'error', 'msg' => $e->getMessage()]);
        }
    }

    /**
     * ════════════════════════════════════════════════════════════════════
     * استيراد للسوبر ادمن
     *
     * القواعد:
     *   - SKU غير موجود → CREATE منتج للسوبر ادمن
     *   - SKU موجود لمنتج سوبر ادمن → UPDATE (N/A = حذف)
     *   - SKU موجود لمنتج شركة → SKIP + خطأ (لا يعدّل منتجات الشركات)
     * ════════════════════════════════════════════════════════════════════
     */
    private function importForSuperAdmin(array $rows, array $headerMap)
    {
        $superAdminId = getSuperAdminCompanyId();
        $importedCount = 0; $updatedCount = 0; $skippedCount = 0;
        $errorCount = 0; $errorMessages = [];

        foreach ($rows as $rowIndex => $row) {
            $displayRow = $rowIndex + 2;

            try {
                $item = [];
                foreach ($headerMap as $key => $col) {
                    $item[$key] = $row[$col] ?? null;
                }

                $sku = $this->extractSku($item);
                if (empty($sku)) {
                    $skippedCount++;
                    $errorMessages[] = "Row {$displayRow}: Missing SKU — skipped.";
                    continue;
                }

                $existingProduct = Product::where('sku', $sku)->first();

                // ⚡ منتج شركة → لا يعدّله السوبر ادمن
                if ($existingProduct && (int) $existingProduct->created_by !== (int) $superAdminId) {
                    $skippedCount++;
                    $errorMessages[] = "Row {$displayRow}: SKU {$sku} belongs to a company. Super Admin cannot edit company products — skipped.";
                    Log::warning("Excel Import [SuperAdmin]: Skipped company product", ['sku' => $sku, 'owner' => $existingProduct->created_by]);
                    continue;
                }

                $existingHealthProduct = $existingProduct ? HealthProduct::where('sku', $sku)->first() : null;

                if ($existingProduct) {
                    // ===== UPDATE =====
                    $changes = $this->applyImportUpdate($existingProduct, $existingHealthProduct, $item, $superAdminId, $superAdminId);

                    if ($changes === 'skipped') {
                        $skippedCount++;
                    } elseif ($changes === 'updated') {
                        \App\Observers\ProductObserver::dispatchProductEvent(
                            $existingProduct->id, 'updated',
                            ['company_slug' => $this->getCurrentCompanySlug($superAdminId), 'override_mode' => false]
                        );
                        $updatedCount++;
                    }
                } else {
                    // ===== CREATE =====
                    $product = $this->createProductFromImport($item, $superAdminId, $sku);
                    if ($product) {
                        \App\Observers\ProductObserver::dispatchProductEvent(
                            $product->id, 'created',
                            ['company_slug' => $this->getCurrentCompanySlug($superAdminId), 'override_mode' => false]
                        );
                        $importedCount++;
                    } else {
                        $skippedCount++;
                    }
                }
            } catch (\Exception $e) {
                $errorCount++;
                $errorMessages[] = "Row {$displayRow}: " . $e->getMessage();
                Log::error("Excel Import [SuperAdmin]: Row failed", ['row' => $displayRow, 'error' => $e->getMessage()]);
            }
        }

        return $this->buildImportResponse($importedCount, $updatedCount, $skippedCount, $errorCount, $errorMessages);
    }

    /**
     * ════════════════════════════════════════════════════════════════════
     * استيراد للشركة
     *
     * القواعد:
     *   - SKU غير موجود → CREATE منتج للشركة
     *   - SKU موجود لمنتج الشركة نفسها → UPDATE كامل (N/A = حذف)
     *   - SKU موجود لمنتج سوبر ادمن → UPDATE override فقط (الحقول المسموح بها)
     *   - SKU موجود لشركة أخرى → SKIP + خطأ
     * ════════════════════════════════════════════════════════════════════
     */
    private function importForCompany(array $rows, array $headerMap)
    {
        $currentCompanyId = createdBy();
        $superAdminId = getSuperAdminCompanyId();
        $importedCount = 0; $updatedCount = 0; $skippedCount = 0;
        $errorCount = 0; $errorMessages = [];

        Log::info("Excel Import [Company]: Started", ['company_id' => $currentCompanyId, 'total_rows' => count($rows)]);

        foreach ($rows as $rowIndex => $row) {
            $displayRow = $rowIndex + 2;

            try {
                $item = [];
                foreach ($headerMap as $key => $col) {
                    $item[$key] = $row[$col] ?? null;
                }

                $sku = $this->extractSku($item);
                if (empty($sku)) {
                    $skippedCount++;
                    $errorMessages[] = "Row {$displayRow}: Missing SKU — skipped.";
                    continue;
                }

                $existingProduct = Product::where('sku', $sku)->first();
                $existingHealthProduct = $existingProduct ? HealthProduct::where('sku', $sku)->first() : null;

                // ⚡ منتج شركة أخرى → ممنوع
                if ($existingProduct
                    && (int) $existingProduct->created_by !== (int) $currentCompanyId
                    && (int) $existingProduct->created_by !== (int) $superAdminId) {
                    $skippedCount++;
                    $errorMessages[] = "Row {$displayRow}: SKU {$sku} belongs to another company — skipped.";
                    continue;
                }

                // ⚡ منتج سوبر ادمن → override فقط
                if ($existingProduct && (int) $existingProduct->created_by === (int) $superAdminId) {
                    $result = $this->applyCompanyOverrideUpdate($existingProduct, $item, $currentCompanyId, $superAdminId);
                    if ($result === 'updated') {
                        \App\Observers\ProductObserver::dispatchProductEvent(
                            $existingProduct->id, 'updated',
                            ['company_slug' => $this->getCurrentCompanySlug($currentCompanyId), 'override_company_id' => $currentCompanyId, 'override_mode' => true]
                        );
                        $updatedCount++;
                    } else {
                        $skippedCount++;
                    }
                    continue;
                }

                // ⚡ منتج الشركة نفسها → تحديث كامل
                if ($existingProduct && (int) $existingProduct->created_by === (int) $currentCompanyId) {
                    $result = $this->applyImportUpdate($existingProduct, $existingHealthProduct, $item, $currentCompanyId, $currentCompanyId);
                    if ($result === 'updated') {
                        \App\Observers\ProductObserver::dispatchProductEvent(
                            $existingProduct->id, 'updated',
                            ['company_slug' => $this->getCurrentCompanySlug($currentCompanyId), 'override_mode' => false]
                        );
                        $updatedCount++;
                    } else {
                        $skippedCount++;
                    }
                    continue;
                }

                // ⚡ SKU غير موجود → CREATE
                if (hasReachedProductLimit()) {
                    $skippedCount++;
                    $errorMessages[] = "Row {$displayRow}: Product limit reached — skipped.";
                    continue;
                }

                $product = $this->createProductFromImport($item, $currentCompanyId, $sku);
                if ($product) {
                    \App\Observers\ProductObserver::dispatchProductEvent(
                        $product->id, 'created',
                        ['company_slug' => $this->getCurrentCompanySlug($currentCompanyId), 'override_mode' => false]
                    );
                    $importedCount++;
                    Log::info("Excel Import [Company]: Created product", ['sku' => $sku, 'product_id' => $product->id]);
                } else {
                    $skippedCount++;
                }
            } catch (\Exception $e) {
                $errorCount++;
                $errorMessages[] = "Row {$displayRow}: " . $e->getMessage();
                Log::error("Excel Import [Company]: Row failed", ['row' => $displayRow, 'error' => $e->getMessage()]);
            }
        }

        Log::info("Excel Import [Company]: Completed", [
            'imported' => $importedCount, 'updated' => $updatedCount,
            'skipped' => $skippedCount, 'errors' => $errorCount,
        ]);

        return $this->buildImportResponse($importedCount, $updatedCount, $skippedCount, $errorCount, $errorMessages);
    }

    /**
     * ════════════════════════════════════════════════════════════════════
     * ⚡ تطبيق التحديث الكامل (لمنتجات المستخدم نفسه)
     *
     * منطق "N/A" = حذف:
     *   - العمود موجود + قيمة = "N/A" أو فارغة → احذف القيمة (set null)
     *   - العمود موجود + قيمة فعلية → حدّث القيمة
     *   - العمود غير موجود → لا تعدّل
     * ════════════════════════════════════════════════════════════════════
     */
    private function applyImportUpdate(Product $product, ?HealthProduct $healthProduct, array $item, int $companyId, int $ownerId): string
    {
        $productChanges = [];
        $healthChanges = [];
        $indicationsChanged = false;
        $tagsChanged = false;

        // ===== Product fields =====
        $productFieldDefs = [
            'name'        => ['aliases' => ['product name', 'name', 'title'], 'type' => 'string'],
            'description' => ['aliases' => ['description', 'desc'], 'type' => 'string'],
            'price'       => ['aliases' => ['regular price', 'price'], 'type' => 'price'],
            'sale_price'  => ['aliases' => ['sale price', 'discount price'], 'type' => 'price'],
            'status'      => ['aliases' => ['is active'], 'type' => 'status'],
            'frequency'   => ['aliases' => ['frequency', 'dosing frequency', 'freq'], 'type' => 'string'],
        ];

        foreach ($productFieldDefs as $field => $def) {
            $resolved = $this->resolveCellValue($item, $def['aliases'], $def['type']);
            if ($resolved['present']) {
                $newVal = $resolved['value'];
                if ($field === 'name' && empty($newVal)) continue; // name can't be empty
                if ($field === 'price' && $newVal === null) continue; // price can't be null
                if ($field === 'status' && $newVal === null) $newVal = 'active';

                if ($this->isFieldDifferent($product->{$field}, $newVal)) {
                    $productChanges[$field] = $newVal;
                }
            }
        }

        // Category (special: name → id)
        $catResolved = $this->resolveCellValue($item, ['category', 'category name', 'cat'], 'category');
        if ($catResolved['present']) {
            $newCatId = $catResolved['value'];
            if ($newCatId !== null) {
                // name → find or create
                $catName = $catResolved['raw'];
                $catName = html_entity_decode($catName, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $cat = Category::where('name', $catName)
                    ->where(function ($q) use ($companyId) {
                        $superId = getSuperAdminCompanyId();
                        $q->where('created_by', $companyId)->orWhere('created_by', $superId);
                    })->first();
                if ($cat) $newCatId = $cat->id;
                else $newCatId = null;
            }
            if ($this->isFieldDifferent($product->category_id, $newCatId)) {
                $productChanges['category_id'] = $newCatId;
            }
        }

        // Brand (special: name → id)
        $brandResolved = $this->resolveCellValue($item, ['supplier', 'brand', 'supplier name', 'brand name'], 'string');
        if ($brandResolved['present']) {
            $newBrandId = null;
            if ($brandResolved['value'] !== null) {
                $brandName = $brandResolved['raw'];
                $brand = Brand::firstOrCreate(['name' => $brandName, 'created_by' => $companyId], ['status' => 'active']);
                $newBrandId = $brand->id;
            }
            if ($this->isFieldDifferent($product->brand_id, $newBrandId)) {
                $productChanges['brand_id'] = $newBrandId;
            }
        }

        // ===== Health product fields =====
        if ($healthProduct) {
            $healthFieldDefs = [
                'product_image_url'  => ['aliases' => ['product image url', 'image url', 'image'], 'type' => 'string'],
                'ingredients'        => ['aliases' => ['ingredients'], 'type' => 'string'],
                'contraindications'  => ['aliases' => ['contraindications'], 'type' => 'string'],
                'research_links'     => ['aliases' => ['research / studies / article links', 'research links', 'research'], 'type' => 'string'],
                'supports'           => ['aliases' => ['supports'], 'type' => 'string'],
                'useful_for'         => ['aliases' => ['useful for'], 'type' => 'string'],
                'full_name'          => ['aliases' => ['full name'], 'type' => 'string'],
                'practitioner_notes' => ['aliases' => ['practitioner notes'], 'type' => 'string'],
                'custom_dosing_notes'=> ['aliases' => ['custom dosing notes'], 'type' => 'string'],
            ];

            foreach ($healthFieldDefs as $field => $def) {
                $resolved = $this->resolveCellValue($item, $def['aliases'], $def['type']);
                if ($resolved['present']) {
                    $newVal = $resolved['value'];
                    // contraindications default to 'N/A' if deleted
                    if ($field === 'contraindications' && $newVal === null) $newVal = 'N/A';
                    if ($this->isFieldDifferent($healthProduct->{$field}, $newVal)) {
                        $healthChanges[$field] = $newVal;
                    }
                }
            }

            // Custom primary indications (array)
            $cpiResolved = $this->resolveCellValue($item, ['custom primary indications'], 'csv_array');
            if ($cpiResolved['present']) {
                $newVal = $cpiResolved['value'] ?? [];
                if ($this->isFieldDifferent($healthProduct->custom_primary_indications, $newVal)) {
                    $healthChanges['custom_primary_indications'] = $newVal;
                }
            }

            // Bottle size + unit (combined field)
            $bsResolved = $this->resolveCellValue($item, ['bottle size / unit count', 'bottle size', 'size'], 'bottle_size');
            if ($bsResolved['present']) {
                $parsed = $bsResolved['value'];
                $newSize = $parsed['bottle_size'];
                $newUnit = $parsed['bottle_size_unit'];
                if ($this->isFieldDifferent($healthProduct->bottle_size, $newSize)) {
                    $healthChanges['bottle_size'] = $newSize;
                }
                if ($this->isFieldDifferent($healthProduct->bottle_size_unit, $newUnit)) {
                    $healthChanges['bottle_size_unit'] = $newUnit;
                }
            }

            // Product form
            $pfResolved = $this->resolveCellValue($item, ['product form', 'form'], 'product_form');
            if ($pfResolved['present']) {
                $newVal = $pfResolved['value'];
                if ($newVal !== null && $this->isFieldDifferent($healthProduct->product_form, $newVal)) {
                    $healthChanges['product_form'] = $newVal;
                }
            }

            // Dosing fields
            $dosingMap = [
                'dosing_upon_rising'      => 'upon rising',
                'dosing_breakfast'        => 'breakfast',
                'dosing_between_meals_am' => 'between meals (am)',
                'dosing_lunch'            => 'lunch',
                'dosing_between_meals_pm' => 'between meals (pm)',
                'dosing_dinner'           => 'dinner',
                'dosing_before_sleep'     => 'before sleep',
            ];

            $anyDosingCol = false;
            $hasDosingData = false;
            foreach ($dosingMap as $dbField => $excelKey) {
                if ($this->columnExists($item, [$excelKey])) {
                    $anyDosingCol = true;
                    $resolved = $this->resolveCellValue($item, [$excelKey], 'string');
                    $val = $resolved['value'];
                    if (!empty($val)) $hasDosingData = true;
                    if ($this->isFieldDifferent($healthProduct->{$dbField}, $val)) {
                        $healthChanges[$dbField] = $val;
                    }
                }
            }

            // dosing_na: لو أي عمود dosing موجود
            if ($anyDosingCol) {
                $newDosingNa = $hasDosingData ? false : true;
                if ($this->isFieldDifferent($healthProduct->dosing_na, $newDosingNa)) {
                    $healthChanges['dosing_na'] = $newDosingNa;
                }
                // لو dosing_na = true، اضبط كل حقول dosing على null
                if ($newDosingNa) {
                    foreach (array_keys($dosingMap) as $dbField) {
                        if (array_key_exists($dbField, $healthChanges)) {
                            $healthChanges[$dbField] = null;
                        }
                    }
                }
            }
        }

        // ===== Primary Indications (pivot) =====
        if ($this->columnExists($item, ['primary indications', 'primary indication', 'indications', 'indication', 'primary_indications', 'primary_indication'])) {
            $indResolved = $this->resolveCellValue($item, ['primary indications', 'primary indication', 'indications', 'indication', 'primary_indications', 'primary_indication'], 'indications');
            $desiredIds = $indResolved['value'] ?? [];
            $currentIds = $product->primaryIndications()->pluck('primary_indications.id')->toArray();
            sort($currentIds);
            $desiredSorted = $desiredIds;
            sort($desiredSorted);
            $indicationsChanged = ($currentIds !== $desiredSorted);
        }

        // ===== Tags (pivot) =====
        if ($this->columnExists($item, ['tags', 'tag'])) {
            $tagResolved = $this->resolveCellValue($item, ['tags', 'tag'], 'tags');
            $desiredTagIds = $tagResolved['value'] ?? [];
            $currentTagIds = DB::table('product_tags')
                ->where('product_id', $product->id)
                ->where('created_by', $companyId)
                ->pluck('tag_id')->toArray();
            sort($currentTagIds);
            $desiredSorted = $desiredTagIds;
            sort($desiredSorted);
            $tagsChanged = ($currentTagIds !== $desiredSorted);
        }

        // ===== SKIP لو مفيش تغيير =====
        if (empty($productChanges) && empty($healthChanges) && !$indicationsChanged && !$tagsChanged) {
            return 'skipped';
        }

        // ===== APPLY =====
        if (!empty($productChanges)) {
            if (isset($productChanges['name'])) {
                $product->name = $productChanges['name'];
                // ⚡ FIX: تأكد من فرادة الـ slug (استثني المنتج الحالي)
                $newSlug = Str::slug($productChanges['name']);
                if ($newSlug && $newSlug !== $product->slug) {
                    $baseSlug = $newSlug;
                    $counter = 1;
                    while (Product::where('slug', $newSlug)->where('id', '!=', $product->id)->exists()) {
                        $newSlug = $baseSlug . '-' . $counter++;
                    }
                    $product->slug = $newSlug;
                }
                unset($productChanges['name']);
            }
            foreach ($productChanges as $field => $val) {
                $product->{$field} = $val;
            }
            $product->saveQuietly();
        }

        if (!empty($healthChanges) && $healthProduct) {
            $healthProduct->fill($healthChanges);
            $healthProduct->save();
        }

        if ($indicationsChanged) {
            $product->syncPrimaryIndications($desiredIds);
        }

        if ($tagsChanged) {
            $this->saveTagsToPivot($product->id, $desiredTagIds, $companyId);
        }

        return 'updated';
    }

    /**
     * ════════════════════════════════════════════════════════════════════
     * ⚡ تطبيق override للشركة على منتج سوبر ادمن
     *
     * الحقول المسموح بتعديلها (override):
     *   description, contraindications, research_links, primary_indications,
     *   category_id, sale_price_override, frequency_override,
     *   dosing_* + dosing_na, practitioner_notes,
     *   custom_primary_indications, custom_dosing_notes, tags
     *
     * الحقول المحمية (لا يمكن للشركة تعديلها):
     *   name, sku, price, brand_id, status, stock_*, product_form,
     *   bottle_size, ingredients, supports, useful_for, image
     * ════════════════════════════════════════════════════════════════════
     */
    private function applyCompanyOverrideUpdate(Product $product, array $item, int $currentCompanyId, int $superAdminId): string
    {
        $overrideChanges = [];
        $tagsChanged = false;
        $desiredTagIds = [];

        // ===== الحقول المسموح بها في override =====
        $overrideFieldDefs = [
            'description'         => ['aliases' => ['description', 'desc'], 'type' => 'string'],
            'contraindications'   => ['aliases' => ['contraindications'], 'type' => 'string'],
            'research_links'      => ['aliases' => ['research / studies / article links', 'research links', 'research'], 'type' => 'string'],
            'practitioner_notes'  => ['aliases' => ['practitioner notes'], 'type' => 'string'],
            'custom_dosing_notes' => ['aliases' => ['custom dosing notes'], 'type' => 'string'],
        ];

        foreach ($overrideFieldDefs as $field => $def) {
            $resolved = $this->resolveCellValue($item, $def['aliases'], $def['type']);
            if ($resolved['present']) {
                $newVal = $resolved['value'];
                $currentVal = $this->getOverrideField($product, $currentCompanyId, $field);
                if ($this->isFieldDifferent($currentVal, $newVal)) {
                    $overrideChanges[$field] = $newVal;
                }
            }
        }

        // sale_price_override
        $spResolved = $this->resolveCellValue($item, ['sale price', 'discount price'], 'price');
        if ($spResolved['present']) {
            $newVal = $spResolved['value'];
            $currentVal = $this->getOverrideField($product, $currentCompanyId, 'sale_price_override');
            if ($this->isFieldDifferent($currentVal, $newVal)) {
                $overrideChanges['sale_price_override'] = $newVal;
            }
        }

        // frequency_override
        $freqResolved = $this->resolveCellValue($item, ['frequency', 'dosing frequency', 'freq'], 'string');
        if ($freqResolved['present']) {
            $newVal = $freqResolved['value'];
            $currentVal = $this->getOverrideField($product, $currentCompanyId, 'frequency_override');
            if ($this->isFieldDifferent($currentVal, $newVal)) {
                $overrideChanges['frequency_override'] = $newVal;
            }
        }

        // category_id (override)
        $catResolved = $this->resolveCellValue($item, ['category', 'category name', 'cat'], 'category');
        if ($catResolved['present']) {
            $newCatId = null;
            if ($catResolved['value'] !== null) {
                $catName = html_entity_decode($catResolved['raw'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $cat = Category::where('name', $catName)
                    ->where(function ($q) use ($currentCompanyId, $superAdminId) {
                        $q->where('created_by', $currentCompanyId)->orWhere('created_by', $superAdminId);
                    })->first();
                if ($cat) $newCatId = $cat->id;
            }
            $currentVal = $this->getOverrideField($product, $currentCompanyId, 'category_id');
            if ($this->isFieldDifferent($currentVal, $newCatId)) {
                $overrideChanges['category_id'] = $newCatId;
            }
        }

        // custom_primary_indications (array)
        $cpiResolved = $this->resolveCellValue($item, ['custom primary indications'], 'csv_array');
        if ($cpiResolved['present']) {
            $newVal = $cpiResolved['value'] ?? [];
            $currentVal = $this->getOverrideField($product, $currentCompanyId, 'custom_primary_indications');
            if ($this->isFieldDifferent($currentVal, $newVal)) {
                $overrideChanges['custom_primary_indications'] = $newVal;
            }
        }

        // primary_indications (as names array)
        if ($this->columnExists($item, ['primary indications', 'primary indication', 'indications', 'indication', 'primary_indications', 'primary_indication'])) {
            $indResolved = $this->resolveCellValue($item, ['primary indications', 'primary indication', 'indications', 'indication', 'primary_indications', 'primary_indication'], 'indications_names');
            $desiredNames = $indResolved['value'] ?? [];
            $currentVal = $this->getOverrideField($product, $currentCompanyId, 'primary_indications');
            if ($this->isFieldDifferent($currentVal, $desiredNames)) {
                $overrideChanges['primary_indications'] = $desiredNames;
            }
        }

        // Dosing fields (override)
        $dosingMap = [
            'dosing_upon_rising'      => 'upon rising',
            'dosing_breakfast'        => 'breakfast',
            'dosing_between_meals_am' => 'between meals (am)',
            'dosing_lunch'            => 'lunch',
            'dosing_between_meals_pm' => 'between meals (pm)',
            'dosing_dinner'           => 'dinner',
            'dosing_before_sleep'     => 'before sleep',
        ];

        $anyDosingCol = false;
        $hasDosingData = false;
        foreach ($dosingMap as $dbField => $excelKey) {
            if ($this->columnExists($item, [$excelKey])) {
                $anyDosingCol = true;
                $resolved = $this->resolveCellValue($item, [$excelKey], 'string');
                $val = $resolved['value'];
                if (!empty($val)) $hasDosingData = true;
                $currentVal = $this->getOverrideField($product, $currentCompanyId, $dbField);
                if ($this->isFieldDifferent($currentVal, $val)) {
                    $overrideChanges[$dbField] = $val;
                }
            }
        }

        if ($anyDosingCol) {
            $newDosingNa = $hasDosingData ? false : true;
            $currentVal = $this->getOverrideField($product, $currentCompanyId, 'dosing_na');
            if ($this->isFieldDifferent($currentVal, $newDosingNa)) {
                $overrideChanges['dosing_na'] = $newDosingNa;
            }
            if ($newDosingNa) {
                foreach (array_keys($dosingMap) as $dbField) {
                    if (array_key_exists($dbField, $overrideChanges)) {
                        $overrideChanges[$dbField] = null;
                    }
                }
            }
        }

        // Tags (override)
        if ($this->columnExists($item, ['tags', 'tag'])) {
            $tagResolved = $this->resolveCellValue($item, ['tags', 'tag'], 'tags');
            $desiredTagIds = $tagResolved['value'] ?? [];
            $currentTagIds = DB::table('product_tags')
                ->where('product_id', $product->id)
                ->where('created_by', $currentCompanyId)
                ->pluck('tag_id')->toArray();
            sort($currentTagIds);
            $desiredSorted = $desiredTagIds;
            sort($desiredSorted);
            $tagsChanged = ($currentTagIds !== $desiredSorted);
        }

        // SKIP لو مفيش تغيير
        if (empty($overrideChanges) && !$tagsChanged) {
            return 'skipped';
        }

        // APPLY override
        if (!empty($overrideChanges)) {
            ProductCompanyOverride::updateOrCreate(
                ['product_id' => $product->id, 'company_id' => $currentCompanyId],
                array_merge(['is_visible' => true], $overrideChanges)
            );
        }

        if ($tagsChanged) {
            $this->saveTagsToPivot($product->id, $desiredTagIds, $currentCompanyId);
        }

        Log::info("Excel Import [Company]: Updated super admin product override", [
            'sku' => $product->sku, 'product_id' => $product->id,
            'override_fields' => array_keys($overrideChanges), 'tags_changed' => $tagsChanged,
        ]);

        return 'updated';
    }

    /**
     * ⚡ Helper: قراءة حقل من override
     */
    private function getOverrideField(Product $product, int $companyId, string $field)
    {
        $override = ProductCompanyOverride::where('product_id', $product->id)
            ->where('company_id', $companyId)->first();
        return $override?->{$field};
    }

    /**
     * ════════════════════════════════════════════════════════════════════
     * ⚡ Helper: استخراج قيمة الخلية مع تمييز "N/A" = حذف
     *
     * Returns:
     *   ['present' => bool, 'value' => mixed, 'raw' => string|null]
     *
     *   - present = false: العمود غير موجود في الملف → لا تعدّل
     *   - present = true + value = null: الخلية فارغة أو "N/A" → احذف القيمة
     *   - present = true + value = (val): الخلية فيها قيمة → حدّث
     * ════════════════════════════════════════════════════════════════════
     */
    private function resolveCellValue(array $item, array $aliases, string $type): array
    {
        // 1) هل العمود موجود في الملف؟
        $present = false;
        $rawVal = null;
        foreach ($aliases as $alias) {
            if (array_key_exists($alias, $item)) {
                $present = true;
                $rawVal = $item[$alias];
                break;
            }
        }

        if (!$present) {
            return ['present' => false, 'value' => null, 'raw' => null];
        }

        // 2) تنظيف القيمة
        $cleaned = $this->cleanImportValue($rawVal);

        // 3) ⚡ فحص "N/A" أو فارغ = حذف
        if ($cleaned === null) {
            return ['present' => true, 'value' => null, 'raw' => null];
        }
        $lower = strtolower(trim($cleaned));
        if ($lower === 'n/a' || $lower === 'none' || $lower === 'null' || $lower === '-') {
            return ['present' => true, 'value' => null, 'raw' => $cleaned];
        }

        // 4) معالجة حسب النوع
        $value = $this->processValueByType($cleaned, $type);

        return ['present' => true, 'value' => $value, 'raw' => $cleaned];
    }

    /**
     * ⚡ Helper: معالجة القيمة حسب النوع
     */
    private function processValueByType(string $val, string $type)
    {
        switch ($type) {
            case 'price':
                return floatval(preg_replace('/[^0-9.]/', '', $val));

            case 'status':
                $lower = strtolower(trim($val));
                if (in_array($lower, ['true', '1', 'yes', 'active'])) return 'active';
                if (in_array($lower, ['false', '0', 'no', 'inactive'])) return 'inactive';
                return 'active';

            case 'product_form':
                $lower = strtolower(trim($val));
                if (in_array($lower, ['liquid', 'tincture', 'drop', 'drops', 'oil', 'spray', 'liquid extract'])) return 'Liquid';
                if (in_array($lower, ['caps', 'capsule', 'capsules', 'caplet', 'caplets', 'tablet', 'tablets', 'softgel', 'softgels'])) return 'Caps';
                if (in_array($lower, ['powder', 'powders'])) return 'Powder';
                if (in_array($lower, ['gummy', 'gummies', 'chewable'])) return 'Gummy';
                return $val;

            case 'bottle_size':
                return $this->parseBottleSizeAndUnit($val);

            case 'category':
                return $val; // name string — caller resolves to ID

            case 'csv_array':
                return array_values(array_filter(array_map('trim', explode(',', $val))));

            case 'indications':
                // Returns array of IDs
                $names = array_filter(array_map('trim', preg_split('/[,|]/', $val)));
                $ids = [];
                foreach ($names as $name) {
                    if (empty($name)) continue;
                    $ind = $this->findOrCreateIndicationRobust($name);
                    if ($ind) $ids[] = $ind->id;
                }
                return $ids;

            case 'indications_names':
                // Returns array of names (for override)
                return array_values(array_filter(array_map('trim', preg_split('/[,|]/', $val))));

            case 'tags':
                $names = array_filter(array_map('trim', preg_split('/[,|]/', $val)));
                $ids = [];
                foreach ($names as $name) {
                    if (empty($name)) continue;
                    $companyId = createdBy();
                    $tag = $this->findOrCreateTagByName($name, $companyId);
                    if ($tag) $ids[] = $tag->id;
                }
                return $ids;

            case 'string':
            default:
                return $val;
        }
    }

    /**
     * ⚡ Helper: فحص وجود عمود في الملف
     */
    private function columnExists(array $item, array $aliases): bool
    {
        foreach ($aliases as $alias) {
            if (array_key_exists($alias, $item)) return true;
        }
        return false;
    }

    /**
     * ⚡ Helper: استخراج SKU
     */
    private function extractSku(array $item): ?string
    {
        $skuRaw = $item['sku'] ?? null;
        $sku = $this->cleanImportValue($skuRaw);
        if ($sku !== null && is_numeric($sku)) {
            $sku = (string) intval(floatval($sku));
        }
        return $sku;
    }

    /**
     * ⚡ Helper: إنشاء منتج جديد من بيانات الاستيراد
     */
    private function createProductFromImport(array $item, int $companyId, string $sku): ?Product
    {
        $productName = $this->resolveCellValue($item, ['product name', 'name', 'title'], 'string');
        if (!$productName['present'] || empty($productName['value'])) {
            return null;
        }

        $productSlug = Str::slug($productName['value']);
        $origSlug = $productSlug;
        $pCounter = 1;
        while (Product::where('slug', $productSlug)->exists()) {
            $productSlug = $origSlug . '-' . $pCounter++;
        }

        // Category
        $categoryId = null;
        $catResolved = $this->resolveCellValue($item, ['category', 'category name', 'cat'], 'category');
        if ($catResolved['present'] && $catResolved['value'] !== null) {
            $catName = html_entity_decode($catResolved['raw'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $superAdminId = getSuperAdminCompanyId();
            $cat = Category::where('name', $catName)
                ->where(function ($q) use ($companyId, $superAdminId) {
                    $q->where('created_by', $companyId)->orWhere('created_by', $superAdminId);
                })->first();
            if (!$cat) {
                $catSlug = Str::slug($catName);
                $origCatSlug = $catSlug;
                $c = 1;
                while (Category::where('slug', $catSlug)->exists()) $catSlug = $origCatSlug . '-' . $c++;
                $cat = Category::create([
                    'name' => $catName, 'slug' => $catSlug,
                    'created_by' => $companyId, 'company_id' => $companyId, 'status' => 'active',
                ]);
            }
            $categoryId = $cat->id;
        }

        // Brand
        $brandId = null;
        $brandResolved = $this->resolveCellValue($item, ['supplier', 'brand', 'supplier name', 'brand name'], 'string');
        if ($brandResolved['present'] && $brandResolved['value'] !== null) {
            $brand = Brand::firstOrCreate(['name' => $brandResolved['raw'], 'created_by' => $companyId], ['status' => 'active']);
            $brandId = $brand->id;
        }

        // Prices
        $priceResolved = $this->resolveCellValue($item, ['regular price', 'price'], 'price');
        $price = $priceResolved['present'] ? ($priceResolved['value'] ?? 0) : 0;

        $saleResolved = $this->resolveCellValue($item, ['sale price', 'discount price'], 'price');
        $salePrice = ($saleResolved['present'] && $saleResolved['value'] !== null) ? $saleResolved['value'] : 0;

        // Description
        $descResolved = $this->resolveCellValue($item, ['description', 'desc'], 'string');
        $description = $descResolved['present'] ? $descResolved['value'] : null;

        // Status
        $statusResolved = $this->resolveCellValue($item, ['is active'], 'status');
        $status = $statusResolved['present'] ? ($statusResolved['value'] ?? 'active') : 'active';

        // Frequency
        $freqResolved = $this->resolveCellValue($item, ['frequency', 'dosing frequency', 'freq'], 'string');
        $frequency = $freqResolved['present'] ? $freqResolved['value'] : null;

        // Image URL
        $imgResolved = $this->resolveCellValue($item, ['product image url', 'image url', 'image'], 'string');
        $imageUrl = $imgResolved['present'] ? $imgResolved['value'] : null;

        // Bottle size + unit
        $bsResolved = $this->resolveCellValue($item, ['bottle size / unit count', 'bottle size', 'size'], 'bottle_size');
        $bottleSize = $bsResolved['present'] ? ($bsResolved['value']['bottle_size'] ?? null) : null;
        $bottleSizeUnit = $bsResolved['present'] ? ($bsResolved['value']['bottle_size_unit'] ?? null) : null;

        // Product form
        $pfResolved = $this->resolveCellValue($item, ['product form', 'form'], 'product_form');
        $productForm = $pfResolved['present'] ? $pfResolved['value'] : null;
        if (!$bottleSizeUnit && $productForm) {
            $bottleSizeUnit = ($productForm === 'Liquid') ? 'oz' : 'caps';
        }

        // Full name
        $fnResolved = $this->resolveCellValue($item, ['full name'], 'string');
        $fullName = $fnResolved['present'] ? $fnResolved['value'] : null;

        // Health fields
        $ingredients = $this->resolveCellValue($item, ['ingredients'], 'string');
        $ingredients = $ingredients['present'] ? $ingredients['value'] : null;

        $contraindications = $this->resolveCellValue($item, ['contraindications'], 'string');
        $contraindications = $contraindications['present'] ? ($contraindications['value'] ?? 'N/A') : 'N/A';

        $researchLinks = $this->resolveCellValue($item, ['research / studies / article links', 'research links', 'research'], 'string');
        $researchLinks = $researchLinks['present'] ? $researchLinks['value'] : null;

        $supports = $this->resolveCellValue($item, ['supports'], 'string');
        $supports = $supports['present'] ? $supports['value'] : null;

        $usefulFor = $this->resolveCellValue($item, ['useful for'], 'string');
        $usefulFor = $usefulFor['present'] ? $usefulFor['value'] : null;

        $practitionerNotes = $this->resolveCellValue($item, ['practitioner notes'], 'string');
        $practitionerNotes = $practitionerNotes['present'] ? $practitionerNotes['value'] : null;

        $customDosingNotes = $this->resolveCellValue($item, ['custom dosing notes'], 'string');
        $customDosingNotes = $customDosingNotes['present'] ? $customDosingNotes['value'] : null;

        $cpiResolved = $this->resolveCellValue($item, ['custom primary indications'], 'csv_array');
        $customPrimaryIndicationsArray = $cpiResolved['present'] ? ($cpiResolved['value'] ?? []) : [];

        // Dosing
        $dosingMap = [
            'dosing_upon_rising'      => 'upon rising',
            'dosing_breakfast'        => 'breakfast',
            'dosing_between_meals_am' => 'between meals (am)',
            'dosing_lunch'            => 'lunch',
            'dosing_between_meals_pm' => 'between meals (pm)',
            'dosing_dinner'           => 'dinner',
            'dosing_before_sleep'     => 'before sleep',
        ];
        $dosingData = [];
        $hasDosingData = false;
        foreach ($dosingMap as $dbField => $excelKey) {
            $resolved = $this->resolveCellValue($item, [$excelKey], 'string');
            $val = $resolved['present'] ? $resolved['value'] : null;
            $dosingData[$dbField] = $val;
            if (!empty($val)) $hasDosingData = true;
        }
        $dosingNa = $hasDosingData ? false : true;

        // Primary Indications
        $indicationIds = [];
        if ($this->columnExists($item, ['primary indications', 'primary indication', 'indications', 'indication', 'primary_indications', 'primary_indication'])) {
            $indResolved = $this->resolveCellValue($item, ['primary indications', 'primary indication', 'indications', 'indication', 'primary_indications', 'primary_indication'], 'indications');
            $indicationIds = $indResolved['value'] ?? [];
        }

        // Tags
        $desiredTagIds = [];
        if ($this->columnExists($item, ['tags', 'tag'])) {
            $tagResolved = $this->resolveCellValue($item, ['tags', 'tag'], 'tags');
            $desiredTagIds = $tagResolved['value'] ?? [];
        }

        // ===== Create Product =====
        $product = new Product();
        $product->name = $productName['value'];
        $product->slug = $productSlug;
        $product->sku = $sku;
        $product->category_id = $categoryId;
        $product->brand_id = $brandId;
        $product->price = $price;
        $product->sale_price = $salePrice ?: 0;
        $product->stock_status = 'in_stock';
        $product->description = $description;
        $product->status = $status;
        $product->created_by = $companyId;
        $product->tax_status = 'taxable';
        $product->frequency = $frequency;
        $product->saveQuietly();

        // ===== Create Health Product =====
        $healthData = [
            'product_id' => $product->id,
            'created_by' => $companyId,
            'sku' => $sku,
            'product_image_url' => $imageUrl,
            'bottle_size' => $bottleSize,
            'bottle_size_unit' => $bottleSizeUnit,
            'ingredients' => $ingredients,
            'contraindications' => $contraindications,
            'research_links' => $researchLinks,
            'supports' => $supports,
            'useful_for' => $usefulFor,
            'dosing_na' => $dosingNa,
            'practitioner_notes' => $practitionerNotes,
            'custom_primary_indications' => $customPrimaryIndicationsArray,
            'custom_dosing_notes' => $customDosingNotes,
        ];
        if ($fullName) $healthData['full_name'] = $fullName;
        if ($productForm) $healthData['product_form'] = $productForm;
        foreach ($dosingData as $field => $val) {
            $healthData[$field] = $dosingNa ? null : $val;
        }
        HealthProduct::create($healthData);

        // ===== Primary Indications =====
        if (!empty($indicationIds)) {
            $product->syncPrimaryIndications($indicationIds);
        }

        // ===== Tags =====
        if (!empty($desiredTagIds)) {
            $this->saveTagsToPivot($product->id, $desiredTagIds, $companyId);
        }

        // ===== Override (empty) =====
        ProductCompanyOverride::updateOrCreate(
            ['product_id' => $product->id, 'company_id' => $companyId],
            ['is_visible' => true]
        );

        return $product;
    }

    /**
     * ⚡ Helper: بناء response الاستيراد
     */
    private function buildImportResponse(int $imported, int $updated, int $skipped, int $errors, array $errorDetails): \Illuminate\Http\JsonResponse
    {
        $msg = __('Imported: :count, Updated: :updated, Skipped: :skipped', [
            'count' => $imported, 'updated' => $updated, 'skipped' => $skipped,
        ]);
        if ($errors > 0) {
            $msg .= ' ' . __('Errors: :errors', ['errors' => $errors]);
        }

        return response()->json([
            'flag' => ($imported > 0 || $updated > 0) ? 'success' : (($errors > 0) ? 'error' : 'warning'),
            'msg' => $msg,
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'error_details' => $errorDetails,
        ]);
    }

    /**
     * Frequency-Only Mode
     */
    private function importFrequencyOnly(array $rows, array $headerMap)
    {
        $superAdminId = getSuperAdminCompanyId();
        $currentCompanyId = createdBy();
        $updatedCount = 0; $skippedCount = 0; $errorCount = 0; $errorMessages = [];

        $frequencyCol = $headerMap['frequency'] ?? $headerMap['dosing_frequency'] ?? $headerMap['freq'] ?? null;
        $skuCol = $headerMap['sku'] ?? null;

        if (!$skuCol || !$frequencyCol) {
            return response()->json(['flag' => 'error', 'msg' => __('Frequency-only mode requires both "sku" and "frequency" columns.')]);
        }

        foreach ($rows as $rowIndex => $row) {
            $displayRow = $rowIndex + 2;
            try {
                $skuRaw = $row[$skuCol] ?? null;
                $sku = $this->cleanImportValue($skuRaw);
                if ($sku !== null && is_numeric($sku)) $sku = (string) intval(floatval($sku));
                if (empty($sku)) {
                    $skippedCount++;
                    $errorMessages[] = "Row {$displayRow}: Missing SKU — skipped.";
                    continue;
                }

                $frequencyRaw = $this->cleanImportValue($row[$frequencyCol] ?? null);
                // ⚡ N/A = حذف
                if ($frequencyRaw && in_array(strtolower($frequencyRaw), ['n/a', 'none', 'null'])) {
                    $frequencyRaw = null;
                }
                $newFrequency = $frequencyRaw;

                $product = Product::where('sku', $sku)->first();
                if (!$product) {
                    $hp = HealthProduct::where('sku', $sku)->first();
                    if ($hp) $product = Product::find($hp->product_id);
                }
                if (!$product) {
                    $skippedCount++;
                    $errorMessages[] = "Row {$displayRow}: SKU {$sku} not found — skipped.";
                    continue;
                }

                // ⚡ فحص الصلاحية: السوبر ادمن لا يعدّل منتجات الشركات
                if (auth()->user()->isSuperAdmin() && (int) $product->created_by !== (int) $superAdminId) {
                    $skippedCount++;
                    $errorMessages[] = "Row {$displayRow}: SKU {$sku} belongs to a company. Super Admin cannot edit — skipped.";
                    continue;
                }
                // ⚡ فحص الصلاحية: الشركة لا يعدّل منتجات شركات أخرى
                if (!auth()->user()->isSuperAdmin()
                    && (int) $product->created_by !== (int) $currentCompanyId
                    && (int) $product->created_by !== (int) $superAdminId) {
                    $skippedCount++;
                    $errorMessages[] = "Row {$displayRow}: SKU {$sku} belongs to another company — skipped.";
                    continue;
                }

                // ⚡ للشركة على منتج سوبر ادمن: احفظ كـ override
                if (!auth()->user()->isSuperAdmin() && (int) $product->created_by === (int) $superAdminId) {
                    $override = ProductCompanyOverride::where('product_id', $product->id)
                        ->where('company_id', $currentCompanyId)->first();
                    $currentFreq = $override?->frequency_override;
                    if (!$this->isFieldDifferent($currentFreq, $newFrequency)) {
                        $skippedCount++;
                        continue;
                    }
                    ProductCompanyOverride::updateOrCreate(
                        ['product_id' => $product->id, 'company_id' => $currentCompanyId],
                        ['frequency_override' => $newFrequency, 'is_visible' => true]
                    );
                    $updatedCount++;
                    continue;
                }

                // ⚡ للمنتج نفسه: حدّث products.frequency
                $currentFrequency = $product->frequency;
                if (!$this->isFieldDifferent($currentFrequency, $newFrequency)) {
                    $skippedCount++;
                    continue;
                }
                $product->frequency = $newFrequency;
                $product->saveQuietly();

                \App\Observers\ProductObserver::dispatchProductEvent(
                    $product->id, 'updated',
                    ['company_slug' => $this->getCurrentCompanySlug($currentCompanyId), 'override_mode' => false]
                );
                $updatedCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $errorMessages[] = "Row {$displayRow}: " . $e->getMessage();
            }
        }

        $msg = __('Frequency update — Updated: :updated, Skipped: :skipped', ['updated' => $updatedCount, 'skipped' => $skippedCount]);
        if ($errorCount > 0) $msg .= ' ' . __('Errors: :errors', ['errors' => $errorCount]);

        return response()->json([
            'flag' => ($updatedCount > 0) ? 'success' : (($errorCount > 0) ? 'error' : 'warning'),
            'msg' => $msg, 'imported' => 0, 'updated' => $updatedCount,
            'skipped' => $skippedCount, 'errors' => $errorCount,
            'error_details' => $errorMessages, 'mode' => 'frequency_only',
        ]);
    }

    // =========================================================================
    // =========== PRIVATE HELPERS =============================================
    // =========================================================================

    private function cleanImportValue($value): ?string
    {
        if ($value === null) return null;
        $val = trim((string) $value);
        if ($val === '' || strtolower($val) === 'null') return null;
        return $val;
    }

    private function generateUniqueSku(): string
    {
        $prefix = 'SKU-' . date('Ymd') . '-';
        do {
            $random = strtoupper(Str::random(6));
            $sku = $prefix . $random;
        } while (Product::where('sku', $sku)->exists());
        return $sku;
    }

    private function buildProductData(array $data, int $companyId, bool $isNew = true): array
    {
        $productData = [
            'name' => $data['name'],
            'slug' => \Illuminate\Support\Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'specification' => $data['specification'] ?? null,
            'detail' => $data['detail'] ?? null,
            'price' => $data['price'],
            'sale_price' => !empty($data['sale_price']) ? $data['sale_price'] : ($data['price'] ?? 0),
            'stock_quantity' => $data['stock_quantity'] ?? 0,
            'stock_status' => $data['stock_status'] ?? 'in_stock',
            'product_weight' => $data['product_weight'] ?? null,
            'category_id' => $data['category_id'],
            'brand_id' => $data['brand_id'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
            'status' => $data['status'] ?? 'active',
            'frequency' => $data['frequency'] ?? null,
            'tax_status' => 'taxable',
        ];
        if ($isNew) {
            $productData['sku'] = $data['sku'] ?? $data['product_sku'] ?? null;
            $productData['created_by'] = $companyId;
        }
        if (!empty($data['main_image_id'])) $productData['main_image_id'] = $data['main_image_id'];
        if (isset($data['additional_image_ids'])) $productData['additional_image_ids'] = $data['additional_image_ids'];
        return $productData;
    }

    private function buildHealthProductData(array $data, int $companyId, string $sku): array
    {
        $dosingNa = !empty($data['dosing_na']);
        $productForm = $data['product_form'] ?? 'Caps';
        $bottleSizeUnit = !empty($data['bottle_size_unit']) ? $data['bottle_size_unit'] : (($productForm === 'Liquid') ? 'oz' : 'caps');

        return [
            'sku' => $sku,
            'product_form' => $productForm,
            'bottle_size' => $data['bottle_size'] ?? 0,
            'bottle_size_unit' => $bottleSizeUnit,
            'product_image_url' => $data['product_image_url'] ?? null,
            'full_name' => $data['full_name'] ?? null,
            'ingredients' => $data['ingredients'] ?? null,
            'contraindications' => $data['contraindications'] ?? 'N/A',
            'research_links' => $data['research_links'] ?? null,
            'supports' => $data['supports'] ?? null,
            'useful_for' => $data['useful_for'] ?? null,
            'practitioner_notes' => $data['practitioner_notes'] ?? null,
            'custom_primary_indications' => $data['custom_primary_indications'] ?? [],
            'custom_dosing_notes' => $data['custom_dosing_notes'] ?? null,
            'dosing_upon_rising' => $dosingNa ? null : ($data['dosing_upon_rising'] ?? null),
            'dosing_breakfast' => $dosingNa ? null : ($data['dosing_breakfast'] ?? null),
            'dosing_between_meals_am' => $dosingNa ? null : ($data['dosing_between_meals_am'] ?? null),
            'dosing_lunch' => $dosingNa ? null : ($data['dosing_lunch'] ?? null),
            'dosing_between_meals_pm' => $dosingNa ? null : ($data['dosing_between_meals_pm'] ?? null),
            'dosing_dinner' => $dosingNa ? null : ($data['dosing_dinner'] ?? null),
            'dosing_before_sleep' => $dosingNa ? null : ($data['dosing_before_sleep'] ?? null),
            'dosing_na' => $dosingNa,
        ];
    }

    private function saveTagsToPivot(int $productId, array $tagIds, int $companyId): void
    {
        $tagIds = array_values(array_unique(array_filter($tagIds, fn($id) => !is_null($id) && $id !== '' && is_numeric($id))));
        if (empty($tagIds)) {
            DB::table('product_tags')->where('product_id', $productId)->where('created_by', $companyId)->delete();
            return;
        }
        $intTagIds = array_map('intval', $tagIds);
        $validTagIds = Tag::whereIn('id', $intTagIds)->visibleTo($companyId)->pluck('id')->toArray();
        $invalidTagIds = array_diff($intTagIds, $validTagIds);
        if (!empty($invalidTagIds)) {
            Log::warning('ProductController: Filtered out invalid tag IDs', [
                'product_id' => $productId, 'company_id' => $companyId,
                'invalid_tag_ids' => array_values($invalidTagIds),
            ]);
        }
        DB::table('product_tags')->where('product_id', $productId)->where('created_by', $companyId)->delete();
        if (empty($validTagIds)) return;

        $existingTagIds = DB::table('product_tags')->where('product_id', $productId)->pluck('tag_id')->map(fn($id) => (int) $id)->toArray();
        $newTagIds = array_values(array_diff($validTagIds, $existingTagIds));
        if (empty($newTagIds)) return;

        $insertData = [];
        $now = now();
        foreach ($newTagIds as $tagId) {
            $insertData[] = [
                'product_id' => $productId, 'tag_id' => (int) $tagId,
                'created_by' => $companyId, 'created_at' => $now, 'updated_at' => $now,
            ];
        }
        try {
            DB::table('product_tags')->insert($insertData);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                foreach ($insertData as $row) {
                    try { DB::table('product_tags')->insert($row); }
                    catch (\Illuminate\Database\QueryException $e2) { if (!str_contains($e2->getMessage(), 'Duplicate entry')) throw $e2; }
                }
            } else { throw $e; }
        }
    }

    private function filterValidTagIds(array $tagIds, int $companyId): array
    {
        $tagIds = array_values(array_filter($tagIds, fn($id) => !is_null($id) && $id !== '' && is_numeric($id)));
        if (empty($tagIds)) return [];
        $intTagIds = array_map('intval', $tagIds);
        return Tag::whereIn('id', $intTagIds)->visibleTo($companyId)->pluck('id')->toArray();
    }

    private function syncPairsWellWith(int $productId, array $pairedIds, int $companyId): void
    {
        $pairedIds = array_values(array_unique(array_filter($pairedIds, fn($id) => !is_null($id) && $id !== '' && is_numeric($id))));
        DB::table('product_pairs')->where('product_id', $productId)->where('created_by', $companyId)->delete();
        if (empty($pairedIds)) return;
        $insertData = [];
        $now = now();
        foreach ($pairedIds as $pairedId) {
            if ((int) $pairedId === (int) $productId) continue;
            $insertData[] = [
                'product_id' => $productId, 'paired_product_id' => (int) $pairedId,
                'created_by' => $companyId, 'created_at' => $now, 'updated_at' => $now,
            ];
        }
        if (!empty($insertData)) DB::table('product_pairs')->insert($insertData);
    }

    private function getHeaderValue(array $item, array $possibleKeys): ?string
    {
        foreach ($possibleKeys as $key) {
            if (isset($item[$key]) && $item[$key] !== null && $item[$key] !== '') {
                return $this->cleanImportValue($item[$key]);
            }
        }
        return null;
    }

    private function findOrCreateIndicationRobust(string $name): ?PrimaryIndication
    {
        $trimmed = trim($name);
        if ($trimmed === '') return null;
        try {
            $existing = PrimaryIndication::whereRaw('LOWER(name) = ?', [mb_strtolower($trimmed)])->first();
            if ($existing) return $existing;
            $slug = Str::slug($trimmed);
            if (empty($slug)) $slug = 'ind-' . substr(md5($trimmed), 0, 8);
            $baseSlug = $slug;
            $counter = 1;
            while (PrimaryIndication::where('slug', $slug)->exists()) $slug = $baseSlug . '-' . $counter++;
            $data = ['name' => $trimmed, 'slug' => $slug];
            $columns = Schema::getColumnListing('primary_indications');
            if (in_array('company_id', $columns)) $data['company_id'] = getSuperAdminCompanyId();
            if (in_array('created_by', $columns)) $data['created_by'] = getSuperAdminCompanyId();
            if (in_array('status', $columns)) $data['status'] = 'active';
            $indication = new PrimaryIndication();
            $indication->forceFill($data)->save();
            return $indication;
        } catch (\Illuminate\Database\QueryException $qe) {
            $existing = PrimaryIndication::whereRaw('LOWER(name) = ?', [mb_strtolower($trimmed)])->first();
            return $existing;
        } catch (\Exception $e) {
            Log::error("findOrCreateIndication failed: " . $e->getMessage());
            return null;
        }
    }

    private function findOrCreateTagByName(string $name, int $companyId): ?Tag
    {
        $trimmed = trim($name);
        if ($trimmed === '') return null;
        $superAdminId = getSuperAdminCompanyId();
        $existing = Tag::whereRaw('LOWER(name) = ?', [mb_strtolower($trimmed)])
            ->where(function ($q) use ($companyId, $superAdminId) {
                $q->where('company_id', $companyId)->orWhere('company_id', $superAdminId)->orWhereNull('company_id');
            })->first();
        if ($existing) return $existing;
        return Tag::create([
            'name' => $trimmed, 'slug' => Str::slug($trimmed), 'color' => '#6366f1',
            'status' => 'active', 'company_id' => $companyId, 'created_by' => $companyId,
        ]);
    }

    private function parseBottleSizeAndUnit($rawValue): array
    {
        $result = ['bottle_size' => null, 'bottle_size_unit' => null];
        if (empty($rawValue)) return $result;
        $value = trim((string) $rawValue);
        $lower = strtolower($value);
        if ($value === '' || $lower === 'none' || $lower === 'n/a' || $lower === 'null') return $result;

        $pattern = '/^(\d+(?:\.\d+)?)\s*(fl\.?\s*oz|floz|oz|ml|milliliter|milliliters|caps|cap|capsule[s]?|caplet[s]?|tablet[s]?|softgel[s]?|gummies|gummy|g|grams|gram|bags|count|ct|pieces|pcs)\b/i';
        if (preg_match($pattern, $value, $matches)) {
            $result['bottle_size'] = (float) $matches[1];
            $unit = strtolower(trim(preg_replace('/\s+/', ' ', $matches[2])));
            if (in_array($unit, ['fl oz', 'fl. oz', 'fl.oz', 'floz', 'oz'])) $result['bottle_size_unit'] = 'oz';
            elseif (in_array($unit, ['ml', 'milliliter', 'milliliters'])) $result['bottle_size_unit'] = 'ml';
            elseif (in_array($unit, ['cap', 'caps', 'capsule', 'capsules'])) $result['bottle_size_unit'] = 'caps';
            elseif (in_array($unit, ['caplet', 'caplets', 'tablet', 'tablets'])) $result['bottle_size_unit'] = 'tablets';
            elseif (in_array($unit, ['softgel', 'softgels'])) $result['bottle_size_unit'] = 'softgels';
            elseif (in_array($unit, ['gummy', 'gummies'])) $result['bottle_size_unit'] = 'gummies';
            elseif (in_array($unit, ['g', 'gram', 'grams'])) $result['bottle_size_unit'] = 'g';
            elseif (in_array($unit, ['bags'])) $result['bottle_size_unit'] = 'bags';
            elseif (in_array($unit, ['count', 'ct', 'pieces', 'pcs'])) $result['bottle_size_unit'] = 'count';
            else $result['bottle_size_unit'] = $unit;
        } else {
            if (preg_match('/^(\d+(?:\.\d+)?)$/', $value)) $result['bottle_size'] = (float) $value;
            elseif (preg_match('/^[a-zA-Z\s\.]+$/', $value)) {
                $unit = strtolower(trim($value));
                if (in_array($unit, ['caps', 'cap', 'capsule'])) $result['bottle_size_unit'] = 'caps';
                elseif (in_array($unit, ['oz', 'fl oz', 'fl.oz'])) $result['bottle_size_unit'] = 'oz';
                elseif ($unit === 'ml') $result['bottle_size_unit'] = 'ml';
                else $result['bottle_size_unit'] = $unit;
            } else $result['bottle_size'] = $value;
        }
        return $result;
    }

    private function isFieldDifferent($current, $new): bool
    {
        return $this->normalizeForCompare($current) !== $this->normalizeForCompare($new);
    }

    private function normalizeForCompare($value)
    {
        if ($value === null || $value === '' || $value === []) return '';
        if (is_bool($value)) return $value ? '1' : '0';
        if (is_array($value)) {
            $normalized = array_map(fn($v) => is_string($v) ? trim($v) : $v, $value);
            $normalized = array_filter($normalized, fn($v) => $v !== '' && $v !== null);
            sort($normalized);
            return implode('|', $normalized);
        }
        if (is_numeric($value)) {
            $float = floatval($value);
            if ($float == intval($float)) return (string) intval($float);
            return (string) $float;
        }
        $trimmed = trim((string) $value);
        if (is_numeric($trimmed)) {
            $float = floatval($trimmed);
            if ($float == intval($float)) return (string) intval($float);
            return (string) $float;
        }
        return $trimmed;
    }

    // =========================================================================
    // =========== MERCHANT COMPARISON ========================================
    // =========================================================================

    public function merchantCompareResults()
    {
        if (auth()->user()->isSuperAdmin()) {
            return redirect()->route('products.index')->with('error', __('This feature is for company users only.'));
        }
        $currentCompanyId = createdBy();
        $superAdminId = getSuperAdminCompanyId();
        $overrides = ProductCompanyOverride::where('company_id', $currentCompanyId)
            ->whereHas('product', function ($q) use ($superAdminId) { $q->where('created_by', $superAdminId); })
            ->with(['product.category', 'product.healthProduct'])->get();
        $allDifferences = [];
        foreach ($overrides as $override) {
            $product = $override->product;
            if (!$product) continue;
            $baseHealthProduct = $product->healthProduct()->where('created_by', $superAdminId)->first();
            $changes = [];
            $dosingFields = [
                'dosing_upon_rising' => __('Upon Rising'), 'dosing_breakfast' => __('Breakfast'),
                'dosing_between_meals_am' => __('Between Meals (AM)'), 'dosing_lunch' => __('Lunch'),
                'dosing_between_meals_pm' => __('Between Meals (PM)'), 'dosing_dinner' => __('Dinner'),
                'dosing_before_sleep' => __('Before Sleep'),
            ];
            $fieldLabels = [
                'price_override' => __('Price'), 'sale_price_override' => __('Sale Price'),
                'stock_quantity_override' => __('Stock Quantity'), 'stock_status_override' => __('Stock Status'),
                'description' => __('Description'), 'category_id' => __('Category'),
                'contraindications' => __('Contraindications'), 'research_links' => __('Research Links'),
                'primary_indications' => __('Primary Indications'), 'frequency_override' => __('Frequency'),
                'dosing_na' => __('Dosing N/A'),
            ];
            foreach ($fieldLabels as $field => $label) {
                if ($override->{$field} !== null) {
                    $changes[] = ['override_id' => $override->id, 'field_name' => $field, 'label' => $label,
                        'original_value' => $product->{$field} ?? '—', 'merchant_value' => $override->{$field}];
                }
            }
            foreach ($dosingFields as $field => $label) {
                if ($override->{$field} !== null) {
                    $changes[] = ['override_id' => $override->id, 'field_name' => $field, 'label' => $label,
                        'original_value' => $baseHealthProduct?->{$field} ?? '—', 'merchant_value' => $override->{$field}];
                }
            }
            if (!empty($changes)) {
                $allDifferences[] = ['product_id' => $product->id, 'product_name' => $product->name,
                    'sku' => $baseHealthProduct?->sku ?? $product->sku, 'changes' => $changes];
            }
        }
        return Inertia::render('products/comparison', [
            'allDifferences' => $allDifferences, 'totalProducts' => count($allDifferences),
            'totalChanges' => array_sum(array_map(fn($d) => count($d['changes']), $allDifferences)),
        ]);
    }

    public function merchantRevertField(Request $request)
    {
        $request->validate(['id' => 'required|exists:product_company_overrides,id', 'field_name' => 'required|string']);
        $override = ProductCompanyOverride::findOrFail($request->id);
        if ($override->company_id !== createdBy()) return response()->json(['flag' => 'error', 'msg' => __('Unauthorized.')], 403);
        $revertibleFields = ['price_override','sale_price_override','stock_quantity_override','stock_status_override',
            'description','contraindications','research_links','category_id','primary_indications','frequency_override',
            'dosing_upon_rising','dosing_breakfast','dosing_between_meals_am','dosing_lunch','dosing_between_meals_pm',
            'dosing_dinner','dosing_before_sleep','dosing_na','practitioner_notes','custom_primary_indications','custom_dosing_notes'];
        if (!in_array($request->field_name, $revertibleFields)) return response()->json(['flag' => 'error', 'msg' => __('Invalid field name.')]);
        $override->{$request->field_name} = null;
        $override->save();
        return response()->json(['flag' => 'success', 'msg' => __('Field reverted to original value.')]);
    }

    // =========================================================================
    // =========== PROVIDER COMPARISON ========================================
    // =========================================================================

    public function compareWithProvider()
    {
        if (!auth()->user()->isSuperAdmin()) return redirect()->back()->with('error', __('Permission denied.'));
        try {
            $providerApiUrl = config('services.provider_api_url');
            if (empty($providerApiUrl)) return redirect()->back()->with('error', __('Provider API URL is not configured.'));
            $liveUrl = $providerApiUrl . '&t=' . time();
            $response = \Illuminate\Support\Facades\Http::timeout(120)->get($liveUrl);
            if (!$response->successful()) return redirect()->back()->with('error', __('Failed to fetch data from provider.'));
            $apiProducts = $response->json();
            if (empty($apiProducts) || !is_array($apiProducts)) return redirect()->back()->with('error', __('No data found from provider.'));
            ProductComparison::truncate();
            $changesDetected = 0; $processedProducts = 0; $skippedCount = 0;
            foreach ($apiProducts as $apiProduct) {
                $sku = $apiProduct['sku'] ?? null;
                if (empty($sku)) continue;
                $healthProduct = HealthProduct::where('sku', $sku)->first();
                if (!$healthProduct) { $skippedCount++; continue; }
                $product = $healthProduct->product;
                if (!$product) { $skippedCount++; continue; }
                $processedProducts++;
                $productName = $product->name;
                $checks = [
                    ['price', floatval($apiProduct['regular_price'] ?? 0), floatval($product->price ?? 0)],
                    ['product_weight', floatval($apiProduct['weight'] ?? 0), floatval($product->product_weight ?? 0)],
                    ['name', trim($apiProduct['name'] ?? ''), trim($product->name ?? '')],
                    ['stock_status', $apiProduct['stock_status'] ?? '', $product->stock_status ?? ''],
                    ['description', trim($apiProduct['description'] ?? ''), trim($product->description ?? '')],
                ];
                foreach ($checks as [$field, $new, $old]) {
                    if ($field === 'stock_status') {
                        $map = ['instock' => 'in_stock', 'outofstock' => 'out_of_stock', 'onbackorder' => 'on_backorder'];
                        $new = $map[$new] ?? 'in_stock';
                    }
                    if ($new != $old && $new !== '') {
                        ProductComparison::create(['product_id' => $product->id, 'sku' => $sku, 'product_name' => $productName,
                            'field_name' => $field, 'old_value' => $old, 'new_value' => $new, 'status' => 'pending']);
                        $changesDetected++;
                    }
                }
                $apiImageUrl = null;
                if (!empty($apiProduct['images']) && is_array($apiProduct['images'])) {
                    $firstImage = $apiProduct['images'][0] ?? null;
                    if ($firstImage) $apiImageUrl = $firstImage['src'] ?? null;
                }
                $localImageUrl = $healthProduct->product_image_url ?? null;
                if ($apiImageUrl !== $localImageUrl && $apiImageUrl !== null) {
                    ProductComparison::create(['product_id' => $product->id, 'sku' => $sku, 'product_name' => $productName,
                        'field_name' => 'product_image_url', 'old_value' => $localImageUrl, 'new_value' => $apiImageUrl, 'status' => 'pending']);
                    $changesDetected++;
                }
            }
            if ($changesDetected === 0) {
                $msg = __('No changes detected. Checked :count products.', ['count' => $processedProducts]);
                if ($skippedCount > 0) $msg .= ' ' . __('Skipped (Not found locally): :skipped', ['skipped' => $skippedCount]);
                return redirect()->back()->with('warning', $msg);
            }
            return redirect()->route('products.provider-comparison')
                ->with('success', __(':count changes detected in :products products.', ['count' => $changesDetected, 'products' => $processedProducts]));
        } catch (\Exception $e) {
            Log::error("Comparison Error: " . $e->getMessage());
            return redirect()->back()->with('error', __('An error occurred: :error', ['error' => $e->getMessage()]));
        }
    }

    public function providerComparisonResults()
    {
        if (!auth()->user()->isSuperAdmin()) return redirect()->back()->with('error', __('Permission denied.'));
        $comparisons = ProductComparison::where('status', 'pending')->orderBy('product_id')->orderBy('field_name')->get()->groupBy('product_id');
        $totalChanges = ProductComparison::where('status', 'pending')->count();
        return Inertia::render('products/provider-comparison', ['comparisons' => $comparisons, 'totalChanges' => $totalChanges]);
    }

    public function acceptComparison($id)
    {
        if (!auth()->user()->isSuperAdmin()) return response()->json(['flag' => 'error', 'msg' => __('Permission denied.')]);
        try {
            $comparison = ProductComparison::findOrFail($id);
            $product = Product::find($comparison->product_id);
            $healthProduct = HealthProduct::where('product_id', $comparison->product_id)->where('created_by', getSuperAdminCompanyId())->first();
            if (!$product) return response()->json(['flag' => 'error', 'msg' => __('Product not found.')]);
            $this->applyFieldChange($product, $healthProduct, $comparison->field_name, $comparison->new_value);
            $comparison->status = 'accepted'; $comparison->save();
            $this->cleanUpResolvedProduct($comparison->product_id);
            return response()->json(['flag' => 'success', 'msg' => __('Change accepted and applied successfully.')]);
        } catch (\Exception $e) { return response()->json(['flag' => 'error', 'msg' => __('An error occurred.')]); }
    }

    public function rejectComparison($id)
    {
        if (!auth()->user()->isSuperAdmin()) return response()->json(['flag' => 'error', 'msg' => __('Permission denied.')]);
        try {
            $comparison = ProductComparison::findOrFail($id);
            $comparison->status = 'rejected'; $comparison->save();
            $this->cleanUpResolvedProduct($comparison->product_id);
            return response()->json(['flag' => 'success', 'msg' => __('Change rejected.')]);
        } catch (\Exception $e) { return response()->json(['flag' => 'error', 'msg' => __('An error occurred.')]); }
    }

    public function acceptAllProductChanges($productId)
    {
        if (!auth()->user()->isSuperAdmin()) return response()->json(['flag' => 'error', 'msg' => __('Permission denied.')]);
        try {
            $comparisons = ProductComparison::where('product_id', $productId)->where('status', 'pending')->get();
            $product = Product::find($productId);
            $healthProduct = HealthProduct::where('product_id', $productId)->where('created_by', getSuperAdminCompanyId())->first();
            if (!$product) return response()->json(['flag' => 'error', 'msg' => __('Product not found.')]);
            foreach ($comparisons as $comparison) {
                $this->applyFieldChange($product, $healthProduct, $comparison->field_name, $comparison->new_value);
                $comparison->status = 'accepted'; $comparison->save();
            }
            ProductComparison::where('product_id', $productId)->delete();
            return response()->json(['flag' => 'success', 'msg' => __('All changes accepted and applied successfully.')]);
        } catch (\Exception $e) { return response()->json(['flag' => 'error', 'msg' => __('An error occurred.')]); }
    }

    public function rejectAllProductChanges($productId)
    {
        if (!auth()->user()->isSuperAdmin()) return response()->json(['flag' => 'error', 'msg' => __('Permission denied.')]);
        try {
            ProductComparison::where('product_id', $productId)->where('status', 'pending')->update(['status' => 'rejected']);
            ProductComparison::where('product_id', $productId)->delete();
            return response()->json(['flag' => 'success', 'msg' => __('All changes rejected.')]);
        } catch (\Exception $e) { return response()->json(['flag' => 'error', 'msg' => __('An error occurred.')]); }
    }

    private function applyFieldChange($product, $healthProduct, $fieldName, $newValue)
    {
        switch ($fieldName) {
            case 'product_image_url':
                if ($healthProduct) { $healthProduct->product_image_url = $newValue; $healthProduct->save(); }
                break;
            case 'stock_status': $product->stock_status = $newValue; $product->save(); break;
            case 'product_weight': $product->product_weight = floatval($newValue); $product->save(); break;
            case 'status': $product->status = $newValue; $product->save(); break;
            default:
                if (Schema::hasColumn('products', $fieldName)) { $product->{$fieldName} = $newValue; $product->save(); }
                break;
        }
    }

    private function cleanUpResolvedProduct($productId)
    {
        if (ProductComparison::where('product_id', $productId)->where('status', 'pending')->count() === 0) {
            ProductComparison::where('product_id', $productId)->delete();
        }
    }

    private function getCurrentCompanySlug(?int $currentCompanyId = null): ?string
    {
        $user = auth()->user();
        if ($user && isset($user->slug) && !empty($user->slug)) return (string) $user->slug;
        if ($user && method_exists($user, 'company') && $user->company && !empty($user->company->slug)) return (string) $user->company->slug;
        if ($currentCompanyId) {
            try {
                $companyRow = \DB::table('companies')->where('id', $currentCompanyId)->first();
                if ($companyRow && !empty($companyRow->slug)) return (string) $companyRow->slug;
            } catch (\Throwable $e) { Log::warning("getCurrentCompanySlug: lookup failed: " . $e->getMessage()); }
        }
        if ($user) {
            foreach (['username', 'store_slug', 'company_slug'] as $f) {
                if (isset($user->{$f}) && !empty($user->{$f})) return (string) $user->{$f};
            }
        }
        return null;
    }
}
