<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeadActivity;
use App\Models\Lead;
use App\Models\User;
use Faker\Factory as Faker;

class LeadActivitySeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $companyUsers = User::where('type', 'company')->get();
        
        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run UserSeeder first.');
            return;
        }

        $comments = [
            'This lead looks promising. Following up next week.',
            'Had a great conversation with the prospect.',
            'Sent proposal via email. Awaiting response.',
            'Lead is interested but needs budget approval.',
            'Scheduled demo for next Tuesday.',
            'Competitor comparison requested.',
            'Decision maker identified. Moving forward.'
        ];

        foreach ($companyUsers as $company) {
            $leads = Lead::where('created_by', $company->id)->get();
            $users = User::where('created_by', $company->id)->orWhere('id', $company->id)->get();
            
            if ($leads->isEmpty() || $users->isEmpty()) {
                continue;
            }

            foreach ($leads as $lead) {
                // Created activity
                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'user_id' => $lead->created_by,
                    'activity_type' => 'created',
                    'title' => $users->find($lead->created_by)->name . ' created this lead',
                    'description' => 'New lead created',
                    'new_values' => ['status' => $lead->status],
                    'created_by' => $lead->created_by,
                    'created_at' => $lead->created_at,
                ]);

                // Status update activity
                if ($lead->lead_status) {
                    LeadActivity::create([
                        'lead_id' => $lead->id,
                        'user_id' => $users->random()->id,
                        'activity_type' => 'updated',
                        'title' => $users->random()->name . ' updated lead status',
                        'description' => $lead->lead_status->name,
                        'field_changed' => 'lead_status_id',
                        'old_values' => ['lead_status_id' => 1],
                        'new_values' => [
                            'lead_status_id' => $lead->lead_status_id,
                            'lead_status_color' => $lead->lead_status->color
                        ],
                        'created_by' => $lead->created_by,
                        'created_at' => $faker->dateTimeBetween($lead->created_at, 'now'),
                    ]);
                }

                // Comment activity
                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'user_id' => $users->random()->id,
                    'activity_type' => 'comment',
                    'title' => $users->random()->name . ' added a comment',
                    'description' => $faker->randomElement($comments),
                    'new_values' => ['comment' => $faker->randomElement($comments)],
                    'created_by' => $lead->created_by,
                    'created_at' => $faker->dateTimeBetween($lead->created_at, 'now'),
                ]);
            }
        }
        
        $this->command->info('Lead activities created for all company users!');
    }
}