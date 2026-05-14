<?php

namespace Database\Seeders;

use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Seeder;

class NoteSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('type', '!=', 'superadmin')->get();

        $noteTitles = [
            'Meeting Notes - Q1 Planning Session',
            'Client Requirements Discussion',
            'Project Kickoff Summary',
            'Weekly Team Standup Notes',
            'Product Feature Ideas',
            'Budget Review Meeting',
            'Customer Feedback Analysis',
            'Sprint Retrospective Notes',
            'Marketing Campaign Strategy',
            'Technical Architecture Discussion',
            'Sales Pipeline Review',
            'Employee Onboarding Checklist',
            'Quarterly Goals and Objectives',
            'Vendor Contract Negotiations',
            'System Upgrade Planning'
        ];

        $noteContents = [
            '<p>Discussed Q1 objectives and key performance indicators. Team agreed on focusing on customer retention and product improvements.</p><p>Action items: Schedule follow-up meetings with department heads and prepare detailed roadmap.</p>',
            '<p>Client expressed interest in additional features including advanced reporting and custom integrations.</p><p>Timeline: 6-8 weeks for implementation. Budget approved for development resources.</p>',
            '<p>Project scope defined with clear deliverables and milestones. Team roles assigned and communication channels established.</p><p>Next steps: Begin initial development phase and schedule weekly progress reviews.</p>',
            '<p>Team updates on current tasks and blockers. Discussed resource allocation and priority adjustments for upcoming sprint.</p><p>Key decisions: Postpone feature X to next sprint, focus on bug fixes this week.</p>',
            '<p>Brainstorming session generated several innovative feature concepts. Top ideas include AI-powered analytics and mobile app enhancements.</p><p>Follow-up: Conduct user research to validate feature demand and feasibility.</p>',
            '<p>Reviewed current budget allocation and identified areas for cost optimization. Discussed investment priorities for next quarter.</p><p>Recommendations: Increase marketing budget by 15%, reduce operational overhead.</p>',
            '<p>Analyzed customer survey results and support ticket trends. Overall satisfaction score improved by 12% this quarter.</p><p>Areas for improvement: Response time and feature documentation quality.</p>',
            '<p>Team reflected on sprint performance and identified process improvements. Velocity increased but quality concerns noted.</p><p>Action items: Implement code review checklist and increase testing coverage.</p>',
            '<p>Outlined multi-channel marketing approach for product launch. Focus on content marketing and social media engagement.</p><p>Budget allocated: $50K for initial campaign phase. Launch date: End of next month.</p>',
            '<p>Reviewed system architecture and scalability requirements. Proposed microservices approach for better performance.</p><p>Technical decisions: Migrate to cloud infrastructure, implement caching layer.</p>',
            '<p>Analyzed sales funnel metrics and conversion rates. Identified bottlenecks in qualification stage.</p><p>Strategy: Improve lead scoring model and provide additional sales training.</p>',
            '<p>Comprehensive onboarding plan for new team members. Includes training schedule, mentor assignment, and 30-60-90 day goals.</p><p>Resources: Access to learning platform and documentation repository.</p>',
            '<p>Established quarterly targets for revenue growth, customer acquisition, and product development.</p><p>Key metrics: 25% revenue increase, 1000 new customers, 5 major feature releases.</p>',
            '<p>Negotiating terms with new vendor for software licensing. Discussed pricing, support levels, and contract duration.</p><p>Status: Awaiting final proposal. Decision deadline: End of month.</p>',
            '<p>Planning system upgrade to latest version. Assessed compatibility issues and migration requirements.</p><p>Timeline: 2-week implementation window. Backup and rollback procedures documented.</p>'
        ];

        foreach ($users as $user) {
            $createdBy = (($user->created_by ?? $user->id) != 1)
                ? $user->created_by
                : $user->id;

            $companyUsers = User::where('created_by', $createdBy)
                ->orWhere('id', $createdBy)
                ->where('id', '!=', $user->id)
                ->pluck('id');

            // Create 2-5 shared notes
            if ($companyUsers->isNotEmpty()) {
                $sharedCount = 5;
                for ($i = 0; $i < $sharedCount; $i++) {
                    $notesExists = Note::where('created_by', $user->id)->exists();
                    if ($notesExists) {
                        continue;
                    }
                    $randomIndex = array_rand($noteTitles);
                    $note = Note::create([
                        'title' => $noteTitles[$randomIndex],
                        'content' => $noteContents[$randomIndex],
                        'created_by' => $user->id,
                    ]);

                    $shareCount = min(rand(1, 3), $companyUsers->count());
                    $note->sharedUsers()->sync($companyUsers->random($shareCount));
                }
            }
        }

        foreach ($users as $user) {
            $notesExists = Note::where('created_by', $user->id)->exists();
            if ($notesExists) {
                continue;
            }
            // Create 6 personal notes
            for ($i = 0; $i < 6; $i++) {
                $randomIndex = array_rand($noteTitles);
                Note::create([
                    'title' => $noteTitles[$randomIndex],
                    'content' => $noteContents[$randomIndex],
                    'created_by' => $user->id,
                ]);
            }
        }
    }
}
