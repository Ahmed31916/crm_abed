<?php

namespace Database\Seeders;

use App\Models\DocumentFolder;
use App\Models\User;
use Illuminate\Database\Seeder;

class DocumentFolderSeeder extends Seeder
{
    public function run(): void
    {
        $companyUsers = User::where('type', 'company')->get();
        
        if ($companyUsers->isEmpty()) {
            $this->command->warn('No company users found. Please run UserSeeder first.');
            return;
        }

        $foldersData = [
            ['name' => 'Contracts', 'description' => 'Legal contracts and agreements'],
            ['name' => 'Marketing Materials', 'description' => 'Brochures, presentations, and marketing content'],
            ['name' => 'Financial Documents', 'description' => 'Invoices, receipts, and financial reports'],
            ['name' => 'HR Documents', 'description' => 'Employee records and HR policies'],
            ['name' => 'Project Files', 'description' => 'Project documentation and deliverables'],
        ];

        foreach ($companyUsers as $company) {
            $createdFolders = [];

            foreach ($foldersData as $folderData) {
                $folder = DocumentFolder::create([
                    'name' => $folderData['name'],
                    'parent_folder_id' => null,
                    'description' => $folderData['description'],
                    'status' => 'active',
                    'created_by' => $company->id,
                ]);

                $createdFolders[] = $folder;
            }

            $subFoldersData = [
                ['name' => 'Client Contracts', 'parent_idx' => 0, 'description' => 'Contracts with clients'],
                ['name' => 'Vendor Agreements', 'parent_idx' => 0, 'description' => 'Agreements with vendors and suppliers'],
                ['name' => 'Brochures', 'parent_idx' => 1, 'description' => 'Product and service brochures'],
            ];

            foreach ($subFoldersData as $subFolderData) {
                DocumentFolder::create([
                    'name' => $subFolderData['name'],
                    'parent_folder_id' => $createdFolders[$subFolderData['parent_idx']]->id,
                    'description' => $subFolderData['description'],
                    'status' => 'active',
                    'created_by' => $company->id,
                ]);
            }
        }
        
        $this->command->info('Document folders created for all company users!');
    }
}