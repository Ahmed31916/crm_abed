<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\AnnouncementCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('type', 'company')->get();

        $categoryTemplates = [
            ['name' => 'Company News', 'description' => 'General company announcements and news'],
            ['name' => 'Policy Update', 'description' => 'Company policy changes and updates'],
            ['name' => 'Event', 'description' => 'Upcoming events and activities'],
            ['name' => 'HR', 'description' => 'Human resources related announcements'],
            ['name' => 'IT Update', 'description' => 'IT system updates and maintenance'],
        ];

        $announcementTemplates = [
            ['title' => 'System Maintenance Scheduled', 'content' => 'Our system will undergo scheduled maintenance on Sunday from 2 AM to 4 AM. Services may be temporarily unavailable during this time.', 'category' => 'IT Update'],
            ['title' => 'New Feature Released', 'content' => 'We are excited to announce the release of our new reporting dashboard. Check it out in the Reports section!', 'category' => 'Company News'],
            ['title' => 'Security Update Required', 'content' => 'Please update your password to comply with our new security policy. Passwords must be at least 12 characters long.', 'category' => 'Policy Update'],
            ['title' => 'Holiday Schedule', 'content' => 'Our support team will have limited availability during the upcoming holidays. Please plan accordingly.', 'category' => 'HR'],
            ['title' => 'Training Session Available', 'content' => 'Join us for a free training session on advanced features this Friday at 3 PM. Register in the Events section.', 'category' => 'Event'],
            ['title' => 'Performance Improvements', 'content' => 'We have implemented several performance improvements. You should notice faster load times across the platform.', 'category' => 'IT Update'],
            ['title' => 'Policy Update', 'content' => 'Our terms of service have been updated. Please review the changes in your account settings.', 'category' => 'Policy Update'],
            ['title' => 'Welcome to the Platform', 'content' => 'Thank you for joining us! Explore our features and let us know if you need any assistance.', 'category' => 'Company News'],
            ['title' => 'Server Upgrade Notice', 'content' => 'We are upgrading our servers to provide better performance. Expect brief downtime this weekend.', 'category' => 'IT Update'],
            ['title' => 'Team Building Event', 'content' => 'Join us for a team building event next month. Details will be shared soon.', 'category' => 'Event'],
            ['title' => 'New HR Policy', 'content' => 'We have introduced a new remote work policy. Please check your email for details.', 'category' => 'HR'],
            ['title' => 'Product Launch', 'content' => 'We are launching a new product line next quarter. Stay tuned for more information.', 'category' => 'Company News'],
            ['title' => 'Data Backup Reminder', 'content' => 'Please ensure all important data is backed up regularly. Contact IT for assistance.', 'category' => 'IT Update'],
            ['title' => 'Annual Review Process', 'content' => 'The annual performance review process will begin next month. Prepare your self-assessments.', 'category' => 'HR'],
            ['title' => 'Office Closure Notice', 'content' => 'Our office will be closed on Monday for a public holiday. Remote work is available.', 'category' => 'Company News'],
            ['title' => 'Webinar Invitation', 'content' => 'Join our webinar on industry trends this Thursday at 2 PM. Registration link in your inbox.', 'category' => 'Event'],
            ['title' => 'Network Maintenance', 'content' => 'Network maintenance is scheduled for tonight. Internet connectivity may be affected briefly.', 'category' => 'IT Update'],
            ['title' => 'Benefits Enrollment', 'content' => 'Open enrollment for benefits starts next week. Review your options and make selections.', 'category' => 'HR'],
            ['title' => 'Customer Appreciation Day', 'content' => 'We are hosting a customer appreciation event. Thank you for your continued support!', 'category' => 'Event'],
            ['title' => 'Quarterly Results', 'content' => 'Our quarterly results are in! Thank you to everyone for your hard work and dedication.', 'category' => 'Company News'],
        ];

        foreach ($users as $user) {
            foreach ($categoryTemplates as $catTemplate) {
                AnnouncementCategory::firstOrCreate(
                    [
                        'name' => $catTemplate['name'],
                        'created_by' => $user->id
                    ],
                    [
                        'description' => $catTemplate['description'],
                        'status' => 'active',
                    ]
                );
            }


            foreach ($announcementTemplates as $template) {

                $category = AnnouncementCategory::where('name', $template['category'])->where('created_by', $user->id)->first();

                $announcementsExists = Announcement::where('created_by', $user->id)->exists();
                if ($announcementsExists) {
                    continue;
                }

                Announcement::create(
                    [
                        'title' => $template['title'],
                        'created_by' => $user->id
                    ],
                    [
                        'content' => $template['content'],
                        'announcement_category_id' => $category?->id ?? null,
                        'start_date' => now()->subDays(rand(0, 7)),
                        'end_date' => rand(0, 1) ? now()->addDays(rand(7, 30)) : null,
                        'status' => rand(0, 10) > 2 ? 'active' : 'inactive',
                        'is_featured' => rand(0, 10) > 7,
                    ]
                );
            }
        }
    }
}
