<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ReceiptOrder;
use App\Models\User;
use App\Models\Account;
use App\Models\Contact;
use App\Models\PurchaseOrder;
use App\Models\ReturnOrder;
use App\Models\Product;
use Faker\Factory as Faker;

class ReceiptOrderSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $companyUsers = User::where('type', 'company')->get();
        
        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run UserSeeder first.');
            return;
        }
        
        $statuses = ['pending', 'received', 'partial', 'completed', 'cancelled'];
        
        foreach ($companyUsers as $company) {
            $accounts = Account::where('created_by', $company->id)->get();
            $contacts = Contact::where('created_by', $company->id)->get();
            $purchaseOrders = PurchaseOrder::where('created_by', $company->id)->get();
            $returnOrders = ReturnOrder::where('created_by', $company->id)->get();
            $products = Product::where('created_by', $company->id)->get();
            $staffUsers = User::where('created_by', $company->id)->get();

            if ($accounts->isEmpty() || $products->isEmpty()) {
                continue;
            }

            for ($i = 1; $i <= 15; $i++) {
                $account = $accounts->random();
                $contact = $contacts->where('account_id', $account->id)->first() ?? $contacts->random();
                
                $receiptOrder = ReceiptOrder::create([
                    'name' => 'Receipt Order ' . $i,
                    'description' => $faker->sentence(6),
                    'purchase_order_id' => $purchaseOrders->isNotEmpty() ? $purchaseOrders->random()->id : null,
                    'account_id' => $account->id,
                    'return_order_id' => $returnOrders->isNotEmpty() && $faker->boolean(30) ? $returnOrders->random()->id : null,
                    'contact_id' => $contact->id,
                    'receipt_date' => $faker->dateTimeBetween('-1 month', 'now'),
                    'expected_date' => $faker->dateTimeBetween('now', '+2 weeks'),
                    'status' => $faker->randomElement($statuses),
                    'notes' => $faker->sentence(8),
                    'shipping_amount' => $faker->randomFloat(2, 5, 50),
                    'created_by' => $company->id,
                    'assigned_to' => $staffUsers->isNotEmpty() ? $staffUsers->random()->id : null,
                    'created_at' => $faker->dateTimeBetween('-1 month', 'now'),
                ]);

                $selectedProducts = $products->random(random_int(1, 3));
                foreach ($selectedProducts as $product) {
                    $quantity = random_int(1, 8);
                    $unitPrice = $product->price;
                    $lineTotal = $quantity * $unitPrice;
                    $discountAmount = $faker->boolean(20) ? $faker->randomFloat(2, 0, $lineTotal * 0.1) : 0;
                    
                    $receiptOrder->products()->attach($product->id, [
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $lineTotal,
                        'discount_type' => $discountAmount > 0 ? 'fixed' : 'none',
                        'discount_value' => $discountAmount,
                        'discount_amount' => $discountAmount,
                    ]);
                }

                $receiptOrder->calculateTotals();
            }
        }
        
        $this->command->info('Receipt orders created for all company users!');
    }
}