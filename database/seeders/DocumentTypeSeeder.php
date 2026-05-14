<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DocumentType;
use App\Models\User;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $companies = User::where('type', 'company')->get();
        
        if ($companies->isEmpty()) {
            $this->command->warn('No company users found. Please run UserSeeder first.');
            return;
        }

        $documentTypes = [
            'Contract',
            'Invoice', 
            'Proposal',
            'Report',
            'Presentation',
            'Agreement',
            'Policy',
            'Manual',
        ];

        foreach ($companies as $company) {
            foreach ($documentTypes as $typeName) {
                DocumentType::firstOrCreate(
                    [
                        'type_name' => $typeName,
                        'created_by' => $company->id
                    ],
                    [
                        'type_name' => $typeName,
                        'status' => 'active',
                        'created_by' => $company->id,
                    ]
                );
            }
        }
        
        $this->command->info('Document types created for all company users!');
    }
}