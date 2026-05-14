<?php

namespace Database\Seeders;

use App\Models\Referral;
use App\Models\User;
use App\Models\Plan;
use App\Models\ReferralSetting;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ReferralSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $users = User::where('type', 'company')->get();
        $plans = Plan::all();

        if ($users->isEmpty() || $plans->isEmpty()) {
            $this->command->warn('No users or plans found. Please seed users and plans first.');
            return;
        }

        foreach ($users->take(10) as $index => $user) {
            $referrer = $users->skip(($index + 1) % $users->count())->first();
            $plan = $plans->random();
            $commissionPercentage = $faker->randomFloat(2, 5, 20);
            $commissionAmount = ($plan->price * $commissionPercentage) / 100;
            
            Referral::create([
                'user_id' => $user->id,
                'company_id' => $referrer->id,
                'commission_percentage' => $commissionPercentage,
                'amount' => $commissionAmount,
                'plan_id' => $plan->id,
            ]);
        }

        $this->command->info('Referrals seeded successfully!');
    }
}