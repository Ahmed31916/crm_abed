<?php

namespace Database\Seeders;

use App\Models\ContactMessage;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ContactMessageSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        $subjects = [
            'Inquiry about your services',
            'Partnership opportunity',
            'Technical support needed',
            'Pricing information request',
            'Demo request',
            'Feature request',
            'General question',
            'Business proposal',
            'Integration support',
            'Account setup help'
        ];

        for ($i = 1; $i <= 10; $i++) {
            ContactMessage::create([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'subject' => $faker->randomElement($subjects),
                'message' => $faker->paragraph(3),
                'created_at' => $faker->dateTimeBetween('-3 months', 'now'),
            ]);
        }

        $this->command->info('10 contact messages created successfully!');
    }
}