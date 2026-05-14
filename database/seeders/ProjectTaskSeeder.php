<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProjectTask;
use App\Models\Project;
use App\Models\User;
use App\Models\TaskStatus;

class ProjectTaskSeeder extends Seeder
{
    public function run(): void
    {
        $companyUsers = User::where('type', 'company')->get();
        
        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run UserSeeder first.');
            return;
        }

        $tasks = [
            [
                'title' => 'Project Requirements Gathering',
                'description' => 'Conduct stakeholder interviews and document detailed project requirements including user roles, workflows, and integration needs',
                'priority' => 'high',
                'status' => 'done',
                'estimated_hours' => 24.00,
                'actual_hours' => 26.50,
                'progress' => 100,
            ],
            [
                'title' => 'Technical Architecture Design',
                'description' => 'Design system architecture, database schema, and technical specifications for the project',
                'priority' => 'high',
                'status' => 'done',
                'estimated_hours' => 32.00,
                'actual_hours' => 28.00,
                'progress' => 100,
            ],
            [
                'title' => 'Frontend Development',
                'description' => 'Develop user interface components, responsive design, and user experience features',
                'priority' => 'high',
                'status' => 'in_progress',
                'estimated_hours' => 48.00,
                'actual_hours' => 32.00,
                'progress' => 70,
            ],
            [
                'title' => 'Backend API Development',
                'description' => 'Develop REST APIs, database models, and business logic implementation',
                'priority' => 'high',
                'status' => 'in_progress',
                'estimated_hours' => 80.00,
                'actual_hours' => 45.00,
                'progress' => 55,
            ],
            [
                'title' => 'Database Setup and Configuration',
                'description' => 'Set up database servers, configure schemas, and implement data security measures',
                'priority' => 'medium',
                'status' => 'done',
                'estimated_hours' => 16.00,
                'actual_hours' => 18.00,
                'progress' => 100,
            ],
            [
                'title' => 'Third-party Integration',
                'description' => 'Integrate with external APIs, payment gateways, and third-party services',
                'priority' => 'medium',
                'status' => 'in_progress',
                'estimated_hours' => 40.00,
                'actual_hours' => 25.00,
                'progress' => 60,
            ],
            [
                'title' => 'Security Implementation',
                'description' => 'Implement authentication, authorization, data encryption, and security best practices',
                'priority' => 'high',
                'status' => 'to_do',
                'estimated_hours' => 32.00,
                'progress' => 0,
            ],
            [
                'title' => 'Performance Optimization',
                'description' => 'Optimize application performance, database queries, and implement caching strategies',
                'priority' => 'medium',
                'status' => 'to_do',
                'estimated_hours' => 24.00,
                'progress' => 0,
            ],
            [
                'title' => 'Unit Testing',
                'description' => 'Write comprehensive unit tests for all components and business logic',
                'priority' => 'medium',
                'status' => 'in_progress',
                'estimated_hours' => 36.00,
                'actual_hours' => 20.00,
                'progress' => 40,
            ],
            [
                'title' => 'Integration Testing',
                'description' => 'Test system integrations, API endpoints, and data flow between components',
                'priority' => 'high',
                'status' => 'to_do',
                'estimated_hours' => 28.00,
                'progress' => 0,
            ],
            [
                'title' => 'User Training Program',
                'description' => 'Develop training materials and conduct user training sessions for all system features',
                'priority' => 'medium',
                'status' => 'to_do',
                'estimated_hours' => 40.00,
                'progress' => 0,
            ],
            [
                'title' => 'Documentation Creation',
                'description' => 'Create technical documentation, user manuals, and system administration guides',
                'priority' => 'low',
                'status' => 'to_do',
                'estimated_hours' => 20.00,
                'progress' => 0,
            ],
            [
                'title' => 'User Acceptance Testing',
                'description' => 'Coordinate UAT with key stakeholders, document feedback, and implement necessary adjustments',
                'priority' => 'high',
                'status' => 'to_do',
                'estimated_hours' => 32.00,
                'progress' => 0,
            ],
            [
                'title' => 'Deployment Preparation',
                'description' => 'Prepare production environment, deployment scripts, and rollback procedures',
                'priority' => 'high',
                'status' => 'to_do',
                'estimated_hours' => 16.00,
                'progress' => 0,
            ],
            [
                'title' => 'Go-Live Support',
                'description' => 'Provide on-site support during system go-live and immediate post-launch period',
                'priority' => 'urgent',
                'status' => 'to_do',
                'estimated_hours' => 24.00,
                'progress' => 0,
            ],
        ];

        foreach ($companyUsers as $company) {
            $projects = Project::where('created_by', $company->id)->get();
            $staffUsers = User::where('created_by', $company->id)->get();
            $taskStatuses = TaskStatus::where('created_by', $company->id)->pluck('id', 'name')->toArray();
            
            if ($projects->isEmpty() || empty($taskStatuses)) {
                continue;
            }
            
            $statusMapping = [
                'to_do' => $taskStatuses['To Do'] ?? null,
                'in_progress' => $taskStatuses['In Progress'] ?? null,
                'review' => $taskStatuses['Review'] ?? null,
                'done' => $taskStatuses['Done'] ?? null,
            ];
            
            foreach ($projects->take(3) as $project) {
                foreach ($tasks as $index => $taskData) {
                    $task = ProjectTask::create([
                        'title' => $taskData['title'],
                        'description' => $taskData['description'],
                        'project_id' => $project->id,
                        'assigned_to' => $staffUsers->isNotEmpty() ? $staffUsers->random()->id : null,
                        'start_date' => now()->addDays($index * 2),
                        'due_date' => now()->addDays(($index * 2) + 7),
                        'priority' => $taskData['priority'],
                        'task_status_id' => $statusMapping[$taskData['status']],
                        'estimated_hours' => $taskData['estimated_hours'],
                        'actual_hours' => $taskData['actual_hours'] ?? null,
                        'progress' => $taskData['progress'],
                        'created_by' => $company->id,
                    ]);

                // Create subtasks for some main tasks
                if (in_array($index, [2, 3, 4])) {
                    ProjectTask::create([
                        'title' => 'Subtask: ' . $taskData['title'] . ' - Phase 1',
                        'description' => 'First phase of ' . strtolower($taskData['title']),
                        'project_id' => $project->id,
                        'parent_id' => $task->id,
                        'assigned_to' => $staffUsers->isNotEmpty() ? $staffUsers->random()->id : null,
                        'start_date' => $task->start_date,
                        'due_date' => $task->start_date->addDays(3),
                        'priority' => $taskData['priority'],
                        'task_status_id' => $statusMapping[$taskData['status']],
                        'estimated_hours' => $taskData['estimated_hours'] / 2,
                        'actual_hours' => isset($taskData['actual_hours']) ? $taskData['actual_hours'] / 2 : null,
                        'progress' => $taskData['progress'],
                        'created_by' => $company->id,
                    ]);
                }
                }
            }
        }
        
        $this->command->info('Project tasks created for all company users!');
    }
}