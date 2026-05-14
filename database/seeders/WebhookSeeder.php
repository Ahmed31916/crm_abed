<?php

namespace Database\Seeders;

use App\Models\Webhook;
use App\Models\User;
use Illuminate\Database\Seeder;

class WebhookSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('type', 'company')->take(3)->get();

        if ($users->isEmpty()) {
            $this->command->warn('No company users found. Please seed users first.');
            return;
        }

        $webhooks = [
            [
                'user_id' => $users->first()->id,
                'module' => 'New User',
                'method' => 'POST',
                'url' => 'https://example.com/webhooks/new-user'
            ],
            [
                'user_id' => $users->first()->id,
                'module' => 'Lead Assigned',
                'method' => 'POST',
                'url' => 'https://example.com/webhooks/lead-assigned'
            ],
            [
                'user_id' => $users->first()->id,
                'module' => 'Case Created',
                'method' => 'POST',
                'url' => 'https://example.com/webhooks/case-created'
            ],
            [
                'user_id' => $users->skip(1)->first()->id,
                'module' => 'Meeting Invitation',
                'method' => 'GET',
                'url' => 'https://company2.com/api/meeting-invitation'
            ],
            [
                'user_id' => $users->skip(1)->first()->id,
                'module' => 'Opportunity Created',
                'method' => 'POST',
                'url' => 'https://company2.com/webhooks/opportunity-created'
            ],
            [
                'user_id' => $users->last()->id,
                'module' => 'Quote Created',
                'method' => 'POST',
                'url' => 'https://company3.com/webhooks/quote-created'
            ],
            [
                'user_id' => $users->last()->id,
                'module' => 'Task Assigned',
                'method' => 'POST',
                'url' => 'https://company3.com/webhooks/task-assigned'
            ]
        ];

        foreach ($webhooks as $webhookData) {
            Webhook::create($webhookData);
        }

        $this->command->info('Webhooks seeded successfully!');
    }
}
