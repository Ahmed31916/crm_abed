<?php

namespace Database\Seeders;

use App\Models\AccountIndustry;
use App\Models\User;
use Illuminate\Database\Seeder;

class AccountIndustrySeeder extends Seeder
{
    public function run(): void
    {
        $companyUsers = User::where('type', 'company')->get();

        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run UserSeeder first.');
            return;
        }

        $industryTemplates = [
            ['name' => 'Technology & Software', 'description' => 'Software development and IT services', 'color' => '#10b77f'],
            ['name' => 'Healthcare & Life Sciences', 'description' => 'Medical and pharmaceutical services', 'color' => '#EF4444'],
            ['name' => 'Financial Services', 'description' => 'Banking and financial consulting', 'color' => '#6366F1'],
            ['name' => 'Manufacturing & Industrial', 'description' => 'Production and industrial equipment', 'color' => '#F97316'],
            ['name' => 'Retail & E-commerce', 'description' => 'Online and retail stores', 'color' => '#8B5CF6'],
            ['name' => 'Education & Training', 'description' => 'Educational institutions and training', 'color' => '#F59E0B'],
            ['name' => 'Professional Services', 'description' => 'Consulting and business services', 'color' => '#06B6D4'],
            ['name' => 'Real Estate & Construction', 'description' => 'Property and construction services', 'color' => '#84CC16']
        ];

        foreach ($companyUsers as $company) {
            foreach ($industryTemplates as $template) {
                AccountIndustry::firstOrCreate(
                    ['name' => $template['name'], 'created_by' => $company->id],
                    [
                        'name' => $template['name'],
                        'description' => $template['description'],
                        'color' => $template['color'],
                        'status' => 'active',
                        'created_by' => $company->id,
                    ]
                );
            }
        }

        $this->command->info('Account industries created for all company users!');
    }
}
