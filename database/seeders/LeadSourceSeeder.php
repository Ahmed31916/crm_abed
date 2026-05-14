<?php

namespace Database\Seeders;

use App\Models\LeadSource;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeadSourceSeeder extends Seeder
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

        $sourceTemplates = [
            ['name' => 'Website', 'description' => 'Leads from company website'],
            ['name' => 'Social Media', 'description' => 'Leads from social media platforms'],
            ['name' => 'Email Campaign', 'description' => 'Leads from email marketing campaigns'],
            ['name' => 'Referral', 'description' => 'Leads from customer referrals'],
            ['name' => 'Cold Call', 'description' => 'Leads from cold calling activities'],
            ['name' => 'Trade Show', 'description' => 'Leads from trade shows and events']
        ];

        foreach ($companyUsers as $company) {
            foreach ($sourceTemplates as $template) {
                LeadSource::firstOrCreate(
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
        
        $this->command->info('Lead sources created for all company users!');
    }
}