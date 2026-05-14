<?php

namespace Database\Seeders;

use App\Models\CaseModel;
use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class CaseSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $companyUsers = User::where('type', 'company')->get();
        
        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run UserSeeder first.');
            return;
        }

        $priorities = ['low', 'medium', 'high', 'urgent'];
        $statuses = ['new', 'in_progress', 'pending', 'resolved', 'closed'];
        $caseTypes = ['support', 'bug', 'feature_request', 'complaint', 'inquiry'];
        
        foreach ($companyUsers as $company) {
            $accounts = Account::where('created_by', $company->id)->get();
            $contacts = Contact::where('created_by', $company->id)->get();
            $staffUsers = User::where('created_by', $company->id)->get();

            if ($accounts->isEmpty()) {
                continue;
            }

            $caseSubjects = [
                'Login Issues with CRM System',
                'Data Export Not Working Properly',
                'Integration Error with Email Platform',
                'Report Generation Taking Too Long',
                'User Permission Settings Need Update',
                'Dashboard Loading Performance Issues',
                'Mobile App Sync Problems',
                'Invoice Template Customization Request',
                'Payment Gateway Connection Failed',
                'Email Notifications Not Sending',
                'Database Connection Timeout Errors',
                'API Rate Limit Exceeded',
                'File Upload Size Limit Issue',
                'Two-Factor Authentication Problems',
                'Bulk Import Data Validation Errors'
            ];
            
            $caseDescriptions = [
                'Customer is experiencing difficulties accessing their account and needs immediate assistance with login credentials.',
                'The data export feature is not functioning correctly and returns incomplete results when generating reports.',
                'Integration with the email marketing platform is failing and causing synchronization errors.',
                'Report generation is taking an unusually long time to complete, affecting daily operations.',
                'User permission settings need to be updated to provide appropriate access levels for new team members.',
                'Dashboard is loading slowly and sometimes fails to display all widgets properly.',
                'Mobile application is not syncing data correctly with the web platform, causing data inconsistencies.',
                'Customer requests customization of invoice templates to match their brand guidelines and requirements.',
                'Payment processing is failing due to gateway connection issues, preventing customers from completing transactions.',
                'System email notifications are not being sent to users, affecting communication and workflow processes.',
                'Database connection is timing out frequently, causing application errors and data access issues.',
                'API requests are being rejected due to rate limit exceeded, affecting third-party integrations.',
                'File upload functionality is rejecting files due to size limit restrictions, impacting user productivity.',
                'Two-factor authentication system is not working properly, preventing secure access to user accounts.',
                'Bulk data import process is failing validation checks, preventing large dataset uploads from completing.'
            ];

            for ($i = 0; $i < 15; $i++) {
                $account = $accounts->random();
                $contact = $contacts->where('account_id', $account->id)->first() ?? $contacts->random();
                $createdDate = $faker->dateTimeBetween('-3 months', 'now');

                CaseModel::create([
                    'subject' => $caseSubjects[$i],
                    'description' => $caseDescriptions[$i],
                    'priority' => $faker->randomElement($priorities),
                    'status' => $faker->randomElement($statuses),
                    'case_type' => $faker->randomElement($caseTypes),
                    'account_id' => $account->id,
                    'contact_id' => $contact?->id,
                    'created_by' => $company->id,
                    'assigned_to' => $staffUsers->isNotEmpty() ? $staffUsers->random()->id : null,
                    'created_at' => $createdDate,
                ]);
            }
        }
        
        $this->command->info('Cases created for all company users!');
    }
}