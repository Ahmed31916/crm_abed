<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['name' => 'Palestine', 'code' => 'PSE', 'phone_code' => '+970', 'flag' => '🇵🇸', 'status' => true],
            ['name' => 'Jordan', 'code' => 'JOR', 'phone_code' => '+962', 'flag' => '🇯🇴', 'status' => true],
            ['name' => 'Saudi Arabia', 'code' => 'SAU', 'phone_code' => '+966', 'flag' => '🇸🇦', 'status' => true],
            ['name' => 'Egypt', 'code' => 'EGY', 'phone_code' => '+20', 'flag' => '🇪🇬', 'status' => true],
            ['name' => 'United Arab Emirates', 'code' => 'ARE', 'phone_code' => '+971', 'flag' => '🇦🇪', 'status' => true],
            ['name' => 'Kuwait', 'code' => 'KWT', 'phone_code' => '+965', 'flag' => '🇰🇼', 'status' => true],
            ['name' => 'Qatar', 'code' => 'QAT', 'phone_code' => '+974', 'flag' => '🇶🇦', 'status' => true],
            ['name' => 'Bahrain', 'code' => 'BHR', 'phone_code' => '+973', 'flag' => '🇧🇭', 'status' => true],
            ['name' => 'Oman', 'code' => 'OMN', 'phone_code' => '+968', 'flag' => '🇴🇲', 'status' => true],
            ['name' => 'Lebanon', 'code' => 'LBN', 'phone_code' => '+961', 'flag' => '🇱🇧', 'status' => true],
            ['name' => 'Syria', 'code' => 'SYR', 'phone_code' => '+963', 'flag' => '🇸🇾', 'status' => true],
            ['name' => 'Iraq', 'code' => 'IRQ', 'phone_code' => '+964', 'flag' => '🇮🇶', 'status' => true],
            ['name' => 'Morocco', 'code' => 'MAR', 'phone_code' => '+212', 'flag' => '🇲🇦', 'status' => true],
            ['name' => 'Tunisia', 'code' => 'TUN', 'phone_code' => '+216', 'flag' => '🇹🇳', 'status' => true],
            ['name' => 'Algeria', 'code' => 'DZA', 'phone_code' => '+213', 'flag' => '🇩🇿', 'status' => true],
            ['name' => 'Libya', 'code' => 'LBY', 'phone_code' => '+218', 'flag' => '🇱🇾', 'status' => true],
            ['name' => 'Sudan', 'code' => 'SDN', 'phone_code' => '+249', 'flag' => '🇸🇩', 'status' => true],
            ['name' => 'Yemen', 'code' => 'YEM', 'phone_code' => '+967', 'flag' => '🇾🇪', 'status' => true],
            ['name' => 'Turkey', 'code' => 'TUR', 'phone_code' => '+90', 'flag' => '🇹🇷', 'status' => true],
            ['name' => 'United States', 'code' => 'USA', 'phone_code' => '+1', 'flag' => '🇺🇸', 'status' => true],
            ['name' => 'United Kingdom', 'code' => 'GBR', 'phone_code' => '+44', 'flag' => '🇬🇧', 'status' => true],
            ['name' => 'Canada', 'code' => 'CAN', 'phone_code' => '+1', 'flag' => '🇨🇦', 'status' => true],
            ['name' => 'Germany', 'code' => 'DEU', 'phone_code' => '+49', 'flag' => '🇩🇪', 'status' => true],
            ['name' => 'France', 'code' => 'FRA', 'phone_code' => '+33', 'flag' => '🇫🇷', 'status' => true],
            ['name' => 'India', 'code' => 'IND', 'phone_code' => '+91', 'flag' => '🇮🇳', 'status' => true],
            ['name' => 'Malaysia', 'code' => 'MYS', 'phone_code' => '+60', 'flag' => '🇲🇾', 'status' => true],
            ['name' => 'Indonesia', 'code' => 'IDN', 'phone_code' => '+62', 'flag' => '🇮🇩', 'status' => true],
            ['name' => 'Pakistan', 'code' => 'PAK', 'phone_code' => '+92', 'flag' => '🇵🇰', 'status' => true],
            ['name' => 'Bangladesh', 'code' => 'BGD', 'phone_code' => '+880', 'flag' => '🇧🇩', 'status' => true],
        ];

        foreach ($countries as $country) {
            Country::create($country);
        }
    }
}