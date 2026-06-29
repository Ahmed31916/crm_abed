<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        Brand::firstOrCreate(
            ['name' => 'Enzyme', 'created_by' => 1],
            [
                'name' => 'Enzyme',
                'description' => 'Enzyme',
                'status' => 'active',
                'created_by' => 1,
            ]
        );
    }
}