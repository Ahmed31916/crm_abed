<?php

namespace Database\Seeders;

use App\Models\ShippingProviderType;
use App\Models\User;
use Illuminate\Database\Seeder;

class ShippingProviderTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyUsers = User::where('type', 'company')->get();

        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run UserSeeder first.');
            return;
        }

        $typeTemplates = [
            ['name' => 'Express Delivery', 'color' => '#EF4444', 'description' => 'Fast delivery within 1-2 business days'],
            ['name' => 'Standard Shipping', 'color' => '#3B82F6', 'description' => 'Regular delivery within 3-5 business days'],
            ['name' => 'Economy Shipping', 'color' => '#10b77f', 'description' => 'Cost-effective delivery within 5-7 business days'],
            ['name' => 'Overnight Delivery', 'color' => '#F59E0B', 'description' => 'Next day delivery service'],
            ['name' => 'International Shipping', 'color' => '#8B5CF6', 'description' => 'International delivery service']
        ];

        foreach ($companyUsers as $company) {
            foreach ($typeTemplates as $template) {
                ShippingProviderType::firstOrCreate(
                    ['name' => $template['name'], 'created_by' => $company->id],
                    [
                        'name' => $template['name'],
                        'color' => $template['color'],
                        'description' => $template['description'],
                        'status' => 'active',
                        'created_by' => $company->id,
                    ]
                );
            }
        }

        $this->command->info('Shipping provider types created for all company users!');
    }
}
