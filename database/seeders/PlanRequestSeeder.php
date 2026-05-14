<?php

namespace Database\Seeders;

use App\Models\PlanRequest;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class PlanRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get required data with proper validation
        $companyUsers = User::where('type', 'company')->get();
        $staffUsers = User::where('type', 'staff')->get();
        $plans = Plan::all();
        $superAdmin = User::where('type', 'superadmin')->first();
        
        $allUsers = $companyUsers->merge($staffUsers);
        
        if ($allUsers->isEmpty() || $plans->isEmpty()) {
            $this->command->warn('No users or plans found. Please run UserSeeder and PlanSeeder first.');
            return;
        }
        
        $statuses = ['pending', 'approved', 'rejected'];
        
        // Sample request messages
        $requestMessages = [
            'I would like to upgrade my plan to access more features and increase user limits.',
            'Our team has grown and we need a higher tier plan with more storage.',
            'Please upgrade us to the Pro plan for advanced analytics and reporting.',
            'We need more project slots and would like to upgrade our subscription.',
            'Can we get access to the premium features? Our current plan is limiting our growth.',
            'We require additional user seats and storage space for our expanding team.',
            'Please consider upgrading our plan to support our increased business needs.',
            'Our current plan limitations are affecting our productivity. Upgrade needed.',
            'We need access to advanced integrations available in higher tier plans.',
            'Team expansion requires plan upgrade for additional collaboration features.'
        ];
        
        // Create plan requests for users
        foreach ($allUsers->take(15) as $user) {
            // Some users may have multiple requests
            $requestCount = $faker->randomElement([1, 1, 1, 2]); // Most users have 1 request
            
            for ($i = 0; $i < $requestCount; $i++) {
                $status = $faker->randomElement($statuses);
                $requestedPlan = $plans->random();
                
                // Ensure user isn't requesting their current plan
                if ($user->plan_id === $requestedPlan->id) {
                    $requestedPlan = $plans->where('id', '!=', $user->plan_id)->random();
                }
                
                $planRequest = PlanRequest::create([
                    'user_id' => $user->id,
                    'plan_id' => $requestedPlan->id,
                    'status' => $status,
                    'message' => $faker->randomElement($requestMessages),
                    'created_at' => $faker->dateTimeBetween('-3 months', 'now'),
                ]);
                
                // Set approval/rejection details for processed requests
                if ($status === 'approved') {
                    $planRequest->update([
                        'approved_at' => $faker->dateTimeBetween($planRequest->created_at, 'now'),
                        'approved_by' => $superAdmin?->id,
                    ]);
                } elseif ($status === 'rejected') {
                    $planRequest->update([
                        'rejected_at' => $faker->dateTimeBetween($planRequest->created_at, 'now'),
                        'rejected_by' => $superAdmin?->id,
                    ]);
                }
            }
        }
        
        $this->command->info('Plan requests created successfully!');
    }
}
