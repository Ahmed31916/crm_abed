<?php

namespace Database\Seeders;

use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskStatusSeeder extends Seeder
{
    public function run(): void
    {
        $companyUsers = User::where('type', 'company')->get();

        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run UserSeeder first.');
            return;
        }

        $taskStatusesData = [
            ['name' => 'To Do', 'color' => '#6B7280', 'description' => 'Tasks that are ready to be started'],
            ['name' => 'In Progress', 'color' => '#3B82F6', 'description' => 'Tasks currently being worked on'],
            ['name' => 'Review', 'color' => '#F59E0B', 'description' => 'Tasks pending review or approval'],
            ['name' => 'Done', 'color' => '#10b77f', 'description' => 'Completed tasks'],
        ];

        foreach ($companyUsers as $company) {
            foreach ($taskStatusesData as $taskStatusData) {
                TaskStatus::create([
                    'name' => $taskStatusData['name'],
                    'color' => $taskStatusData['color'],
                    'description' => $taskStatusData['description'],
                    'status' => 'active',
                    'created_by' => $company->id,
                ]);
            }
        }

        $this->command->info('Task statuses created for all company users!');
    }
}
