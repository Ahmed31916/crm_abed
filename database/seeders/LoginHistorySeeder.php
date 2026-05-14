<?php

namespace Database\Seeders;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class LoginHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing users
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }

        // Sample IP addresses
        $ipAddresses = [
            '192.168.1.100',
            '10.0.0.50',
            '172.16.0.25',
            '203.0.113.45',
            '198.51.100.78',
            '127.0.0.1',
            '192.168.0.15',
            '10.1.1.200'
        ];

        // Sample browser and device data
        $browserData = [
            [
                'browser_name' => 'Chrome',
                'os_name' => 'Windows',
                'device_type' => 'desktop',
                'browser_language' => 'en'
            ],
            [
                'browser_name' => 'Firefox',
                'os_name' => 'Linux',
                'device_type' => 'desktop',
                'browser_language' => 'en'
            ],
            [
                'browser_name' => 'Safari',
                'os_name' => 'macOS',
                'device_type' => 'desktop',
                'browser_language' => 'en'
            ],
            [
                'browser_name' => 'Chrome',
                'os_name' => 'Android',
                'device_type' => 'mobile',
                'browser_language' => 'en'
            ],
            [
                'browser_name' => 'Safari',
                'os_name' => 'iOS',
                'device_type' => 'mobile',
                'browser_language' => 'en'
            ]
        ];

        // Sample location data
        $locationData = [
            [
                'country' => 'United States',
                'countryCode' => 'US',
                'region' => 'CA',
                'regionName' => 'California',
                'city' => 'San Francisco',
                'zip' => '94102',
                'lat' => 37.7749,
                'lon' => -122.4194,
                'timezone' => 'America/Los_Angeles',
                'isp' => 'Comcast Cable',
                'org' => 'Comcast Cable Communications'
            ],
            [
                'country' => 'United Kingdom',
                'countryCode' => 'GB',
                'region' => 'ENG',
                'regionName' => 'England',
                'city' => 'London',
                'zip' => 'SW1A',
                'lat' => 51.5074,
                'lon' => -0.1278,
                'timezone' => 'Europe/London',
                'isp' => 'British Telecom',
                'org' => 'BT Group'
            ],
            [
                'country' => 'Germany',
                'countryCode' => 'DE',
                'region' => 'BE',
                'regionName' => 'Berlin',
                'city' => 'Berlin',
                'zip' => '10115',
                'lat' => 52.5200,
                'lon' => 13.4050,
                'timezone' => 'Europe/Berlin',
                'isp' => 'Deutsche Telekom',
                'org' => 'T-Systems'
            ],
            [
                'country' => null,
                'countryCode' => null,
                'region' => null,
                'regionName' => null,
                'city' => null,
                'zip' => null,
                'lat' => null,
                'lon' => null,
                'timezone' => null,
                'isp' => null,
                'org' => null
            ]
        ];

        // Get users by type
        $superadminUsers = User::where('type', 'superadmin')->get();
        $companyUsers = User::where('type', 'company')->get();
        $staffUsers = User::where('type', '!=', 'superadmin')->where('type', '!=', 'company')->get();

        // Create mixed login history records (20 total)
        $recordsCreated = 0;

        // Create 5 superadmin login records
        if ($superadminUsers->isNotEmpty()) {
            for ($i = 0; $i < 5 && $recordsCreated < 20; $i++) {
                $user = $superadminUsers->random();
                $this->createLoginRecord($user, $browserData, $locationData, $ipAddresses, $user->id);
                $recordsCreated++;
            }
        }

        // Create 8 company login records
        if ($companyUsers->isNotEmpty()) {
            for ($i = 0; $i < 8 && $recordsCreated < 20; $i++) {
                $user = $companyUsers->random();
                $superadmin = $superadminUsers->first();
                $createdBy = $superadmin ? $superadmin->id : $user->id;
                $this->createLoginRecord($user, $browserData, $locationData, $ipAddresses, $createdBy);
                $recordsCreated++;
            }
        }

        // Create 7 staff login records (mostly created_by=2)
        if ($staffUsers->isNotEmpty()) {
            for ($i = 0; $i < 7 && $recordsCreated < 20; $i++) {
                $user = $staffUsers->random();
                $createdBy = rand(1,2);
                $this->createLoginRecord($user, $browserData, $locationData, $ipAddresses, $createdBy);
                $recordsCreated++;
            }
        }

        // Fill remaining records if any user type is missing
        while ($recordsCreated < 20 && $users->isNotEmpty()) {
            $user = $users->random();
            $createdBy = $this->getCreatedBy($user, $superadminUsers, $companyUsers);
            $this->createLoginRecord($user, $browserData, $locationData, $ipAddresses, $createdBy);
            $recordsCreated++;
        }

        $this->command->info("{$recordsCreated} login history records created successfully.");
        $this->command->info('Distribution: 5 superadmin, 8 company, 7 staff records.');
    }

    private function createLoginRecord($user, $browserData, $locationData, $ipAddresses, $createdBy)
    {
        $browser = $browserData[array_rand($browserData)];
        $location = $locationData[array_rand($locationData)];
        $ip = $ipAddresses[array_rand($ipAddresses)];

        // Combine all details
        $details = array_merge($browser, $location, [
            'status' => 'success',
            'query' => $ip,
            'referrer_host' => fake()->randomElement(['localhost', 'example.com', 'app.domain.com', null]),
            'referrer_path' => fake()->randomElement(['/login', '/dashboard', '/home', null]),
            'as' => null
        ]);

        LoginHistory::create([
            'user_id' => $user->id,
            'ip' => $ip,
            'date' => Carbon::now()->subDays(rand(0, 30))->toDateString(),
            'details' => $details,
            'type' => $user->type,
            'created_by' => $createdBy,
            'created_at' => Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59)),
            'updated_at' => Carbon::now()
        ]);
    }

    private function getCreatedBy($user, $superadminUsers, $companyUsers)
    {
        if ($user->type === 'superadmin') {
            return $user->id;
        } elseif ($user->type === 'company') {
            $superadmin = $superadminUsers->first();
            return $superadmin ? $superadmin->id : $user->id;
        } else {
            return $user->created_by ?: ($companyUsers->first() ? $companyUsers->first()->id : $user->id);
        }
    }
}
