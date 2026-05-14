<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Document;
use App\Models\User;
use App\Models\Account;
use App\Models\DocumentFolder;
use App\Models\DocumentType;
use App\Models\Opportunity;
use Faker\Factory as Faker;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $companyUsers = User::where('type', 'company')->get();
        
        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run UserSeeder first.');
            return;
        }

        $documentNames = [
            'Sales Agreement Template',
            'Product Specification Document',
            'Client Onboarding Checklist',
            'Marketing Campaign Report',
            'Financial Analysis Report',
            'Project Implementation Plan',
            'Employee Handbook',
            'Privacy Policy Document',
            'Terms of Service Agreement',
            'Quality Assurance Manual',
            'Training Materials Guide',
            'Compliance Audit Report',
            'Risk Assessment Document',
            'Business Continuity Plan',
            'Customer Feedback Analysis'
        ];
        
        $documentDescriptions = [
            'Standard sales agreement template for new client contracts',
            'Detailed product specifications and technical requirements',
            'Comprehensive checklist for client onboarding process',
            'Monthly marketing campaign performance analysis',
            'Quarterly financial analysis and budget review',
            'Step-by-step project implementation and timeline',
            'Complete employee handbook with policies and procedures',
            'Privacy policy outlining data collection and usage practices',
            'Terms of service agreement for platform usage',
            'Quality assurance procedures and testing protocols',
            'Comprehensive training materials for new staff members',
            'Annual compliance audit findings and recommendations',
            'Risk assessment analysis for business operations',
            'Business continuity and disaster recovery procedures',
            'Customer feedback analysis and improvement recommendations'
        ];

        foreach ($companyUsers as $company) {
            $accounts = Account::where('created_by', $company->id)->get();
            $folders = DocumentFolder::where('created_by', $company->id)->get();
            $types = DocumentType::all();
            $opportunities = Opportunity::where('created_by', $company->id)->get();
            $staffUsers = User::where('created_by', $company->id)->get();

            for ($i = 0; $i < 15; $i++) {
                Document::create([
                    'name' => $documentNames[$i],
                    'account_id' => $accounts->isNotEmpty() && $faker->boolean(60) ? $accounts->random()->id : null,
                    'folder_id' => $folders->isNotEmpty() ? $folders->random()->id : null,
                    'type_id' => $types->isNotEmpty() ? $types->random()->id : null,
                    'opportunity_id' => $opportunities->isNotEmpty() && $faker->boolean(30) ? $opportunities->random()->id : null,
                    'status' => 'active',
                    'publish_date' => $faker->dateTimeBetween('-2 months', 'now'),
                    'expiration_date' => $faker->boolean(70) ? $faker->dateTimeBetween('now', '+6 months') : null,
                    'description' => $documentDescriptions[$i],
                    'created_by' => $company->id,
                    'assigned_to' => $staffUsers->isNotEmpty() ? $staffUsers->random()->id : null,
                    'created_at' => $faker->dateTimeBetween('-1 month', 'now'),
                ]);
            }
        }
        
        $this->command->info('Documents created for all company users!');
    }
}