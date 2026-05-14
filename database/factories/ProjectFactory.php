<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'code' => 'PRJ-' . $this->faker->unique()->numberBetween(1000, 9999),
            'description' => $this->faker->paragraph(),
            'start_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'end_date' => $this->faker->dateTimeBetween('now', '+6 months'),
            'budget' => $this->faker->randomFloat(2, 1000, 100000),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'status' => $this->faker->randomElement(['active', 'inactive', 'completed', 'on_hold']),
            'account_id' => Account::factory(),
            'created_by' => User::factory(),
            'assigned_to' => User::factory(),
        ];
    }
}