<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Dashboard permissions
            ['name' => 'manage-dashboard', 'module' => 'dashboard', 'label' => 'Manage Dashboard', 'description' => 'Can view dashboard'],
            ['name' => 'view-dashboard', 'module' => 'dashboard', 'label' => 'View Dashboard', 'description' => 'Can view dashboard'],

            // Calendar permissions
            ['name' => 'manage-calendar', 'module' => 'calendar', 'label' => 'Manage Calendar', 'description' => 'Can manage calendar'],
            ['name' => 'view-calendar', 'module' => 'calendar', 'label' => 'View Calendar', 'description' => 'Can view calendar'],

            // User management
            ['name' => 'manage-users', 'module' => 'users', 'label' => 'Manage Users', 'description' => 'Can manage users'],
            ['name' => 'manage-any-users', 'module' => 'users', 'label' => 'Manage All Users', 'description' => 'Manage Any Users'],
            ['name' => 'manage-own-users', 'module' => 'users', 'label' => 'Manage Own Users', 'description' => 'Manage Limited Users that is created by own'],
            ['name' => 'view-users', 'module' => 'users', 'label' => 'View Users', 'description' => 'View Users'],
            ['name' => 'create-users', 'module' => 'users', 'label' => 'Create Users', 'description' => 'Can create users'],
            ['name' => 'edit-users', 'module' => 'users', 'label' => 'Edit Users', 'description' => 'Can edit users'],
            ['name' => 'delete-users', 'module' => 'users', 'label' => 'Delete Users', 'description' => 'Can delete users'],
            ['name' => 'reset-password-users', 'module' => 'users', 'label' => 'Reset Password Users', 'description' => 'Can reset password users'],
            ['name' => 'toggle-status-users', 'module' => 'users', 'label' => 'Change Status Users', 'description' => 'Can change status users'],

            // Role management
            ['name' => 'manage-roles', 'module' => 'roles', 'label' => 'Manage Roles', 'description' => 'Can manage roles'],
            ['name' => 'manage-any-roles', 'module' => 'roles', 'label' => 'Manage All Roles', 'description' => 'Manage Any Roles'],
            ['name' => 'manage-own-roles', 'module' => 'roles', 'label' => 'Manage Own Roles', 'description' => 'Manage Limited Roles that is created by own'],
            ['name' => 'view-roles', 'module' => 'roles', 'label' => 'View Roles', 'description' => 'View Roles'],
            ['name' => 'create-roles', 'module' => 'roles', 'label' => 'Create Roles', 'description' => 'Can create roles'],
            ['name' => 'edit-roles', 'module' => 'roles', 'label' => 'Edit Roles', 'description' => 'Can edit roles'],
            ['name' => 'delete-roles', 'module' => 'roles', 'label' => 'Delete Roles', 'description' => 'Can delete roles'],

            // Permission management
            ['name' => 'manage-permissions', 'module' => 'permissions', 'label' => 'Manage Permissions', 'description' => 'Can manage permissions'],
            ['name' => 'manage-any-permissions', 'module' => 'permissions', 'label' => 'Manage All Permissions', 'description' => 'Manage Any Permissions'],
            ['name' => 'manage-own-permissions', 'module' => 'permissions', 'label' => 'Manage Own Permissions', 'description' => 'Manage Limited Permissions that is created by own'],
            ['name' => 'view-permissions', 'module' => 'permissions', 'label' => 'View Permissions', 'description' => 'View Permissions'],
            ['name' => 'create-permissions', 'module' => 'permissions', 'label' => 'Create Permissions', 'description' => 'Can create permissions'],
            ['name' => 'edit-permissions', 'module' => 'permissions', 'label' => 'Edit Permissions', 'description' => 'Can edit permissions'],
            ['name' => 'delete-permissions', 'module' => 'permissions', 'label' => 'Delete Permissions', 'description' => 'Can delete permissions'],

            // Company management
            ['name' => 'manage-companies', 'module' => 'companies', 'label' => 'Manage Companies', 'description' => 'Can manage Companies'],
            ['name' => 'manage-any-companies', 'module' => 'companies', 'label' => 'Manage All Companies', 'description' => 'Manage Any Companies'],
            ['name' => 'manage-own-companies', 'module' => 'companies', 'label' => 'Manage Own Companies', 'description' => 'Manage Limited Companies that is created by own'],
            ['name' => 'view-companies', 'module' => 'companies', 'label' => 'View Companies', 'description' => 'View Companies'],
            ['name' => 'create-companies', 'module' => 'companies', 'label' => 'Create Companies', 'description' => 'Can create Companies'],
            ['name' => 'edit-companies', 'module' => 'companies', 'label' => 'Edit Companies', 'description' => 'Can edit Companies'],
            ['name' => 'delete-companies', 'module' => 'companies', 'label' => 'Delete Companies', 'description' => 'Can delete Companies'],
            ['name' => 'reset-password-companies', 'module' => 'companies', 'label' => 'Reset Password Companies', 'description' => 'Can reset password Companies'],
            ['name' => 'toggle-status-companies', 'module' => 'companies', 'label' => 'Change Status Companies', 'description' => 'Can change status companies'],
            ['name' => 'manage-plans-companies', 'module' => 'companies', 'label' => 'Manage Plan Companies', 'description' => 'Can manage plans companies'],
            ['name' => 'upgrade-plan-companies', 'module' => 'companies', 'label' => 'Upgrade Plan Companies', 'description' => 'Can upgrade plan of companies'],

            // Plan management
            ['name' => 'manage-plans', 'module' => 'plans', 'label' => 'Manage Plans', 'description' => 'Can manage subscription plans'],
            ['name' => 'manage-any-plans', 'module' => 'plans', 'label' => 'Manage All Plans', 'description' => 'Manage Any Plans'],
            ['name' => 'manage-own-plans', 'module' => 'plans', 'label' => 'Manage Own Plans', 'description' => 'Manage Limited Plans that is created by own'],
            ['name' => 'view-plans', 'module' => 'plans', 'label' => 'View Plans', 'description' => 'View Plans'],
            ['name' => 'create-plans', 'module' => 'plans', 'label' => 'Create Plans', 'description' => 'Can create subscription plans'],
            ['name' => 'edit-plans', 'module' => 'plans', 'label' => 'Edit Plans', 'description' => 'Can edit subscription plans'],
            ['name' => 'delete-plans', 'module' => 'plans', 'label' => 'Delete Plans', 'description' => 'Can delete subscription plans'],
            ['name' => 'request-plans', 'module' => 'plans', 'label' => 'Request Plans', 'description' => 'Can request subscription plans'],
            ['name' => 'trial-plans', 'module' => 'plans', 'label' => 'Trial Plans', 'description' => 'Can start trial for subscription plans'],
            ['name' => 'subscribe-plans', 'module' => 'plans', 'label' => 'Subscribe Plans', 'description' => 'Can subscribe to subscription plans'],


            // Coupon management
            ['name' => 'manage-coupons', 'module' => 'coupons', 'label' => 'Manage Coupons', 'description' => 'Can manage subscription Coupons'],
            ['name' => 'manage-any-coupons', 'module' => 'coupons', 'label' => 'Manage All Coupons', 'description' => 'Manage Any Coupons'],
            ['name' => 'manage-own-coupons', 'module' => 'coupons', 'label' => 'Manage Own Coupons', 'description' => 'Manage Limited Coupons that is created by own'],
            ['name' => 'view-coupons', 'module' => 'coupons', 'label' => 'View Coupons', 'description' => 'View Coupons'],
            ['name' => 'create-coupons', 'module' => 'coupons', 'label' => 'Create Coupons', 'description' => 'Can create subscription Coupons'],
            ['name' => 'edit-coupons', 'module' => 'coupons', 'label' => 'Edit Coupons', 'description' => 'Can edit subscription Coupons'],
            ['name' => 'delete-coupons', 'module' => 'coupons', 'label' => 'Delete Coupons', 'description' => 'Can delete subscription Coupons'],
            ['name' => 'toggle-status-coupons', 'module' => 'coupons', 'label' => 'Change Status Coupons', 'description' => 'Can change status Coupons'],

            // Plan Requests management
            ['name' => 'manage-plan-requests', 'module' => 'plan_requests', 'label' => 'Manage Plan Requests', 'description' => 'Can manage plan requests'],
            ['name' => 'view-plan-requests', 'module' => 'plan_requests', 'label' => 'View Plan Requests', 'description' => 'View Plan Requests'],
            ['name' => 'create-plan-requests', 'module' => 'plan_requests', 'label' => 'Create Plan Requests', 'description' => 'Can create plan requests'],
            ['name' => 'edit-plan-requests', 'module' => 'plan_requests', 'label' => 'Edit Plan Requests', 'description' => 'Can edit plan requests'],
            ['name' => 'delete-plan-requests', 'module' => 'plan_requests', 'label' => 'Delete Plan Requests', 'description' => 'Can delete plan requests'],
            ['name' => 'approve-plan-requests', 'module' => 'plan_requests', 'label' => 'Approve plan requests', 'description' => 'Can approve plan requests'],
            ['name' => 'reject-plan-requests', 'module' => 'plan_requests', 'label' => 'Reject plan requests', 'description' => 'Can reject plplan requests'],

            // Plan Orders management
            ['name' => 'manage-plan-orders', 'module' => 'plan_orders', 'label' => 'Manage Plan Orders', 'description' => 'Can manage plan orders'],
            ['name' => 'view-plan-orders', 'module' => 'plan_orders', 'label' => 'View Plan Orders', 'description' => 'View Plan Orders'],
            ['name' => 'create-plan-orders', 'module' => 'plan_orders', 'label' => 'Create Plan Orders', 'description' => 'Can create plan orders'],
            ['name' => 'edit-plan-orders', 'module' => 'plan_orders', 'label' => 'Edit Plan Orders', 'description' => 'Can edit plan orders'],
            ['name' => 'delete-plan-orders', 'module' => 'plan_orders', 'label' => 'Delete Plan Orders', 'description' => 'Can delete plan orders'],
            ['name' => 'approve-plan-orders', 'module' => 'plan_orders', 'label' => 'Approve Plan Orders', 'description' => 'Can approve plan orders'],
            ['name' => 'reject-plan-orders', 'module' => 'plan_orders', 'label' => 'Reject Plan Orders', 'description' => 'Can reject plan orders'],


            // Settings
            ['name' => 'manage-settings', 'module' => 'settings', 'label' => 'Manage Settings', 'description' => 'Can manage All settings'],
            ['name' => 'manage-system-settings', 'module' => 'settings', 'label' => 'Manage System Settings', 'description' => 'Can manage system settings'],
            ['name' => 'manage-email-settings', 'module' => 'settings', 'label' => 'Manage Email Settings', 'description' => 'Can manage email settings'],
            ['name' => 'manage-brand-settings', 'module' => 'settings', 'label' => 'Manage Brand Settings', 'description' => 'Can manage brand settings'],
            ['name' => 'manage-company-settings', 'module' => 'settings', 'label' => 'Manage Company Settings', 'description' => 'Can manage Company settings'],

            ['name' => 'manage-payment-settings', 'module' => 'settings', 'label' => 'Manage Payment Settings', 'description' => 'Can manage payment settings'],
            ['name' => 'manage-currency-settings', 'module' => 'settings', 'label' => 'Manage Currency Settings', 'description' => 'Can manage currency settings'],
            ['name' => 'manage-recaptcha-settings', 'module' => 'settings', 'label' => 'Manage ReCaptch Settings', 'description' => 'Can manage recaptcha settings'],
            ['name' => 'manage-chatgpt-settings', 'module' => 'settings', 'label' => 'Manage ChatGpt Settings', 'description' => 'Can manage chatgpt settings'],
            ['name' => 'manage-cookie-settings', 'module' => 'settings', 'label' => 'Manage Cookie(GDPR) Settings', 'description' => 'Can manage cookie settings'],
            ['name' => 'manage-seo-settings', 'module' => 'settings', 'label' => 'Manage Seo Settings', 'description' => 'Can manage seo settings'],
            ['name' => 'manage-cache-settings', 'module' => 'settings', 'label' => 'Manage Cache Settings', 'description' => 'Can manage cache settings'],
            ['name' => 'manage-storage-settings', 'module' => 'settings', 'label' => 'Manage Storage Settings', 'description' => 'Can manage storage settings'],
            ['name' => 'manage-account-settings', 'module' => 'settings', 'label' => 'Manage Account Settings', 'description' => 'Can manage account settings'],
            ['name' => 'manage-invoices-settings', 'module' => 'settings', 'label' => 'Manage Invoices Settings', 'description' => 'Can manage invoices settings'],
            ['name' => 'manage-quotes-settings', 'module' => 'settings', 'label' => 'Manage Quotes Settings', 'description' => 'Can manage quotes settings'],
            ['name' => 'manage-sales-orders-settings', 'module' => 'settings', 'label' => 'Manage Sales Orders Settings', 'description' => 'Can manage sales orders settings'],


            // Currency management
            ['name' => 'manage-currencies', 'module' => 'currencies', 'label' => 'Manage Currencies', 'description' => 'Can manage currencies'],
            ['name' => 'manage-any-currencies', 'module' => 'currencies', 'label' => 'Manage All currencies', 'description' => 'Manage Any currencies'],
            ['name' => 'manage-own-currencies', 'module' => 'currencies', 'label' => 'Manage Own currencies', 'description' => 'Manage Limited currencies that is created by own'],
            ['name' => 'view-currencies', 'module' => 'currencies', 'label' => 'View Currencies', 'description' => 'View Currencies'],
            ['name' => 'create-currencies', 'module' => 'currencies', 'label' => 'Create Currencies', 'description' => 'Can create currencies'],
            ['name' => 'edit-currencies', 'module' => 'currencies', 'label' => 'Edit Currencies', 'description' => 'Can edit currencies'],
            ['name' => 'delete-currencies', 'module' => 'currencies', 'label' => 'Delete Currencies', 'description' => 'Can delete currencies'],



            // Referral management
            ['name' => 'manage-referral', 'module' => 'referral', 'label' => 'Manage Referral', 'description' => 'Can manage referral program'],
            ['name' => 'manage-users-referral', 'module' => 'referral', 'label' => 'Manage User Referral', 'description' => 'Can manage user referral program'],
            ['name' => 'manage-setting-referral', 'module' => 'referral', 'label' => 'Manage Referral Setting', 'description' => 'Can manage Referral Setting'],
            ['name' => 'manage-payout-referral', 'module' => 'referral', 'label' => 'Manage Referral Payout', 'description' => 'Can manage Referral Payout program'],
            ['name' => 'approve-payout-referral', 'module' => 'referral', 'label' => 'Manage Referral', 'description' => 'Can approve payout request'],
            ['name' => 'reject-payout-referral', 'module' => 'referral', 'label' => 'Manage Referral', 'description' => 'Can approve payout request'],

            // Language management
            ['name' => 'manage-language', 'module' => 'language', 'label' => 'Manage Language', 'description' => 'Can manage language'],
            ['name' => 'edit-language', 'module' => 'language', 'label' => 'Edit Language', 'description' => 'Edit Language'],
            ['name' => 'view-language', 'module' => 'language', 'label' => 'View Language', 'description' => 'View Language'],

            // Media management
            ['name' => 'manage-media', 'module' => 'media', 'label' => 'Manage Media', 'description' => 'Can manage media'],
            ['name' => 'manage-any-media', 'module' => 'media', 'label' => 'Manage All Media', 'description' => 'Manage Any media'],
            ['name' => 'manage-own-media', 'module' => 'media', 'label' => 'Manage Own Media', 'description' => 'Manage Limited media that is created by own'],
            ['name' => 'create-media', 'module' => 'media', 'label' => 'Create media', 'description' => 'Create media'],
            ['name' => 'edit-media', 'module' => 'media', 'label' => 'Edit media', 'description' => 'Edit media'],
            ['name' => 'delete-media', 'module' => 'media', 'label' => 'Delete media', 'description' => 'Delete media'],
            ['name' => 'view-media', 'module' => 'media', 'label' => 'View media', 'description' => 'View media'],
            ['name' => 'download-media', 'module' => 'media', 'label' => 'Download media', 'description' => 'Download media'],

            // Webhook management
            // ['name' => 'manage-webhook-settings', 'module' => 'settings', 'label' => 'Manage Webhook Settings', 'description' => 'Can manage webhook settings'],
            // Landing Page management
            ['name' => 'manage-landing-page', 'module' => 'landing_page', 'label' => 'Manage Landing Page', 'description' => 'Can manage landing page'],
            ['name' => 'view-landing-page', 'module' => 'landing_page', 'label' => 'View Landing Page', 'description' => 'View landing page'],
            ['name' => 'edit-landing-page', 'module' => 'landing_page', 'label' => 'Edit Landing Page', 'description' => 'Edit landing page'],

            // Tax management
            ['name' => 'manage-taxes', 'module' => 'taxes', 'label' => 'Manage Taxes', 'description' => 'Can manage taxes'],
            ['name' => 'view-taxes', 'module' => 'taxes', 'label' => 'View Taxes', 'description' => 'View Taxes'],
            ['name' => 'create-taxes', 'module' => 'taxes', 'label' => 'Create Taxes', 'description' => 'Can create taxes'],
            ['name' => 'edit-taxes', 'module' => 'taxes', 'label' => 'Edit Taxes', 'description' => 'Can edit taxes'],
            ['name' => 'delete-taxes', 'module' => 'taxes', 'label' => 'Delete Taxes', 'description' => 'Can delete taxes'],
            ['name' => 'toggle-status-taxes', 'module' => 'taxes', 'label' => 'Toggle Status Taxes', 'description' => 'Can toggle status taxes'],

            // Brand management
            ['name' => 'manage-brands', 'module' => 'brands', 'label' => 'Manage Brands', 'description' => 'Can manage brands'],
            ['name' => 'view-brands', 'module' => 'brands', 'label' => 'View Brands', 'description' => 'View Brands'],
            ['name' => 'create-brands', 'module' => 'brands', 'label' => 'Create Brands', 'description' => 'Can create brands'],
            ['name' => 'edit-brands', 'module' => 'brands', 'label' => 'Edit Brands', 'description' => 'Can edit brands'],
            ['name' => 'delete-brands', 'module' => 'brands', 'label' => 'Delete Brands', 'description' => 'Can delete brands'],
            ['name' => 'toggle-status-brands', 'module' => 'brands', 'label' => 'Toggle Status Brands', 'description' => 'Can toggle status brands'],

            // Category management
            ['name' => 'manage-categories', 'module' => 'categories', 'label' => 'Manage Categories', 'description' => 'Can manage categories'],
            ['name' => 'view-categories', 'module' => 'categories', 'label' => 'View Categories', 'description' => 'View Categories'],
            ['name' => 'create-categories', 'module' => 'categories', 'label' => 'Create Categories', 'description' => 'Can create categories'],
            ['name' => 'edit-categories', 'module' => 'categories', 'label' => 'Edit Categories', 'description' => 'Can edit categories'],
            ['name' => 'delete-categories', 'module' => 'categories', 'label' => 'Delete Categories', 'description' => 'Can delete categories'],
            ['name' => 'toggle-status-categories', 'module' => 'categories', 'label' => 'Toggle Status Categories', 'description' => 'Can toggle status categories'],

            // Product management
            ['name' => 'manage-products', 'module' => 'products', 'label' => 'Manage Products', 'description' => 'Can manage products'],
            ['name' => 'view-products', 'module' => 'products', 'label' => 'View Products', 'description' => 'View Products'],
            ['name' => 'create-products', 'module' => 'products', 'label' => 'Create Products', 'description' => 'Can create products'],
            ['name' => 'edit-products', 'module' => 'products', 'label' => 'Edit Products', 'description' => 'Can edit products'],
            ['name' => 'delete-products', 'module' => 'products', 'label' => 'Delete Products', 'description' => 'Can delete products'],
            ['name' => 'toggle-status-products', 'module' => 'products', 'label' => 'Toggle Status Products', 'description' => 'Can toggle status products'],
            ['name' => 'export-products', 'module' => 'products', 'label' => 'Export Products', 'description' => 'Can export products'],
            ['name' => 'import-products', 'module' => 'products', 'label' => 'Import Products', 'description' => 'Can import products'],

            // Contact management
            ['name' => 'manage-contacts', 'module' => 'contacts', 'label' => 'Manage Contacts', 'description' => 'Can manage contacts'],
            ['name' => 'view-contacts', 'module' => 'contacts', 'label' => 'View Contacts', 'description' => 'View Contacts'],
            ['name' => 'create-contacts', 'module' => 'contacts', 'label' => 'Create Contacts', 'description' => 'Can create contacts'],
            ['name' => 'edit-contacts', 'module' => 'contacts', 'label' => 'Edit Contacts', 'description' => 'Can edit contacts'],
            ['name' => 'delete-contacts', 'module' => 'contacts', 'label' => 'Delete Contacts', 'description' => 'Can delete contacts'],
            ['name' => 'toggle-status-contacts', 'module' => 'contacts', 'label' => 'Toggle Status Contacts', 'description' => 'Can toggle status contacts'],
            ['name' => 'export-contacts', 'module' => 'contacts', 'label' => 'Export Contacts', 'description' => 'Can export contacts'],

            // Account management
            ['name' => 'manage-accounts', 'module' => 'accounts', 'label' => 'Manage Accounts', 'description' => 'Can manage accounts'],
            ['name' => 'view-accounts', 'module' => 'accounts', 'label' => 'View Accounts', 'description' => 'View Accounts'],
            ['name' => 'create-accounts', 'module' => 'accounts', 'label' => 'Create Accounts', 'description' => 'Can create accounts'],
            ['name' => 'edit-accounts', 'module' => 'accounts', 'label' => 'Edit Accounts', 'description' => 'Can edit accounts'],
            ['name' => 'delete-accounts', 'module' => 'accounts', 'label' => 'Delete Accounts', 'description' => 'Can delete accounts'],
            ['name' => 'toggle-status-accounts', 'module' => 'accounts', 'label' => 'Toggle Status Accounts', 'description' => 'Can toggle status accounts'],
            ['name' => 'export-accounts', 'module' => 'accounts', 'label' => 'Export Accounts', 'description' => 'Can export accounts'],

            // Account Type management
            ['name' => 'manage-account-types', 'module' => 'account_types', 'label' => 'Manage Account Types', 'description' => 'Can manage account types'],
            ['name' => 'view-account-types', 'module' => 'account_types', 'label' => 'View Account Types', 'description' => 'View Account Types'],
            ['name' => 'create-account-types', 'module' => 'account_types', 'label' => 'Create Account Types', 'description' => 'Can create account types'],
            ['name' => 'edit-account-types', 'module' => 'account_types', 'label' => 'Edit Account Types', 'description' => 'Can edit account types'],
            ['name' => 'delete-account-types', 'module' => 'account_types', 'label' => 'Delete Account Types', 'description' => 'Can delete account types'],
            ['name' => 'toggle-status-account-types', 'module' => 'account_types', 'label' => 'Toggle Status Account Types', 'description' => 'Can toggle status account types'],

            // Account Industry management
            ['name' => 'manage-account-industries', 'module' => 'account_industries', 'label' => 'Manage Account Industries', 'description' => 'Can manage account industries'],
            ['name' => 'view-account-industries', 'module' => 'account_industries', 'label' => 'View Account Industries', 'description' => 'View Account Industries'],
            ['name' => 'create-account-industries', 'module' => 'account_industries', 'label' => 'Create Account Industries', 'description' => 'Can create account industries'],
            ['name' => 'edit-account-industries', 'module' => 'account_industries', 'label' => 'Edit Account Industries', 'description' => 'Can edit account industries'],
            ['name' => 'delete-account-industries', 'module' => 'account_industries', 'label' => 'Delete Account Industries', 'description' => 'Can delete account industries'],
            ['name' => 'toggle-status-account-industries', 'module' => 'account_industries', 'label' => 'Toggle Status Account Industries', 'description' => 'Can toggle status account industries'],

            // Lead Status management
            ['name' => 'manage-lead-statuses', 'module' => 'lead_statuses', 'label' => 'Manage Lead Statuses', 'description' => 'Can manage lead statuses'],
            ['name' => 'view-lead-statuses', 'module' => 'lead_statuses', 'label' => 'View Lead Statuses', 'description' => 'View Lead Statuses'],
            ['name' => 'create-lead-statuses', 'module' => 'lead_statuses', 'label' => 'Create Lead Statuses', 'description' => 'Can create lead statuses'],
            ['name' => 'edit-lead-statuses', 'module' => 'lead_statuses', 'label' => 'Edit Lead Statuses', 'description' => 'Can edit lead statuses'],
            ['name' => 'delete-lead-statuses', 'module' => 'lead_statuses', 'label' => 'Delete Lead Statuses', 'description' => 'Can delete lead statuses'],
            ['name' => 'toggle-status-lead-statuses', 'module' => 'lead_statuses', 'label' => 'Toggle Status Lead Statuses', 'description' => 'Can toggle status lead statuses'],

            // Lead Source management
            ['name' => 'manage-lead-sources', 'module' => 'lead_sources', 'label' => 'Manage Lead Sources', 'description' => 'Can manage lead sources'],
            ['name' => 'view-lead-sources', 'module' => 'lead_sources', 'label' => 'View Lead Sources', 'description' => 'View Lead Sources'],
            ['name' => 'create-lead-sources', 'module' => 'lead_sources', 'label' => 'Create Lead Sources', 'description' => 'Can create lead sources'],
            ['name' => 'edit-lead-sources', 'module' => 'lead_sources', 'label' => 'Edit Lead Sources', 'description' => 'Can edit lead sources'],
            ['name' => 'delete-lead-sources', 'module' => 'lead_sources', 'label' => 'Delete Lead Sources', 'description' => 'Can delete lead sources'],
            ['name' => 'toggle-status-lead-sources', 'module' => 'lead_sources', 'label' => 'Toggle Status Lead Sources', 'description' => 'Can toggle status lead sources'],

            // Lead management
            ['name' => 'manage-leads', 'module' => 'leads', 'label' => 'Manage Leads', 'description' => 'Can manage leads'],
            ['name' => 'view-leads', 'module' => 'leads', 'label' => 'View Leads', 'description' => 'View Leads'],
            ['name' => 'create-leads', 'module' => 'leads', 'label' => 'Create Leads', 'description' => 'Can create leads'],
            ['name' => 'edit-leads', 'module' => 'leads', 'label' => 'Edit Leads', 'description' => 'Can edit leads'],
            ['name' => 'delete-leads', 'module' => 'leads', 'label' => 'Delete Leads', 'description' => 'Can delete leads'],
            ['name' => 'toggle-status-leads', 'module' => 'leads', 'label' => 'Toggle Status Leads', 'description' => 'Can toggle status leads'],
            ['name' => 'convert-leads', 'module' => 'leads', 'label' => 'Convert Leads', 'description' => 'Can convert leads to accounts/contacts'],
            ['name' => 'export-leads', 'module' => 'leads', 'label' => 'Export Leads', 'description' => 'Can export leads'],
            ['name' => 'import-leads', 'module' => 'leads', 'label' => 'Import Leads', 'description' => 'Can import leads'],

            // Opportunity Stage management
            ['name' => 'manage-opportunity-stages', 'module' => 'opportunity_stages', 'label' => 'Manage Opportunity Stages', 'description' => 'Can manage opportunity stages'],
            ['name' => 'view-opportunity-stages', 'module' => 'opportunity_stages', 'label' => 'View Opportunity Stages', 'description' => 'View Opportunity Stages'],
            ['name' => 'create-opportunity-stages', 'module' => 'opportunity_stages', 'label' => 'Create Opportunity Stages', 'description' => 'Can create opportunity stages'],
            ['name' => 'edit-opportunity-stages', 'module' => 'opportunity_stages', 'label' => 'Edit Opportunity Stages', 'description' => 'Can edit opportunity stages'],
            ['name' => 'delete-opportunity-stages', 'module' => 'opportunity_stages', 'label' => 'Delete Opportunity Stages', 'description' => 'Can delete opportunity stages'],
            ['name' => 'toggle-status-opportunity-stages', 'module' => 'opportunity_stages', 'label' => 'Toggle Status Opportunity Stages', 'description' => 'Can toggle status opportunity stages'],

            // Opportunity Source management
            ['name' => 'manage-opportunity-sources', 'module' => 'opportunity_sources', 'label' => 'Manage Opportunity Sources', 'description' => 'Can manage opportunity sources'],
            ['name' => 'view-opportunity-sources', 'module' => 'opportunity_sources', 'label' => 'View Opportunity Sources', 'description' => 'View Opportunity Sources'],
            ['name' => 'create-opportunity-sources', 'module' => 'opportunity_sources', 'label' => 'Create Opportunity Sources', 'description' => 'Can create opportunity sources'],
            ['name' => 'edit-opportunity-sources', 'module' => 'opportunity_sources', 'label' => 'Edit Opportunity Sources', 'description' => 'Can edit opportunity sources'],
            ['name' => 'delete-opportunity-sources', 'module' => 'opportunity_sources', 'label' => 'Delete Opportunity Sources', 'description' => 'Can delete opportunity sources'],
            ['name' => 'toggle-status-opportunity-sources', 'module' => 'opportunity_sources', 'label' => 'Toggle Status Opportunity Sources', 'description' => 'Can toggle status opportunity sources'],

            // Opportunity management
            ['name' => 'manage-opportunities', 'module' => 'opportunities', 'label' => 'Manage Opportunities', 'description' => 'Can manage opportunities'],
            ['name' => 'view-opportunities', 'module' => 'opportunities', 'label' => 'View Opportunities', 'description' => 'View Opportunities'],
            ['name' => 'create-opportunities', 'module' => 'opportunities', 'label' => 'Create Opportunities', 'description' => 'Can create opportunities'],
            ['name' => 'edit-opportunities', 'module' => 'opportunities', 'label' => 'Edit Opportunities', 'description' => 'Can edit opportunities'],
            ['name' => 'delete-opportunities', 'module' => 'opportunities', 'label' => 'Delete Opportunities', 'description' => 'Can delete opportunities'],
            ['name' => 'toggle-status-opportunities', 'module' => 'opportunities', 'label' => 'Toggle Status Opportunities', 'description' => 'Can toggle status opportunities'],
            ['name' => 'export-opportunities', 'module' => 'opportunities', 'label' => 'Export Opportunities', 'description' => 'Can export opportunities'],

            // Campaign Type management
            ['name' => 'manage-campaign-types', 'module' => 'campaign_types', 'label' => 'Manage Campaign Types', 'description' => 'Can manage campaign types'],
            ['name' => 'view-campaign-types', 'module' => 'campaign_types', 'label' => 'View Campaign Types', 'description' => 'View Campaign Types'],
            ['name' => 'create-campaign-types', 'module' => 'campaign_types', 'label' => 'Create Campaign Types', 'description' => 'Can create campaign types'],
            ['name' => 'edit-campaign-types', 'module' => 'campaign_types', 'label' => 'Edit Campaign Types', 'description' => 'Can edit campaign types'],
            ['name' => 'delete-campaign-types', 'module' => 'campaign_types', 'label' => 'Delete Campaign Types', 'description' => 'Can delete campaign types'],
            ['name' => 'toggle-status-campaign-types', 'module' => 'campaign_types', 'label' => 'Toggle Status Campaign Types', 'description' => 'Can toggle status campaign types'],

            // Target List management
            ['name' => 'manage-target-lists', 'module' => 'target_lists', 'label' => 'Manage Target Lists', 'description' => 'Can manage target lists'],
            ['name' => 'view-target-lists', 'module' => 'target_lists', 'label' => 'View Target Lists', 'description' => 'View Target Lists'],
            ['name' => 'create-target-lists', 'module' => 'target_lists', 'label' => 'Create Target Lists', 'description' => 'Can create target lists'],
            ['name' => 'edit-target-lists', 'module' => 'target_lists', 'label' => 'Edit Target Lists', 'description' => 'Can edit target lists'],
            ['name' => 'delete-target-lists', 'module' => 'target_lists', 'label' => 'Delete Target Lists', 'description' => 'Can delete target lists'],
            ['name' => 'toggle-status-target-lists', 'module' => 'target_lists', 'label' => 'Toggle Status Target Lists', 'description' => 'Can toggle status target lists'],

            // Campaign management
            ['name' => 'manage-campaigns', 'module' => 'campaigns', 'label' => 'Manage Campaigns', 'description' => 'Can manage campaigns'],
            ['name' => 'view-campaigns', 'module' => 'campaigns', 'label' => 'View Campaigns', 'description' => 'View Campaigns'],
            ['name' => 'create-campaigns', 'module' => 'campaigns', 'label' => 'Create Campaigns', 'description' => 'Can create campaigns'],
            ['name' => 'edit-campaigns', 'module' => 'campaigns', 'label' => 'Edit Campaigns', 'description' => 'Can edit campaigns'],
            ['name' => 'delete-campaigns', 'module' => 'campaigns', 'label' => 'Delete Campaigns', 'description' => 'Can delete campaigns'],
            ['name' => 'toggle-status-campaigns', 'module' => 'campaigns', 'label' => 'Toggle Status Campaigns', 'description' => 'Can toggle status campaigns'],

            // Shipping Provider Type management
            ['name' => 'manage-shipping-provider-types', 'module' => 'shipping_provider_types', 'label' => 'Manage Shipping Provider Types', 'description' => 'Can manage shipping provider types'],
            ['name' => 'view-shipping-provider-types', 'module' => 'shipping_provider_types', 'label' => 'View Shipping Provider Types', 'description' => 'View Shipping Provider Types'],
            ['name' => 'create-shipping-provider-types', 'module' => 'shipping_provider_types', 'label' => 'Create Shipping Provider Types', 'description' => 'Can create shipping provider types'],
            ['name' => 'edit-shipping-provider-types', 'module' => 'shipping_provider_types', 'label' => 'Edit Shipping Provider Types', 'description' => 'Can edit shipping provider types'],
            ['name' => 'delete-shipping-provider-types', 'module' => 'shipping_provider_types', 'label' => 'Delete Shipping Provider Types', 'description' => 'Can delete shipping provider types'],
            ['name' => 'toggle-status-shipping-provider-types', 'module' => 'shipping_provider_types', 'label' => 'Toggle Status Shipping Provider Types', 'description' => 'Can toggle status shipping provider types'],

            // Case management
            ['name' => 'manage-cases', 'module' => 'cases', 'label' => 'Manage Cases', 'description' => 'Can manage cases'],
            ['name' => 'view-cases', 'module' => 'cases', 'label' => 'View Cases', 'description' => 'View Cases'],
            ['name' => 'create-cases', 'module' => 'cases', 'label' => 'Create Cases', 'description' => 'Can create cases'],
            ['name' => 'edit-cases', 'module' => 'cases', 'label' => 'Edit Cases', 'description' => 'Can edit cases'],
            ['name' => 'delete-cases', 'module' => 'cases', 'label' => 'Delete Cases', 'description' => 'Can delete cases'],
            ['name' => 'toggle-status-cases', 'module' => 'cases', 'label' => 'Toggle Status Cases', 'description' => 'Can toggle status cases'],
            ['name' => 'export-cases', 'module' => 'cases', 'label' => 'Export Cases', 'description' => 'Can export cases'],

            // Quote management
            ['name' => 'manage-quotes', 'module' => 'quotes', 'label' => 'Manage Quotes', 'description' => 'Can manage quotes'],
            ['name' => 'view-quotes', 'module' => 'quotes', 'label' => 'View Quotes', 'description' => 'View Quotes'],
            ['name' => 'create-quotes', 'module' => 'quotes', 'label' => 'Create Quotes', 'description' => 'Can create quotes'],
            ['name' => 'edit-quotes', 'module' => 'quotes', 'label' => 'Edit Quotes', 'description' => 'Can edit quotes'],
            ['name' => 'delete-quotes', 'module' => 'quotes', 'label' => 'Delete Quotes', 'description' => 'Can delete quotes'],
            ['name' => 'toggle-status-quotes', 'module' => 'quotes', 'label' => 'Toggle Status Quotes', 'description' => 'Can toggle status quotes'],
            ['name' => 'export-quotes', 'module' => 'quotes', 'label' => 'Export Quotes', 'description' => 'Can export quotes'],

            // Sales Order management
            ['name' => 'manage-sales-orders', 'module' => 'sales_orders', 'label' => 'Manage Sales Orders', 'description' => 'Can manage sales orders'],
            ['name' => 'view-sales-orders', 'module' => 'sales_orders', 'label' => 'View Sales Orders', 'description' => 'View Sales Orders'],
            ['name' => 'create-sales-orders', 'module' => 'sales_orders', 'label' => 'Create Sales Orders', 'description' => 'Can create sales orders'],
            ['name' => 'edit-sales-orders', 'module' => 'sales_orders', 'label' => 'Edit Sales Orders', 'description' => 'Can edit sales orders'],
            ['name' => 'delete-sales-orders', 'module' => 'sales_orders', 'label' => 'Delete Sales Orders', 'description' => 'Can delete sales orders'],
            ['name' => 'toggle-status-sales-orders', 'module' => 'sales_orders', 'label' => 'Toggle Status Sales Orders', 'description' => 'Can toggle status sales orders'],
            ['name' => 'export-sales-orders', 'module' => 'sales_orders', 'label' => 'Export Sales Orders', 'description' => 'Can export sales orders'],

            // Invoice management
            ['name' => 'manage-invoices', 'module' => 'invoices', 'label' => 'Manage Invoices', 'description' => 'Can manage invoices'],
            ['name' => 'view-invoices', 'module' => 'invoices', 'label' => 'View Invoices', 'description' => 'View Invoices'],
            ['name' => 'create-invoices', 'module' => 'invoices', 'label' => 'Create Invoices', 'description' => 'Can create invoices'],
            ['name' => 'edit-invoices', 'module' => 'invoices', 'label' => 'Edit Invoices', 'description' => 'Can edit invoices'],
            ['name' => 'delete-invoices', 'module' => 'invoices', 'label' => 'Delete Invoices', 'description' => 'Can delete invoices'],
            ['name' => 'toggle-status-invoices', 'module' => 'invoices', 'label' => 'Toggle Status Invoices', 'description' => 'Can toggle status invoices'],
            ['name' => 'export-invoices', 'module' => 'invoices', 'label' => 'Export Invoices', 'description' => 'Can export invoices'],
            ['name' => 'send-reminder-invoices', 'module' => 'invoices', 'label' => 'Send Reminder Invoices', 'description' => 'Can send payment reminder for invoices'],

            // Delivery Order management
            ['name' => 'manage-delivery-orders', 'module' => 'delivery_orders', 'label' => 'Manage Delivery Orders', 'description' => 'Can manage delivery orders'],
            ['name' => 'view-delivery-orders', 'module' => 'delivery_orders', 'label' => 'View Delivery Orders', 'description' => 'View Delivery Orders'],
            ['name' => 'create-delivery-orders', 'module' => 'delivery_orders', 'label' => 'Create Delivery Orders', 'description' => 'Can create delivery orders'],
            ['name' => 'edit-delivery-orders', 'module' => 'delivery_orders', 'label' => 'Edit Delivery Orders', 'description' => 'Can edit delivery orders'],
            ['name' => 'delete-delivery-orders', 'module' => 'delivery_orders', 'label' => 'Delete Delivery Orders', 'description' => 'Can delete delivery orders'],
            ['name' => 'toggle-status-delivery-orders', 'module' => 'delivery_orders', 'label' => 'Toggle Status Delivery Orders', 'description' => 'Can toggle status delivery orders'],
            ['name' => 'export-delivery-orders', 'module' => 'delivery_orders', 'label' => 'Export Delivery Orders', 'description' => 'Can export delivery orders'],

            // Return Order management
            ['name' => 'manage-return-orders', 'module' => 'return_orders', 'label' => 'Manage Return Orders', 'description' => 'Can manage return orders'],
            ['name' => 'view-return-orders', 'module' => 'return_orders', 'label' => 'View Return Orders', 'description' => 'View Return Orders'],
            ['name' => 'create-return-orders', 'module' => 'return_orders', 'label' => 'Create Return Orders', 'description' => 'Can create return orders'],
            ['name' => 'edit-return-orders', 'module' => 'return_orders', 'label' => 'Edit Return Orders', 'description' => 'Can edit return orders'],
            ['name' => 'delete-return-orders', 'module' => 'return_orders', 'label' => 'Delete Return Orders', 'description' => 'Can delete return orders'],
            ['name' => 'export-return-orders', 'module' => 'return_orders', 'label' => 'Export Return Orders', 'description' => 'Can export return orders'],

            // Purchase Order management
            ['name' => 'manage-purchase-orders', 'module' => 'purchase_orders', 'label' => 'Manage Purchase Orders', 'description' => 'Can manage purchase orders'],
            ['name' => 'view-purchase-orders', 'module' => 'purchase_orders', 'label' => 'View Purchase Orders', 'description' => 'View Purchase Orders'],
            ['name' => 'create-purchase-orders', 'module' => 'purchase_orders', 'label' => 'Create Purchase Orders', 'description' => 'Can create purchase orders'],
            ['name' => 'edit-purchase-orders', 'module' => 'purchase_orders', 'label' => 'Edit Purchase Orders', 'description' => 'Can edit purchase orders'],
            ['name' => 'delete-purchase-orders', 'module' => 'purchase_orders', 'label' => 'Delete Purchase Orders', 'description' => 'Can delete purchase orders'],
            ['name' => 'toggle-status-purchase-orders', 'module' => 'purchase_orders', 'label' => 'Toggle Status Purchase Orders', 'description' => 'Can toggle status purchase orders'],
            ['name' => 'export-purchase-orders', 'module' => 'purchase_orders', 'label' => 'Export Purchase Orders', 'description' => 'Can export purchase orders'],

            // Receipt Order management
            ['name' => 'manage-receipt-orders', 'module' => 'receipt_orders', 'label' => 'Manage Receipt Orders', 'description' => 'Can manage receipt orders'],
            ['name' => 'view-receipt-orders', 'module' => 'receipt_orders', 'label' => 'View Receipt Orders', 'description' => 'View Receipt Orders'],
            ['name' => 'create-receipt-orders', 'module' => 'receipt_orders', 'label' => 'Create Receipt Orders', 'description' => 'Can create receipt orders'],
            ['name' => 'edit-receipt-orders', 'module' => 'receipt_orders', 'label' => 'Edit Receipt Orders', 'description' => 'Can edit receipt orders'],
            ['name' => 'delete-receipt-orders', 'module' => 'receipt_orders', 'label' => 'Delete Receipt Orders', 'description' => 'Can delete receipt orders'],
            ['name' => 'toggle-status-receipt-orders', 'module' => 'receipt_orders', 'label' => 'Toggle Status Receipt Orders', 'description' => 'Can toggle status receipt orders'],
            ['name' => 'export-receipt-orders', 'module' => 'receipt_orders', 'label' => 'Export Receipt Orders', 'description' => 'Can export receipt orders'],

            // Project management
            ['name' => 'manage-projects', 'module' => 'projects', 'label' => 'Manage Projects', 'description' => 'Can manage projects'],
            ['name' => 'view-projects', 'module' => 'projects', 'label' => 'View Projects', 'description' => 'View Projects'],
            ['name' => 'create-projects', 'module' => 'projects', 'label' => 'Create Projects', 'description' => 'Can create projects'],
            ['name' => 'edit-projects', 'module' => 'projects', 'label' => 'Edit Projects', 'description' => 'Can edit projects'],
            ['name' => 'delete-projects', 'module' => 'projects', 'label' => 'Delete Projects', 'description' => 'Can delete projects'],
            ['name' => 'toggle-status-projects', 'module' => 'projects', 'label' => 'Toggle Status Projects', 'description' => 'Can toggle status projects'],
            ['name' => 'export-projects', 'module' => 'projects', 'label' => 'Export Projects', 'description' => 'Can export projects'],

            // Project Task management
            ['name' => 'manage-project-tasks', 'module' => 'project_tasks', 'label' => 'Manage Project Tasks', 'description' => 'Can manage project tasks'],
            ['name' => 'view-project-tasks', 'module' => 'project_tasks', 'label' => 'View Project Tasks', 'description' => 'View Project Tasks'],
            ['name' => 'create-project-tasks', 'module' => 'project_tasks', 'label' => 'Create Project Tasks', 'description' => 'Can create project tasks'],
            ['name' => 'edit-project-tasks', 'module' => 'project_tasks', 'label' => 'Edit Project Tasks', 'description' => 'Can edit project tasks'],
            ['name' => 'delete-project-tasks', 'module' => 'project_tasks', 'label' => 'Delete Project Tasks', 'description' => 'Can delete project tasks'],
            ['name' => 'toggle-status-project-tasks', 'module' => 'project_tasks', 'label' => 'Toggle Status Project Tasks', 'description' => 'Can toggle status project tasks'],
            ['name' => 'export-project-tasks', 'module' => 'project_tasks', 'label' => 'Export Project Tasks', 'description' => 'Can export project tasks'],

            // Task Status management
            ['name' => 'manage-task-statuses', 'module' => 'task_statuses', 'label' => 'Manage Task Statuses', 'description' => 'Can manage task statuses'],
            ['name' => 'view-task-statuses', 'module' => 'task_statuses', 'label' => 'View Task Statuses', 'description' => 'View Task Statuses'],
            ['name' => 'create-task-statuses', 'module' => 'task_statuses', 'label' => 'Create Task Statuses', 'description' => 'Can create task statuses'],
            ['name' => 'edit-task-statuses', 'module' => 'task_statuses', 'label' => 'Edit Task Statuses', 'description' => 'Can edit task statuses'],
            ['name' => 'delete-task-statuses', 'module' => 'task_statuses', 'label' => 'Delete Task Statuses', 'description' => 'Can delete task statuses'],
            ['name' => 'toggle-status-task-statuses', 'module' => 'task_statuses', 'label' => 'Toggle Status Task Statuses', 'description' => 'Can toggle status task statuses'],

            // Meeting management
            ['name' => 'manage-meetings', 'module' => 'meetings', 'label' => 'Manage Meetings', 'description' => 'Can manage meetings'],
            ['name' => 'view-meetings', 'module' => 'meetings', 'label' => 'View Meetings', 'description' => 'View Meetings'],
            ['name' => 'create-meetings', 'module' => 'meetings', 'label' => 'Create Meetings', 'description' => 'Can create meetings'],
            ['name' => 'edit-meetings', 'module' => 'meetings', 'label' => 'Edit Meetings', 'description' => 'Can edit meetings'],
            ['name' => 'delete-meetings', 'module' => 'meetings', 'label' => 'Delete Meetings', 'description' => 'Can delete meetings'],
            ['name' => 'toggle-status-meetings', 'module' => 'meetings', 'label' => 'Toggle Status Meetings', 'description' => 'Can toggle status meetings'],

            // Call management
            ['name' => 'manage-calls', 'module' => 'calls', 'label' => 'Manage Calls', 'description' => 'Can manage calls'],
            ['name' => 'view-calls', 'module' => 'calls', 'label' => 'View Calls', 'description' => 'View Calls'],
            ['name' => 'create-calls', 'module' => 'calls', 'label' => 'Create Calls', 'description' => 'Can create calls'],
            ['name' => 'edit-calls', 'module' => 'calls', 'label' => 'Edit Calls', 'description' => 'Can edit calls'],
            ['name' => 'delete-calls', 'module' => 'calls', 'label' => 'Delete Calls', 'description' => 'Can delete calls'],
            ['name' => 'toggle-status-calls', 'module' => 'calls', 'label' => 'Toggle Status Calls', 'description' => 'Can toggle status calls'],

            // Document Folder management
            ['name' => 'manage-document-folders', 'module' => 'document_folders', 'label' => 'Manage Document Folders', 'description' => 'Can manage document folders'],
            ['name' => 'view-document-folders', 'module' => 'document_folders', 'label' => 'View Document Folders', 'description' => 'View Document Folders'],
            ['name' => 'create-document-folders', 'module' => 'document_folders', 'label' => 'Create Document Folders', 'description' => 'Can create document folders'],
            ['name' => 'edit-document-folders', 'module' => 'document_folders', 'label' => 'Edit Document Folders', 'description' => 'Can edit document folders'],
            ['name' => 'delete-document-folders', 'module' => 'document_folders', 'label' => 'Delete Document Folders', 'description' => 'Can delete document folders'],
            ['name' => 'toggle-status-document-folders', 'module' => 'document_folders', 'label' => 'Toggle Status Document Folders', 'description' => 'Can toggle status document folders'],

            // Document Type management
            ['name' => 'manage-document-types', 'module' => 'document_types', 'label' => 'Manage Document Types', 'description' => 'Can manage document types'],
            ['name' => 'view-document-types', 'module' => 'document_types', 'label' => 'View Document Types', 'description' => 'View Document Types'],
            ['name' => 'create-document-types', 'module' => 'document_types', 'label' => 'Create Document Types', 'description' => 'Can create document types'],
            ['name' => 'edit-document-types', 'module' => 'document_types', 'label' => 'Edit Document Types', 'description' => 'Can edit document types'],
            ['name' => 'delete-document-types', 'module' => 'document_types', 'label' => 'Delete Document Types', 'description' => 'Can delete document types'],
            ['name' => 'toggle-status-document-types', 'module' => 'document_types', 'label' => 'Toggle Status Document Types', 'description' => 'Can toggle status document types'],

            // Document management
            ['name' => 'manage-documents', 'module' => 'documents', 'label' => 'Manage Documents', 'description' => 'Can manage documents'],
            ['name' => 'view-documents', 'module' => 'documents', 'label' => 'View Documents', 'description' => 'View Documents'],
            ['name' => 'create-documents', 'module' => 'documents', 'label' => 'Create Documents', 'description' => 'Can create documents'],
            ['name' => 'edit-documents', 'module' => 'documents', 'label' => 'Edit Documents', 'description' => 'Can edit documents'],
            ['name' => 'delete-documents', 'module' => 'documents', 'label' => 'Delete Documents', 'description' => 'Can delete documents'],
            ['name' => 'toggle-status-documents', 'module' => 'documents', 'label' => 'Toggle Status Documents', 'description' => 'Can toggle status documents'],

            // Stream management
            ['name' => 'manage-stream', 'module' => 'stream', 'label' => 'Manage Stream', 'description' => 'Can manage stream'],
            ['name' => 'view-stream', 'module' => 'stream', 'label' => 'View Stream', 'description' => 'View stream'],
            ['name' => 'delete-stream', 'module' => 'stream', 'label' => 'Delete Stream', 'description' => 'Can delete stream'],

            // Reports management
            ['name' => 'manage-reports', 'module' => 'reports', 'label' => 'Manage Reports', 'description' => 'Can manage reports'],

            // // Notification Template management
            ['name' => 'manage-notification-templates', 'module' => 'notification_templates', 'label' => 'Manage Notification Templates', 'description' => 'Can manage notification templates'],
            ['name' => 'view-notification-templates', 'module' => 'notification_templates', 'label' => 'View Notification Templates', 'description' => 'View Notification Templates'],
            ['name' => 'create-notification-templates', 'module' => 'notification_templates', 'label' => 'Create Notification Templates', 'description' => 'Can create notification templates'],
            ['name' => 'edit-notification-templates', 'module' => 'notification_templates', 'label' => 'Edit Notification Templates', 'description' => 'Can edit notification templates'],
            ['name' => 'delete-notification-templates', 'module' => 'notification_templates', 'label' => 'Delete Notification Templates', 'description' => 'Can delete notification templates'],

            // Note management
            ['name' => 'manage-notes', 'module' => 'notes', 'label' => 'Manage Notes', 'description' => 'Can manage notes'],
            ['name' => 'view-notes', 'module' => 'notes', 'label' => 'View Notes', 'description' => 'View Notes'],
            ['name' => 'create-notes', 'module' => 'notes', 'label' => 'Create Notes', 'description' => 'Can create notes'],
            ['name' => 'edit-notes', 'module' => 'notes', 'label' => 'Edit Notes', 'description' => 'Can edit notes'],
            ['name' => 'delete-notes', 'module' => 'notes', 'label' => 'Delete Notes', 'description' => 'Can delete notes'],

            // Announcement Category management
            ['name' => 'manage-announcement-categories', 'module' => 'announcement_categories', 'label' => 'Manage Announcement Categories', 'description' => 'Can manage announcement categories'],
            ['name' => 'view-announcement-categories', 'module' => 'announcement_categories', 'label' => 'View Announcement Categories', 'description' => 'View Announcement Categories'],
            ['name' => 'create-announcement-categories', 'module' => 'announcement_categories', 'label' => 'Create Announcement Categories', 'description' => 'Can create announcement categories'],
            ['name' => 'edit-announcement-categories', 'module' => 'announcement_categories', 'label' => 'Edit Announcement Categories', 'description' => 'Can edit announcement categories'],
            ['name' => 'delete-announcement-categories', 'module' => 'announcement_categories', 'label' => 'Delete Announcement Categories', 'description' => 'Can delete announcement categories'],
            ['name' => 'toggle-status-announcement-categories', 'module' => 'announcement_categories', 'label' => 'Toggle Status Announcement Categories', 'description' => 'Can toggle status announcement categories'],

            // Announcement management
            ['name' => 'manage-announcements', 'module' => 'announcements', 'label' => 'Manage Announcements', 'description' => 'Can manage announcements'],
            ['name' => 'view-announcements', 'module' => 'announcements', 'label' => 'View Announcements', 'description' => 'View Announcements'],
            ['name' => 'create-announcements', 'module' => 'announcements', 'label' => 'Create Announcements', 'description' => 'Can create announcements'],
            ['name' => 'edit-announcements', 'module' => 'announcements', 'label' => 'Edit Announcements', 'description' => 'Can edit announcements'],
            ['name' => 'delete-announcements', 'module' => 'announcements', 'label' => 'Delete Announcements', 'description' => 'Can delete announcements'],
            ['name' => 'toggle-status-announcements', 'module' => 'announcements', 'label' => 'Toggle Status Announcements', 'description' => 'Can toggle status announcements'],

            // Login History management
            ['name' => 'manage-login-history', 'module' => 'login_history', 'label' => 'Manage Login History', 'description' => 'Can manage login history'],
            ['name' => 'show-login-history', 'module' => 'login_history', 'label' => 'Show Login History', 'description' => 'Can view login history'],
            ['name' => 'delete-login-history', 'module' => 'login_history', 'label' => 'Delete Login History', 'description' => 'Can delete login history'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                [
                    'module' => $permission['module'],
                    'label' => $permission['label'],
                    'description' => $permission['description'],
                ]
            );
        }
    }
}
