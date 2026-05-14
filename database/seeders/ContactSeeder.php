<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ContactSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $companyUsers = User::where('type', 'company')->get();
        
        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run UserSeeder first.');
            return;
        }

        $positions = ['CEO', 'CTO', 'VP Sales', 'IT Director', 'Operations Manager', 'Marketing Director', 'Procurement Manager'];

        foreach ($companyUsers as $company) {
            $accounts = Account::where('created_by', $company->id)->get();
            $staffUsers = User::where('created_by', $company->id)->get();
            
            if ($accounts->isEmpty()) {
                continue;
            }

            for ($i = 1; $i <= 15; $i++) {
                $account = $accounts->random();
                $firstName = $faker->firstName;
                $lastName = $faker->lastName;
                
                Contact::create([
                    'name' => $firstName . ' ' . $lastName,
                    'email' => strtolower($firstName . '.' . $lastName . '.' . $company->id) . '@' . $faker->domainName,
                    'phone' => $faker->phoneNumber,
                    'position' => $faker->randomElement($positions),
                    'address' => $faker->address,
                    'status' => $faker->randomElement(['active', 'active', 'active', 'inactive']), // 75% active
                    'account_id' => $account->id,
                    'created_by' => $company->id,
                    'assigned_to' => $staffUsers->isNotEmpty() ? $staffUsers->random()->id : null,
                    'created_at' => $faker->dateTimeBetween('-6 months', 'now'),
                ]);
            }
        }
        
        $this->command->info('Contacts created for all company users!');
    }
}