<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Tax;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

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

            // Resolve category by name
            if (isset($mapped['category_id']) && !is_numeric($mapped['category_id'])) {
                $cat = Category::where('name', $mapped['category_id'])
                    ->where('created_by', createdBy())
                    ->first();
                $mapped['category_id'] = $cat ? $cat->id : null;
            }

            // Resolve brand by name
            if (isset($mapped['brand_id']) && !is_numeric($mapped['brand_id'])) {
                $brand = Brand::where('name', $mapped['brand_id'])
                    ->where('created_by', createdBy())
                    ->first();
                $mapped['brand_id'] = $brand ? $brand->id : null;
            }

            // Resolve tax by name
            if (isset($mapped['tax_id']) && !is_numeric($mapped['tax_id'])) {
                $tax = Tax::where('name', $mapped['tax_id'])
                    ->where('created_by', createdBy())
                    ->first();
                $mapped['tax_id'] = $tax ? $tax->id : null;
            }

            // Set defaults
            $mapped['created_by'] = createdBy();
            $mapped['status'] = $mapped['status'] ?? 'active';
            $mapped['stock_quantity'] = $mapped['stock_quantity'] ?? 0;
            $mapped['stock_status'] = $mapped['stock_status'] ?? 'in_stock';

            // Clean up non-fillable fields
            unset($mapped['category'], $mapped['brand'], $mapped['tax'], $mapped['supplier'], $mapped['supplier_name']);

            try {
                Product::create($mapped);
                $this->addedCount++;
            } catch (\Exception $e) {
                \Log::error('Product import failed for SKU: ' . $mapped['sku'], [
                    'error' => $e->getMessage(),
                ]);
                $this->skippedCount++;
            }
        }
    }

    public function getAddedCount()
    {
        return $this->addedCount;
    }

    public function getSkippedCount()
    {
        return $this->skippedCount;
    }
}
