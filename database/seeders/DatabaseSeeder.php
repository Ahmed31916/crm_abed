<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (!IsDemo()) {
            $this->call([
                // Core system seeders
                PermissionSeeder::class,
                RoleSeeder::class,
                PlanSeeder::class,

                //Company and User seeders
                UserSeeder::class,
                StaffRoleSeeder::class,

                EmailTemplateSeeder::class,
                NotificationTemplateSeeder::class,
                CountrySeeder::class,

                LandingPageCustomPageSeeder::class,
                CurrencySeeder::class,
            ]);
        } else {
            $this->call([
                // Core system seeders
                PermissionSeeder::class,
                RoleSeeder::class,
                PlanSeeder::class,

                //Company and User seeders
                UserSeeder::class,
                CompanySeeder::class,
                StaffRoleSeeder::class,

                // System configuration seeders
                CouponSeeder::class,
                PlanOrderSeeder::class,
                PlanRequestSeeder::class,
                ReferralSettingSeeder::class,
                CurrencySeeder::class,
                LandingPageCustomPageSeeder::class,
                LeadStatusSeeder::class,
                LeadSourceSeeder::class,
                TaskStatusSeeder::class,
                OpportunityStageSeeder::class,

                // ReferralProgramSeeder::class,
                TaxSeeder::class,
                BrandSeeder::class,
                CategorySeeder::class,
                PrimaryIndicationSeeder::class,
                TagSeeder::class,
                ProductSeeder::class,
                AccountTypeSeeder::class,
                AccountIndustrySeeder::class,
                AccountSeeder::class,
                ContactSeeder::class,
                OpportunitySourceSeeder::class,
                OpportunitySeeder::class,
                LeadSeeder::class,
                LeadActivitySeeder::class,
                CampaignTypeSeeder::class,
                TargetListSeeder::class,
                CampaignSeeder::class,
                ShippingProviderTypeSeeder::class,
                QuoteSeeder::class,
                SalesOrderSeeder::class,
                PurchaseOrderSeeder::class,
                InvoiceSeeder::class,
                DeliveryOrderSeeder::class,
                ReturnOrderSeeder::class,
                ReceiptOrderSeeder::class,
                ProjectSeeder::class,
                ProjectTaskSeeder::class,
                MeetingSeeder::class,
                CallSeeder::class,
                CaseSeeder::class,
                DocumentFolderSeeder::class,
                DocumentTypeSeeder::class,
                DocumentSeeder::class,
                CountrySeeder::class,

                // // Assign specific data to Sarah Johnson
                SarahJohnsonDataSeeder::class,

                // Removed seeders
                WebhookSeeder::class,

                EmailTemplateSeeder::class,
                NotificationTemplateSeeder::class,
                NoteSeeder::class,
                AnnouncementSeeder::class,

                ContactMessageSeeder::class,
                NewsletterSeeder::class,

                LoginHistorySeeder::class,
            ]);
        }
    }
}
