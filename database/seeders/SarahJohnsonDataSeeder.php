<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Tax;
use App\Models\DeliveryOrder;
use App\Models\ReturnOrder;
use App\Models\ReceiptOrder;
use App\Models\PurchaseOrder;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\DocumentType;
use App\Models\Campaign;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class SarahJohnsonDataSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Find Sarah Johnson
        $sarah = User::where('email', 'sarahjohnson@example.com')->first();

        if (!$sarah) {
            $this->command->error('Sarah Johnson not found. Please run StaffRoleSeeder first.');
            return;
        }

        $companyId = $sarah->created_by;

        // Get lookup data
        $accountTypes = \App\Models\AccountType::where('created_by', $companyId)->get();
        $accountIndustries = \App\Models\AccountIndustry::where('created_by', $companyId)->get();
        $categories = Category::where('created_by', $companyId)->get();
        $brands = Brand::where('created_by', $companyId)->get();
        $taxes = Tax::where('created_by', $companyId)->get();
        $opportunityStages = \App\Models\OpportunityStage::where('created_by', $companyId)->get();
        $opportunitySources = \App\Models\OpportunitySource::where('created_by', $companyId)->get();
        $leadStatuses = \App\Models\LeadStatus::where('created_by', $companyId)->get();
        $leadSources = \App\Models\LeadSource::where('created_by', $companyId)->get();

        // Create realistic Accounts
        $companies = [
            ['name' => 'TechFlow Solutions', 'email' => 'contact@techflow.com', 'website' => 'https://techflow.com', 'status' => 'active'],
            ['name' => 'Global Marketing Inc', 'email' => 'info@globalmarketing.com', 'website' => 'https://globalmarketing.com', 'status' => 'active'],
            ['name' => 'DataSync Corp', 'email' => 'sales@datasync.com', 'website' => 'https://datasync.com', 'status' => 'active'],
            ['name' => 'CloudVision Ltd', 'email' => 'hello@cloudvision.com', 'website' => 'https://cloudvision.com', 'status' => 'inactive'],
            ['name' => 'InnovateTech Group', 'email' => 'contact@innovatetech.com', 'website' => 'https://innovatetech.com', 'status' => 'active']
        ];

        foreach ($companies as $company) {
            Account::create([
                'name' => $company['name'],
                'email' => $company['email'],
                'phone' => $faker->phoneNumber,
                'website' => $company['website'],
                'billing_address' => $faker->streetAddress,
                'billing_city' => $faker->city,
                'billing_state' => $faker->state,
                'billing_postal_code' => $faker->postcode,
                'billing_country' => 'United States',
                'shipping_address' => $faker->streetAddress,
                'shipping_city' => $faker->city,
                'shipping_state' => $faker->state,
                'shipping_postal_code' => $faker->postcode,
                'shipping_country' => 'United States',
                'status' => $company['status'],
                'account_type_id' => $accountTypes->isNotEmpty() ? $accountTypes->random()->id : null,
                'account_industry_id' => $accountIndustries->isNotEmpty() ? $accountIndustries->random()->id : null,
                'assigned_to' => $sarah->id,
                'created_by' => $companyId,
            ]);
        }

        $accounts = Account::where('created_by', $companyId)->get();

        // Create realistic Products with stock and detailed info
        $productData = [
            ['name' => 'iPhone 15 Pro', 'price' => 1199.99, 'sku' => 'IPHONE-15-001', 'stock' => 50, 'status' => 'active'],
            ['name' => 'MacBook Pro M3', 'price' => 2399.99, 'sku' => 'MACBOOK-M3-002', 'stock' => 25, 'status' => 'active'],
            ['name' => 'Samsung Galaxy S24', 'price' => 899.99, 'sku' => 'GALAXY-S24-003', 'stock' => 75, 'status' => 'active'],
            ['name' => 'Dell XPS 15', 'price' => 1899.99, 'sku' => 'DELL-XPS15-004', 'stock' => 30, 'status' => 'active'],
            ['name' => 'iPad Air', 'price' => 699.99, 'sku' => 'IPAD-AIR-005', 'stock' => 0, 'status' => 'inactive'],
            ['name' => 'Surface Pro 9', 'price' => 1199.99, 'sku' => 'SURFACE-PRO9-006', 'stock' => 20, 'status' => 'active'],
            ['name' => 'Dell UltraSharp Monitor', 'price' => 399.99, 'sku' => 'DELL-MONITOR-007', 'stock' => 100, 'status' => 'active'],
            ['name' => 'Microsoft Wireless Mouse', 'price' => 49.99, 'sku' => 'MS-MOUSE-008', 'stock' => 200, 'status' => 'active']
        ];

        foreach ($productData as $prod) {
            Product::create([
                'name' => $prod['name'],
                'sku' => $prod['sku'],
                'price' => $prod['price'],
                'description' => 'High-quality ' . strtolower($prod['name']) . ' for professional and business use with warranty and support.',
                'stock_quantity' => $prod['stock'],
                'status' => $prod['status'],
                'category_id' => $categories->isNotEmpty() ? $categories->random()->id : null,
                'brand_id' => $brands->isNotEmpty() ? $brands->random()->id : null,
                'tax_id' => $taxes->isNotEmpty() ? $taxes->random()->id : null,
                'assigned_to' => $sarah->id,
                'created_by' => $companyId,
            ]);
        }

        $products = Product::where('created_by', $companyId)->get();

        // Create realistic Contacts
        $contacts = [
            ['name' => 'Michael Chen', 'position' => 'Chief Technology Officer', 'email' => 'michael.chen@techflow.com', 'status' => 'active'],
            ['name' => 'Sarah Williams', 'position' => 'Marketing Director', 'email' => 'sarah.w@globalmarketing.com', 'status' => 'active'],
            ['name' => 'David Rodriguez', 'position' => 'VP of Sales', 'email' => 'david.r@datasync.com', 'status' => 'active'],
            ['name' => 'Emily Johnson', 'position' => 'Product Manager', 'email' => 'emily.j@cloudvision.com', 'status' => 'inactive'],
            ['name' => 'James Thompson', 'position' => 'Chief Executive Officer', 'email' => 'james.t@innovatetech.com', 'status' => 'active'],
            ['name' => 'Lisa Anderson', 'position' => 'Operations Manager', 'email' => 'lisa.a@techflow.com', 'status' => 'active'],
            ['name' => 'Robert Kim', 'position' => 'IT Director', 'email' => 'robert.k@globalmarketing.com', 'status' => 'active'],
            ['name' => 'Jennifer Davis', 'position' => 'Senior Business Analyst', 'email' => 'jennifer.d@datasync.com', 'status' => 'active']
        ];

        foreach ($contacts as $contact) {
            Contact::create([
                'name' => $contact['name'],
                'email' => $contact['email'],
                'phone' => $faker->phoneNumber,
                'position' => $contact['position'],
                'address' => $faker->address,
                'status' => $contact['status'],
                'account_id' => $accounts->random()->id,
                'assigned_to' => $sarah->id,
                'created_by' => $companyId,
            ]);
        }

        // Create realistic Leads with different statuses
        $leads = [
            ['name' => 'Alex Martinez', 'company' => 'StartupHub Inc', 'position' => 'Founder & CEO', 'email' => 'alex@startuphub.com', 'status' => 'active', 'value' => 15000],
            ['name' => 'Rachel Green', 'company' => 'EcoSolutions LLC', 'position' => 'Chief Executive Officer', 'email' => 'rachel@ecosolutions.com', 'status' => 'active', 'value' => 25000],
            ['name' => 'Tom Wilson', 'company' => 'FinanceFirst Corp', 'position' => 'Chief Financial Officer', 'email' => 'tom@financefirst.com', 'status' => 'active', 'value' => 35000],
            ['name' => 'Maria Garcia', 'company' => 'HealthTech Systems', 'position' => 'VP Technology', 'email' => 'maria@healthtech.com', 'status' => 'active', 'value' => 45000],
            ['name' => 'Kevin Brown', 'company' => 'RetailMax Group', 'position' => 'Operations Director', 'email' => 'kevin@retailmax.com', 'status' => 'active', 'value' => 20000],
            ['name' => 'Amanda Lee', 'company' => 'EduPlatform Co', 'position' => 'Product Lead', 'email' => 'amanda@eduplatform.com', 'status' => 'inactive', 'value' => 18000],
            ['name' => 'Daniel Taylor', 'company' => 'LogiFlow Inc', 'position' => 'Supply Chain Manager', 'email' => 'daniel@logiflow.com', 'status' => 'active', 'value' => 12000],
            ['name' => 'Sophie Clark', 'company' => 'MediaStream Ltd', 'position' => 'Content Director', 'email' => 'sophie@mediastream.com', 'status' => 'active', 'value' => 28000],
            ['name' => 'Ryan Murphy', 'company' => 'SecureNet Solutions', 'position' => 'Security Analyst', 'email' => 'ryan@securenet.com', 'status' => 'active', 'value' => 22000],
            ['name' => 'Jessica White', 'company' => 'GreenEnergy Corp', 'position' => 'Project Manager', 'email' => 'jessica@greenenergy.com', 'status' => 'active', 'value' => 32000]
        ];

        foreach ($leads as $lead) {
            Lead::create([
                'name' => $lead['name'],
                'email' => $lead['email'],
                'phone' => $faker->phoneNumber,
                'company' => $lead['company'],
                'position' => $lead['position'],
                'address' => $faker->address,
                'website' => 'https://' . strtolower(str_replace(' ', '', $lead['company'])) . '.com',
                'notes' => 'Initial contact made through ' . $faker->randomElement(['LinkedIn', 'Trade Show', 'Referral', 'Cold Call', 'Website Inquiry']),
                'value' => $lead['value'],
                'status' => $lead['status'],
                'is_converted' => false,
                'lead_status_id' => $leadStatuses->isNotEmpty() ? $leadStatuses->random()->id : null,
                'lead_source_id' => $leadSources->isNotEmpty() ? $leadSources->random()->id : null,
                'assigned_to' => $sarah->id,
                'created_by' => $companyId,
            ]);
        }

        // Create realistic Opportunities with different stages
        $opportunities = [
            ['name' => 'Enterprise CRM Implementation - TechFlow', 'amount' => 75000, 'status' => 'active'],
            ['name' => 'Cloud Migration Project - GlobalMarketing', 'amount' => 120000, 'status' => 'active'],
            ['name' => 'Marketing Automation Setup - DataSync', 'amount' => 45000, 'status' => 'active'],
            ['name' => 'Data Analytics Platform - CloudVision', 'amount' => 95000, 'status' => 'inactive'],
            ['name' => 'Mobile App Development - InnovateTech', 'amount' => 85000, 'status' => 'active'],
            ['name' => 'Security Audit & Compliance - TechFlow', 'amount' => 35000, 'status' => 'active']
        ];

        foreach ($opportunities as $opp) {
            Opportunity::create([
                'name' => $opp['name'],
                'description' => 'Strategic business opportunity for ' . $opp['name'] . '. Includes implementation, training, and ongoing support.',
                'amount' => $opp['amount'],
                'close_date' => $faker->dateTimeBetween('now', '+6 months'),
                'notes' => 'Key decision makers identified. Budget approved. Timeline: ' . $faker->randomElement(['Q1 2025', 'Q2 2025', 'Q3 2025']),
                'status' => $opp['status'],
                'account_id' => $accounts->random()->id,
                'contact_id' => Contact::where('created_by', $companyId)->inRandomOrder()->first()?->id,
                'opportunity_stage_id' => $opportunityStages->isNotEmpty() ? $opportunityStages->random()->id : null,
                'opportunity_source_id' => $opportunitySources->isNotEmpty() ? $opportunitySources->random()->id : null,
                'assigned_to' => $sarah->id,
                'created_by' => $companyId,
            ]);
        }

        // Create Quotes with realistic data
        $quoteData = [
            ['name' => 'CRM Implementation Quote - TechFlow', 'status' => 'draft'],
            ['name' => 'Cloud Services Quote - GlobalMarketing', 'status' => 'sent'],
            ['name' => 'Analytics Package Quote - DataSync', 'status' => 'accepted'],
            ['name' => 'Security Services Quote - InnovateTech', 'status' => 'rejected']
        ];

        foreach ($quoteData as $quoteInfo) {
            $subtotal = $faker->numberBetween(5000, 15000);
            $totalAmount = $subtotal;

            $quote = Quote::create([
                'name' => $quoteInfo['name'],
                'description' => 'Comprehensive solution package including software licenses, implementation services, and training.',
                'account_id' => $accounts->random()->id,
                'subtotal' => $subtotal,
                'total_amount' => $totalAmount,
                'status' => $quoteInfo['status'],
                'valid_until' => $faker->dateTimeBetween('now', '+60 days'),
                'assigned_to' => $sarah->id,
                'created_by' => $companyId,
            ]);

            // Add products to quote
            if ($products->isNotEmpty()) {
                $selectedProducts = $products->where('status', 'active')->random(rand(2, 4));
                foreach ($selectedProducts as $product) {
                    $quantity = rand(1, 5);
                    $unitPrice = $product->price;
                    $quote->products()->attach($product->id, [
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $quantity * $unitPrice,
                    ]);
                }
            }
        }

        // Create Sales Orders
        $salesOrderData = [
            ['name' => 'SO-2024-001 TechFlow Solutions', 'status' => 'confirmed'],
            ['name' => 'SO-2024-002 GlobalMarketing Inc', 'status' => 'processing'],
            ['name' => 'SO-2024-003 DataSync Corp', 'status' => 'shipped']
        ];

        foreach ($salesOrderData as $orderInfo) {
            $subtotal = $faker->numberBetween(8000, 25000);
            $totalAmount = $subtotal;

            $salesOrder = SalesOrder::create([
                'name' => $orderInfo['name'],
                'description' => 'Sales order for enterprise software solutions and services.',
                'account_id' => $accounts->random()->id,
                'order_date' => $faker->dateTimeBetween('-60 days', 'now'),
                'status' => $orderInfo['status'],
                'subtotal' => $subtotal,
                'total_amount' => $totalAmount,
                'assigned_to' => $sarah->id,
                'created_by' => $companyId,
            ]);

            // Add products to sales order
            if ($products->isNotEmpty()) {
                $selectedProducts = $products->where('status', 'active')->random(rand(2, 5));
                foreach ($selectedProducts as $product) {
                    $quantity = rand(1, 8);
                    $unitPrice = $product->price;
                    $salesOrder->products()->attach($product->id, [
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $quantity * $unitPrice,
                    ]);
                }
            }
        }

        // Create Invoices with different statuses
        $invoiceData = [
            ['name' => 'INV-2024-001 TechFlow Solutions', 'status' => 'paid'],
            ['name' => 'INV-2024-002 GlobalMarketing Inc', 'status' => 'partially_paid'],
            ['name' => 'INV-2024-003 DataSync Corp', 'status' => 'sent'],
            ['name' => 'INV-2024-004 CloudVision Ltd', 'status' => 'overdue'],
            ['name' => 'INV-2024-005 InnovateTech Group', 'status' => 'draft']
        ];

        foreach ($invoiceData as $invInfo) {
            $subtotal = $faker->numberBetween(3000, 18000);
            $totalAmount = $subtotal;

            $invoice = Invoice::create([
                'name' => $invInfo['name'],
                'description' => 'Invoice for professional services and software licenses.',
                'account_id' => $accounts->random()->id,
                'invoice_date' => $faker->dateTimeBetween('-90 days', 'now'),
                'due_date' => $faker->dateTimeBetween('now', '+30 days'),
                'status' => $invInfo['status'],
                'subtotal' => $subtotal,
                'total_amount' => $totalAmount,
                'payment_method' => $faker->randomElement(['stripe', 'paypal', 'bank_transfer']),
                'terms' => 'Net 30 days. Late payment fee of 1.5% per month applies.',
                'notes' => 'Thank you for your business. Please remit payment by due date.',
                'assigned_to' => $sarah->id,
                'created_by' => $companyId,
            ]);

            // Add products to invoice
            if ($products->isNotEmpty()) {
                $selectedProducts = $products->where('status', 'active')->random(rand(1, 4));
                foreach ($selectedProducts as $product) {
                    $quantity = rand(1, 6);
                    $unitPrice = $product->price;
                    $invoice->products()->attach($product->id, [
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $quantity * $unitPrice,
                    ]);
                }
            }
        }

        // Create realistic Projects
        $projects = [
            [
                'name' => 'TechFlow CRM Integration Project',
                'code' => 'PROJ-2024-001',
                'description' => 'Complete CRM system integration including data migration, customization, and staff training for TechFlow Solutions.',
                'status' => 'active',
                'priority' => 'high',
                'budget' => 75000,
                'tasks' => ['Requirements Gathering', 'System Analysis', 'Data Migration', 'Customization', 'User Training', 'Go-Live Support']
            ],
            [
                'name' => 'CloudVision Digital Transformation',
                'code' => 'PROJ-2024-002',
                'description' => 'Comprehensive digital transformation initiative including cloud migration, process automation, and staff training.',
                'status' => 'active',
                'priority' => 'urgent',
                'budget' => 120000,
                'tasks' => ['Current State Assessment', 'Future State Design', 'Migration Planning', 'Implementation', 'Testing & Validation']
            ],
            [
                'name' => 'DataSync Analytics Implementation',
                'code' => 'PROJ-2024-003',
                'description' => 'Business intelligence and analytics platform implementation with custom dashboards and reporting.',
                'status' => 'completed',
                'priority' => 'medium',
                'budget' => 95000,
                'tasks' => ['Data Source Integration', 'Dashboard Development', 'Report Creation', 'Performance Optimization', 'User Training']
            ]
        ];

        foreach ($projects as $proj) {
            $project = Project::create([
                'name' => $proj['name'],
                'code' => $proj['code'],
                'description' => $proj['description'],
                'start_date' => $faker->dateTimeBetween('-60 days', '-30 days'),
                'end_date' => $faker->dateTimeBetween('+30 days', '+120 days'),
                'budget' => $proj['budget'],
                'status' => $proj['status'],
                'priority' => $proj['priority'],
                'account_id' => $accounts->random()->id,
                'assigned_to' => $sarah->id,
                'created_by' => $companyId,
            ]);

            // Create tasks for each project
            foreach ($proj['tasks'] as $taskName) {
                $taskStatuses = \App\Models\TaskStatus::where('created_by', $companyId)->get();

                ProjectTask::create([
                    'project_id' => $project->id,
                    'title' => $taskName,
                    'description' => 'Complete ' . strtolower($taskName) . ' phase of the ' . $project->name . ' project with all deliverables.',
                    'start_date' => $faker->dateTimeBetween('-30 days', 'now'),
                    'due_date' => $faker->dateTimeBetween('now', '+60 days'),
                    'priority' => $faker->randomElement(['low', 'medium', 'high', 'urgent']),
                    'progress' => $project->status === 'completed' ? 100 : $faker->numberBetween(0, 85),
                    'estimated_hours' => $faker->numberBetween(40, 120),
                    'actual_hours' => $faker->numberBetween(35, 100),
                    'task_status_id' => $taskStatuses->isNotEmpty() ? $taskStatuses->random()->id : null,
                    'assigned_to' => $sarah->id,
                    'created_by' => $companyId,
                ]);
            }
        }

        // Create Delivery Orders
        $deliveryOrders = [
            ['name' => 'DO-2024-001 TechFlow Delivery', 'status' => 'in_transit'],
            ['name' => 'DO-2024-002 GlobalMarketing Delivery', 'status' => 'delivered'],
            ['name' => 'DO-2024-003 DataSync Delivery', 'status' => 'pending'],
            ['name' => 'DO-2024-004 CloudVision Delivery', 'status' => 'pending'],
            ['name' => 'DO-2024-005 InnovateTech Delivery', 'status' => 'in_transit'],
            ['name' => 'DO-2024-006 TechFlow Express', 'status' => 'delivered'],
            ['name' => 'DO-2024-007 GlobalMarketing Rush', 'status' => 'pending'],
            ['name' => 'DO-2024-008 DataSync Priority', 'status' => 'in_transit']
        ];

        foreach ($deliveryOrders as $doInfo) {
            DeliveryOrder::create([
                'name' => $doInfo['name'],
                'account_id' => $accounts->random()->id,
                'sales_order_id' => SalesOrder::where('created_by', $companyId)->inRandomOrder()->first()?->id,
                'delivery_date' => $faker->dateTimeBetween('now', '+15 days'),
                'status' => $doInfo['status'],
                'tracking_number' => 'TRK' . $faker->numerify('########'),
                'delivery_notes' => 'Standard delivery with signature required.',
                'assigned_to' => $sarah->id,
                'created_by' => $companyId,
            ]);
        }

        // Create Return Orders
        $returnOrders = [
            ['name' => 'RO-2024-001 Product Return', 'status' => 'pending'],
            ['name' => 'RO-2024-002 Defective Item Return', 'status' => 'approved'],
            ['name' => 'RO-2024-003 Wrong Size Return', 'status' => 'shipped'],
            ['name' => 'RO-2024-004 Damaged Package', 'status' => 'processed'],
            ['name' => 'RO-2024-005 Customer Change Mind', 'status' => 'cancelled'],
            ['name' => 'RO-2024-006 Quality Issue', 'status' => 'approved'],
            ['name' => 'RO-2024-007 Late Delivery Return', 'status' => 'pending'],
            ['name' => 'RO-2024-008 Warranty Return', 'status' => 'received']
        ];

        foreach ($returnOrders as $roInfo) {
            ReturnOrder::create([
                'name' => $roInfo['name'],
                'account_id' => $accounts->random()->id,
                'sales_order_id' => SalesOrder::where('created_by', $companyId)->inRandomOrder()->first()?->id,
                'return_date' => $faker->dateTimeBetween('-30 days', 'now'),
                'reason' => $faker->randomElement(['defective', 'wrong_item', 'damaged', 'not_needed', 'other']),
                'status' => $roInfo['status'],
                'notes' => 'Return processed according to company policy.',
                'assigned_to' => $sarah->id,
                'created_by' => $companyId,
            ]);
        }

        // Create Purchase Orders
        $purchaseOrders = [
            ['name' => 'PO-2024-001 Office Supplies', 'status' => 'confirmed'],
            ['name' => 'PO-2024-002 IT Equipment', 'status' => 'draft'],
            ['name' => 'PO-2024-003 Software Licenses', 'status' => 'received'],
            ['name' => 'PO-2024-004 Marketing Materials', 'status' => 'sent'],
            ['name' => 'PO-2024-005 Furniture Order', 'status' => 'confirmed'],
            ['name' => 'PO-2024-006 Security Systems', 'status' => 'draft'],
            ['name' => 'PO-2024-007 Cleaning Supplies', 'status' => 'received'],
            ['name' => 'PO-2024-008 Training Materials', 'status' => 'sent']
        ];

        foreach ($purchaseOrders as $poInfo) {
            $subtotal = $faker->numberBetween(2000, 8000);
            PurchaseOrder::create([
                'name' => $poInfo['name'],
                'account_id' => $accounts->random()->id,
                'order_date' => $faker->dateTimeBetween('-30 days', 'now'),
                'expected_delivery_date' => $faker->dateTimeBetween('now', '+30 days'),
                'status' => $poInfo['status'],
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
                'assigned_to' => $sarah->id,
                'created_by' => $companyId,
            ]);
        }

        // Create Receipt Orders
        $receiptOrders = [
            ['name' => 'REC-2024-001 Equipment Receipt', 'status' => 'received'],
            ['name' => 'REC-2024-002 Supply Receipt', 'status' => 'partial'],
            ['name' => 'REC-2024-003 Software Receipt', 'status' => 'received'],
            ['name' => 'REC-2024-004 Furniture Receipt', 'status' => 'pending'],
            ['name' => 'REC-2024-005 Marketing Receipt', 'status' => 'received'],
            ['name' => 'REC-2024-006 Security Receipt', 'status' => 'partial'],
            ['name' => 'REC-2024-007 Cleaning Receipt', 'status' => 'received'],
            ['name' => 'REC-2024-008 Training Receipt', 'status' => 'pending']
        ];

        foreach ($receiptOrders as $recInfo) {
            ReceiptOrder::create([
                'name' => $recInfo['name'],
                'account_id' => $accounts->random()->id,
                'purchase_order_id' => PurchaseOrder::where('created_by', $companyId)->inRandomOrder()->first()?->id,
                'receipt_date' => $faker->dateTimeBetween('-15 days', 'now'),
                'status' => $recInfo['status'],
                'notes' => 'Items received and verified against purchase order.',
                'assigned_to' => $sarah->id,
                'created_by' => $companyId,
            ]);
        }

        // Create Document Folders and Documents
        $folders = [
            ['name' => 'Client Contracts', 'description' => 'Legal contracts and agreements'],
            ['name' => 'Project Documentation', 'description' => 'Technical specifications and project docs'],
            ['name' => 'Financial Records', 'description' => 'Invoices, receipts, and financial documents']
        ];

        foreach ($folders as $folderInfo) {
            $folder = DocumentFolder::create([
                'name' => $folderInfo['name'],
                'description' => $folderInfo['description'],
                'created_by' => $companyId,
            ]);

            // Create documents in each folder
            $documents = [
                ['name' => 'Service Agreement.pdf', 'type' => 'Contract'],
                ['name' => 'Technical Specification.docx', 'type' => 'Specification'],
                ['name' => 'Project Timeline.xlsx', 'type' => 'Planning']
            ];

            foreach ($documents as $docInfo) {
                $docType = DocumentType::where('created_by', $companyId)->where('type_name', $docInfo['type'])->first();

                Document::create([
                    'name' => $docInfo['name'],
                    'description' => 'Important business document for operations.',
                    'folder_id' => $folder->id,
                    'type_id' => $docType?->id,
                    'account_id' => $accounts->random()->id,
                    'status' => 'active',
                    'assigned_to' => $sarah->id,
                    'created_by' => $companyId,
                ]);
            }
        }

        // Create Campaigns
        $campaigns = [
            ['name' => 'Q1 2024 Product Launch', 'type' => 'Product Launch', 'status' => 'active'],
            ['name' => 'Summer Sales Campaign', 'type' => 'Sales', 'status' => 'inactive'],
            ['name' => 'Customer Retention Program', 'type' => 'Retention', 'status' => 'active'],
            ['name' => 'Holiday Promotion 2024', 'type' => 'Seasonal', 'status' => 'active'],
            ['name' => 'Brand Awareness Drive', 'type' => 'Branding', 'status' => 'active'],
            ['name' => 'Lead Generation Campaign', 'type' => 'Lead Gen', 'status' => 'inactive'],
            ['name' => 'Email Marketing Blast', 'type' => 'Email', 'status' => 'active'],
            ['name' => 'Social Media Campaign', 'type' => 'Social', 'status' => 'active']
        ];

        foreach ($campaigns as $campInfo) {
            $campaignType = \App\Models\CampaignType::where('created_by', $companyId)->inRandomOrder()->first();

            Campaign::create([
                'name' => $campInfo['name'],
                'description' => 'Strategic marketing campaign to drive business growth and customer engagement.',
                'start_date' => $faker->dateTimeBetween('-60 days', 'now'),
                'end_date' => $faker->dateTimeBetween('now', '+90 days'),
                'budget' => $faker->numberBetween(5000, 25000),
                'status' => $campInfo['status'],
                'campaign_type_id' => $campaignType?->id,
                'assigned_to' => $sarah->id,
                'created_by' => $companyId,
            ]);
        }

        $this->command->info('Successfully created comprehensive data for Sarah Johnson!');
        $this->command->info('- 5 Accounts with complete address info');
        $this->command->info('- 8 Products with stock levels and detailed specs');
        $this->command->info('- 8 Contacts with professional positions');
        $this->command->info('- 10 Leads with various statuses and values');
        $this->command->info('- 6 Opportunities with different stages');
        $this->command->info('- 4 Quotes with products and pricing');
        $this->command->info('- 3 Sales Orders with shipping info');
        $this->command->info('- 5 Invoices with different payment statuses');
        $this->command->info('- 3 Projects with budgets and 16 detailed tasks');
        $this->command->info('- 8 Delivery Orders with tracking');
        $this->command->info('- 8 Return Orders with reasons');
        $this->command->info('- 8 Purchase Orders for supplies');
        $this->command->info('- 8 Receipt Orders for deliveries');
        $this->command->info('- 3 Document Folders with 9 documents');
        $this->command->info('- 8 Marketing Campaigns with budgets');
    }
}
