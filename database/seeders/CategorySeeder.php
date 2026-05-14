<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $companyUsers = User::where('type', 'company')->get();
        
        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run UserSeeder first.');
            return;
        }

        $categoryTemplates = [
            ['name' => 'Electronics', 'description' => 'Consumer electronics and digital devices'],
            ['name' => 'Computers & Laptops', 'description' => 'Desktop computers and laptops'],
            ['name' => 'Mobile Devices', 'description' => 'Smartphones, tablets, and accessories'],
            ['name' => 'Software & Licenses', 'description' => 'Software applications and licenses'],
            ['name' => 'Office Equipment', 'description' => 'Printers, scanners, and office hardware'],
            ['name' => 'Networking & Security', 'description' => 'Network equipment and security devices']
        ];

        foreach ($companyUsers as $company) {
            foreach ($categoryTemplates as $template) {
                Category::firstOrCreate(
                    ['slug' => Str::slug($template['name']), 'created_by' => $company->id],
                    [
                        'name' => $template['name'],
                        'slug' => Str::slug($template['name']),
                        'description' => $template['description'],
                        'status' => 'active',
                        'created_by' => $company->id,
                    ]
                );
            }
        }
        
        $this->command->info('Categories created for all company users!');
    }
}