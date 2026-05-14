<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Plan;
use App\Models\Referral;
use App\Models\PayoutRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class ReferralProgramSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $plan = Plan::first();
        
        // Create company@example.com with MORE data
        $mainCompany = User::updateOrCreate(
            ['email' => 'company@example.com'],
            [
                'name' => 'Company',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'type' => 'company',
                'plan_id' => $plan->id,
                'referral_code' => rand(100000, 999999),
            ]
        );
        $mainCompany->assignRole('company');
        
        // Realistic company names for referred users
        $referredCompanies = [
            'TechStart Solutions', 'Digital Marketing Pro', 'CloudSync Systems', 'DataFlow Analytics',
            'WebCraft Studios', 'MobileFirst Apps', 'SecureNet Services', 'AutoScale Tech',
            'SmartBiz Tools', 'InnovateLab Inc', 'GrowthHack Agency', 'NextGen Software'
        ];
        
        // Create 12 referred users for company@example.com (MORE than others)
        foreach ($referredCompanies as $i => $companyName) {
            $email = strtolower(str_replace(' ', '', $companyName)) . '@business.com';
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $companyName,
                    'email_verified_at' => $faker->dateTimeBetween('-8 months', '-1 month'),
                    'password' => Hash::make('password'),
                    'type' => 'company',
                    'plan_id' => $plan->id,
                    'referral_code' => rand(100000, 999999),
                    'used_referral_code' => $mainCompany->referral_code,
                    'created_at' => $faker->dateTimeBetween('-8 months', '-1 month'),
                ]
            );
            $user->assignRole('company');
            
            // Create realistic commission pattern - monthly recurring
            $joinDate = $user->created_at;
            $currentDate = now();
            $monthsDiff = $joinDate->diffInMonths($currentDate);
            
            // Create monthly commissions based on plan price
            $planPrice = $plan->price ?? 99;
            $commissionRate = 15; // 15%
            $monthlyCommission = ($planPrice * $commissionRate) / 100;
            
            for ($month = 0; $month <= $monthsDiff; $month++) {
                $commissionDate = $joinDate->copy()->addMonths($month);
                if ($commissionDate <= $currentDate) {
                    // Add some variation to commission amounts
                    $variation = $faker->randomFloat(2, -10, 25);
                    $finalAmount = max(5, $monthlyCommission + $variation);
                    
                    Referral::create([
                        'user_id' => $user->id,
                        'company_id' => $mainCompany->id,
                        'commission_percentage' => $commissionRate,
                        'amount' => $finalAmount,
                        'plan_id' => $plan->id,
                        'created_at' => $commissionDate,
                        'updated_at' => $commissionDate,
                    ]);
                }
            }
        }
        
        // Create realistic payout requests based on actual earnings
        $totalEarnings = Referral::where('company_id', $mainCompany->id)->sum('amount');
        $availableBalance = $totalEarnings;
        
        // Create historical payout requests
        $payoutHistory = [
            ['months_ago' => 4, 'percentage' => 0.3, 'status' => 'approved', 'note' => 'Q1 commission payout'],
            ['months_ago' => 3, 'percentage' => 0.25, 'status' => 'approved', 'note' => 'Monthly withdrawal - March'],
            ['months_ago' => 2, 'percentage' => 0.2, 'status' => 'rejected', 'note' => 'Incomplete tax documentation'],
            ['months_ago' => 1, 'percentage' => 0.15, 'status' => 'approved', 'note' => 'Monthly withdrawal - May'],
            ['months_ago' => 0.5, 'percentage' => 0.1, 'status' => 'pending', 'note' => 'Current month withdrawal request'],
        ];
        
        $processedAmount = 0;
        foreach ($payoutHistory as $payout) {
            $requestAmount = min($totalEarnings * $payout['percentage'], $availableBalance - $processedAmount);
            if ($requestAmount > 50) {
                PayoutRequest::create([
                    'company_id' => $mainCompany->id,
                    'amount' => round($requestAmount, 2),
                    'status' => $payout['status'],
                    'notes' => $payout['note'],
                    'created_at' => now()->subMonths($payout['months_ago']),
                    'updated_at' => now()->subMonths($payout['months_ago'] - 0.1),
                ]);
                
                if ($payout['status'] === 'approved') {
                    $processedAmount += $requestAmount;
                }
            }
        }
        
        // Create other realistic companies with LESS data
        $otherCompanies = [
            ['name' => 'StartupHub Co', 'email' => 'startuphub@business.com'],
            ['name' => 'LocalBiz Solutions', 'email' => 'localbiz@company.com'],
            ['name' => 'FreelanceForce', 'email' => 'freelanceforce@agency.com'],
            ['name' => 'ConsultPro Services', 'email' => 'consultpro@services.com'],
        ];
        
        foreach ($otherCompanies as $companyData) {
            $company = User::updateOrCreate(
                ['email' => $companyData['email']],
                [
                    'name' => $companyData['name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                    'type' => 'company',
                    'plan_id' => $plan->id,
                    'referral_code' => rand(100000, 999999),
                ]
            );
            $company->assignRole('company');
            
            // Create fewer referred users for other companies (2-4 users)
            $referredCount = $faker->numberBetween(2, 4);
            $smallBusinessNames = ['QuickStart', 'EasyFlow', 'SimpleTools', 'FastTrack', 'SmallBiz'];
            
            for ($i = 1; $i <= $referredCount; $i++) {
                $businessName = $faker->randomElement($smallBusinessNames) . ' ' . $faker->word();
                $email = strtolower(str_replace(' ', '', $businessName)) . $i . '@small.biz';
                
                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $businessName,
                        'email_verified_at' => $faker->dateTimeBetween('-3 months', '-2 weeks'),
                        'password' => Hash::make('password'),
                        'type' => 'company',
                        'plan_id' => $plan->id,
                        'referral_code' => rand(100000, 999999),
                        'used_referral_code' => $company->referral_code,
                        'created_at' => $faker->dateTimeBetween('-3 months', '-2 weeks'),
                    ]
                );
                $user->assignRole('company');
                
                // Create realistic monthly commissions (fewer months)
                $joinDate = $user->created_at;
                $monthsActive = min(3, $joinDate->diffInMonths(now()));
                $planPrice = $plan->price ?? 49; // Smaller companies use cheaper plans
                $monthlyCommission = ($planPrice * 15) / 100;
                
                for ($month = 0; $month <= $monthsActive; $month++) {
                    $commissionDate = $joinDate->copy()->addMonths($month);
                    if ($commissionDate <= now()) {
                        Referral::create([
                            'user_id' => $user->id,
                            'company_id' => $company->id,
                            'commission_percentage' => 15,
                            'amount' => $monthlyCommission + $faker->randomFloat(2, -5, 10),
                            'plan_id' => $plan->id,
                            'created_at' => $commissionDate,
                        ]);
                    }
                }
            }
            
            // Create multiple payout requests for other companies
            $companyEarnings = Referral::where('company_id', $company->id)->sum('amount');
            if ($companyEarnings > 50) {
                $payoutRequests = [
                    ['months_ago' => 2, 'percentage' => 0.4, 'status' => 'approved', 'note' => 'Initial payout'],
                    ['months_ago' => 1, 'percentage' => 0.3, 'status' => $faker->randomElement(['approved', 'rejected']), 'note' => 'Monthly withdrawal'],
                    ['months_ago' => 0.2, 'percentage' => 0.25, 'status' => $faker->randomElement(['pending', 'approved']), 'note' => 'Recent withdrawal request'],
                ];
                
                $processedAmount = 0;
                foreach ($payoutRequests as $payout) {
                    $requestAmount = min($companyEarnings * $payout['percentage'], $companyEarnings - $processedAmount);
                    if ($requestAmount > 25) {
                        PayoutRequest::create([
                            'company_id' => $company->id,
                            'amount' => round($requestAmount, 2),
                            'status' => $payout['status'],
                            'notes' => $payout['note'],
                            'created_at' => now()->subMonths($payout['months_ago']),
                            'updated_at' => now()->subMonths($payout['months_ago'] - 0.1),
                        ]);
                        
                        if ($payout['status'] === 'approved') {
                            $processedAmount += $requestAmount;
                        }
                    }
                }
            }
        }
        
        $this->command->info('Referral program data created successfully!');
        $this->command->info('Main Company (MORE data): company@example.com / password');
        $this->command->info('Other Companies (LESS data): companya@example.com, companyb@example.com, companyc@example.com / password');
        $this->command->info('Main company earnings: $' . Referral::where('company_id', $mainCompany->id)->sum('amount'));
    }
}