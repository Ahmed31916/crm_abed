<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default plans if they don't exist
        $plans = [
            [
                'name' => 'Free',
                'price' => 0,
                'yearly_price' => 0,
                'duration' => 'monthly',
                'description' => 'Basic plan for small businesses just getting started.',
                'max_users' => 2,
                'max_projects' => 1,
                'max_contacts' => 10,
                'max_accounts' => 5,
                'enable_branding' => 'on',
                'enable_chatgpt' => 'off',
                'storage_limit' => 1,
                'is_trial' => null,
                'trial_day' => 0,
                'is_plan_enable' => 'on',
                'is_default' => true,
                'module' => null
            ],
            [
                'name' => 'Starter',
                'price' => 19.99,
                'yearly_price' => 191.90, // 20% discount for yearly
                'duration' => 'monthly',
                'description' => 'Perfect for small businesses looking to grow their online presence.',
                'max_users' => 10,
                'max_projects' => 5,
                'max_contacts' => 20,
                'max_accounts' => 25,
                'enable_branding' => 'off',
                'enable_chatgpt' => 'off',
                'storage_limit' => 5,
                'is_trial' => 'on',
                'trial_day' => 7,
                'is_plan_enable' => 'on',
                'is_default' => false,
                'module' => null
            ],
            [
                'name' => 'Pro',
                'price' => 49.99,
                'yearly_price' => 479.90, // 20% discount for yearly
                'duration' => 'monthly',
                'description' => 'Ideal for growing businesses with multiple stores and advanced needs.',
                'max_users' => 50,
                'max_projects' => 20,
                'max_contacts' => 100,
                'max_accounts' => 100,
                'enable_branding' => 'off',
                'enable_chatgpt' => 'on',
                'storage_limit' => 50,
                'is_trial' => 'on',
                'trial_day' => 14,
                'is_plan_enable' => 'on',
                'is_default' => false,
                'module' => null
            ]
        ];
        
        foreach ($plans as $planData) {
            // Check if plan with this name already exists
            $existingPlan = Plan::where('name', $planData['name'])->first();
            
            if (!$existingPlan) {
                Plan::create($planData);
            }
        }
    }
}