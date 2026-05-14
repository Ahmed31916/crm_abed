<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $companyUsers = User::where('type', 'company')->get();
        
        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run UserSeeder first.');
            return;
        }

        $projectTypes = [
            'CRM Implementation', 'System Integration', 'Data Migration', 'Process Automation', 'Custom Development',
            'Website Redesign', 'Mobile App Development', 'Cloud Migration', 'Security Audit', 'Database Optimization',
            'API Development', 'E-commerce Platform', 'Business Intelligence', 'Digital Transformation', 'Infrastructure Upgrade'
        ];
        $statuses = ['active', 'inactive', 'completed', 'on_hold'];
        $priorities = ['low', 'medium', 'high', 'urgent'];
        
        foreach ($companyUsers as $company) {
            $accounts = Account::where('created_by', $company->id)->get();
            $staffUsers = User::where('created_by', $company->id)->get();

            if ($accounts->isEmpty()) {
                continue;
            }

            for ($i = 1; $i <= 15; $i++) {
                $startDate = $faker->dateTimeBetween('-4 months', 'now');
                $endDate = $faker->dateTimeBetween($startDate, '+6 months');
                $status = $faker->randomElement($statuses);
                
                if ($status === 'completed') {
                    $endDate = $faker->dateTimeBetween('-2 months', 'now');
                    $startDate = $faker->dateTimeBetween('-6 months', $endDate);
                }
                
                Project::create([
                    'name' => 'Project ' . $i . ' - ' . $faker->randomElement($projectTypes),
                    'code' => 'PRJ-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'description' => $faker->sentence(12),
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'budget' => $faker->randomFloat(2, 15000, 250000),
                    'priority' => $faker->randomElement($priorities),
                    'status' => $status,
                    'account_id' => $accounts->random()->id,
                    'created_by' => $company->id,
                    'assigned_to' => $staffUsers->isNotEmpty() ? $staffUsers->random()->id : null,
                    'created_at' => $startDate,
                ]);
            }
        }
        
        $this->command->info('Projects created for all company users!');
    }
}