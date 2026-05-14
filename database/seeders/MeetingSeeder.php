<?php

namespace Database\Seeders;

use App\Models\Meeting;
use App\Models\MeetingAttendee;
use App\Models\User;
use App\Models\Lead;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Opportunity;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class MeetingSeeder extends Seeder
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
        $modules = ['lead', 'account', 'contact', 'opportunity'];

        // Fetch all data once to avoid N+1 queries
        $allStaffUsers = User::whereIn('created_by', $companyUsers->pluck('id'))->get()->groupBy('created_by');
        $allLeads = Lead::whereIn('created_by', $companyUsers->pluck('id'))->get()->groupBy('created_by');
        $allAccounts = Account::whereIn('created_by', $companyUsers->pluck('id'))->get()->groupBy('created_by');
        $allContacts = Contact::whereIn('created_by', $companyUsers->pluck('id'))->get()->groupBy('created_by');
        $allOpportunities = Opportunity::whereIn('created_by', $companyUsers->pluck('id'))->get()->groupBy('created_by');

        foreach ($companyUsers as $company) {
            $staffUsers = $allStaffUsers->get($company->id, collect());
            $parentCollections = [
                'lead' => $allLeads->get($company->id, collect()),
                'account' => $allAccounts->get($company->id, collect()),
                'contact' => $allContacts->get($company->id, collect()),
                'opportunity' => $allOpportunities->get($company->id, collect()),
            ];

            $meetingTitles = [
                'Product Demo Session',
                'Quarterly Business Review',
                'Project Kickoff Meeting',
                'Sales Strategy Discussion',
                'Client Onboarding Call',
                'Technical Requirements Review',
                'Contract Negotiation Meeting',
                'Team Performance Review',
                'Budget Planning Session',
                'Market Analysis Presentation',
                'Customer Feedback Review',
                'Partnership Discussion',
                'Training Workshop',
                'System Implementation Planning',
                'Annual Planning Meeting'
            ];

            $meetingDescriptions = [
                'Comprehensive product demonstration showcasing key features and benefits to potential clients',
                'Quarterly review of business performance, goals achievement, and strategic planning for next quarter',
                'Project initiation meeting to discuss scope, timeline, resources, and deliverables with stakeholders',
                'Strategic discussion on sales approaches, target markets, and revenue optimization strategies',
                'Welcome and orientation session for new clients to ensure smooth onboarding process',
                'Technical review meeting to gather and validate system requirements and specifications',
                'Negotiation session to discuss contract terms, pricing, and service level agreements',
                'Performance evaluation meeting to review team achievements and set future objectives',
                'Financial planning session to review budget allocations and forecast future expenses',
                'Market research presentation covering industry trends, competitor analysis, and opportunities',
                'Review session to analyze customer feedback and implement improvement strategies',
                'Business partnership discussion to explore collaboration opportunities and mutual benefits',
                'Educational workshop to enhance team skills and knowledge in specific areas',
                'Planning meeting for system implementation including timeline, resources, and risk assessment',
                'Annual strategic planning session to set long-term goals and organizational direction'
            ];

            $locations = [
                'Main Conference Room',
                'Virtual - Zoom',
                'Virtual - Microsoft Teams',
                'Client Office - Downtown',
                'Executive Boardroom',
                'Virtual - Google Meet',
                'Training Room A',
                'Client Site Visit',
                'Coffee Shop Meeting',
                'Hotel Conference Center',
                'Co-working Space',
                'Virtual - WebEx',
                'Outdoor Terrace',
                'Restaurant Meeting',
                'Home Office - Remote'
            ];

            for ($i = 0; $i < 15; $i++) {
                $startDate = $faker->dateTimeBetween('-1 month', '+2 years');
                // $startDate = $faker->dateTimeBetween('-1 month', '+2 months');
                $endDate = $startDate;
                $startTime = $faker->time('H:i', '17:00');
                $endTime = date('H:i', strtotime($startTime) + 3600 + random_int(0, 3600));

                $parentModule = $faker->boolean(70) ? $faker->randomElement($modules) : null;
                $parentId = null;

                if ($parentModule && isset($parentCollections[$parentModule])) {
                    $collection = $parentCollections[$parentModule];
                    $parentId = $collection->isNotEmpty() ? $collection->random()->id : null;
                }

                $meeting = Meeting::create([
                    'title' => $meetingTitles[$i],
                    'description' => $meetingDescriptions[$i],
                    'location' => $faker->randomElement($locations),
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

                if ($staffUsers->isNotEmpty()) {
                    MeetingAttendee::create([
                        'meeting_id' => $meeting->id,
                        'attendee_type' => 'user',
                        'attendee_id' => $staffUsers->random()->id,
                    ]);
                }

                $leads = $parentCollections['lead'];
                if ($leads->isNotEmpty() && $faker->boolean(40)) {
                    MeetingAttendee::create([
                        'meeting_id' => $meeting->id,
                        'attendee_type' => 'lead',
                        'attendee_id' => $leads->random()->id,
                    ]);
                }
            }
        }

        $this->command->info('Meetings created for all company users!');
    }
}
