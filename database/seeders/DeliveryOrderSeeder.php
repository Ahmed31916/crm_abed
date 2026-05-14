<?php

namespace Database\Seeders;

use App\Models\DeliveryOrder;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\ShippingProviderType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class DeliveryOrderSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $companyUsers = User::where('type', 'company')->get();
        
        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run UserSeeder first.');
            return;
        }
        
        $statuses = ['pending', 'in_transit', 'delivered', 'cancelled'];
        
        foreach ($companyUsers as $company) {
            $accounts = Account::where('created_by', $company->id)->get();
            $contacts = Contact::where('created_by', $company->id)->get();
            $products = Product::where('created_by', $company->id)->get();
            $salesOrders = SalesOrder::where('created_by', $company->id)->get();
            $shippingTypes = ShippingProviderType::where('created_by', $company->id)->get();
            $staffUsers = User::where('created_by', $company->id)->get();
            
            if ($accounts->isEmpty() || $contacts->isEmpty() || $products->isEmpty()) {
                continue;
            }
            
            for ($i = 1; $i <= 15; $i++) {
                $account = $accounts->random();
                $contact = $contacts->where('account_id', $account->id)->first() ?? $contacts->random();
                $deliveryDate = $faker->dateTimeBetween('-1 month', '+1 month');
                $expectedDeliveryDate = $faker->dateTimeBetween('now', '+2 weeks');
                
                $deliveryOrder = DeliveryOrder::create([
                    'name' => 'Delivery ' . $i,
                    'description' => $faker->sentence(5),
                    'sales_order_id' => $salesOrders->isNotEmpty() ? $salesOrders->random()->id : null,
                    'account_id' => $account->id,
                    'contact_id' => $contact->id,
                    'shipping_provider_type_id' => $shippingTypes->isNotEmpty() ? $shippingTypes->random()->id : null,
                    'delivery_address' => $faker->streetAddress,
                    'delivery_city' => $faker->city,
                    'delivery_state' => $faker->state,
                    'delivery_postal_code' => $faker->postcode,
                    'delivery_country' => 'USA',
                    'delivery_date' => $deliveryDate,
                    'expected_delivery_date' => $expectedDeliveryDate,
                    'status' => $faker->randomElement($statuses),
                    'tracking_number' => strtoupper($faker->bothify('??########')),
                    'delivery_notes' => $faker->sentence(8),
                    'shipping_cost' => $faker->randomFloat(2, 10, 100),
                    'created_by' => $company->id,
                    'assigned_to' => $staffUsers->isNotEmpty() ? $staffUsers->random()->id : null,
                    'created_at' => $faker->dateTimeBetween('-1 month', 'now'),
                ]);
                
                $selectedProducts = $products->random(random_int(1, 3));
                foreach ($selectedProducts as $product) {
                    $quantity = random_int(1, 5);
                    $unitWeight = $faker->randomFloat(2, 0.5, 10);
                    $totalWeight = $quantity * $unitWeight;
                    
                    $deliveryOrder->products()->attach($product->id, [
                        'quantity' => $quantity,
                        'unit_weight' => $unitWeight,
                        'total_weight' => $totalWeight,
                    ]);
                }
                
                $deliveryOrder->calculateTotalWeight();
            }
        }
        
        $this->command->info('Delivery orders created for all company users!');
    }
}