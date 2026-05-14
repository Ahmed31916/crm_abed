<?php

namespace Database\Seeders;

use App\Models\Call;
use App\Models\CallAttendee;
use App\Models\User;
use App\Models\Lead;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\CaseModel;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class CallSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $companyUsers = User::where('type', 'company')->get();

        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run UserSeeder first.');
            return;
        }

        $statuses = ['planned', 'held', 'not_held'];
        $modules = ['lead', 'account', 'contact', 'opportunity', 'case'];

        // Fetch all data once to avoid N+1 queries
        $allStaffUsers = User::whereIn('created_by', $companyUsers->pluck('id'))->get()->groupBy('created_by');
        $allLeads = Lead::whereIn('created_by', $companyUsers->pluck('id'))->get()->groupBy('created_by');
        $allAccounts = Account::whereIn('created_by', $companyUsers->pluck('id'))->get()->groupBy('created_by');
        $allContacts = Contact::whereIn('created_by', $companyUsers->pluck('id'))->get()->groupBy('created_by');
        $allOpportunities = Opportunity::whereIn('created_by', $companyUsers->pluck('id'))->get()->groupBy('created_by');
        $allCases = CaseModel::whereIn('created_by', $companyUsers->pluck('id'))->get()->groupBy('created_by');

        foreach ($companyUsers as $company) {
            $staffUsers = $allStaffUsers->get($company->id, collect());
            $parentCollections = [
                'lead' => $allLeads->get($company->id, collect()),
                'account' => $allAccounts->get($company->id, collect()),
                'contact' => $allContacts->get($company->id, collect()),
                'opportunity' => $allOpportunities->get($company->id, collect()),
                'case' => $allCases->get($company->id, collect()),
            ];

            $callTitles = [
                'Initial Sales Inquiry Call',
                'Product Demo Follow-up',
                'Contract Terms Discussion',
                'Technical Support Call',
                'Customer Onboarding Check-in',
                'Quarterly Account Review',
                'Lead Qualification Call',
                'Pricing Negotiation Discussion',
                'Project Status Update Call',
                'Customer Feedback Session',
                'Renewal Discussion Call',
                'Complaint Resolution Call',
                'Partnership Opportunity Call',
                'Training Session Call',
                'Emergency Support Call'
            ];

            $callDescriptions = [
                'Initial contact with potential customer to understand their needs and introduce our solutions',
                'Follow-up call after product demonstration to address questions and discuss next steps',
                'Detailed discussion about contract terms, service levels, and implementation timeline',
                'Technical support call to resolve system issues and provide troubleshooting assistance',
                'Check-in call with new customer to ensure smooth onboarding and address any concerns',
                'Quarterly business review call to discuss account performance and future opportunities',
                'Qualification call to assess lead potential and determine fit for our products and services',
                'Negotiation call to discuss pricing options, discounts, and payment terms with prospect',
                'Regular project update call to review progress, milestones, and address any blockers',
                'Customer feedback collection call to gather insights on product satisfaction and improvements',
                'Contract renewal discussion to review current services and explore expansion opportunities',
                'Customer service call to address complaints and work towards satisfactory resolution',
                'Exploratory call to discuss potential partnership opportunities and mutual benefits',
                'Training call to educate users on new features, best practices, and system optimization',
                'Urgent support call to address critical system issues requiring immediate attention'
            ];

            for ($i = 0; $i < 15; $i++) {
                $startDate = $faker->dateTimeBetween('-1 month', '+2 years');

                // $startDate = $faker->dateTimeBetween('-1 month', '+1 month');
                $endDate = $startDate;
                $startTime = $faker->time('H:i', '17:00');
                $endTime = date('H:i', strtotime($startTime) + random_int(900, 3600));

                $parentModule = $faker->boolean(80) ? $faker->randomElement($modules) : null;
                $parentId = null;

                if ($parentModule && isset($parentCollections[$parentModule])) {
                    $collection = $parentCollections[$parentModule];
                    $parentId = $collection->isNotEmpty() ? $collection->random()->id : null;
                }

                $call = Call::create([
                    'title' => $callTitles[$i],
                    'description' => $callDescriptions[$i],
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'parent_module' => $parentModule,
                    'parent_id' => $parentId,
                    'status' => $faker->randomElement($statuses),
                    'created_by' => $company->id,
                    'assigned_to' => $staffUsers->isNotEmpty() ? $staffUsers->random()->id : null,
                    'created_at' => $faker->dateTimeBetween('-1 month', 'now'),
                ]);

                // Add attendees
                if ($staffUsers->isNotEmpty()) {
                    CallAttendee::create([
                        'call_id' => $call->id,
                        'attendee_type' => 'user',
                        'attendee_id' => $staffUsers->random()->id,
                    ]);
                }

                $contacts = $parentCollections['contact'];
                if ($contacts->isNotEmpty() && $faker->boolean(60)) {
                    CallAttendee::create([
                        'call_id' => $call->id,
                        'attendee_type' => 'contact',
                        'attendee_id' => $contacts->random()->id,
                    ]);
                }

                $leads = $parentCollections['lead'];
                if ($leads->isNotEmpty() && $faker->boolean(40)) {
                    CallAttendee::create([
                        'call_id' => $call->id,
                        'attendee_type' => 'lead',
                        'attendee_id' => $leads->random()->id,
                    ]);
                }
            }
        }

        $this->command->info('Calls created for all company users!');
    }
}
