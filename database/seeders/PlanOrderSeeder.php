<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Plan;
use App\Models\Coupon;
use App\Models\PlanOrder;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class PlanOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get required data with proper validation
        $companyUsers = User::where('type', 'company')->get();
        $plans = Plan::all();
        $activeCoupons = Coupon::where('status', true)->get();
        $superAdmin = User::where('type', 'superadmin')->first();
        
        if ($companyUsers->isEmpty() || $plans->isEmpty()) {
            $this->command->warn('No company users or plans found. Please run UserSeeder and PlanSeeder first.');
            return;
        }
        
        $statuses = ['pending', 'approved', 'rejected'];
        $billingCycles = ['monthly', 'yearly'];
        $paymentMethods = ['stripe', 'paypal', 'bank_transfer', 'credit_card'];
        
        // Create plan orders for each company user
        foreach ($companyUsers as $user) {
            // Create 1-3 orders per user
            $orderCount = random_int(1, 3);
            
            for ($i = 0; $i < $orderCount; $i++) {
                $plan = $plans->random();
                $status = $faker->randomElement($statuses);
                $billingCycle = $faker->randomElement($billingCycles);
                $coupon = ($i === 0 && $activeCoupons->isNotEmpty()) ? $activeCoupons->random() : null;
                
                // Calculate price based on billing cycle
                $planPrice = $billingCycle === 'yearly' ? $plan->yearly_price : $plan->price;
                
                $planOrder = new PlanOrder();
                $planOrder->user_id = $user->id;
                $planOrder->plan_id = $plan->id;
                $planOrder->billing_cycle = $billingCycle;
                $planOrder->payment_method = $faker->randomElement($paymentMethods);
                $planOrder->calculatePrices($planPrice, $coupon);
                $planOrder->status = $status;
                $planOrder->ordered_at = $faker->dateTimeBetween('-6 months', 'now');
                
                // Set processed details for non-pending orders
                if ($status !== 'pending') {
                    $planOrder->processed_at = $faker->dateTimeBetween($planOrder->ordered_at, 'now');
                    $planOrder->processed_by = $superAdmin?->id;
                    
                    if ($status === 'approved') {
                        $planOrder->payment_id = 'pay_' . strtoupper($faker->bothify('??########'));
                    } elseif ($status === 'rejected') {
                        $planOrder->notes = $faker->randomElement([
                            'Payment failed - insufficient funds',
                            'Invalid payment method',
                            'Fraud detection triggered',
                            'User requested cancellation'
                        ]);
                    }
                }
                
                $planOrder->save();
            }
        }
        
        $this->command->info('Plan orders created successfully!');
    }
}
