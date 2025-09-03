<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CacheService
{
    /**
     * Cache duration constants (in seconds)
     */
    const SHORT_CACHE = 300; // 5 minutes
    const MEDIUM_CACHE = 1800; // 30 minutes
    const LONG_CACHE = 3600; // 1 hour
    const DAILY_CACHE = 86400; // 24 hours
    const WEEKLY_CACHE = 604800; // 7 days

    /**
     * Cache key prefixes
     */
    const DASHBOARD_PREFIX = 'dashboard';
    const MISSIONS_PREFIX = 'missions';
    const BAIL_MOBILITES_PREFIX = 'bail_mobilites';
    const CHECKLISTS_PREFIX = 'checklists';
    const STATS_PREFIX = 'stats';
    const CALENDAR_PREFIX = 'calendar';
    const NOTIFICATIONS_PREFIX = 'notifications';

    /**
     * Get cached data with fallback
     */
    public function remember(string $key, int $ttl, callable $callback, array $tags = [])
    {
        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            Log::warning('Cache remember failed', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to direct execution
            return $callback();
        }
    }

    /**
     * Get cached data with tags support
     */
    public function rememberWithTags(string $key, array $tags, int $ttl, callable $callback)
    {
        try {
            if (config('cache.default') === 'redis') {
                return Cache::tags($tags)->remember($key, $ttl, $callback);
            } else {
                // Fallback for non-Redis cache drivers
                return $this->remember($key, $ttl, $callback);
            }
        } catch (\Exception $e) {
            Log::warning('Tagged cache remember failed', [
                'key' => $key,
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            
            return $callback();
        }
    }

    /**
     * Invalidate cache by tags
     */
    public function invalidateTags(array $tags): void
    {
        try {
            if (config('cache.default') === 'redis') {
                Cache::tags($tags)->flush();
            } else {
                // For non-Redis drivers, we need to track keys manually
                $this->invalidateKeysByPattern($tags);
            }
        } catch (\Exception $e) {
            Log::warning('Cache tag invalidation failed', [
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Dashboard caching methods
     */
    public function getDashboardStats(int $userId, string $role, callable $callback)
    {
        $key = $this->buildKey(self::DASHBOARD_PREFIX, 'stats', $userId, $role);
        $tags = [self::DASHBOARD_PREFIX, self::STATS_PREFIX, "user_{$userId}"];
        
        return $this->rememberWithTags($key, $tags, self::MEDIUM_CACHE, $callback);
    }

    public function getRecentActivity(int $userId, string $role, callable $callback)
    {
        $key = $this->buildKey(self::DASHBOARD_PREFIX, 'activity', $userId, $role);
        $tags = [self::DASHBOARD_PREFIX, "user_{$userId}"];
        
        return $this->rememberWithTags($key, $tags, self::SHORT_CACHE, $callback);
    }

    /**
     * Mission caching methods
     */
    public function getMissionsList(array $filters, callable $callback)
    {
        $filterHash = md5(serialize($filters));
        $key = $this->buildKey(self::MISSIONS_PREFIX, 'list', $filterHash);
        $tags = [self::MISSIONS_PREFIX];
        
        return $this->rememberWithTags($key, $tags, self::SHORT_CACHE, $callback);
    }

    public function getMissionDetails(int $missionId, callable $callback)
    {
        $key = $this->buildKey(self::MISSIONS_PREFIX, 'details', $missionId);
        $tags = [self::MISSIONS_PREFIX, "mission_{$missionId}"];
        
        return $this->rememberWithTags($key, $tags, self::MEDIUM_CACHE, $callback);
    }

    public function getMissionsByAgent(int $agentId, callable $callback)
    {
        $key = $this->buildKey(self::MISSIONS_PREFIX, 'agent', $agentId);
        $tags = [self::MISSIONS_PREFIX, "agent_{$agentId}"];
        
        return $this->rememberWithTags($key, $tags, self::SHORT_CACHE, $callback);
    }

    /**
     * Bail Mobilité caching methods
     */
    public function getBailMobilitesList(array $filters, callable $callback)
    {
        $filterHash = md5(serialize($filters));
        $key = $this->buildKey(self::BAIL_MOBILITES_PREFIX, 'list', $filterHash);
        $tags = [self::BAIL_MOBILITES_PREFIX];
        
        return $this->rememberWithTags($key, $tags, self::SHORT_CACHE, $callback);
    }

    public function getBailMobiliteDetails(int $bailMobiliteId, callable $callback)
    {
        $key = $this->buildKey(self::BAIL_MOBILITES_PREFIX, 'details', $bailMobiliteId);
        $tags = [self::BAIL_MOBILITES_PREFIX, "bail_mobilite_{$bailMobiliteId}"];
        
        return $this->rememberWithTags($key, $tags, self::MEDIUM_CACHE, $callback);
    }

    public function getBailMobilitesByStatus(string $status, callable $callback)
    {
        $key = $this->buildKey(self::BAIL_MOBILITES_PREFIX, 'status', $status);
        $tags = [self::BAIL_MOBILITES_PREFIX, "status_{$status}"];
        
        return $this->rememberWithTags($key, $tags, self::SHORT_CACHE, $callback);
    }

    /**
     * Calendar caching methods
     */
    public function getCalendarEvents(string $date, callable $callback)
    {
        $key = $this->buildKey(self::CALENDAR_PREFIX, 'events', $date);
        $tags = [self::CALENDAR_PREFIX, "date_{$date}"];
        
        return $this->rememberWithTags($key, $tags, self::MEDIUM_CACHE, $callback);
    }

    public function getCalendarMonth(string $month, callable $callback)
    {
        $key = $this->buildKey(self::CALENDAR_PREFIX, 'month', $month);
        $tags = [self::CALENDAR_PREFIX, "month_{$month}"];
        
        return $this->rememberWithTags($key, $tags, self::LONG_CACHE, $callback);
    }

    /**
     * Statistics caching methods
     */
    public function getPerformanceStats(string $period, callable $callback)
    {
        $key = $this->buildKey(self::STATS_PREFIX, 'performance', $period);
        $tags = [self::STATS_PREFIX];
        
        $ttl = $period === 'daily' ? self::LONG_CACHE : self::DAILY_CACHE;
        return $this->rememberWithTags($key, $tags, $ttl, $callback);
    }

    public function getSystemHealth(callable $callback)
    {
        $key = $this->buildKey(self::STATS_PREFIX, 'system_health');
        $tags = [self::STATS_PREFIX];
        
        return $this->rememberWithTags($key, $tags, self::SHORT_CACHE, $callback);
    }

    /**
     * Notification caching methods
     */
    public function getUserNotifications(int $userId, callable $callback)
    {
        $key = $this->buildKey(self::NOTIFICATIONS_PREFIX, 'user', $userId);
        $tags = [self::NOTIFICATIONS_PREFIX, "user_{$userId}"];
        
        return $this->rememberWithTags($key, $tags, self::SHORT_CACHE, $callback);
    }

    /**
     * Cache invalidation methods
     */
    public function invalidateMissionCache(int $missionId): void
    {
        $this->invalidateTags([
            self::MISSIONS_PREFIX,
            "mission_{$missionId}",
            self::DASHBOARD_PREFIX,
            self::CALENDAR_PREFIX
        ]);
    }

    public function invalidateBailMobiliteCache(int $bailMobiliteId): void
    {
        $this->invalidateTags([
            self::BAIL_MOBILITES_PREFIX,
            "bail_mobilite_{$bailMobiliteId}",
            self::DASHBOARD_PREFIX,
            self::CALENDAR_PREFIX
        ]);
    }

    public function invalidateUserCache(int $userId): void
    {
        $this->invalidateTags([
            "user_{$userId}",
            self::DASHBOARD_PREFIX,
            self::NOTIFICATIONS_PREFIX
        ]);
    }

    public function invalidateCalendarCache(string $date = null): void
    {
        $tags = [self::CALENDAR_PREFIX];
        
        if ($date) {
            $tags[] = "date_{$date}";
            $month = Carbon::parse($date)->format('Y-m');
            $tags[] = "month_{$month}";
        }
        
        $this->invalidateTags($tags);
    }

    public function invalidateStatsCache(): void
    {
        $this->invalidateTags([self::STATS_PREFIX, self::DASHBOARD_PREFIX]);
    }

    /**
     * Bulk cache operations
     */
    public function warmupDashboardCache(int $userId, string $role): void
    {
        try {
            // Pre-load common dashboard data
            $this->getDashboardStats($userId, $role, function() use ($userId, $role) {
                // This would be implemented in the respective controllers
                return [];
            });
            
            $this->getRecentActivity($userId, $role, function() use ($userId, $role) {
                // This would be implemented in the respective controllers
                return [];
            });
            
        } catch (\Exception $e) {
            Log::warning('Dashboard cache warmup failed', [
                'user_id' => $userId,
                'role' => $role,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function clearAllCache(): void
    {
        try {
            Cache::flush();
        } catch (\Exception $e) {
            Log::error('Failed to clear all cache', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Cache health check
     */
    public function healthCheck(): array
    {
        $health = [
            'status' => 'healthy',
            'driver' => config('cache.default'),
            'issues' => []
        ];

        try {
            // Test basic cache operations
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';
            
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            if ($retrieved !== $testValue) {
                $health['status'] = 'unhealthy';
                $health['issues'][] = 'Cache read/write test failed';
            }
            
            // Test Redis connection if using Redis
            if (config('cache.default') === 'redis') {
                try {
                    Redis::ping();
                } catch (\Exception $e) {
                    $health['status'] = 'unhealthy';
                    $health['issues'][] = 'Redis connection failed: ' . $e->getMessage();
                }
            }
            
        } catch (\Exception $e) {
            $health['status'] = 'unhealthy';
            $health['issues'][] = 'Cache health check failed: ' . $e->getMessage();
        }

        return $health;
    }

    /**
     * Helper methods
     */
    private function buildKey(string ...$parts): string
    {
        return implode(':', array_filter($parts));
    }

    private function invalidateKeysByPattern(array $patterns): void
    {
        // This is a simplified implementation for non-Redis drivers
        // In production, you might want to maintain a registry of keys
        foreach ($patterns as $pattern) {
            try {
                Cache::forget($pattern);
            } catch (\Exception $e) {
                Log::warning('Failed to invalidate cache key', [
                    'pattern' => $pattern,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        $stats = [
            'driver' => config('cache.default'),
            'prefix' => config('cache.prefix'),
        ];

        try {
            if (config('cache.default') === 'redis') {
                $redis = Redis::connection();
                $info = $redis->info();
                
                $stats['redis'] = [
                    'used_memory' => $info['used_memory_human'] ?? 'unknown',
                    'connected_clients' => $info['connected_clients'] ?? 'unknown',
                    'total_commands_processed' => $info['total_commands_processed'] ?? 'unknown',
                    'keyspace_hits' => $info['keyspace_hits'] ?? 'unknown',
                    'keyspace_misses' => $info['keyspace_misses'] ?? 'unknown',
                ];
                
                if (isset($info['keyspace_hits']) && isset($info['keyspace_misses'])) {
                    $total = $info['keyspace_hits'] + $info['keyspace_misses'];
                    $stats['redis']['hit_rate'] = $total > 0 ? round(($info['keyspace_hits'] / $total) * 100, 2) . '%' : '0%';
                }
            }
        } catch (\Exception $e) {
            $stats['error'] = 'Failed to get cache stats: ' . $e->getMessage();
        }

        return $stats;
    }
}