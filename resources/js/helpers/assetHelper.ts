/**
 * Asset URL helper for JavaScript
 * Ensures assets work correctly regardless of installation URL
 */

/**
 * Get the correct asset URL
 * @param path - The asset path
 * @returns The full asset URL
 */
export function asset(path: string): string {
    // Remove leading slash if present
    const cleanPath = path.startsWith('/') ? path.substring(1) : path;
    
    // For absolute URLs, return as is
    if (cleanPath.startsWith('http://') || cleanPath.startsWith('https://')) {
        return cleanPath;
    }
    
    // For relative URLs in production, use relative paths
    if (import.meta.env.PROD) {
        return cleanPath;
    }
    
    // For development, use the base URL from the window object
    const baseUrl = (window as any).baseUrl || '';
    return `${baseUrl}/${cleanPath}`;
}

/**
 * Get the correct Vite asset URL
 * @param path - The asset path
 * @returns The full asset URL
 */
export function viteAsset(path: string): string {
    // For Vite assets, we need to handle them differently
    // This ensures they work with the correct manifest
    return asset(path);
}