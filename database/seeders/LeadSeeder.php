<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\LeadSource;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $companyUsers = User::where('type', 'company')->get();
        
        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run UserSeeder first.');
            return;
        }

        $companyNames = ['TechCorp', 'HealthPlus', 'RetailMax', 'FinanceHub', 'ManufacturePro', 'EduSoft', 'LogisticsPro', 'GreenEnergy'];
        $positions = ['CEO', 'CTO', 'VP Sales', 'IT Director', 'Operations Manager', 'Marketing Director', 'Head of Digital'];
        $values = [25000, 45000, 75000, 95000, 125000, 150000, 200000, 300000];

        foreach ($companyUsers as $company) {
            $leadStatuses = LeadStatus::where('created_by', $company->id)->get();
            $leadSources = LeadSource::where('created_by', $company->id)->get();
            $campaigns = \App\Models\Campaign::where('created_by', $company->id)->get();
            $accountIndustries = \App\Models\AccountIndustry::where('created_by', $company->id)->get();
            $staffUsers = User::where('created_by', $company->id)->get();
            
            if ($leadStatuses->isEmpty() || $leadSources->isEmpty()) {
                continue;
            }

            for ($i = 1; $i <= 25; $i++) {
                $companyName = $faker->randomElement($companyNames);
                $firstName = $faker->firstName;
                $lastName = $faker->lastName;
                
                Lead::create([
                    'name' => $firstName . ' ' . $lastName,
                    'email' => strtolower($firstName . '.' . $lastName . '.' . $company->id) . '@' . strtolower($companyName) . '.com',
                    'phone' => $faker->phoneNumber,
                    'company' => $companyName . ' Inc',
                    'position' => $faker->randomElement($positions),
                    'value' => $faker->randomElement($values),
                    'status' => 'active',
                    'is_converted' => $faker->boolean(30), // 30% conversion rate
                    'lead_status_id' => $leadStatuses->random()->id,
                    'lead_source_id' => $leadSources->random()->id,
                    'created_by' => $company->id,
                    'assigned_to' => $staffUsers->isNotEmpty() ? $staffUsers->random()->id : null,
                    'campaign_id' => $campaigns->isNotEmpty() ? $campaigns->random()->id : null,
                    'account_industry_id' => $accountIndustries->isNotEmpty() ? $accountIndustries->random()->id : null,
                    'created_at' => $faker->dateTimeBetween('-3 months', 'now'),
                ]);
            }
        }
        
        $this->command->info('Leads created for all company users!');
    }
}