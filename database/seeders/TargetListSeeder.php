<?php

namespace Database\Seeders;

use App\Models\TargetList;
use App\Models\User;
use Illuminate\Database\Seeder;

class TargetListSeeder extends Seeder
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

        $listTemplates = [
            ['name' => 'High-Value Prospects', 'description' => 'Prospects with high potential value'],
            ['name' => 'New Leads', 'description' => 'Recently acquired leads'],
            ['name' => 'Existing Customers', 'description' => 'Current customer base'],
            ['name' => 'Cold Prospects', 'description' => 'Prospects requiring nurturing'],
            ['name' => 'Hot Leads', 'description' => 'Ready-to-convert leads'],
            ['name' => 'Industry Specific', 'description' => 'Targeted industry contacts']
        ];

        foreach ($companyUsers as $company) {
            foreach ($listTemplates as $template) {
                TargetList::firstOrCreate(
                    ['name' => $template['name'], 'created_by' => $company->id],
                    [
                        'name' => $template['name'],
                        'description' => $template['description'],
                        'status' => 'active',
                        'created_by' => $company->id,
                    ]
                );
            }
        }
        
        $this->command->info('Target lists created for all company users!');
    }
}