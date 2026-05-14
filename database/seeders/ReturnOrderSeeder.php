<?php

namespace Database\Seeders;

use App\Models\ReturnOrder;
use App\Models\SalesOrder;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Product;
use App\Models\ShippingProviderType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ReturnOrderSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $companyUsers = User::where('type', 'company')->get();

        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run UserSeeder first.');
            return;
        }

        $statuses = ['pending', 'approved', 'shipped', 'received', 'processed', 'cancelled'];
        $reasons = ['defective', 'wrong_item', 'damaged', 'not_needed', 'other'];

        foreach ($companyUsers as $company) {
            $salesOrders = SalesOrder::where('created_by', $company->id)->get();
            $accounts = Account::where('created_by', $company->id)->get();
            $contacts = Contact::where('created_by', $company->id)->get();
            $products = Product::where('created_by', $company->id)->get();
            $shippingTypes = ShippingProviderType::where('created_by', $company->id)->get();
            $staffUsers = User::where('created_by', $company->id)->get();

            if ($salesOrders->isEmpty() || $accounts->isEmpty() || $products->isEmpty()) {
                continue;
            }

            for ($i = 1; $i <= 15; $i++) {
                $salesOrder = $salesOrders->random();
                $account = $accounts->random();
                $contact = $contacts->where('account_id', $account->id)->first() ?? $contacts->random();

                $returnOrder = ReturnOrder::create([
                    'name' => 'Return Order ' . $i,
                    'description' => $faker->sentence(6),
                    'sales_order_id' => $salesOrder->id,
                    'account_id' => $account->id,
                    'contact_id' => $contact->id,
                    'shipping_provider_type_id' => $shippingTypes->isNotEmpty() ? $shippingTypes->random()->id : null,
                    'tracking_number' => strtoupper($faker->bothify('RET########')),
                    'status' => $faker->randomElement($statuses),
                    'reason' => $faker->randomElement($reasons),
                    'reason_description' => $faker->sentence(8),
                    'return_date' => $faker->dateTimeBetween('-1 month', '+1 month'),
                    'notes' => $faker->sentence(10),
                    'created_by' => $company->id,
                    'assigned_to' => $staffUsers->isNotEmpty() ? $staffUsers->random()->id : null,
                    'created_at' => $faker->dateTimeBetween('-1 month', 'now'),
                ]);

                $selectedProducts = $products->random(random_int(1, 3));
                foreach ($selectedProducts as $product) {
                    $quantity = random_int(1, 3);
                    $unitPrice = $product->price;
                    $totalPrice = $quantity * $unitPrice;

                    $returnOrder->products()->attach($product->id, [
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                    ]);
                }

                $returnOrder->calculateTotals();
            }
        }

        $this->command->info('Return orders created for all company users!');
    }
}
