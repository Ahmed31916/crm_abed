<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        $query = Product::with(['category', 'brand', 'tax', 'assignedUser'])
            ->where('created_by', createdBy());
        
        if (!auth()->user()->hasRole('company')) {
            $query->where('assigned_to', auth()->id());
        }
        
        return $query->get();
    }

    public function map($product): array
    {
        return [
            $product->name,
            $product->sku,
            $product->description,
            $product->price,
            $product->stock_quantity,
            $product->category?->name,
            $product->brand?->name,
            $product->tax?->name,
            $product->status,
            $product->assignedUser?->name,
        ];
    }

    public function headings(): array
    {
        return [
            'Name',
            'SKU',
            'Description',
            'Price',
            'Stock Quantity',
            'Category',
            'Brand',
            'Tax',
            'Status',
            'Assigned User',
        ];
    }
}