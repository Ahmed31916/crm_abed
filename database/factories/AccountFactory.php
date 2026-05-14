<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'website' => $this->faker->url(),
            'industry' => $this->faker->word(),
            'annual_revenue' => $this->faker->randomFloat(2, 10000, 10000000),
            'employees' => $this->faker->numberBetween(1, 1000),
            'description' => $this->faker->paragraph(),
            'billing_address' => $this->faker->address(),
            'shipping_address' => $this->faker->address(),
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }
}