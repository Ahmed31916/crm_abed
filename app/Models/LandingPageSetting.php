<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingPageSetting extends Model
{
    protected $fillable = [
        'company_name',
        'contact_email',
        'contact_phone',
        'contact_address',
        'config_sections'
    ];

    protected $attributes = [
        'company_name' => 'Sales SaaS',
        'contact_email' => 'support@sales.com',
        'contact_phone' => '+1 (555) 123-4567',
        'contact_address' => 'San Francisco, CA'
    ];

    protected $casts = [
        'config_sections' => 'array'
    ];

    public static function getSettings()
    {
        $settings = self::first();

        if (!$settings) {
            // Import default sections from the template file structure
            $defaultConfig = [
                'sections' => [
                    [
                        'key' => 'header',
                        'transparent' => false,
                        'background_color' => '#ffffff',
                        'text_color' => '#1f2937',
                        'button_style' => 'solid'
                    ],
                    [
                        'key' => 'hero',
                        'title' => 'All-in-One Sales SaaS to Power Your Business Growth',
                        'subtitle' => 'Manage leads, opportunities, quotes, orders, invoices, projects, and reports — all from one platform.',
                        'announcement_text' => '🚀 Smart Reports & Advanced Analytics',
                        'primary_button_text' => 'Start Free Trial',
                        'secondary_button_text' => 'Login',
                        'image' => '',
                        'background_color' => '#f8fafc',
                        'text_color' => '#1f2937',
                        'layout' => 'image-right',
                        'height' => 600,
                        'stats' => [
                            ['value' => '5K+', 'label' => 'Businesses Powered'],
                            ['value' => '30+', 'label' => 'Modules Included'],
                            ['value' => '99%', 'label' => 'Customer Satisfaction']
                        ]
                    ],
                    [
                        'key' => 'features',
                        'title' => 'Powerful Features to Streamline Your Sales',
                        'description' => 'From lead management to invoicing, get everything you need to manage and grow your sales pipeline in one platform.',
                        'background_color' => '#ffffff',
                        'layout' => 'grid',
                        'columns' => 3,
                        'image' => '',
                        'show_icons' => true,
                        'features_list' => [
                            [
                                'title' => 'CRM & Lead Management',
                                'description' => 'Capture, nurture, and convert leads with smart automation tools.',
                                'icon' => 'users'
                            ],
                            [
                                'title' => 'Opportunity & Pipeline',
                                'description' => 'Track every deal stage and source to close more sales.',
                                'icon' => 'zap'
                            ],
                            [
                                'title' => 'Quotes & Orders',
                                'description' => 'Create professional quotes, manage sales and purchase orders with ease.',
                                'icon' => 'globe'
                            ],
                            [
                                'title' => 'Invoices & Payments',
                                'description' => 'Automate invoices, track payments, and simplify your billing process.',
                                'icon' => 'smartphone'
                            ],
                            [
                                'title' => 'Projects & Tasks',
                                'description' => 'Collaborate on projects, assign tasks, and deliver work on time.',
                                'icon' => 'star'
                            ],
                            [
                                'title' => 'Reports & Analytics',
                                'description' => 'Gain deep insights with customizable reports and dashboards.',
                                'icon' => 'bar-chart'
                            ]
                        ]
                    ],
                    [
                        'key' => 'screenshots',
                        'title' => 'See Our Sales SaaS in Action',
                        'subtitle' => 'Explore the modern interface and powerful modules that make managing your sales process effortless',
                        'screenshots_list' => [
                            [
                                'src' => '/screenshots/dashboard.png',
                                'alt' => 'Sales SaaS Dashboard Overview',
                                'title' => 'Dashboard Overview',
                                'description' => 'Get a complete view of leads, employees, projects, sales, projects, and performance insights in one place'
                            ],
                            [
                                'src' => '/screenshots/crm.png',
                                'alt' => 'CRM & Lead Management',
                                'title' => 'CRM & Lead Management',
                                'description' => 'Easily manage accounts, contacts, and leads with a user-friendly CRM system'
                            ],
                            [
                                'src' => '/screenshots/orders.png',
                                'alt' => 'Quotes & Orders',
                                'title' => 'Quotes & Orders',
                                'description' => 'Quickly generate quotes, process orders, and track every transaction with ease'
                            ],
                            [
                                'src' => '/screenshots/invoices.png',
                                'alt' => 'Invoices & Payments',
                                'title' => 'Invoices & Payments',
                                'description' => 'Automate billing, manage payments, and simplify your financial workflows'
                            ],
                            [
                                'src' => '/screenshots/projects.png',
                                'alt' => 'Projects & Task Management',
                                'title' => 'Projects & Tasks',
                                'description' => 'Plan, assign, and track tasks to deliver projects on time and boost team productivity.'
                            ],
                            [
                                'src' => '/screenshots/reports.png',
                                'alt' => 'Reports & Analytics',
                                'title' => 'Reports & Analytics',
                                'description' => 'Visualize your sales performance with powerful reports and real-time analytics.'
                            ]
                        ]
                    ],
                    [
                        'key' => 'why_choose_us',
                        'title' => 'Why Choose Our Sales SaaS?',
                        'subtitle' => 'We\'re more than just CRM — we\'re your complete sales growth platform.',
                        'reasons' => [
                            [
                                'title' => 'Quick Setup',
                                'description' => 'Get started in minutes with a user-friendly interface and ready-to-use modules.',
                                'icon' => 'clock'
                            ],
                            [
                                'title' => 'All-in-One Solution',
                                'description' => 'From leads to invoices, manage your entire sales process in one place.',
                                'icon' => 'check-circle'
                            ],
                            [
                                'title' => 'Boost Productivity',
                                'description' => 'Streamline tasks, automate workflows, and close deals faster.',
                                'icon' => 'zap'
                            ],
                            [
                                'title' => 'Scalable & Secure',
                                'description' => 'Built with enterprise-grade security and flexibility to grow with your business.',
                                'icon' => 'shield'
                            ]
                        ],
                        'stats' => [
                            ['value' => '5K+', 'label' => 'Businesses Powered', 'color' => 'blue'],
                            ['value' => '99%', 'label' => 'Customer Satisfaction', 'color' => 'green']
                        ]
                    ],
                    [
                        'key' => 'about',
                        'title' => 'About Our Sales SaaS',
                        'description' => 'We are dedicated to simplifying and automating the entire sales lifecycle for businesses of all sizes.',
                        'story_title' => 'Empowering Sales Teams Since 2020',
                        'story_content' => 'Founded by a group of sales professionals and technology experts, our Sales SaaS was created to solve the common challenges businesses face in managing leads, orders, invoices, and projects. Today, we power thousands of businesses worldwide with a reliable, scalable, and easy-to-use platform.',
                        'image' => '',
                        'background_color' => '#f9fafb',
                        'layout' => 'image-right',
                        'stats' => [
                            ['value' => '4+ Years', 'label' => 'Industry Experience', 'color' => 'blue'],
                            ['value' => '10K+', 'label' => 'Happy Users', 'color' => 'green'],
                            ['value' => '5K+', 'label' => 'Businesses Powered', 'color' => 'purple']
                        ]
                    ],
                    [
                        'key' => 'team',
                        'title' => 'Meet Our Team',
                        'subtitle' => 'We are a passionate group of sales experts, developers, and innovators building the future of sales automation.',
                        'cta_title' => 'Want to Join Our Team?',
                        'cta_description' => 'We are always looking for talented individuals to help us shape the next generation of sales technology.',
                        'cta_button_text' => 'View Open Positions',
                        'members' => [
                            [
                                'name' => 'Sarah Johnson',
                                'role' => 'CEO & Founder',
                                'bio' => 'Sales strategist and former tech executive with 15+ years of experience in scaling SaaS businesses.',
                                'image' => '',
                                'linkedin' => '#',
                                'email' => 'sarah@sales.com'
                            ],
                            [
                                'name' => 'Michael Lee',
                                'role' => 'CTO',
                                'bio' => 'Full-stack engineer specializing in Laravel and React with a passion for building scalable SaaS platforms.',
                                'image' => '',
                                'linkedin' => '#',
                                'email' => 'michael@sales.com'
                            ],
                            [
                                'name' => 'Priya Sharma',
                                'role' => 'Head of Product',
                                'bio' => 'Product leader focused on delivering user-friendly sales solutions that solve real-world challenges.',
                                'image' => '',
                                'linkedin' => '#',
                                'email' => 'priya@sales.com'
                            ],
                            [
                                'name' => 'David Kim',
                                'role' => 'Head of Marketing',
                                'bio' => 'Growth marketer with expertise in SaaS positioning, customer acquisition, and brand strategy.',
                                'image' => '',
                                'linkedin' => '#',
                                'email' => 'david@sales.com'
                            ]
                        ]
                    ],
                    [
                        'key' => 'testimonials',
                        'title' => 'What Our Clients Say',
                        'subtitle' => 'Don\'t just take our word for it — hear from businesses using our Sales SaaS.',
                        'trust_title' => 'Trusted by Businesses Worldwide',
                        'trust_stats' => [
                            ['value' => '4.9/5', 'label' => 'Average Rating', 'color' => 'blue'],
                            ['value' => '10K+', 'label' => 'Happy Businesses', 'color' => 'green']
                        ],
                        'testimonials' => [
                            ['name' => 'Alex Thompson', 'role' => 'Sales Director', 'company' => 'TechCorp Inc.', 'content' => 'This platform has transformed how we manage leads and opportunities. Our conversion rate has doubled since adopting it!', 'rating' => 5],
                            ['name' => 'Maria Lopez', 'role' => 'Operations Manager', 'company' => 'Global Enterprises', 'content' => 'Invoices and orders are now automated, saving us hours every week. The reports feature gives us clear insights into performance.', 'rating' => 5],
                            ['name' => 'Ravi Patel', 'role' => 'Founder & CEO', 'company' => 'StartUp Hub', 'content' => 'As a growing business, we needed a scalable CRM and project management tool. This SaaS delivers everything in one place!', 'rating' => 5]
                        ]
                    ],
                    [
                        'key' => 'plans',
                        'title' => 'Choose Your Plan',
                        'subtitle' => 'Start with our free plan and upgrade as your business grows.',
                        'faq_text' => 'Have questions about our plans? Contact our sales team'
                    ],
                    [
                        'key' => 'faq',
                        'title' => 'Frequently Asked Questions',
                        'subtitle' => 'Everything you need to know before getting started.',
                        'cta_text' => 'Didn’t find your answer?',
                        'button_text' => 'Talk to Sales',
                        'faqs' => [
                            [
                                'question' => 'What makes this SaaS different from others?',
                                'answer' => 'Unlike other tools, our platform is built to help you grow faster. With powerful automation, real-time analytics, and world-class support, you’ll get results from day one.'
                            ],
                            [
                                'question' => 'Do I need to pay upfront to get started?',
                                'answer' => 'Not at all! You can start with our free trial today. No credit card required. Upgrade anytime when you’re ready to unlock advanced features.'
                            ],
                            [
                                'question' => 'Can I scale my plan as my business grows?',
                                'answer' => 'Yes! Our pricing is flexible. Start small and scale seamlessly — whether you’re a freelancer, startup, or enterprise, we have a plan tailored for your growth.'
                            ],
                            [
                                'question' => 'How secure is my data on your platform?',
                                'answer' => 'We use enterprise-grade encryption and follow strict compliance standards, so your data is always safe. Security is our top priority.'
                            ],
                            [
                                'question' => 'What kind of support do I get?',
                                'answer' => 'Every plan includes email support, and higher tiers unlock priority support with a dedicated account manager — so you’re never left waiting.'
                            ],
                            [
                                'question' => 'Why should I upgrade to a paid plan?',
                                'answer' => 'Paid plans unlock premium features like advanced analytics, integrations, team management, and unlimited usage — giving your business the competitive edge it needs.'
                            ]
                        ]
                    ],
                    [
                        'key' => 'newsletter',
                        'title' => 'Stay Updated with Sales SaaS',
                        'subtitle' => 'Get the latest sales strategies, product updates, and growth insights.',
                        'privacy_text' => 'We value your privacy — no spam, unsubscribe anytime.',
                        'benefits' => [
                            ['icon' => '📧', 'title' => 'Weekly Insights', 'description' => 'Proven sales strategies and SaaS growth hacks'],
                            ['icon' => '🚀', 'title' => 'Product Updates', 'description' => 'Stay informed about new features and improvements'],
                            ['icon' => '📊', 'title' => 'Data-Driven Tips', 'description' => 'Learn how to optimize sales with actionable analytics']
                        ]
                    ],
                    [
                        'key' => 'contact',
                        'title' => 'Connect with Sales SaaS',
                        'subtitle' => 'Have questions about Sales SaaS? Our team is here to help you succeed.',
                        'form_title' => 'Send us a Message',
                        'info_title' => 'Contact Information',
                        'info_description' => 'We\'re here to help and answer any question you might have.',
                        'layout' => 'split',
                        'background_color' => '#f9fafb'
                    ],
                    [
                        'key' => 'footer',
                        'description' => 'Empowering businesses to boost sales and grow faster with our all-in-one Sales SaaS platform.',
                        'newsletter_title' => 'Stay Ahead',
                        'newsletter_subtitle' => 'Subscribe for sales tips, product updates, and growth insights.',
                        'footerText', '© 2026 Sales SaaS. All rights reserved.',
                        'links' => [
                            'product' => [
                                ['name' => 'Features', 'href' => '#features'],
                                ['name' => 'Pricing', 'href' => '#pricing'],
                                ['name' => 'Integrations', 'href' => '#integrations']
                            ],
                            'company' => [
                                ['name' => 'About Us', 'href' => '#about'],
                                ['name' => 'Careers', 'href' => '#careers'],
                                ['name' => 'Contact', 'href' => '#contact']
                            ],
                            'support' => [
                                ['name' => 'Help Center', 'href' => '#help-center'],
                                ['name' => 'FAQs', 'href' => '#faqs']
                            ],
                            'legal' => [
                                ['name' => 'Privacy Policy', 'href' => '#privacy-policy'],
                                ['name' => 'Terms of Service', 'href' => '#terms-of-service']
                            ]
                        ],
                        'social_links' => [
                            ['name' => 'Facebook', 'icon' => 'Facebook', 'href' => '#'],
                            ['name' => 'Twitter', 'icon' => 'Twitter', 'href' => '#'],
                            ['name' => 'LinkedIn', 'icon' => 'Linkedin', 'href' => '#'],
                            ['name' => 'Instagram', 'icon' => 'Instagram', 'href' => '#']
                        ],
                        'section_titles' => [
                            'product' => 'Product',
                            'company' => 'Company'
                        ]
                    ]
                ],
                'theme' => [
                    'primary_color' => '#10b77f',
                    'secondary_color' => '#ffffff',
                    'accent_color' => '#f7f7f7',
                    'logo_light' => 'logo/logo-light.png',
                    'logo_dark' => 'logo/logo-dark.png',
                    'favicon' => 'logo/favicon.png'
                ],
                'seo' => [
                    'meta_title' => 'Sales SaaS - Boost Your Sales & Grow Faster',
                    'meta_description' => 'All-in-one Sales SaaS platform to manage leads, close deals, and scale your business effortlessly.',
                    'meta_keywords' => 'sales software, CRM, lead management, deal tracking, sales automation, SaaS'
                ],
                'custom_css' => '',
                'custom_js' => '',
                'section_order' => ['header', 'hero', 'features', 'screenshots', 'why_choose_us',  'about', 'team', 'testimonials', 'plans', 'faq', 'newsletter', 'contact', 'footer'],
                'section_visibility' => [
                    'header' => true,
                    'hero' => true,
                    'features' => true,
                    'screenshots' => true,
                    'why_choose_us' => true,
                    'about' => true,
                    'team' => true,
                    'testimonials' => true,
                    'plans' => true,
                    'faq' => true,
                    'newsletter' => true,
                    'contact' => true,
                    'footer' => true
                ]
            ];

            $settings = self::create([
                'config_sections' => $defaultConfig
            ]);
        }
        $superAdminId = User::where('type', 'superadmin')->first()->id;

        $landingPageSettings = $settings;

        $configSections = $settings->config_sections;

        // Theme defaults
        if (empty($configSections['theme']['logo_light'])) {
            $configSections['theme']['logo_light'] = getSetting(
                'logoLight',
                'logo/logo-light.png',
                $superAdminId
            );
        }

        if (empty($configSections['theme']['logo_dark'])) {
            $configSections['theme']['logo_dark'] = getSetting(
                'logoDark',
                'logo/logo-dark.png',
                $superAdminId
            );
        }

        if (empty($configSections['theme']['favicon'])) {
            $configSections['theme']['favicon'] = getSetting(
                'favicon',
                'logo/favicon.png',
                $superAdminId
            );
        }

        // Sections
        $configSections['sections'] = collect($configSections['sections'] ?? [])
            ->map(function ($section) use ($superAdminId) {
                if ($section['key'] === 'footer') {
                    $section['footerText'] = getSetting(
                        'footerText',
                        '© 2026 Advocate SaaS. All rights reserved.',
                        $superAdminId
                    );
                }
                return $section;
            })
            ->toArray();

        // ✅ Reassign back (IMPORTANT)
        $landingPageSettings->config_sections = $configSections;
        return $landingPageSettings;
    }
}
