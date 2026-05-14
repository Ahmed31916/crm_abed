<?php

namespace Database\Seeders;

use App\Models\Opportunity;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Product;
use App\Models\OpportunityStage;
use App\Models\OpportunitySource;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class OpportunitySeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $companyUsers = User::where('type', 'company')->get();
        
        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run UserSeeder first.');
            return;
        }

        $amounts = [25000, 45000, 75000, 95000, 125000, 165000, 195000, 225000, 285000];

        foreach ($companyUsers as $company) {
            $accounts = Account::where('created_by', $company->id)->get();
            $contacts = Contact::where('created_by', $company->id)->get();
            $products = Product::where('created_by', $company->id)->get();
            $opportunityStages = OpportunityStage::where('created_by', $company->id)->get();
            $opportunitySources = OpportunitySource::where('created_by', $company->id)->get();
            $staffUsers = User::where('created_by', $company->id)->get();
            
            if ($accounts->isEmpty() || $opportunityStages->isEmpty() || $opportunitySources->isEmpty()) {
                continue;
            }

            for ($i = 1; $i <= 12; $i++) {
                $account = $accounts->random();
                $contact = $contacts->where('account_id', $account->id)->first() ?? $contacts->random();
                $createdDate = $faker->dateTimeBetween('-3 months', 'now');
                $closeDate = $faker->dateTimeBetween($createdDate, '+4 months');
                
                $opportunity = Opportunity::create([
                    'name' => $account->name . ' - ' . $faker->words(2, true) . ' Project',
                    'description' => 'Strategic business opportunity for ' . $account->name,
                    'amount' => $faker->randomElement($amounts),
                    'close_date' => $closeDate,
                    'notes' => $faker->sentence(10),
                    'status' => 'active',
                    'account_id' => $account->id,
                    'contact_id' => $contact?->id,
                    'opportunity_stage_id' => $opportunityStages->random()->id,
                    'opportunity_source_id' => $opportunitySources->random()->id,
                    'created_by' => $company->id,
                    'assigned_to' => $staffUsers->isNotEmpty() ? $staffUsers->random()->id : null,
                    'created_at' => $createdDate,
                ]);

                if ($products->isNotEmpty()) {
                    $selectedProducts = $products->random(random_int(1, 2));
                    foreach ($selectedProducts as $product) {
                        $quantity = random_int(5, 50);
                        $unitPrice = $product->price;
                        $opportunity->products()->attach($product->id, [
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'total_price' => $quantity * $unitPrice,
                        ]);
                    }
                }
            }
        }
        
        $this->command->info('Opportunities created for all company users!');
    }
}