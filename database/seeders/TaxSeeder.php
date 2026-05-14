<?php

namespace Database\Seeders;

use App\Models\Tax;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaxSeeder extends Seeder
{
    public function run(): void
    {
        $companyUsers = User::where('type', 'company')->get();
        
        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run UserSeeder first.');
            return;
        }

        $taxTemplates = [
            ['name' => 'VAT', 'rate' => 15.00, 'description' => 'Value Added Tax - Standard rate'],
            ['name' => 'GST', 'rate' => 18.00, 'description' => 'Goods and Services Tax'],
            ['name' => 'Sales Tax', 'rate' => 8.50, 'description' => 'State sales tax'],
            ['name' => 'Luxury Tax', 'rate' => 25.00, 'description' => 'Tax for luxury goods'],
            ['name' => 'Export Exempt', 'rate' => 0.00, 'description' => 'Tax exemption for exports']
        ];

        foreach ($companyUsers as $company) {
            foreach ($taxTemplates as $template) {
                Tax::firstOrCreate(
                    ['name' => $template['name'], 'created_by' => $company->id],
                    [
                        'name' => $template['name'],
                        'rate' => $template['rate'],
                        'type' => 'percentage',
                        'description' => $template['description'],
                        'status' => 'active',
                        'created_by' => $company->id,
                    ]
                );
            }
        }
        
        $this->command->info('Taxes created for all company users!');
    }
}