<?php

namespace Database\Seeders;

use App\Models\AccountType;
use App\Models\User;
use Illuminate\Database\Seeder;

class AccountTypeSeeder extends Seeder
{
    public function run(): void
    {
        $companyUsers = User::where('type', 'company')->get();

        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run UserSeeder first.');
            return;
        }

        $typeTemplates = [
            ['name' => 'Enterprise Customer', 'description' => 'Large enterprise clients', 'color' => '#3B82F6'],
            ['name' => 'SMB Customer', 'description' => 'Small to medium business customers', 'color' => '#10b77f'],
            ['name' => 'Strategic Partner', 'description' => 'Key business partners', 'color' => '#F59E0B'],
            ['name' => 'Supplier/Vendor', 'description' => 'Suppliers and vendors', 'color' => '#8B5CF6'],
            ['name' => 'Reseller/Channel', 'description' => 'Authorized resellers and partners', 'color' => '#06B6D4'],
            ['name' => 'Prospect', 'description' => 'Potential customers', 'color' => '#84CC16']
        ];

        foreach ($companyUsers as $company) {
            foreach ($typeTemplates as $template) {
                AccountType::firstOrCreate(
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

        $this->command->info('Account types created for all company users!');
    }
}
