<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CaseModel>
 */
class CaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $priorities = ['low', 'medium', 'high', 'urgent'];
        $statuses = ['new', 'in_progress', 'pending', 'resolved', 'closed'];
        $caseTypes = ['support', 'bug', 'feature_request', 'complaint', 'inquiry'];

        return [
            'subject' => fake()->sentence(4),
            'description' => fake()->paragraph(3),
            'priority' => fake()->randomElement($priorities),
            'status' => fake()->randomElement($statuses),
            'case_type' => fake()->randomElement($caseTypes),
            'account_id' => Account::factory(),
            'contact_id' => Contact::factory(),
            'created_by' => User::factory(),
            'assigned_to' => fake()->boolean(70) ? User::factory() : null,
        ];
    }
}