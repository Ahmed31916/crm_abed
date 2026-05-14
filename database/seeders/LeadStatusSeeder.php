<?php

namespace Database\Seeders;

use App\Models\LeadStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeadStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyUsers = User::where('type', 'company')->get();

        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run UserSeeder first.');
            return;
        }

        $statusTemplates = [
            ['name' => 'New', 'color' => '#3B82F6', 'description' => 'Newly created leads'],
            ['name' => 'Contacted', 'color' => '#F59E0B', 'description' => 'Leads that have been contacted'],
            ['name' => 'Qualified', 'color' => '#10b77f', 'description' => 'Qualified leads ready for conversion'],
            ['name' => 'Proposal Sent', 'color' => '#8B5CF6', 'description' => 'Proposal has been sent to the lead'],
            ['name' => 'Converted', 'color' => '#059669', 'description' => 'Lead converted to account/contact'],
            ['name' => 'Lost', 'color' => '#EF4444', 'description' => 'Lead is lost or not interested']
        ];

        foreach ($companyUsers as $company) {
            foreach ($statusTemplates as $template) {
                LeadStatus::firstOrCreate(
                    ['name' => $template['name'], 'created_by' => $company->id],
                    [
                        'name' => $template['name'],
                        'color' => $template['color'],
                        'description' => $template['description'],
                        'status' => 'active',
                        'created_by' => $company->id,
                    ]
                );
            }
        }

        $this->command->info('Lead statuses created for all company users!');
    }
}
