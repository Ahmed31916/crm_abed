<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $companyUsers = User::where('type', 'company')->get();
        
        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run UserSeeder first.');
            return;
        }

        $brandTemplates = [
            ['name' => 'Apple', 'description' => 'Premium technology brand', 'website' => 'https://apple.com'],
            ['name' => 'Samsung', 'description' => 'Global electronics manufacturer', 'website' => 'https://samsung.com'],
            ['name' => 'Dell', 'description' => 'Computer technology company', 'website' => 'https://dell.com'],
            ['name' => 'Microsoft', 'description' => 'Software and cloud computing', 'website' => 'https://microsoft.com'],
            ['name' => 'Sony', 'description' => 'Electronics and entertainment', 'website' => 'https://sony.com'],
            ['name' => 'HP', 'description' => 'Personal computing solutions', 'website' => 'https://hp.com']
        ];

        foreach ($companyUsers as $company) {
            foreach ($brandTemplates as $template) {
                Brand::firstOrCreate(
                    ['name' => $template['name'], 'created_by' => $company->id],
                    [
                        'name' => $template['name'],

                        'description' => $template['description'],
                        'website' => $template['website'],
                        'status' => 'active',
                        'created_by' => $company->id,
                    ]
                );
            }
        }
        
        $this->command->info('Brands created for all company users!');
    }
}