<?php

namespace Database\Seeders;

use App\Models\LandingPageCustomPage;
use Illuminate\Database\Seeder;

class LandingPageCustomPageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title' => 'About Us',
                'slug' => 'about-us',
                'content' => "About Our Sales SaaS Platform: Empowering businesses to <b>sell smarter, faster, and better</b>.<br>We are dedicated to helping companies streamline sales processes, optimize pipelines, and close deals with ease.<br>Our Sales SaaS solution centralizes customer data, automates repetitive tasks, and provides actionable insights to drive revenue growth.<br>Whether you're a startup or an enterprise, our platform adapts to your sales journey—from lead generation to customer retention—ensuring transparency, collaboration, and measurable success.<br><b>Stats:</b> &bull; 7+ Years Industry Experience &bull; 25K+ Active Users &bull; 80+ Countries Served<br><b>Our Mission:</b> Transform the way businesses sell by providing scalable, intelligent, and user-friendly sales management solutions.<br><b>Our Values:</b> Innovation, transparency, and customer success are at the heart of everything we build.<br><b>Our Commitment:</b> Deliver secure, scalable, and reliable sales solutions with world-class support.<br><b>Our Vision:</b> A future where every business maximizes its revenue potential through automation, data-driven decisions, and seamless customer engagement.",
                'meta_title' => 'About Us - Sales SaaS Platform',
                'meta_description' => 'Learn more about our Sales SaaS platform – designed to simplify sales management, optimize pipelines, and accelerate revenue growth for businesses worldwide.',
                'is_active' => true,
                'sort_order' => 1
            ],

            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'content' => "Your privacy is important to us. This Privacy Policy explains how our Sales SaaS platform collects, uses, and protects your information.<br><b>Information We Collect:</b> &bull; Business and personal details such as name, email, phone, and company information &bull; Sales pipeline, leads, deals, and customer interactions &bull; Billing and subscription details for account management &bull; Communication data including emails, chats, and activity logs &bull; System usage analytics to enhance platform performance<br><b>How We Use Your Information:</b> &bull; Provide, maintain, and improve Sales SaaS services &bull; Enable lead management, customer relationship tracking, and reporting &bull; Process payments, subscriptions, and invoices securely &bull; Send important updates, notifications, and promotional offers (with your consent) &bull; Monitor and enhance security, prevent fraud, and ensure compliance<br><b>Information Sharing:</b> We do not sell or trade personal or business data. Information may be shared with: &bull; Authorized company users and administrators &bull; Trusted third-party service providers (e.g., payment gateways, analytics tools) &bull; Legal authorities when required by law<br><b>Data Security:</b> We use encryption, firewalls, access control, and regular audits to safeguard customer and sales data from unauthorized access or misuse.<br><b>Data Retention:</b> Data is stored as long as your account remains active or as legally required. Upon request, data can be deleted, anonymized, or exported as needed.<br><b>Your Rights:</b> You have the right to access, correct, or request deletion of your personal data. You may also manage communication preferences or withdraw consent anytime by contacting our support team.",
                'meta_title' => 'Privacy Policy - Sales SaaS',
                'meta_description' => 'Read the privacy policy of our Sales SaaS platform to understand how sales, lead, and customer data is collected, used, and protected.',
                'is_active' => true,
                'sort_order' => 2
            ],

            [
                'title' => 'Terms of Service',
                'slug' => 'terms-of-service',
                'content' => "Please read these terms carefully before using our Sales SaaS platform. By accessing or using our services, you agree to these terms.<br><br>
                                <b>Acceptance of Terms:</b> By creating an account or using our Sales SaaS product, you confirm that you have read, understood, and agree to be bound by these Terms of Service. If you do not agree, you may not use the platform.<br><br>
                                <b>Service Description:</b> Our platform provides businesses with Sales and Customer Relationship Management (CRM) solutions, including but not limited to:<br>
                                &bull; Lead and opportunity tracking<br>
                                &bull; Sales pipeline and forecasting tools<br>
                                &bull; Customer and contact management<br>
                                &bull; Quotation and invoicing features<br>
                                &bull; Reporting, analytics, and integrations<br><br>
                                <b>User Responsibilities:</b> As a user of our Sales SaaS, you agree to:<br>
                                &bull; Provide accurate and updated information when creating an account<br>
                                &bull; Maintain confidentiality of your login credentials<br>
                                &bull; Ensure that all uploaded content complies with applicable laws<br>
                                &bull; Use the platform only for lawful sales and CRM management purposes<br><br>
                                <b>Subscription & Payments:</b> You agree to pay all fees associated with your chosen plan in accordance with the billing terms. Failure to pay may result in suspension or termination of your account.<br><br>
                                <b>Termination of Service:</b> We reserve the right to suspend or terminate your access if you violate these Terms or engage in harmful activities.<br><br>
                                <b>Data & Privacy:</b> Your data will be handled per our Privacy Policy. You are responsible for safeguarding your account access.<br><br>
                                <b>Limitation of Liability:</b> Our company shall not be held liable for any indirect, incidental, or consequential damages arising from your use of the Sales SaaS platform.",
                'meta_title' => 'Terms of Service - Sales SaaS',
                'meta_description' => 'Read our terms of service to understand the rules and responsibilities for using our Sales SaaS platform.',
                'is_active' => true,
                'sort_order' => 3
            ],

            [
                'title' => 'Contact Us',
                'slug' => 'contact-us',
                'content' => "Have questions about <b>Sales SaaS</b>? Our team is here to assist you with demos, pricing, integrations, and more.<br><br><b>Send us a Message:</b> Fill out the form with your Full Name, Email Address, Subject, and Message. Our dedicated support team will get back to you promptly.<br><br><b>Contact Information:</b><br>&bull; <b>Email Us:</b> support@salessaas.com (Average response time: within 24 hours)<br>&bull; <b>Call Us:</b> +1 (555) 987-6543 (Available Monday – Friday, 9am – 6pm EST)<br>&bull; <b>Visit Us:</b> 456 Growth St, Suite 200, New York, NY 10001<br><br><b>Business Hours:</b><br>&bull; Monday - Friday: 9:00 AM - 6:00 PM EST<br>&bull; Saturday: 10:00 AM - 2:00 PM EST<br>&bull; Sunday: Closed",
                'meta_title' => 'Contact Us - Sales SaaS Support',
                'meta_description' => 'Reach out to our Sales SaaS support team for inquiries, demos, pricing, or technical assistance. We’re here to help you succeed.',
                'is_active' => true,
                'sort_order' => 4
            ],

            [
                'title' => 'FAQ',
                'slug' => 'faq',
                'content' => "Find quick answers to the most <b>common questions</b> about using our Sales SaaS platform.<br><br>
                            <b>Getting Started:</b><br>
                            <b>What is Sales SaaS?</b> Sales SaaS is a cloud-based sales management platform that helps businesses streamline lead generation, track opportunities, manage pipelines, automate workflows, and close deals faster.<br>
                            <b>How do I get started?</b> You can sign up for a free trial, set up your company profile, add your sales team, and start tracking leads and deals right away.<br><br>
                            
                            <b>Features & Plans:</b><br>
                            <b>Which subscription plans are available?</b> We offer Basic, Professional, and Enterprise plans to fit teams of all sizes, each with advanced features such as pipeline automation, AI-driven insights, and reporting tools.<br>
                            <b>Can I integrate Sales SaaS with other tools?</b> Yes, Sales SaaS integrates with popular CRMs, email marketing platforms, communication apps, and payment gateways.<br><br>
                            
                            <b>Analytics & Support:</b><br>
                            <b>How does reporting work?</b> Our analytics dashboard provides real-time insights into sales performance, conversion rates, revenue forecasting, and team productivity.<br>
                            <b>What support options are available?</b> We offer 24/7 email support, live chat, and phone assistance for premium users. You can also explore our Help Center for detailed guides and tutorials.",
                'meta_title' => 'FAQ - Sales SaaS Help Center',
                'meta_description' => 'Get answers to frequently asked questions about Sales SaaS, including features, pricing plans, integrations, and support options.',
                'is_active' => true,
                'sort_order' => 5
            ],

            [
                'title' => 'Refund Policy',
                'slug' => 'refund-policy',
                'content' => "We value your trust in <b>Sales SaaS</b> and are committed to delivering the best experience. Please review our refund policy below.<br><br>
    
                            <b>30-Day Money Back Guarantee:</b> We offer a 30-day money-back guarantee on all premium subscription plans. If Sales SaaS does not meet your expectations, you can request a full refund within 30 days of purchase.<br><br>
                            
                            <b>Eligible Refunds:</b><br>
                            &bull; Monthly and annual subscription plans<br>
                            &bull; One-time premium features or add-ons<br>
                            &bull; Unused portions of prepaid services<br><br>
                            
                            <b>Refund Process:</b><br>
                            1. Contact our support team within 30 days of purchase.<br>
                            2. Provide your registered account details and reason for the refund.<br>
                            3. Our team will review and process your request within 3–5 business days.<br>
                            4. Refunds will be credited to your original payment method.<br><br>
                            
                            <b>Non-Refundable Items:</b><br>
                            &bull; Custom development, consulting, or integration services<br>
                            &bull; Third-party services or marketplace add-ons<br>
                            &bull; Domain registration or external licensing fees<br>
                            &bull; Subscriptions after the 30-day guarantee period<br><br>
                            
                            If you have any questions about our refund policy, please reach out to <b>support@sales-saas.com</b>. Our team is here to help.",

                'meta_title' => 'Refund Policy - Sales SaaS',
                'meta_description' => 'Read about the Sales SaaS refund policy, including our 30-day money-back guarantee and eligibility details.',
                'is_active' => true,
                'sort_order' => 6
            ]

        ];

        foreach ($pages as $pageData) {
            LandingPageCustomPage::firstOrCreate(
                ['slug' => $pageData['slug']],
                $pageData
            );
        }

        $this->command->info('Landing page custom pages seeded successfully!');
    }
}
