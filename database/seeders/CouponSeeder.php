<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = \App\Models\User::first();
        
        if (!$user) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }

        $coupons = [
            [
                'name' => 'Summer Sale',
                'type' => 'percentage',
                'minimum_spend' => 50.00,
                'maximum_spend' => 500.00,
                'discount_amount' => 20.00,
                'use_limit_per_coupon' => 100,
                'use_limit_per_user' => 1,
                'expiry_date' => now()->addMonths(3),
                'code' => 'SUMMER20',
                'code_type' => 'manual',
                'status' => true,
                'created_by' => $user->id,
            ],
            [
                'name' => 'New Customer Discount',
                'type' => 'flat',
                'minimum_spend' => 25.00,
                'maximum_spend' => null,
                'discount_amount' => 10.00,
                'use_limit_per_coupon' => null,
                'use_limit_per_user' => 1,
                'expiry_date' => now()->addMonths(6),
                'code' => 'WELCOME10',
                'code_type' => 'manual',
                'status' => true,
                'created_by' => $user->id,
            ],
            [
                'name' => 'Flash Sale',
                'type' => 'percentage',
                'minimum_spend' => 100.00,
                'maximum_spend' => 1000.00,
                'discount_amount' => 15.00,
                'use_limit_per_coupon' => 50,
                'use_limit_per_user' => 2,
                'expiry_date' => now()->addWeeks(2),
                'code' => 'FLASH15',
                'code_type' => 'manual',
                'status' => true,
                'created_by' => $user->id,
            ],
            [
                'name' => 'Auto Generated Coupon',
                'type' => 'flat',
                'minimum_spend' => null,
                'maximum_spend' => null,
                'discount_amount' => 5.00,
                'use_limit_per_coupon' => 200,
                'use_limit_per_user' => 3,
                'expiry_date' => now()->addMonths(1),
                'code' => 'AUTO5OFF',
                'code_type' => 'auto',
                'status' => false,
                'created_by' => $user->id,
            ]
        ];

        // Add more coupons for pagination testing
        $additionalCoupons = [
            [
                'name' => 'Black Friday Deal',
                'type' => 'percentage',
                'minimum_spend' => 200.00,
                'maximum_spend' => 2000.00,
                'discount_amount' => 30.00,
                'use_limit_per_coupon' => 500,
                'use_limit_per_user' => 1,
                'expiry_date' => now()->addMonths(2),
                'code' => 'BLACKFRI30',
                'code_type' => 'manual',
                'status' => true,
                'created_by' => $user->id,
            ],
            [
                'name' => 'Holiday Special',
                'type' => 'flat',
                'minimum_spend' => 75.00,
                'maximum_spend' => null,
                'discount_amount' => 25.00,
                'use_limit_per_coupon' => 300,
                'use_limit_per_user' => 2,
                'expiry_date' => now()->addMonths(4),
                'code' => 'HOLIDAY25',
                'code_type' => 'manual',
                'status' => true,
                'created_by' => $user->id,
            ],
            [
                'name' => 'Student Discount',
                'type' => 'percentage',
                'minimum_spend' => 30.00,
                'maximum_spend' => 300.00,
                'discount_amount' => 10.00,
                'use_limit_per_coupon' => null,
                'use_limit_per_user' => 5,
                'expiry_date' => now()->addMonths(12),
                'code' => 'STUDENT10',
                'code_type' => 'manual',
                'status' => true,
                'created_by' => $user->id,
            ],
            [
                'name' => 'VIP Member Bonus',
                'type' => 'flat',
                'minimum_spend' => 150.00,
                'maximum_spend' => 1500.00,
                'discount_amount' => 50.00,
                'use_limit_per_coupon' => 100,
                'use_limit_per_user' => 1,
                'expiry_date' => now()->addMonths(6),
                'code' => 'VIP50BONUS',
                'code_type' => 'manual',
                'status' => false,
                'created_by' => $user->id,
            ],
            [
                'name' => 'Weekend Sale',
                'type' => 'percentage',
                'minimum_spend' => 40.00,
                'maximum_spend' => 400.00,
                'discount_amount' => 12.00,
                'use_limit_per_coupon' => 200,
                'use_limit_per_user' => 3,
                'expiry_date' => now()->addWeeks(8),
                'code' => 'WEEKEND12',
                'code_type' => 'manual',
                'status' => true,
                'created_by' => $user->id,
            ]
        ];

        $allCoupons = array_merge($coupons, $additionalCoupons);

        foreach ($allCoupons as $couponData) {
            if (!\App\Models\Coupon::where('code', $couponData['code'])->exists()) {
                \App\Models\Coupon::create($couponData);
            }
        }

        $this->command->info('Sample coupons created successfully!');
    }
}
