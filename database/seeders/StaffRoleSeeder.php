<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Faker\Factory as Faker;

class StaffRoleSeeder extends Seeder
{
    /**
     * Ensure manage permissions are included when CRUD permissions exist
     */
    private function ensureManagePermissions(array $permissions): array
    {
        $processedPermissions = $permissions;
        $managePermissions = [];
        foreach ($permissions as $permission) {
            // Extract module from permission (e.g., 'create-products' -> 'products', 'toggle-status-meetings' -> 'meetings')
            if (preg_match('/^(create|edit|view|delete|toggle-status|reset-password|convert)-(.+)$/', $permission, $matches)) {
                $module = $matches[2];
                $managePermission = 'manage-' . $module;
                if (!in_array($managePermission, $processedPermissions)) {
                    $managePermissions[] = $managePermission;
                }
            }
        }
        return array_merge($processedPermissions, $managePermissions);
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Get all company users
        $companyUsers = User::where('type', 'company')->get();

        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run CompanySeeder first.');
            return;
        }

        // Define role templates with permissions
        if (IsDemo()) {
            $roleTemplates = [
                [
                    'name' => 'sales-manager',
                    'label' => 'Sales Manager',
                    'description' => 'Sales Manager has access to manage sales operations',
                    'permissions' =>  [
                        'manage-dashboard',

                        'manage-media',
                        'manage-own-media',
                        'view-media',
                        'create-media',
                        'edit-media',
                        'delete-media',
                        'download-media',

                        'manage-leads',
                        'view-leads',
                        'create-leads',
                        'edit-leads',
                        'delete-leads',
                        'convert-leads',
                        'import-leads',
                        'export-leads',

                        'manage-contacts',
                        'view-contacts',
                        'create-contacts',
                        'edit-contacts',
                        'delete-contacts',
                        'export-contacts',

                        'manage-accounts',
                        'view-accounts',
                        'create-accounts',
                        'edit-accounts',
                        'delete-accounts',
                        'export-accounts',

                        'manage-opportunities',
                        'view-opportunities',
                        'create-opportunities',
                        'edit-opportunities',
                        'delete-opportunities',
                        'export-opportunities',

                        'manage-quotes',
                        'view-quotes',
                        'create-quotes',
                        'edit-quotes',
                        'delete-quotes',
                        'export-quotes',

                        'manage-sales-orders',
                        'view-sales-orders',
                        'create-sales-orders',
                        'edit-sales-orders',
                        'delete-sales-orders',
                        'export-sales-orders',

                        'manage-invoices',
                        'view-invoices',
                        'create-invoices',
                        'edit-invoices',
                        'delete-invoices',
                        'export-invoices',
                        'send-reminder-invoices',

                        'manage-delivery-orders',
                        'view-delivery-orders',
                        'create-delivery-orders',
                        'edit-delivery-orders',
                        'export-delivery-orders',

                        'manage-campaigns',
                        'view-campaigns',
                        'create-campaigns',
                        'edit-campaigns',

                        'manage-products',
                        'view-products',
                        'import-products',
                        'export-products',

                        'manage-categories',
                        'view-categories',

                        'manage-brands',
                        'view-brands',

                        'manage-meetings',
                        'view-meetings',
                        'create-meetings',
                        'edit-meetings',
                        'delete-meetings',

                        'manage-calls',
                        'view-calls',
                        'create-calls',
                        'edit-calls',
                        'delete-calls',

                        'manage-reports',
                        'view-reports',

                        'manage-stream',
                        'view-stream',
                        'delete-stream',

                        'manage-notes',
                        'view-notes',
                        'create-notes',
                        'edit-notes',
                        'delete-notes',

                        'manage-announcements',
                        'view-announcements',
                    ]
                ],
                [
                    'name' => 'product-manager',
                    'label' => 'Product Manager',
                    'description' => 'Product Manager has access to manage products and inventory',
                    'permissions' =>  [
                        'manage-dashboard',

                        'manage-products',
                        'view-products',
                        'create-products',
                        'edit-products',
                        'delete-products',
                        'toggle-status-products',

                        'manage-categories',
                        'view-categories',
                        'create-categories',
                        'edit-categories',
                        'delete-categories',
                        'toggle-status-categories',

                        'manage-brands',
                        'view-brands',
                        'create-brands',
                        'edit-brands',
                        'delete-brands',
                        'toggle-status-brands',

                        'manage-taxes',
                        'view-taxes',
                        'create-taxes',
                        'edit-taxes',
                        'delete-taxes',
                        'toggle-status-taxes',

                        'manage-media',
                        'manage-own-media',
                        'view-media',
                        'create-media',
                        'edit-media',

                        'manage-sales-orders',
                        'view-sales-orders',

                        'manage-invoices',
                        'view-invoices',

                        'manage-quotes',
                        'view-quotes',

                        'manage-delivery-orders',
                        'view-delivery-orders',

                        'manage-purchase-orders',
                        'view-purchase-orders',

                        'manage-accounts',
                        'view-accounts',

                        'manage-contacts',
                        'view-contacts',

                        'manage-reports',
                        'view-reports',

                        'manage-announcements',
                        'view-announcements',
                    ]
                ],
                [
                    'name' => 'accountant',
                    'label' => 'Accountant',
                    'description' => 'Accountant has access to financial operations',
                    'permissions' =>  [
                        'manage-dashboard',

                        'manage-taxes',
                        'view-taxes',
                        'create-taxes',
                        'edit-taxes',
                        'delete-taxes',
                        'toggle-status-taxes',

                        'manage-accounts',
                        'view-accounts',

                        'manage-contacts',
                        'view-contacts',

                        'manage-invoices',
                        'view-invoices',
                        'create-invoices',
                        'edit-invoices',
                        'delete-invoices',

                        'manage-sales-orders',
                        'view-sales-orders',

                        'manage-quotes',
                        'view-quotes',

                        'manage-delivery-orders',
                        'view-delivery-orders',

                        'manage-purchase-orders',
                        'view-purchase-orders',
                        'create-purchase-orders',
                        'edit-purchase-orders',

                        'manage-receipt-orders',
                        'view-receipt-orders',
                        'create-receipt-orders',
                        'edit-receipt-orders',

                        'manage-products',
                        'view-products',

                        'manage-brands',
                        'view-brands',

                        'manage-categories',
                        'view-categories',

                        'manage-reports',
                        'view-reports',

                        'manage-announcements',
                        'view-announcements',
                    ]
                ],
                [
                    'name' => 'inventory-manager',
                    'label' => 'Inventory Manager',
                    'description' => 'Inventory Manager has access to manage inventory and orders',
                    'permissions' =>  [
                        'manage-dashboard',

                        'manage-products',
                        'view-products',
                        'edit-products',
                        'toggle-status-products',

                        'manage-categories',
                        'view-categories',

                        'manage-brands',
                        'view-brands',

                        'manage-taxes',
                        'view-taxes',

                        'manage-sales-orders',
                        'view-sales-orders',

                        'manage-invoices',
                        'view-invoices',

                        'manage-quotes',
                        'view-quotes',

                        'manage-delivery-orders',
                        'view-delivery-orders',
                        'create-delivery-orders',
                        'edit-delivery-orders',
                        'delete-delivery-orders',

                        'manage-return-orders',
                        'view-return-orders',
                        'create-return-orders',
                        'edit-return-orders',
                        'export-return-orders',

                        'manage-purchase-orders',
                        'view-purchase-orders',
                        'create-purchase-orders',
                        'edit-purchase-orders',
                        'export-purchase-orders',

                        'manage-receipt-orders',
                        'view-receipt-orders',
                        'create-receipt-orders',
                        'edit-receipt-orders',
                        'export-receipt-orders',

                        'manage-accounts',
                        'view-accounts',

                        'manage-contacts',
                        'view-contacts',

                        'manage-reports',
                        'view-reports',

                        'manage-announcements',
                        'view-announcements',
                    ]
                ],
                [
                    'name' => 'sales-rep',
                    'label' => 'Sales Representative',
                    'description' => 'Sales Representative has limited access to sales operations',
                    'permissions' =>  [
                        'manage-dashboard',

                        'manage-leads',
                        'view-leads',
                        'create-leads',
                        'edit-leads',

                        'manage-contacts',
                        'view-contacts',
                        'create-contacts',
                        'edit-contacts',

                        'manage-accounts',
                        'view-accounts',
                        'create-accounts',
                        'edit-accounts',

                        'manage-opportunities',
                        'view-opportunities',
                        'create-opportunities',
                        'edit-opportunities',

                        'manage-quotes',
                        'view-quotes',
                        'create-quotes',
                        'edit-quotes',

                        'manage-sales-orders',
                        'view-sales-orders',

                        'manage-invoices',
                        'view-invoices',

                        'manage-delivery-orders',
                        'view-delivery-orders',

                        'manage-products',
                        'view-products',

                        'manage-categories',
                        'view-categories',

                        'manage-brands',
                        'view-brands',

                        'manage-meetings',
                        'view-meetings',
                        'create-meetings',
                        'edit-meetings',

                        'manage-calls',
                        'view-calls',
                        'create-calls',
                        'edit-calls',

                        'manage-reports',
                        'view-reports',

                        'manage-announcements',
                        'view-announcements',
                    ]
                ],
                [
                    'name' => 'manager',
                    'label' => 'Manager',
                    'description' => 'Manager has comprehensive access to all modules',
                    'permissions' =>  [
                        'manage-dashboard',

                        'manage-users',
                        'view-users',
                        'create-users',
                        'edit-users',
                        'reset-password-users',
                        'toggle-status-users',

                        'manage-roles',
                        'view-roles',
                        'create-roles',
                        'edit-roles',
                        'delete-roles',

                        'manage-products',
                        'view-products',
                        'create-products',
                        'edit-products',
                        'delete-products',
                        'toggle-status-products',

                        'manage-categories',
                        'view-categories',
                        'create-categories',
                        'edit-categories',
                        'delete-categories',
                        'toggle-status-categories',

                        'manage-brands',
                        'view-brands',
                        'create-brands',
                        'edit-brands',
                        'delete-brands',
                        'toggle-status-brands',

                        'manage-taxes',
                        'view-taxes',
                        'create-taxes',
                        'edit-taxes',
                        'delete-taxes',
                        'toggle-status-taxes',

                        'manage-accounts',
                        'view-accounts',
                        'create-accounts',
                        'edit-accounts',
                        'delete-accounts',
                        'toggle-status-accounts',

                        'manage-leads',
                        'view-leads',
                        'create-leads',
                        'edit-leads',
                        'delete-leads',
                        'convert-leads',
                        'toggle-status-leads',

                        'manage-contacts',
                        'view-contacts',
                        'create-contacts',
                        'edit-contacts',
                        'delete-contacts',
                        'toggle-status-contacts',

                        'manage-opportunities',
                        'view-opportunities',
                        'create-opportunities',
                        'edit-opportunities',
                        'delete-opportunities',
                        'toggle-status-opportunities',

                        'manage-quotes',
                        'view-quotes',
                        'create-quotes',
                        'edit-quotes',
                        'delete-quotes',
                        'toggle-status-quotes',

                        'manage-sales-orders',
                        'view-sales-orders',
                        'create-sales-orders',
                        'edit-sales-orders',
                        'delete-sales-orders',
                        'toggle-status-sales-orders',

                        'manage-invoices',
                        'view-invoices',
                        'create-invoices',
                        'edit-invoices',
                        'delete-invoices',
                        'toggle-status-invoices',

                        'manage-delivery-orders',
                        'view-delivery-orders',
                        'create-delivery-orders',
                        'edit-delivery-orders',
                        'delete-delivery-orders',
                        'toggle-status-delivery-orders',

                        'manage-return-orders',
                        'view-return-orders',
                        'create-return-orders',
                        'edit-return-orders',
                        'delete-return-orders',

                        'manage-purchase-orders',
                        'view-purchase-orders',
                        'create-purchase-orders',
                        'edit-purchase-orders',
                        'delete-purchase-orders',
                        'toggle-status-purchase-orders',

                        'manage-receipt-orders',
                        'view-receipt-orders',
                        'create-receipt-orders',
                        'edit-receipt-orders',
                        'delete-receipt-orders',
                        'toggle-status-receipt-orders',

                        'manage-projects',
                        'view-projects',
                        'create-projects',
                        'edit-projects',
                        'delete-projects',
                        'toggle-status-projects',

                        'manage-project-tasks',
                        'view-project-tasks',
                        'create-project-tasks',
                        'edit-project-tasks',
                        'delete-project-tasks',
                        'toggle-status-project-tasks',

                        'manage-task-statuses',
                        'view-task-statuses',
                        'create-task-statuses',
                        'edit-task-statuses',
                        'delete-task-statuses',
                        'toggle-status-task-statuses',

                        'manage-meetings',
                        'view-meetings',
                        'create-meetings',
                        'edit-meetings',
                        'delete-meetings',
                        'toggle-status-meetings',

                        'manage-calls',
                        'view-calls',
                        'create-calls',
                        'edit-calls',
                        'delete-calls',
                        'toggle-status-calls',

                        'manage-campaigns',
                        'view-campaigns',
                        'create-campaigns',
                        'edit-campaigns',
                        'delete-campaigns',
                        'toggle-status-campaigns',

                        'manage-document-folders',
                        'view-document-folders',
                        'create-document-folders',
                        'edit-document-folders',
                        'delete-document-folders',

                        'manage-document-types',
                        'view-document-types',
                        'create-document-types',
                        'edit-document-types',
                        'delete-document-types',

                        'manage-documents',
                        'view-documents',
                        'create-documents',
                        'edit-documents',
                        'delete-documents',

                        'manage-media',
                        'manage-own-media',
                        'view-media',
                        'create-media',
                        'edit-media',
                        'delete-media',

                        'manage-reports',
                        'view-reports',

                        'manage-stream',
                        'view-stream',
                        'delete-stream',

                        'manage-notes',
                        'view-notes',
                        'create-notes',
                        'edit-notes',
                        'delete-notes',

                        'manage-announcements',
                        'view-announcements',
                    ]
                ],
                [
                    'name' => 'project-manager',
                    'label' => 'Project Manager',
                    'description' => 'Project Manager has access to manage projects and tasks',
                    'permissions' =>  [
                        'manage-dashboard',

                        'manage-projects',
                        'view-projects',
                        'create-projects',
                        'edit-projects',
                        'delete-projects',
                        'toggle-status-projects',
                        'export-projects',

                        'manage-project-tasks',
                        'view-project-tasks',
                        'create-project-tasks',
                        'edit-project-tasks',
                        'delete-project-tasks',
                        'toggle-status-project-tasks',
                        'export-project-tasks',

                        'manage-task-statuses',
                        'view-task-statuses',
                        'create-task-statuses',
                        'edit-task-statuses',
                        'delete-task-statuses',
                        'toggle-status-task-statuses',

                        'manage-meetings',
                        'view-meetings',
                        'create-meetings',
                        'edit-meetings',
                        'delete-meetings',

                        'manage-calls',
                        'view-calls',
                        'create-calls',
                        'edit-calls',
                        'delete-calls',

                        'manage-contacts',
                        'view-contacts',

                        'manage-accounts',
                        'view-accounts',

                        'manage-opportunities',
                        'view-opportunities',

                        'manage-document-folders',
                        'view-document-folders',
                        'create-document-folders',
                        'edit-document-folders',

                        'manage-document-types',
                        'view-document-types',
                        'create-document-types',
                        'edit-document-types',

                        'manage-documents',
                        'view-documents',
                        'create-documents',
                        'edit-documents',
                        'delete-documents',

                        'manage-media',
                        'manage-own-media',
                        'view-media',
                        'create-media',
                        'edit-media',

                        'manage-reports',
                        'view-reports',

                        'manage-announcements',
                        'view-announcements',
                    ]
                ],
                [
                    'name' => 'support-agent',
                    'label' => 'Support Agent',
                    'description' => 'Support Agent has access to view and support customers',
                    'permissions' =>  [
                        'manage-dashboard',

                        'manage-products',
                        'view-products',

                        'manage-categories',
                        'view-categories',

                        'manage-brands',
                        'view-brands',

                        'manage-leads',
                        'view-leads',

                        'manage-contacts',
                        'view-contacts',

                        'manage-accounts',
                        'view-accounts',

                        'manage-opportunities',
                        'view-opportunities',

                        'manage-quotes',
                        'view-quotes',

                        'manage-sales-orders',
                        'view-sales-orders',

                        'manage-invoices',
                        'view-invoices',

                        'manage-delivery-orders',
                        'view-delivery-orders',

                        'manage-return-orders',
                        'view-return-orders',

                        'manage-cases',
                        'view-cases',

                        'create-cases',
                        'edit-cases',
                        'delete-cases',
                        'export-cases',

                        'manage-projects',
                        'view-projects',

                        'manage-project-tasks',
                        'view-project-tasks',

                        'manage-meetings',
                        'view-meetings',

                        'manage-calls',
                        'view-calls',

                        'manage-documents',
                        'view-documents',

                        'manage-document-folders',
                        'view-document-folders',

                        'manage-document-types',
                        'view-document-types',

                        'manage-reports',
                        'view-reports',

                        'manage-announcements',
                        'view-announcements',
                    ]
                ]
            ];
        } else {
            $roleTemplates = [
                [
                    'name' => 'sales-manager',
                    'label' => 'Sales Manager',
                    'description' => 'Sales Manager has access to manage sales operations',
                    'permissions' =>  [
                        'manage-dashboard',

                        'manage-media',
                        'manage-own-media',
                        'view-media',
                        'create-media',
                        'edit-media',
                        'delete-media',
                        'download-media',

                        'manage-leads',
                        'view-leads',
                        'create-leads',
                        'edit-leads',
                        'delete-leads',
                        'convert-leads',
                        'import-leads',
                        'export-leads',

                        'manage-contacts',
                        'view-contacts',
                        'create-contacts',
                        'edit-contacts',
                        'delete-contacts',
                        'export-contacts',

                        'manage-accounts',
                        'view-accounts',
                        'create-accounts',
                        'edit-accounts',
                        'delete-accounts',
                        'export-accounts',

                        'manage-opportunities',
                        'view-opportunities',
                        'create-opportunities',
                        'edit-opportunities',
                        'delete-opportunities',
                        'export-opportunities',

                        'manage-quotes',
                        'view-quotes',
                        'create-quotes',
                        'edit-quotes',
                        'delete-quotes',
                        'export-quotes',

                        'manage-sales-orders',
                        'view-sales-orders',
                        'create-sales-orders',
                        'edit-sales-orders',
                        'delete-sales-orders',
                        'export-sales-orders',

                        'manage-invoices',
                        'view-invoices',
                        'create-invoices',
                        'edit-invoices',
                        'delete-invoices',
                        'export-invoices',
                        'send-reminder-invoices',

                        'manage-delivery-orders',
                        'view-delivery-orders',
                        'create-delivery-orders',
                        'edit-delivery-orders',
                        'export-delivery-orders',

                        'manage-campaigns',
                        'view-campaigns',
                        'create-campaigns',
                        'edit-campaigns',

                        'manage-products',
                        'view-products',
                        'import-products',
                        'export-products',

                        'manage-categories',
                        'view-categories',

                        'manage-brands',
                        'view-brands',

                        'manage-meetings',
                        'view-meetings',
                        'create-meetings',
                        'edit-meetings',
                        'delete-meetings',

                        'manage-calls',
                        'view-calls',
                        'create-calls',
                        'edit-calls',
                        'delete-calls',

                        'manage-reports',
                        'view-reports',

                        'manage-stream',
                        'view-stream',
                        'delete-stream',

                        'manage-notes',
                        'view-notes',
                        'create-notes',
                        'edit-notes',
                        'delete-notes',

                        'manage-announcements',
                        'view-announcements',
                    ]
                ],
            ];
        }

        // Create roles and staff users for each company
        foreach ($companyUsers as $company) {
            // Create roles for each company
            foreach ($roleTemplates as $roleTemplate) {
                $role = Role::firstOrCreate([
                    'name' => $roleTemplate['name'],
                    'label' => $roleTemplate['label'],
                    'description' => $roleTemplate['description'],
                    'guard_name' => 'web',
                    'created_by' => $company->id
                ]);

                // Get permissions for this role
                $permissions = $roleTemplate['permissions'];

                // Get permission objects
                $permissionObjects = Permission::whereIn('name', $permissions)->get();

                // Assign permissions to role
                $role->syncPermissions($permissionObjects);

                // Create 2-3 staff users for each role (total 10-15 per company)
                if (IsDemo()) {
                    $staffCount = rand(2, 3);
                } else {
                    $staffCount = 1;
                }

                for ($i = 0; $i < $staffCount; $i++) {
                    $firstName = $faker->firstName;
                    $lastName = $faker->lastName;
                    $name = $firstName . ' ' . $lastName;
                    $email = strtolower($firstName . '.' . $lastName . '.' . $company->id . '@example.com');

                    // Skip if user already exists
                    if (User::where('email', $email)->exists()) {
                        continue;
                    }

                    // Create specific user for manager role (first iteration only) - only for company@example.com
                    if ($roleTemplate['name'] === 'manager' && $i === 0 && $company->email === 'company@example.com') {
                        $staff = User::firstOrCreate(
                            ['email' => 'sarahjohnson@example.com'],
                            [
                                'name' => 'Sarah Johnson',
                                'email_verified_at' => now(),
                                'password' => Hash::make('password'),
                                'type' => 'staff',
                                'lang' => 'en',
                                'created_by' => $company->id,
                                'created_at' => now(),
                            ]
                        );
                    } else {
                        $staff = User::create([
                            'name' => $name,
                            'email' => $email,
                            'email_verified_at' => now(),
                            'password' => Hash::make('password'),
                            'type' => 'staff',
                            'lang' => $faker->randomElement(['en', 'es', 'fr', 'de']),
                            'created_by' => $company->id,
                            'created_at' => $faker->dateTimeBetween('-6 months', 'now'),
                        ]);
                    }

                    // Assign role to staff
                    $staff->assignRole($role);
                }
            }
        }

        $totalUsers = User::where('type', 'staff')->count();
        $this->command->info("Created staff roles and {$totalUsers} users successfully!");
    }
}
