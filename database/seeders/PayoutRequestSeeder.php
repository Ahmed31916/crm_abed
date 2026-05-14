<?php

namespace Database\Seeders;

use App\Models\PayoutRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class PayoutRequestSeeder extends Seeder
{
    public function run(): void
    {
        $companies = User::where('type', 'company')->take(4)->get();

        if ($companies->isEmpty()) {
            $this->command->warn('No company users found. Please seed users first.');
            return;
        }

        foreach ($companies as $company) {
            // Calculate available balance for realistic payout amounts
            $totalEarned = random_int(1000, 10000);
            // $totalEarned = max(0, \App\Models\Referral::where('company_id', $company->id)->sum('amount'));
            $existingPayouts = random_int(0, $totalEarned);
            // $existingPayouts = max(0, PayoutRequest::where('company_id', $company->id)->sum('amount'));
            $availableBalance = max(0, $totalEarned - $existingPayouts);

            if ($availableBalance > 50) {
                $payoutAmount = max(0, min($availableBalance * 0.8, 500)); // Request up to 80% of available balance

                PayoutRequest::create([
                    'company_id' => $company->id,
                    'amount' => $payoutAmount,
                    'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
                    'notes' => fake()->randomElement([
                        'Monthly commission payout request',
                        'Referral commission for Q1',
                        'Weekly payout request',
                        'Quarterly earnings withdrawal'
                    ])
                ]);
            }
        }

        $this->command->info('Payout requests seeded successfully!');
    }
}
