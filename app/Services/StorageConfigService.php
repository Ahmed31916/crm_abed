<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StorageConfigService
{
    private static $config = null;

    /**
     * Get the active storage disk name
     */
    public static function getActiveDisk(): string
    {
        $userId = Auth::id(); // Current user or superadmin fallback
        if (!$userId) {
            return 'public'; // Default for unauthenticated users
        }

        $cacheKey = 'active_storage_config_' . $userId;
        $config = Cache::remember($cacheKey, 60, function() use ($userId) {
            return self::loadStorageConfigFromDB($userId);
        });

        return $config['disk'] ?? 'public';
    }

    /**
     * Get file validation rules based on settings
     */
    public static function getFileValidationRules(): array
    {
        $config = self::getStorageConfig();

        $allowedTypes = $config['allowed_file_types'] ?? '';
        $maxSize = ($config['max_file_size_mb'] ?? 2) * 1024; // Convert MB to KB

        return [
            'mimes:' . $allowedTypes,
            'max:' . $maxSize
        ];
    }

    /**
     * Get complete storage configuration
     */
    public static function getStorageConfig(): array
    {
        $userId = Auth::id() ?: 1; // Current user or superadmin fallback
        if (!$userId) {
            return self::getDefaultConfig(); // Default for unauthenticated users
        }

        $cacheKey = 'active_storage_config_' . $userId;
        return Cache::remember($cacheKey, 300, function() use ($userId) {
            return self::loadStorageConfigFromDB($userId);
        });
    }

    /**
     * Clear storage configuration cache
     */
    public static function clearCache(): void
    {
        $userId = Auth::id() ?: 1; // Current user or superadmin fallback
        if ($userId) {
            Cache::forget('active_storage_config_' . $userId);
        }
        // Also clear for all users if needed
        Cache::flush();
    }

    /**
     * Load storage configuration from database
     */
    private static function loadStorageConfigFromDB($userId = null): array
    {
        try {
            if (!$userId) {
                return self::getDefaultConfig();
            }

            $settings = DB::table('settings')
                ->where('user_id', $userId)
                ->whereIn('key', [
                    'storage_type',
                    'storage_file_types',
                    'storage_max_upload_size',
                    'aws_access_key_id',
                    'aws_secret_access_key',
                    'aws_default_region',
                    'aws_bucket',
                    'aws_url',
                    'aws_endpoint',
                    'wasabi_access_key',
                    'wasabi_secret_key',
                    'wasabi_region',
                    'wasabi_bucket',
                    'wasabi_url',
                    'wasabi_root'
                ])
                ->pluck('value', 'key')
                ->toArray();

            \Log::info('Storage settings loaded', ['user_id' => $userId, 'settings' => $settings]);

            // If no settings found, return default
            if (empty($settings)) {
                \Log::info('No storage settings found, using defaults');
                return self::getDefaultConfig();
            }
            // Map storage_type to correct disk name
            $storageType = $settings['storage_type'] ?? 'local';
            $diskName = match($storageType) {
                'local' => 'public',
                's3' => 's3',
                'wasabi' => 'wasabi',
                default => 'public'
            };

            return [
                'disk' => $diskName,
                'allowed_file_types' => $settings['storage_file_types'] ?? 'jpg,png,webp,gif',
                'max_file_size_mb' => (int)($settings['storage_max_upload_size'] ?? 2),
                's3' => [
                    'key' => $settings['aws_access_key_id'] ?? '',
                    'secret' => $settings['aws_secret_access_key'] ?? '',
                    'bucket' => $settings['aws_bucket'] ?? '',
                    'region' => $settings['aws_default_region'] ?? 'us-east-1',
                    'url' => $settings['aws_url'] ?? '',
                    'endpoint' => $settings['aws_endpoint'] ?? '',
                ],
                'wasabi' => [
                    'key' => $settings['wasabi_access_key'] ?? '',
                    'secret' => $settings['wasabi_secret_key'] ?? '',
                    'bucket' => $settings['wasabi_bucket'] ?? '',
                    'region' => $settings['wasabi_region'] ?? 'us-east-1',
                    'url' => $settings['wasabi_url'] ?? '',
                    'root' => $settings['wasabi_root'] ?? '',
                ]
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to load storage config from DB', ['error' => $e->getMessage()]);
            return self::getDefaultConfig();
        }
    }

    /**
     * Get default storage configuration
     */
    private static function getDefaultConfig(): array
    {
        return [
            'disk' => 'public',
            'allowed_file_types' => 'jpg,png,webp,gif',
            'max_file_size_mb' => 2,
            's3' => [],
            'wasabi' => []
        ];
    }
}
