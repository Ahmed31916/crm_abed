<?php

namespace Database\Seeders;

use App\Models\OpportunitySource;
use App\Models\User;
use Illuminate\Database\Seeder;

class OpportunitySourceSeeder extends Seeder
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
            ['name' => 'Website', 'description' => 'Opportunities from company website'],
            ['name' => 'Referral', 'description' => 'Opportunities from customer referrals'],
            ['name' => 'Cold Outreach', 'description' => 'Opportunities from cold calling and emails'],
            ['name' => 'Social Media', 'description' => 'Opportunities from social media platforms'],
            ['name' => 'Trade Show', 'description' => 'Opportunities from trade shows and events'],
            ['name' => 'Partner', 'description' => 'Opportunities from business partners']
        ];

        foreach ($companyUsers as $company) {
            foreach ($sourceTemplates as $template) {
                OpportunitySource::firstOrCreate(
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
        
        $this->command->info('Opportunity sources created for all company users!');
    }
}