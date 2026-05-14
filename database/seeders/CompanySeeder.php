<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Plan;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        // Get default plan
        $defaultPlan = Plan::where('is_default', true)->first();
        $plans = Plan::all();
        
        // Company names
        $companyNames = [
            'Acme Corporation',
            'Globex Industries',
            'Stark Enterprises',
            'Wayne Enterprises',
            'Umbrella Corporation',
            'Cyberdyne Systems',
            'Soylent Corp',
            'Initech Technologies',
            'Massive Dynamic',
            'Oscorp Industries',
            'Aperture Science',
            'Weyland-Yutani Corp',
            'Tyrell Corporation',
            'Rekall Inc',
            'Virtucon Industries'
        ];
        
        // Create company users
        foreach ($companyNames as $index => $companyName) {
            $email = strtolower(str_replace(' ', '', $companyName)) . '@example.com';
            
            // Skip if user already exists
            if (User::where('email', $email)->exists()) {
                continue;
            }
            
            // Create user
            $user = User::create([
                'name' => $companyName,
                'email' => $email,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'type' => 'company',
                'lang' => $faker->randomElement(['en', 'es', 'fr', 'de']),
                'plan_id' => $plans->random()->id,
                'referral_code' => rand(100000, 999999),
                'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
            ]);
            
            // Assign company role
            $user->assignRole('company');
            
            // Create default settings
            if (!Setting::where('user_id', $user->id)->exists()) {
                copySettingsFromSuperAdmin($user->id);
            }
        }
        
        $this->command->info('Created ' . count($companyNames) . ' company users successfully!');
    }
}