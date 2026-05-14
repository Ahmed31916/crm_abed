<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Tax;
use App\Models\ProductCompanyOverride;
use App\Exports\ProductExport;
use App\Imports\ProductImport;
use App\Services\StorageConfigService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $currentCompanyId = createdBy();
        $visibleCompanyIds = getVisibleCompanyIds();

        $query = Product::query()
            ->with(['category', 'brand', 'tax', 'assignedUser', 'media'])
            ->whereIn('created_by', $visibleCompanyIds);

        // Prioritize own products at the top for non-super-admin users
        if (!auth()->user()->isSuperAdmin()) {
            $query->orderByRaw('CASE WHEN created_by = ? THEN 0 ELSE 1 END', [$currentCompanyId]);
        }

        // Handle search
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('sku', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Handle category filter
        if ($request->has('category') && !empty($request->category) && $request->category !== 'all') {
            $query->where('category_id', $request->category);
        }

        // Handle brand filter
        if ($request->has('brand') && !empty($request->brand) && $request->brand !== 'all') {
            $query->where('brand_id', $request->brand);
        }

        // Handle status filter
        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Handle stock_status filter
        if ($request->has('stock_status') && !empty($request->stock_status) && $request->stock_status !== 'all') {
            $query->where('stock_status', $request->stock_status);
        }

        // Handle assigned_to filter
        if ($request->has('assigned_to') && !empty($request->assigned_to) && $request->assigned_to !== 'all') {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Handle company filter (super admin only)
        if ($request->has('company_id') && !empty($request->company_id) && $request->company_id !== 'all') {
            if (auth()->user()->isSuperAdmin()) {
                $query->where('created_by', $request->company_id);
            }
        }

        // Handle sorting
        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts = ['id', 'name', 'price', 'stock_quantity', 'created_at'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $perPage = $request->get('per_page', 10);
        $products = $query->paginate((int)$perPage);

        // Apply company overrides for non-super-admin users
        if (!auth()->user()->isSuperAdmin()) {
            $products->each(function ($product) use ($currentCompanyId) {
                if (isSuperAdminProduct($product)) {
                    $override = ProductCompanyOverride::where('product_id', $product->id)
                        ->where('company_id', $currentCompanyId)
                        ->first();

                    if ($override) {
                        $product->price = $override->getEffectivePrice();
                        $product->sale_price = $override->sale_price_override;
                        $product->stock_quantity = $override->getEffectiveStock();
                        $product->stock_status = $override->getEffectiveStockStatus();
                    }
                }
            });
        }

        // Get dropdown data
        $categories = Category::where('created_by', createdBy())
            ->where('status', 'active')
            ->get(['id', 'name']);

        $brands = Brand::where('created_by', createdBy())
            ->where('status', 'active')
            ->get(['id', 'name']);

        $taxes = Tax::where('created_by', createdBy())
            ->where('status', 'active')
            ->get(['id', 'name', 'rate']);

        // Get users for assignment dropdown (only for company users)
        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        // Get companies list for super admin filter
        $companies = [];
        if (auth()->user()->isSuperAdmin()) {
            $companies = \App\Models\User::where('type', 'company')
                ->select('id', 'name', 'email')
                ->get();
        }

        // Product limit info
        $productLimitInfo = null;
        if (auth()->user()->type === 'company') {
            $currentCount = Product::where('created_by', $currentCompanyId)->count();
            $limit = auth()->user()->product_limit ?? 10;
            $productLimitInfo = [
                'current' => $currentCount,
                'limit' => $limit,
                'can_create' => $currentCount < $limit,
            ];
        }

        return Inertia::render('products/index', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'taxes' => $taxes,
            'users' => $users,
            'companies' => $companies,
            'productLimitInfo' => $productLimitInfo,
            'isSuperAdmin' => auth()->user()->isSuperAdmin(),
            'superAdminCompanyId' => getSuperAdminCompanyId(),
            'samplePath' => file_exists(storage_path('uploads/sample/sample-product.xlsx')) ? route('product.download.template') : null,
            'filters' => $request->all(['search', 'category', 'brand', 'status', 'stock_status', 'assigned_to', 'company_id', 'sort_field', 'sort_direction', 'per_page', 'view', 'page']),
        ]);
    }

    public function create()
    {
        // Check product limit for company users
        if (hasReachedProductLimit()) {
            return redirect()->route('products.index')->with('error', __('Product limit reached. Your plan allows :max products. Contact admin to increase.', ['max' => getProductLimit()]));
        }

        $categories = Category::where('created_by', createdBy())
            ->where('status', 'active')
            ->get(['id', 'name']);

        $brands = Brand::where('created_by', createdBy())
            ->where('status', 'active')
            ->get(['id', 'name']);

        $taxes = Tax::where('created_by', createdBy())
            ->where('status', 'active')
            ->get(['id', 'name', 'rate']);

        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        return Inertia::render('products/create', [
            'categories' => $categories,
            'brands' => $brands,
            'taxes' => $taxes,
            'users' => $users,
            'productLimitInfo' => auth()->user()->type === 'company' ? [
                'current' => Product::where('created_by', createdBy())->count(),
                'limit' => getProductLimit(),
            ] : null,
        ]);
    }

    public function show($id)
    {
        $product = Product::with(['category', 'brand', 'tax', 'assignedUser', 'creator', 'media'])
            ->whereIn('created_by', getVisibleCompanyIds())
            ->findOrFail($id);

        // Apply override for company users viewing super admin products
        if (!auth()->user()->isSuperAdmin() && isSuperAdminProduct($product)) {
            $override = ProductCompanyOverride::where('product_id', $product->id)
                ->where('company_id', createdBy())
                ->first();

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
            'canEdit' => canEditProduct($product),
            'canDelete' => canDeleteProduct($product),
            'isSuperAdminProduct' => isSuperAdminProduct($product),
        ]);
    }

    public function edit($id)
    {
        $product = Product::with(['category', 'brand', 'tax', 'assignedUser', 'creator', 'media'])
            ->whereIn('created_by', getVisibleCompanyIds())
            ->findOrFail($id);

        // If company user trying to edit super admin product, load override data
        $overrideData = null;
        if (!auth()->user()->isSuperAdmin() && isSuperAdminProduct($product)) {
            $override = ProductCompanyOverride::where('product_id', $product->id)
                ->where('company_id', createdBy())
                ->first();

            if ($override) {
                $overrideData = [
                    'price' => $override->price_override,
                    'sale_price' => $override->sale_price_override,
                    'stock_quantity' => $override->stock_quantity_override,
                    'stock_status' => $override->stock_status_override,
                ];
                $product->price = $override->getEffectivePrice();
                $product->sale_price = $override->sale_price_override;
                $product->stock_quantity = $override->getEffectiveStock();
                $product->stock_status = $override->getEffectiveStockStatus();
            }
        }

        $categories = Category::where('created_by', createdBy())
            ->where('status', 'active')
            ->get(['id', 'name']);

        $brands = Brand::where('created_by', createdBy())
            ->where('status', 'active')
            ->get(['id', 'name']);

        $taxes = Tax::where('created_by', createdBy())
            ->where('status', 'active')
            ->get(['id', 'name', 'rate']);

        $users = [];
        if (auth()->user()->type === 'company') {
            $users = \App\Models\User::where('created_by', createdBy())
                ->select('id', 'name', 'email')
                ->get();
        }

        return Inertia::render('products/edit', [
            'product' => array_merge($product->toArray(), [
                'main_image_id' => $product->main_image_id,
                'additional_image_ids' => $product->additional_image_ids ?: []
            ]),
            'categories' => $categories,
            'brands' => $brands,
            'taxes' => $taxes,
            'users' => $users,
            'mainImage' => $product->main_image_url,
            'additionalImages' => $product->additional_image_urls,
            'overrideData' => $overrideData,
            'isSuperAdminProduct' => isSuperAdminProduct($product),
            'canEditOriginal' => canEditProduct($product),
        ]);
    }

    public function store(Request $request)
    {
        // Check product limit for company users
        if (hasReachedProductLimit()) {
            return redirect()->back()->with('error', __('Product limit reached. Your plan allows :max products. Contact admin to increase.', ['max' => getProductLimit()]));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:products,sku',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'stock_status' => 'nullable|string|in:in_stock,out_of_stock,on_backorder',
            'image' => 'nullable|string',
            'main_image_id' => 'nullable|exists:media,id',
            'additional_image_ids' => 'nullable|array',
            'additional_image_ids.*' => 'exists:media,id',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'tax_id' => 'required|exists:taxes,id',
            'status' => 'nullable|in:active,inactive',
            'assigned_to' => auth()->user()->type == 'company' ? 'required|exists:users,id' : 'nullable|exists:users,id',
        ]);

        try {
            $validated['created_by'] = createdBy();
            $validated['status'] = $validated['status'] ?? 'active';
            $validated['stock_quantity'] = $validated['stock_quantity'] ?? 0;
            $validated['stock_status'] = $validated['stock_status'] ?? 'in_stock';

            if (auth()->user()->type != 'company') {
                $validated['assigned_to'] = auth()->id();
            }

            $product = Product::create($validated);

            return redirect()->route('products.index')->with('success', __('Product created successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to create product: :error', ['error' => $e->getMessage()]));
        }
    }

    public function update(Request $request, $productId)
    {
        $product = Product::where('id', $productId)
            ->whereIn('created_by', getVisibleCompanyIds())
            ->first();

        if (!$product) {
            return redirect()->back()->with('error', __('Product not found.'));
        }

        // Company user editing a super admin product -> create/update override instead
        if (!auth()->user()->isSuperAdmin() && isSuperAdminProduct($product)) {
            return $this->updateOverride($request, $product);
        }

        // Normal edit (own product or super admin editing any product)
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'sku' => 'required|string|max:255|unique:products,sku,' . $productId,
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'stock_quantity' => 'nullable|integer|min:0',
                'stock_status' => 'nullable|string|in:in_stock,out_of_stock,on_backorder',
                'image' => 'nullable|string',
                'main_image_id' => 'nullable|exists:media,id',
                'additional_image_ids' => 'nullable|array',
                'additional_image_ids.*' => 'exists:media,id',
                'category_id' => 'required|exists:categories,id',
                'brand_id' => 'required|exists:brands,id',
                'tax_id' => 'required|exists:taxes,id',
                'status' => 'nullable|in:active,inactive',
                'assigned_to' => auth()->user()->type == 'company' ? 'required|exists:users,id' : 'nullable|exists:users,id',
            ]);

            if (auth()->user()->type != 'company') {
                $validated['assigned_to'] = auth()->id();
            }

            $product->update($validated);

            return redirect()->back()->with('success', __('Product updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update product.'));
        }
    }

    /**
     * Update or create a company override for a super admin product.
     * This allows company users to set custom prices/stock for shared products
     * without modifying the original product.
     */
    private function updateOverride(Request $request, Product $product)
    {
        $validated = $request->validate([
            'price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'stock_status' => 'nullable|string|in:in_stock,out_of_stock,on_backorder',
        ]);

        try {
            ProductCompanyOverride::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'company_id' => createdBy(),
                ],
                [
                    'price_override' => $validated['price'] ?? $product->price,
                    'sale_price_override' => $validated['sale_price'] ?? null,
                    'stock_quantity_override' => $validated['stock_quantity'] ?? $product->stock_quantity,
                    'stock_status_override' => $validated['stock_status'] ?? $product->stock_status,
                    'is_visible' => true,
                ]
            );

            return redirect()->back()->with('success', __('Product override updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update product override: :error', ['error' => $e->getMessage()]));
        }
    }

    public function destroy($productId)
    {
        $product = Product::where('id', $productId)
            ->whereIn('created_by', getVisibleCompanyIds())
            ->first();

        if (!$product) {
            return redirect()->back()->with('error', __('Product not found.'));
        }

        if (!canDeleteProduct($product)) {
            return redirect()->back()->with('error', __('You cannot delete this product. Only the owner can delete.'));
        }

        try {
            // Also delete all overrides for this product
            ProductCompanyOverride::where('product_id', $product->id)->delete();
            $product->delete();
            return redirect()->back()->with('success', __('Product deleted successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete product.'));
        }
    }

    public function toggleStatus($productId)
    {
        $product = Product::where('id', $productId)
            ->whereIn('created_by', getVisibleCompanyIds())
            ->first();

        if (!$product) {
            return redirect()->back()->with('error', __('Product not found.'));
        }

        if (!canEditProduct($product)) {
            return redirect()->back()->with('error', __('You cannot edit this product.'));
        }

        try {
            $product->status = $product->status === 'active' ? 'inactive' : 'active';
            $product->save();

            return redirect()->back()->with('success', __('Product status updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update product status.'));
        }
    }

    public function fileExport()
    {
        if (!auth()->user()->can('export-products')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $name = 'product_' . date('Y-m-d i:h:s');
        return Excel::download(new ProductExport(), $name . '.xlsx');
    }

    public function downloadTemplate()
    {
        if (!auth()->user()->can('import-products')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        $filePath = storage_path('uploads/sample/sample-product.xlsx');

        if (!file_exists($filePath)) {
            return response()->json(['error' => __('Template file not available')], 404);
        }

        return response()->download($filePath, 'sample-product.xlsx');
    }

    public function parseFile(Request $request)
    {
        if (!auth()->user()->can('import-products')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $rules = [
            'file' => 'required|mimes:csv,txt,xlsx',
        ];

        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        try {
            $file = $request->file('file');
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $highestColumn = $worksheet->getHighestColumn();
            $highestRow = $worksheet->getHighestRow();
            $headers = [];

            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $value = $worksheet->getCell($col . '1')->getValue();
                if ($value) {
                    $headers[] = (string)$value;
                }
            }

            $previewData = [];
            for ($row = 2; $row <= $highestRow; $row++) {
                $rowData = [];
                $colIndex = 0;
                for ($col = 'A'; $col <= $highestColumn; $col++) {
                    if ($colIndex < count($headers)) {
                        $rowData[$headers[$colIndex]] = (string)$worksheet->getCell($col . $row)->getValue();
                    }
                    $colIndex++;
                }
                $previewData[] = $rowData;
            }

            return response()->json([
                'excelColumns' => $headers,
                'previewData' => $previewData
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to parse file: :error', ['error' => $e->getMessage()]));
        }
    }

    public function fileImport(Request $request)
    {
        if (!auth()->user()->can('import-products')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        // Check product limit before import
        if (hasReachedProductLimit()) {
            return redirect()->back()->with('error', __('Product limit reached. Cannot import more products.'));
        }

        $rules = [
            'data' => 'required|array',
        ];

        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();
            return redirect()->back()->with('error', $messages->first());
        }

        try {
            $data = $request->data;

            $tempFile = storage_path('tmp/import_' . time() . '.csv');

            if (!file_exists(dirname($tempFile))) {
                mkdir(dirname($tempFile), 0755, true);
            }

            $handle = fopen($tempFile, 'w');

            if (!empty($data)) {
                fputcsv($handle, array_keys($data[0]));
                foreach ($data as $row) {
                    fputcsv($handle, $row);
                }
            }
            fclose($handle);

            $import = new ProductImport();
            Excel::import($import, $tempFile);

            unlink($tempFile);

            $message = __('Import completed: :added products added, :skipped products skipped', [
                'added' => $import->getAddedCount(),
                'skipped' => $import->getSkippedCount()
            ]);

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to import: :error', ['error' => $e->getMessage()]));
        }
    }
}
