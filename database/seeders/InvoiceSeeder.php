<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Quote;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $companyUsers = User::where('type', 'company')->get();

        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run UserSeeder first.');
            return;
        }

        $statuses = ['draft', 'sent', 'pending', 'paid', 'partially_paid', 'overdue', 'cancelled'];
        $paymentMethods = ['stripe', 'paypal', 'bank_transfer'];

        foreach ($companyUsers as $company) {
            $accounts = Account::where('created_by', $company->id)->get();
            $contacts = Contact::where('created_by', $company->id)->get();
            $products = Product::where('created_by', $company->id)->get();
            $salesOrders = SalesOrder::where('created_by', $company->id)->get();
            $quotes = Quote::where('created_by', $company->id)->get();
            $opportunities = Opportunity::where('created_by', $company->id)->get();
            $staffUsers = User::where('created_by', $company->id)->get();

            if ($accounts->isEmpty() || $contacts->isEmpty() || $products->isEmpty()) {
                continue;
            }

            for ($i = 1; $i <= 15; $i++) {
                $account = $accounts->random();
                $contact = $contacts->where('account_id', $account->id)->first() ?? $contacts->random();
                $invoiceDate = $faker->dateTimeBetween('-2 months', 'now');
                $dueDate = $faker->dateTimeBetween($invoiceDate, '+1 month');

                $invoice = Invoice::create([
                    'name' => 'Invoice ' . $i,
                    'description' => $faker->sentence(6),
                    'sales_order_id' => $salesOrders->isNotEmpty() ? $salesOrders->random()->id : null,
                    'quote_id' => $quotes->isNotEmpty() ? $quotes->random()->id : null,
                    'opportunity_id' => $opportunities->isNotEmpty() ? $opportunities->random()->id : null,
                    'account_id' => $account->id,
                    'contact_id' => $contact->id,
                    'invoice_date' => $invoiceDate,
                    'due_date' => $dueDate,
                    'status' => $faker->randomElement($statuses),
                    'billing_address' => $faker->streetAddress,
                    'billing_city' => $faker->city,
                    'billing_state' => $faker->state,
                    'billing_postal_code' => $faker->postcode,
                    'billing_country' => 'USA',
                    'notes' => $faker->sentence(8),
                    'terms' => 'Payment due within 30 days',
                    'payment_method' => $faker->randomElement($paymentMethods),
                    'created_by' => $company->id,
                    'assigned_to' => $staffUsers->isNotEmpty() ? $staffUsers->random()->id : null,
                    'created_at' => $invoiceDate,
                ]);

                $selectedProducts = $products->random(random_int(1, 3));
                foreach ($selectedProducts as $product) {
                    $quantity = random_int(1, 8);
                    $unitPrice = $product->price;
                    $totalPrice = $quantity * $unitPrice;

                    $invoice->products()->attach($product->id, [
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                        'discount_type' => 'none',
                        'discount_value' => 0,
                        'discount_amount' => 0,
                    ]);
                }

                $invoice->calculateTotals();
            }
        }

        $this->command->info('Invoices created for all company users!');
    }
}
