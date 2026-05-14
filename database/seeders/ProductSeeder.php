<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Tax;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $companyUsers = User::where('type', 'company')->get();
        
        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run UserSeeder first.');
            return;
        }

        $productTemplates = [
            ['name' => 'iPhone 15 Pro', 'price' => 1200, 'brand' => 'Apple', 'category' => 'Mobile Devices'],
            ['name' => 'Samsung Galaxy S24', 'price' => 900, 'brand' => 'Samsung', 'category' => 'Mobile Devices'],
            ['name' => 'MacBook Pro M3', 'price' => 2400, 'brand' => 'Apple', 'category' => 'Computers & Laptops'],
            ['name' => 'Dell XPS 15', 'price' => 1900, 'brand' => 'Dell', 'category' => 'Computers & Laptops'],
            ['name' => 'HP EliteBook', 'price' => 1500, 'brand' => 'HP', 'category' => 'Computers & Laptops'],
            ['name' => 'iPad Air', 'price' => 700, 'brand' => 'Apple', 'category' => 'Mobile Devices'],
            ['name' => 'Surface Pro 9', 'price' => 1200, 'brand' => 'Microsoft', 'category' => 'Computers & Laptops'],
            ['name' => 'Dell UltraSharp Monitor', 'price' => 400, 'brand' => 'Dell', 'category' => 'Office Equipment'],
            ['name' => 'Microsoft Wireless Mouse', 'price' => 50, 'brand' => 'Microsoft', 'category' => 'Office Equipment'],
            ['name' => 'HP Mechanical Keyboard', 'price' => 150, 'brand' => 'HP', 'category' => 'Office Equipment']
        ];

        foreach ($companyUsers as $company) {
            $categories = Category::where('created_by', $company->id)->get();
            $brands = Brand::where('created_by', $company->id)->get();
            $taxes = Tax::where('created_by', $company->id)->get();
            $staffUsers = User::where('created_by', $company->id)->get();

            if ($categories->isEmpty() || $brands->isEmpty() || $taxes->isEmpty()) {
                continue;
            }

            foreach ($productTemplates as $index => $template) {
                $brand = $brands->where('name', $template['brand'])->first();
                $category = $categories->where('name', $template['category'])->first();
                
                if (!$brand || !$category) {
                    continue;
                }
                
                Product::updateOrCreate(
                    ['sku' => 'PRD-' . $company->id . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT), 'created_by' => $company->id],
                    [
                        'name' => $template['name'],
                        'sku' => 'PRD-' . $company->id . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                        'description' => 'High-quality ' . strtolower($template['name']) . ' for professional use.',
                        'price' => $template['price'],
                        'stock_quantity' => random_int(10, 100),
                        'category_id' => $category->id,
                        'brand_id' => $brand->id,
                        'tax_id' => $taxes->random()->id,
                        'status' => 'active',
                        'created_by' => $company->id,
                        'assigned_to' => $staffUsers->isNotEmpty() ? $staffUsers->random()->id : null,
                    ]
                );
            }
        }
        
        $this->command->info('Products created for all company users!');
    }
}