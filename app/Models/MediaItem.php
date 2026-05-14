<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Services\StorageConfigService;

class MediaItem extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = ['name', 'description'];

    public function registerMediaCollections(): void
    {
        $config = StorageConfigService::getStorageConfig();
        $allowedExtensions = array_map('trim', explode(',', strtolower($config['allowed_file_types'])));
        
        // Add jpeg as alias for jpg
        if (in_array('jpg', $allowedExtensions) && !in_array('jpeg', $allowedExtensions)) {
            $allowedExtensions[] = 'jpeg';
        }
        if (in_array('jpeg', $allowedExtensions) && !in_array('jpg', $allowedExtensions)) {
            $allowedExtensions[] = 'jpg';
        }
        
        $maxSizeBytes = ($config['max_file_size_mb'] ?? 10) * 1024 * 1024; // Convert MB to bytes
        
        $this->addMediaCollection('images')
            ->acceptsFile(function ($file) use ($allowedExtensions, $maxSizeBytes) {
                try {
                    // Check file extension
                    $fileName = $file->name ?? $file->getFilename();
                    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    
                    if (!in_array($extension, $allowedExtensions)) {
                        \Log::warning('File extension not allowed', ['extension' => $extension, 'allowed' => $allowedExtensions]);
                        return false;
                    }
                    
                    // Check file size
                    $fileSize = $file->size ?? filesize($file->getPathname());
                    if ($fileSize > $maxSizeBytes) {
                        \Log::warning('File size too large', ['size' => $fileSize, 'max' => $maxSizeBytes]);
                        return false;
                    }
                    
                    return true;
                } catch (\Exception $e) {
                    \Log::error('Error checking file acceptance', ['error' => $e->getMessage()]);
                    return false;
                }
            })
            ->useDisk(StorageConfigService::getActiveDisk());
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->performOnCollections('images')
            ->nonQueued();
    }
}