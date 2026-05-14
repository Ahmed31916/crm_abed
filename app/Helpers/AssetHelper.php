<?php

namespace App\Helpers;

class AssetHelper
{
    /**
     * Generate the correct asset URL regardless of installation environment
     *
     * @param string $path
     * @return string
     */
    public static function asset($path)
    {
        // Get the current URL from the request
        $currentUrl = url('/');
        
        // For Vite assets, use the correct manifest path
        if (str_starts_with($path, 'build/')) {
            return self::viteAsset($path);
        }
        
        // For other assets, use the standard asset helper
        return asset($path);
    }
    
    /**
     * Generate the correct Vite asset URL
     *
     * @param string $path
     * @return string
     */
    public static function viteAsset($path)
    {
        // Use Vite's asset helper but ensure it uses relative paths
        return vite(str_replace('build/', '', $path));
    }
}