<?php

namespace App\Services;

class CacheConfig
{
    /**
     * Define cache TTLs for different types of data
     */
    public const TTL_SHORT = 900;    // 15 minutes
    public const TTL_MEDIUM = 1800;  // 30 minutes 
    public const TTL_LONG = 3600;    // 1 hour
    public const TTL_VERY_LONG = 7200; // 2 hours

    /**
     * Cache configuration for different data types
     */
    public const CACHE_CONFIG = [
        // User-related data
        'user_permissions' => [
            'ttl' => self::TTL_MEDIUM,
            'key_pattern' => 'user_permissions_{id}'
        ],
        
        'user_roles' => [
            'ttl' => self::TTL_MEDIUM,
            'key_pattern' => 'user_roles_{id}'
        ],
        
        // Role-related data
        'role_permissions' => [
            'ttl' => self::TTL_LONG,
            'key_pattern' => 'role_permissions_{name}'
        ],
        
        // Model data that doesn't change frequently
        'config' => [
            'ttl' => self::TTL_VERY_LONG,
            'key_pattern' => 'config_{key}'
        ],
        
        'dropdown_data' => [
            'ttl' => self::TTL_LONG,
            'key_pattern' => 'dropdown_{type}'
        ],
        
        // Dashboard analytics
        'dashboard_stats' => [
            'ttl' => self::TTL_SHORT,
            'key_pattern' => 'dashboard_stats_{type}_{user_id}'
        ],
        
        'mission_summary' => [
            'ttl' => self::TTL_SHORT,
            'key_pattern' => 'mission_summary_{user_id}'
        ],
        
        'active_users' => [
            'ttl' => self::TTL_SHORT,
            'key_pattern' => 'active_users'
        ],
        
        // API responses
        'api_response' => [
            'ttl' => self::TTL_MEDIUM,
            'key_pattern' => 'api_response_{endpoint}_{params}'
        ],
    ];

    /**
     * Get cache configuration for a specific type
     *
     * @param string $type
     * @return array|null
     */
    public static function getConfig(string $type): ?array
    {
        return self::CACHE_CONFIG[$type] ?? null;
    }

    /**
     * Get TTL for a specific cache type
     *
     * @param string $type
     * @return int
     */
    public static function getTtl(string $type): int
    {
        $config = self::getConfig($type);
        return $config['ttl'] ?? self::TTL_MEDIUM;
    }

    /**
     * Get cache key pattern for a specific type
     *
     * @param string $type
     * @return string
     */
    public static function getKeyPattern(string $type): string
    {
        $config = self::getConfig($type);
        return $config['key_pattern'] ?? 'cache_{type}_{id}';
    }
}