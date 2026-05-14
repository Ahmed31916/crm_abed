<?php

namespace Database\Seeders;

use App\Models\Quote;
use App\Models\User;
use App\Models\Opportunity;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Product;
use App\Models\ShippingProviderType;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class QuoteSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $companyUsers = User::where('type', 'company')->get();
        
        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run UserSeeder first.');
            return;
        }

        $statuses = ['draft', 'sent', 'accepted', 'rejected', 'expired'];

        foreach ($companyUsers as $company) {
            $opportunities = Opportunity::where('created_by', $company->id)->get();
            $accounts = Account::where('created_by', $company->id)->get();
            $contacts = Contact::where('created_by', $company->id)->get();
            $products = Product::where('created_by', $company->id)->get();
            $shippingTypes = ShippingProviderType::where('created_by', $company->id)->get();
            $staffUsers = User::where('created_by', $company->id)->get();

            if ($accounts->isEmpty() || $contacts->isEmpty()) {
                continue;
            }

            for ($i = 1; $i <= 20; $i++) {
                $account = $accounts->random();
                $contact = $contacts->where('account_id', $account->id)->first() ?? $contacts->random();
                $createdDate = $faker->dateTimeBetween('-2 months', 'now');
                $validUntil = $faker->dateTimeBetween($createdDate, '+2 months');
                $subtotal = $faker->numberBetween(5000, 50000);
                $totalAmount = $subtotal + ($subtotal * 0.1); // Add 10% for taxes/fees
                
                $quote = Quote::create([
                    'quote_number' => 'QUO-' . $company->id . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                    'name' => 'Quote for ' . $account->name . ' - Project ' . $i,
                    'description' => $faker->sentence(10),
                    'opportunity_id' => $opportunities->isNotEmpty() ? $opportunities->random()->id : null,
                    'account_id' => $account->id,
                    'billing_contact_id' => $contact->id,
                    'shipping_contact_id' => $contact->id,
                    'shipping_provider_type_id' => $shippingTypes->isNotEmpty() ? $shippingTypes->random()->id : null,
                    'subtotal' => $subtotal,
                    'total_amount' => $totalAmount,
                    'billing_address' => $faker->streetAddress,
                    'billing_city' => $faker->city,
                    'billing_state' => $faker->state,
                    'billing_postal_code' => $faker->postcode,
                    'billing_country' => 'USA',
                    'shipping_address' => $faker->streetAddress,
                    'shipping_city' => $faker->city,
                    'shipping_state' => $faker->state,
                    'shipping_postal_code' => $faker->postcode,
                    'shipping_country' => 'USA',
                    'status' => $faker->randomElement($statuses),
                    'valid_until' => $validUntil,
                    'created_by' => $company->id,
                    'assigned_to' => $staffUsers->isNotEmpty() ? $staffUsers->random()->id : $company->id,
                    'created_at' => $createdDate,
                ]);

                if ($products->isNotEmpty()) {
                    $selectedProducts = $products->random(random_int(1, 3));
                    foreach ($selectedProducts as $product) {
                        $quantity = random_int(1, 10);
                        $unitPrice = $product->price;
                        $quote->products()->attach($product->id, [
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'total_price' => $quantity * $unitPrice,
                        ]);
                    }
                }
            }
        }
        
        $this->command->info('Quotes created for all company users!');
    }
}