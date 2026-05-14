<?php

namespace App\Http\Controllers;

use App\Models\MediaItem;
use App\Models\User;
use App\Services\StorageConfigService;
use App\Services\DynamicStorageService;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
class MediaController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $mediaItems = MediaItem::with('media')->latest()->get();

        $media = $mediaItems->flatMap(function ($item) use ($user) {
            $mediaQuery = $item->getMedia('images');

            // SuperAdmin can see all media
            if ($user->type === 'superadmin') {
                // No user_id filter for superadmin
            }
            // Users with manage-any-media can see all media
            elseif ($user->hasPermissionTo('manage-any-media')) {
                // No user_id filter for manage-any-media
            }
            // Others can only see their own media
            else {
                $mediaQuery = $mediaQuery->where('user_id', $user->id);
            }

            return $mediaQuery->map(function ($media) {
                try {
                    $originalUrl = $this->getFullUrl($media->getUrl());
                    $thumbUrl = $originalUrl;

                    try {
                        $thumbUrl = $this->getFullUrl($media->getUrl('thumb'));
                    } catch (\Exception $e) {
                        // If thumb conversion fails, use original
                    }

                    return [
                        'id' => $media->id,
                        'name' => $media->name,
                        'file_name' => $media->file_name,
                        'url' => $originalUrl,
                        'thumb_url' => $thumbUrl,
                        'size' => $media->size,
                        'mime_type' => $media->mime_type,
                        'user_id' => $media->user_id,
                        'created_at' => $media->created_at,
                    ];
                } catch (\Exception $e) {
                    // Skip media files with unavailable storage disks
                    return null;
                }
            })->filter(); // Remove null entries
        });

        return response()->json($media);
    }

    private function getFullUrl($url)
    {
        if (str_starts_with($url, 'http')) {
            return $url;
        }

        $baseUrl = request()->getSchemeAndHttpHost();
        return $baseUrl . $url;
    }

    private function getUserFriendlyError(\Exception $e, $fileName, $maxSizeMB = null): string
    {
        $message = $e->getMessage();
        $extension = strtoupper(pathinfo($fileName, PATHINFO_EXTENSION));

        \Log::error('Media upload error', [
            'file' => $fileName,
            'error' => $message,
            'trace' => $e->getTraceAsString()
        ]);

        // Handle media library collection errors
        if (str_contains($message, 'was not accepted into the collection')) {
            if (str_contains($message, 'mime:')) {
                return __("File type not allowed: :extension. Please check your storage settings.", ['extension' => $extension]);
            }
            return __("File format not supported: :extension. Please check your storage settings.", ['extension' => $extension]);
        }

        // Handle storage disk errors
        if (str_contains($message, 'storage') || str_contains($message, 'disk') || str_contains($message, 'No such file or directory')) {
            return __("Storage error: :extension. Please check storage configuration.", ['extension' => $extension]);
        }

        // Handle file size errors
        if (str_contains($message, 'size') || str_contains($message, 'large') || str_contains($message, 'exceeds')) {
            if ($maxSizeMB) {
                return __("Max :max MB is allowed.", ['max' => $maxSizeMB]);
            }
            return __("File too large: :extension", ['extension' => $extension]);
        }

        // Handle permission errors
        if (str_contains($message, 'permission') || str_contains($message, 'denied') || str_contains($message, 'not writable')) {
            return __("Permission denied: :extension. Check directory permissions.", ['extension' => $extension]);
        }

        // Handle image processing errors
        if (str_contains($message, 'image') || str_contains($message, 'conversion') || str_contains($message, 'gd') || str_contains($message, 'imagick')) {
            return __("Image processing error: :extension. File may be corrupted.", ['extension' => $extension]);
        }

        // Generic fallback with more detail
        return __("Upload failed: :extension. Error: :error", ['extension' => $extension, 'error' => substr($message, 0, 100)]);
    }

    public function batchStore(Request $request)
    {
        // Validate storage configuration
        $storageValidation = $this->validateStorageConfig();
        if ($storageValidation) {
            return $storageValidation;
        }

        // Check storage limits
        $storageCheck = $this->checkStorageLimit($request->file('files'));
        if ($storageCheck) {
            return $storageCheck;
        }

        // Get superadmin storage config (bypass cache)
        $superadmin = User::where('type', 'superadmin')->first();
        // $config = ['allowed_file_types' => 'jpg,jpeg,png,webp,gif', 'max_file_size_kb' => 2048];

        // if ($superadmin) {
            // Clear cache to get fresh settings
            \Cache::forget('active_storage_config_' . $superadmin->id);

            $settings = \DB::table('settings')
                ->where('user_id', $superadmin->id)
                ->whereIn('key', ['storage_file_types', 'storage_max_upload_size'])
                ->pluck('value', 'key')
                ->toArray();

            $config = [
                'allowed_file_types' => $settings['storage_file_types'] ?? 'jpg,jpeg,png,webp,gif',
                'max_file_size_kb' => (int)($settings['storage_max_upload_size'] ?? 2048)
            ];

        //     \Log::info('Superadmin storage config loaded', $config);
        // }

        // Normalize allowed file types to handle case sensitivity
        $allowedTypes = $config['allowed_file_types'];
        $normalizedTypes = strtolower($allowedTypes);
        $maxSizeKB = $config['max_file_size_kb'];
        $maxSizeMB = round($maxSizeKB / 1024, 2);
        $validationRules = StorageConfigService::getFileValidationRules();

        // Custom validation with user-friendly messages
        $validator = \Validator::make($request->all(), [
            'files' => 'required|array',
            'files.*' => array_merge(['file'], $validationRules),
        ], [
            'files.*.mimes' => __('Only these file types are allowed: :type', [
                'type' => strtoupper(str_replace(',', ', ', $allowedTypes))
            ]),
            'files.*.max' => __('File size cannot exceed :max MB.', ['max' => $maxSizeMB]),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('File validation failed'),
                'errors' => $validator->errors()->all(),
                'allowed_types' => $config['allowed_file_types'],
                'max_size_mb' => $maxSizeMB
            ], 422);
        }

        // Set max file size for Spatie Media Library (in bytes)
        config(['media-library.max_file_size' => $maxSizeKB * 1024]);

        $uploadedMedia = [];
        $errors = [];

        foreach ($request->file('files') as $file) {
            try {
                $mediaItem = MediaItem::create([
                    'name' => $file->getClientOriginalName(),
                ]);

                $media = $mediaItem->addMedia($file)
                    ->toMediaCollection('images');

                $media->user_id = auth()->id();
                $media->save();

                // Update user storage usage
                $this->updateStorageUsage(auth()->user(), $media->size);

                // Force thumbnail generationAdd commentMore actions
                try {
                    $media->getUrl('thumb');
                } catch (\Exception $e) {
                    // Thumbnail generation failed, but continue
                }

                $originalUrl = $this->getFullUrl($media->getUrl());
                $thumbUrl = $originalUrl; // Default to original

                try {
                    $thumbUrl = $this->getFullUrl($media->getUrl('thumb'));
                } catch (\Exception $e) {
                    // If thumb conversion fails, use original
                }

                $uploadedMedia[] = [
                    'id' => $media->id,
                    'name' => $media->name,
                    'file_name' => $media->file_name,
                    'url' => $originalUrl,
                    'thumb_url' => $thumbUrl,
                    'size' => $media->size,
                    'mime_type' => $media->mime_type,
                    'user_id' => $media->user_id,
                    'created_at' => $media->created_at,
                ];
            } catch (\Exception $e) {
                if (isset($mediaItem)) {
                    $mediaItem->delete();
                }
                $errors[] = [
                    'file' => $file->getClientOriginalName(),
                    'error' => $this->getUserFriendlyError($e, $file->getClientOriginalName(), $maxSizeMB)
                ];
            }
        }

        if (count($uploadedMedia) > 0 && empty($errors)) {
            return response()->json([
                'message' => count($uploadedMedia) . __(' file(s) uploaded successfully'),
                'data' => $uploadedMedia
            ]);
        } elseif (count($uploadedMedia) > 0 && !empty($errors)) {
            return response()->json([
                'message' => count($uploadedMedia) . ' uploaded, ' . count($errors) . ' failed',
                'data' => $uploadedMedia,
                'errors' => array_column($errors, 'error')
            ]);
        } else {
            return response()->json([
                'message' => 'Upload failed',
                'errors' => array_column($errors, 'error')
            ], 422);
        }
    }

    public function download($id)
    {
        $user = auth()->user();
        $query = Media::where('id', $id);

        // SuperAdmin and users with manage-any-media can download any media
        if ($user->type !== 'superadmin' && !$user->hasPermissionTo('manage-any-media')) {
            $query->where('user_id', $user->id);
        }

        $media = $query->firstOrFail();

        try {
            $filePath = $media->getPath();

            if (!file_exists($filePath)) {
                abort(404, __('File not found'));
            }

            return response()->download($filePath, $media->file_name);
        } catch (\Exception $e) {
            abort(404, __('File storage unavailable'));
        }
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $query = Media::where('id', $id);

        // SuperAdmin and users with manage-any-media can delete any media
        if ($user->type !== 'superadmin' && !$user->hasPermissionTo('manage-any-media')) {
            $query->where('user_id', $user->id);
        }

        $media = $query->firstOrFail();
        $mediaItem = $media->model;

        $fileSize = $media->size;

        try {
            $media->delete();
        } catch (\Exception $e) {
            // If storage disk is unavailable, force delete from database
            $media->forceDelete();
        }

        // Update user storage usage
        $this->updateStorageUsage(auth()->user(), -$fileSize);

        // Delete the MediaItem if it has no more media files
        if ($mediaItem && $mediaItem->getMedia()->count() === 0) {
            $mediaItem->delete();
        }

        return response()->json(['message' => __('Media deleted successfully')]);
    }

    private function checkStorageLimit($files)
    {
        $user = auth()->user();
        if ($user->type === 'superadmin') return null;

        $limit = $this->getUserStorageLimit($user);
        if (!$limit) return null;

        $uploadSize = collect($files)->sum('size');
        $currentUsage = $this->getUserStorageUsage($user);

        if (($currentUsage + $uploadSize) > $limit) {
            return response()->json([
                'message' => __('Storage limit exceeded'),
                'errors' => [__('Please delete files or upgrade plan')]
            ], 422);
        }

        return null;
    }

    private function getUserStorageLimit($user)
    {
        if ($user->type === 'company' && $user->plan) {
            return $user->plan->storage_limit * 1024 * 1024 * 1024;
        }

        if ($user->created_by) {
            $company = User::find($user->created_by);
            if ($company && $company->plan) {
                return $company->plan->storage_limit * 1024 * 1024 * 1024;
            }
        }

        return null;
    }

    private function getUserStorageUsage($user)
    {
        if ($user->type === 'company') {
            // Get storage usage for company and all its staff
            $companyUsers = User::where('created_by', $user->id)->pluck('id')->push($user->id);
            return Media::whereIn('user_id', $companyUsers)->sum('size');
        }

        if ($user->created_by) {
            // Get storage usage for entire company
            $company = User::find($user->created_by);
            if ($company) {
                $companyUsers = User::where('created_by', $company->id)->pluck('id')->push($company->id);
                return Media::whereIn('user_id', $companyUsers)->sum('size');
            }
        }

        // Individual user storage usage
        return Media::where('user_id', $user->id)->sum('size');
    }

    private function updateStorageUsage($user, $size)
    {
        // Storage usage is now calculated from actual media files
        // No need to update any user field as we calculate from Media table
    }

    private function validateStorageConfig()
    {
        try {
            $disk = StorageConfigService::getActiveDisk();
            $storage = \Storage::disk($disk);

            // Test if we can write to the storage
            $testFile = 'test_' . time() . '.txt';
            $storage->put($testFile, 'test');
            $storage->delete($testFile);

            return null; // No error
        } catch (\Exception $e) {
            \Log::error('Storage validation failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => __('Storage configuration error'),
                'errors' => [__('Unable to access storage. Please check storage settings.')]
            ], 500);
        }
    }
}
