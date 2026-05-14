<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\CampaignType;
use App\Models\TargetList;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class CampaignSeeder extends Seeder
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

        $campaignTemplates = [
            ['name' => 'Product Launch Campaign', 'description' => 'New product launch marketing campaign'],
            ['name' => 'Lead Generation Drive', 'description' => 'Multi-channel lead generation campaign'],
            ['name' => 'Customer Retention Program', 'description' => 'Campaign to retain existing customers'],
            ['name' => 'Brand Awareness Initiative', 'description' => 'Campaign to increase brand visibility'],
            ['name' => 'Webinar Series', 'description' => 'Educational webinar campaign'],
            ['name' => 'Email Marketing Blast', 'description' => 'Targeted email marketing campaign'],
            ['name' => 'Social Media Promotion', 'description' => 'Cross-platform social media campaign'],
            ['name' => 'Trade Show Participation', 'description' => 'Industry trade show marketing campaign'],
            ['name' => 'Content Marketing Drive', 'description' => 'Blog and content marketing initiative'],
            ['name' => 'Referral Program Launch', 'description' => 'Customer referral incentive campaign'],
            ['name' => 'Holiday Sales Campaign', 'description' => 'Seasonal holiday promotion campaign'],
            ['name' => 'Partnership Marketing', 'description' => 'Strategic partnership marketing initiative'],
            ['name' => 'Mobile App Promotion', 'description' => 'Mobile application marketing campaign'],
            ['name' => 'Customer Survey Campaign', 'description' => 'Customer feedback and survey campaign'],
            ['name' => 'Loyalty Program Rollout', 'description' => 'Customer loyalty program launch campaign']
        ];

        foreach ($companyUsers as $company) {
            $campaignTypes = CampaignType::where('created_by', $company->id)->get();
            $targetLists = TargetList::where('created_by', $company->id)->get();
            $staffUsers = User::where('created_by', $company->id)->get();
            
            if ($campaignTypes->isEmpty()) {
                continue;
            }

            foreach ($campaignTemplates as $template) {
                $startDate = $faker->dateTimeBetween('-2 months', '+1 month');
                $endDate = $faker->dateTimeBetween($startDate, '+3 months');
                $budget = $faker->randomElement([25000, 35000, 45000, 55000, 75000]);
                $actualCost = $faker->numberBetween(0, $budget);
                $expectedResponse = $faker->numberBetween(200, 1500);
                $actualResponse = $faker->numberBetween(0, $expectedResponse);
                
                Campaign::create([
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'budget' => $budget,
                    'actual_cost' => $actualCost,
                    'expected_response' => $expectedResponse,
                    'actual_response' => $actualResponse,
                    'campaign_type_id' => $campaignTypes->random()->id,
                    'target_list_id' => $targetLists->isNotEmpty() ? $targetLists->random()->id : null,
                    'status' => $faker->randomElement(['active', 'active', 'inactive']), // 67% active
                    'created_by' => $company->id,
                    'assigned_to' => $staffUsers->isNotEmpty() ? $staffUsers->random()->id : null,
                ]);
            }
        }
        
        $this->command->info('Campaigns created for all company users!');
    }
}