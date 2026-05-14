<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductImport implements ToModel, WithHeadingRow
{
    use Importable;

    private $addedCount = 0;
    private $skippedCount = 0;

    public function model(array $row)
    {
        // Skip if both name and sku are empty
        if (empty($row['name']) && empty($row['sku'])) {
            return null;
        }

        // Check for duplicate SKU
        if (!empty($row['sku'])) {
            $productBySku = Product::where('sku', $row['sku'])->where('created_by', createdBy())->first();
            if ($productBySku) {
                $this->skippedCount++;
                return null;
            }
        }

        $productData = [
            'name' => $row['name'] ?? '',
            'sku' => $row['sku'] ?? '',
            'description' => $row['description'] ?? '',
            'price' => is_numeric($row['price'] ?? null) ? (float)$row['price'] : 0,
            'stock_quantity' => is_numeric($row['stock'] ?? null) ? (int)$row['stock'] : 0,
            'status' => in_array($row['status'] ?? 'active', ['active', 'inactive']) ? $row['status'] : 'active',
            'created_by' => createdBy(),
        ];

        // Category
        if (!empty($row['category'])) {
            $category = \App\Models\Category::where('name', $row['category'])
                ->where('created_by', createdBy())
                ->first();
            if ($category) {
                $productData['category_id'] = $category->id;
            }
        }

        // Brand
        if (!empty($row['brand'])) {
            $brand = \App\Models\Brand::where('name', $row['brand'])
                ->where('created_by', createdBy())
                ->first();
            if ($brand) {
                $productData['brand_id'] = $brand->id;
            }
        }

        // Tax
        $taxValue = trim($row['tax'] ?? '');
        $tax = !empty($taxValue)
            ? \App\Models\Tax::where('name', $taxValue)->where('created_by', createdBy())->first()
            : null;
        $productData['tax_id'] = $tax?->id ?? \App\Models\Tax::where('created_by', createdBy())->value('id');

        if (!auth()->user()->hasRole('company')) {
            $productData['assigned_to'] = auth()->id();
        }

        $this->addedCount++;
        return new Product($productData);
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
