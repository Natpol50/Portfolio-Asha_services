<?php

namespace App\Services;

/**
 * CacheService - Simple file-based caching service
 * 
 * This class provides basic caching functionality for storing and retrieving
 * frequently accessed data to improve performance.
 */
class CacheService
{
    private string $cacheDir;
    private int $defaultTtl;
    
    /**
     * Create a new CacheService instance
     * 
     * @param string|null $cacheDir Cache directory (default: ROOT_DIR/var/cache)
     * @param int $defaultTtl Default time-to-live in seconds (default: 3600)
     */
    public function __construct(?string $cacheDir = null, int $defaultTtl = 3600)
    {
        $this->cacheDir = $cacheDir ?? ROOT_DIR . '/var/cache';
        $this->defaultTtl = $defaultTtl;
        
        // Ensure cache directory exists
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Get cached role permissions
     * 
     * @param int $roleId Role ID
     * @return array Role permissions
     */
    public function getRolePermission(int $roleId): array
    {
        $cacheKey = "role_permission_$roleId";
        
        // Try to get from cache first
        $cachedData = $this->get($cacheKey);
        
        if ($cachedData !== null) {
            return $cachedData;
        }
        
        // If not in cache, load from database (simplified for this example)
        // In a real application, this would query the database
        
        // For this example, we'll use hardcoded permissions
        $permissions = [
            1 => [
                'id' => 1,
                'name' => 'Admin',
                'view_projects' => 1,
                'edit_projects' => 1,
                'delete_projects' => 1,
                'view_personal_info' => 1,
                'edit_personal_info' => 1
            ],
            2 => [
                'id' => 2,
                'name' => 'User',
                'view_projects' => 1,
                'edit_projects' => 0,
                'delete_projects' => 0,
                'view_personal_info' => 1,
                'edit_personal_info' => 0
            ]
        ];
        
        // Default permissions for unknown roles
        $rolePermissions = $permissions[$roleId] ?? [
            'id' => $roleId,
            'name' => 'Unknown',
            'view_projects' => 0,
            'edit_projects' => 0,
            'delete_projects' => 0,
            'view_personal_info' => 0,
            'edit_personal_info' => 0
        ];
        
        // Cache the permissions
        $this->set($cacheKey, $rolePermissions, 86400); // 24 hours
        
        return $rolePermissions;
    }
    
    /**
     * Get a value from cache
     * 
     * @param string $key Cache key
     * @return mixed|null Cached value or null if not found/expired
     */
    public function get(string $key)
    {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $content = file_get_contents($filename);
        $data = json_decode($content, true);
        
        // Check if data is expired
        if ($data['expires'] < time()) {
            // Remove expired cache file
            unlink($filename);
            return null;
        }
        
        return $data['value'];
    }
    
    /**
     * Set a value in cache
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int|null $ttl Time-to-live in seconds (null for default)
     * @return bool True on success
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        
        $data = [
            'expires' => time() + $ttl,
            'value' => $value
        ];
        
        $filename = $this->getCacheFilename($key);
        
        return file_put_contents($filename, json_encode($data)) !== false;
    }
    
    /**
     * Delete a value from cache
     * 
     * @param string $key Cache key
     * @return bool True if the cache was deleted
     */
    public function delete(string $key): bool
    {
        $filename = $this->getCacheFilename($key);
        
        if (file_exists($filename)) {
            return unlink($filename);
        }
        
        return true;
    }
    
    /**
     * Clear all cache files
     * 
     * @return bool True on success
     */
    public function clear(): bool
    {
        $files = glob($this->cacheDir . '/*.cache');
        
        foreach ($files as $file) {
            if (!unlink($file)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get the filename for a cache key
     * 
     * @param string $key Cache key
     * @return string Cache filename
     */
    private function getCacheFilename(string $key): string
    {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
}
