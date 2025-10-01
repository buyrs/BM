<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CacheService
{
    /**
     * Cache frequently accessed data
     *
     * @param string $key
     * @param mixed $data
     * @param int $ttl Time to live in seconds (default: 3600 seconds = 1 hour)
     * @return mixed
     */
    public function put(string $key, mixed $data, int $ttl = 3600): mixed
    {
        return Cache::put($key, $data, now()->addSeconds($ttl));
    }

    /**
     * Put data in cache using configuration
     *
     * @param string $type Cache type defined in CacheConfig
     * @param array $params Parameters to replace in key pattern
     * @param mixed $data Data to cache
     * @return mixed
     */
    public function putWithConfig(string $type, array $params, mixed $data): mixed
    {
        $keyPattern = CacheConfig::getKeyPattern($type);
        $key = $this->replaceKeyPattern($keyPattern, $params);
        $ttl = CacheConfig::getTtl($type);
        
        return $this->put($key, $data, $ttl);
    }

    /**
     * Get cached data
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($key, $default);
    }

    /**
     * Get cached data using configuration
     *
     * @param string $type Cache type defined in CacheConfig
     * @param array $params Parameters to replace in key pattern
     * @param mixed $default Default value
     * @return mixed
     */
    public function getWithConfig(string $type, array $params, mixed $default = null): mixed
    {
        $keyPattern = CacheConfig::getKeyPattern($type);
        $key = $this->replaceKeyPattern($keyPattern, $params);
        
        return $this->get($key, $default);
    }

    /**
     * Get cached data or store if not exists
     *
     * @param string $key
     * @param int $ttl
     * @param callable $callback
     * @return mixed
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        return Cache::remember($key, now()->addSeconds($ttl), $callback);
    }

    /**
     * Get cached data or store if not exists using configuration
     *
     * @param string $type Cache type defined in CacheConfig
     * @param array $params Parameters to replace in key pattern
     * @param callable $callback
     * @return mixed
     */
    public function rememberWithConfig(string $type, array $params, callable $callback): mixed
    {
        $keyPattern = CacheConfig::getKeyPattern($type);
        $key = $this->replaceKeyPattern($keyPattern, $params);
        $ttl = CacheConfig::getTtl($type);
        
        return $this->remember($key, $ttl, $callback);
    }

    /**
     * Get cached data or store forever if not exists
     *
     * @param string $key
     * @param callable $callback
     * @return mixed
     */
    public function rememberForever(string $key, callable $callback): mixed
    {
        return Cache::rememberForever($key, $callback);
    }

    /**
     * Remove item from cache
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Remove item from cache using configuration
     *
     * @param string $type Cache type defined in CacheConfig
     * @param array $params Parameters to replace in key pattern
     * @return bool
     */
    public function forgetWithConfig(string $type, array $params): bool
    {
        $keyPattern = CacheConfig::getKeyPattern($type);
        $key = $this->replaceKeyPattern($keyPattern, $params);
        
        return $this->forget($key);
    }

    /**
     * Check if key exists in cache
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * Check if key exists in cache using configuration
     *
     * @param string $type Cache type defined in CacheConfig
     * @param array $params Parameters to replace in key pattern
     * @return bool
     */
    public function hasWithConfig(string $type, array $params): bool
    {
        $keyPattern = CacheConfig::getKeyPattern($type);
        $key = $this->replaceKeyPattern($keyPattern, $params);
        
        return $this->has($key);
    }

    /**
     * Increment cached value
     *
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public function increment(string $key, int $value = 1): int|bool
    {
        return Cache::increment($key, $value);
    }

    /**
     * Decrement cached value
     *
     * @param string $key
     * @param int $value
     * @return int|bool
     */
    public function decrement(string $key, int $value = 1): int|bool
    {
        return Cache::decrement($key, $value);
    }

    /**
     * Get Redis connection instance
     *
     * @return \Illuminate\Redis\Connections\Connection
     */
    public function redis(): \Illuminate\Redis\Connections\Connection
    {
        return Redis::connection();
    }

    /**
     * Cache user permissions for faster lookup
     *
     * @param int $userId
     * @param array $permissions
     * @return void
     */
    public function cacheUserPermissions(int $userId, array $permissions): void
    {
        $this->putWithConfig('user_permissions', ['id' => $userId], $permissions);
    }

    /**
     * Get cached user permissions
     *
     * @param int $userId
     * @return array|null
     */
    public function getCachedUserPermissions(int $userId): ?array
    {
        return $this->getWithConfig('user_permissions', ['id' => $userId]);
    }

    /**
     * Cache user roles
     *
     * @param int $userId
     * @param array $roles
     * @return void
     */
    public function cacheUserRoles(int $userId, array $roles): void
    {
        $this->putWithConfig('user_roles', ['id' => $userId], $roles);
    }

    /**
     * Get cached user roles
     *
     * @param int $userId
     * @return array|null
     */
    public function getCachedUserRoles(int $userId): ?array
    {
        return $this->getWithConfig('user_roles', ['id' => $userId]);
    }

    /**
     * Cache role permissions
     *
     * @param string $roleName
     * @param array $permissions
     * @return void
     */
    public function cacheRolePermissions(string $roleName, array $permissions): void
    {
        $this->putWithConfig('role_permissions', ['name' => $roleName], $permissions);
    }

    /**
     * Get cached role permissions
     *
     * @param string $roleName
     * @return array|null
     */
    public function getCachedRolePermissions(string $roleName): ?array
    {
        return $this->getWithConfig('role_permissions', ['name' => $roleName]);
    }

    /**
     * Cache frequently accessed configuration data
     *
     * @param string $configKey
     * @param mixed $configValue
     * @return void
     */
    public function cacheConfig(string $configKey, mixed $configValue): void
    {
        $this->putWithConfig('config', ['key' => $configKey], $configValue);
    }

    /**
     * Get cached configuration data
     *
     * @param string $configKey
     * @return mixed
     */
    public function getCachedConfig(string $configKey): mixed
    {
        return $this->getWithConfig('config', ['key' => $configKey]);
    }

    /**
     * Cache query results
     *
     * @param string $queryKey
     * @param mixed $results
     * @param int $ttl
     * @return void
     */
    public function cacheQuery(string $queryKey, mixed $results, int $ttl = 1800): void
    {
        $key = "query_{$queryKey}";
        $this->put($key, $results, $ttl);
    }

    /**
     * Get cached query results
     *
     * @param string $queryKey
     * @return mixed
     */
    public function getCachedQuery(string $queryKey): mixed
    {
        $key = "query_{$queryKey}";
        return $this->get($key);
    }

    /**
     * Cache dashboard statistics
     *
     * @param int $userId
     * @param string $type
     * @param mixed $stats
     * @return void
     */
    public function cacheDashboardStats(int $userId, string $type, mixed $stats): void
    {
        $this->putWithConfig('dashboard_stats', ['type' => $type, 'user_id' => $userId], $stats);
    }

    /**
     * Get cached dashboard statistics
     *
     * @param int $userId
     * @param string $type
     * @return mixed
     */
    public function getCachedDashboardStats(int $userId, string $type): mixed
    {
        return $this->getWithConfig('dashboard_stats', ['type' => $type, 'user_id' => $userId]);
    }

    /**
     * Cache dropdown data
     *
     * @param string $type
     * @param mixed $data
     * @return void
     */
    public function cacheDropdownData(string $type, mixed $data): void
    {
        $this->putWithConfig('dropdown_data', ['type' => $type], $data);
    }

    /**
     * Get cached dropdown data
     *
     * @param string $type
     * @return mixed
     */
    public function getCachedDropdownData(string $type): mixed
    {
        return $this->getWithConfig('dropdown_data', ['type' => $type]);
    }

    /**
     * Cache mission summary for user
     *
     * @param int $userId
     * @param mixed $summary
     * @return void
     */
    public function cacheMissionSummary(int $userId, mixed $summary): void
    {
        $this->putWithConfig('mission_summary', ['user_id' => $userId], $summary);
    }

    /**
     * Get cached mission summary for user
     *
     * @param int $userId
     * @return mixed
     */
    public function getCachedMissionSummary(int $userId): mixed
    {
        return $this->getWithConfig('mission_summary', ['user_id' => $userId]);
    }

    /**
     * Clear all cached permissions
     *
     * @return void
     */
    public function clearPermissionCache(): void
    {
        // In a real Redis implementation, you might use Redis patterns to delete keys
        // For now, we'll just flush all cache (in production, be more specific)
        Cache::flush();
    }

    /**
     * Clear user-specific cached permissions
     *
     * @param int $userId
     * @return void
     */
    public function clearUserPermissionCache(int $userId): void
    {
        $this->forgetWithConfig('user_permissions', ['id' => $userId]);
    }

    /**
     * Clear user-specific cached roles
     *
     * @param int $userId
     * @return void
     */
    public function clearUserRoleCache(int $userId): void
    {
        $this->forgetWithConfig('user_roles', ['id' => $userId]);
    }

    /**
     * Clear role-specific cached permissions
     *
     * @param string $roleName
     * @return void
     */
    public function clearRolePermissionCache(string $roleName): void
    {
        $this->forgetWithConfig('role_permissions', ['name' => $roleName]);
    }

    /**
     * Replace placeholders in key pattern with actual values
     *
     * @param string $pattern
     * @param array $params
     * @return string
     */
    private function replaceKeyPattern(string $pattern, array $params): string
    {
        $key = $pattern;
        foreach ($params as $param => $value) {
            $key = str_replace("{{$param}}", $value, $key);
        }
        return $key;
    }
}