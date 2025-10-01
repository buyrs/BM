<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class CacheService extends BaseService implements \App\Contracts\CacheServiceInterface
{
    protected array $taggedCacheKeys = [];

    protected function getDefaultConfig(): array
    {
        return config('cache');
    }

    /**
     * Get cached data or execute callback and cache result
     */
    public function remember(string $key, $ttl, callable $callback, array $tags = [])
    {
        try {
            if (!empty($tags)) {
                return Cache::tags($tags)->remember($key, $ttl, $callback);
            }

            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            Log::error('Cache remember failed', [
                'key' => $key,
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);

            // Fallback to executing callback without caching
            return $callback();
        }
    }

    /**
     * Store data in cache
     */
    public function put(string $key, $value, $ttl = null, array $tags = []): bool
    {
        try {
            $ttl = $ttl ?? config('cache.default_ttl', 3600);

            if (!empty($tags)) {
                return Cache::tags($tags)->put($key, $value, $ttl);
            }

            return Cache::put($key, $value, $ttl);
        } catch (\Exception $e) {
            Log::error('Cache put failed', [
                'key' => $key,
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get data from cache
     */
    public function get(string $key, $default = null, array $tags = [])
    {
        try {
            if (!empty($tags)) {
                return Cache::tags($tags)->get($key, $default);
            }

            return Cache::get($key, $default);
        } catch (\Exception $e) {
            Log::error('Cache get failed', [
                'key' => $key,
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            return $default;
        }
    }

    /**
     * Remove data from cache
     */
    public function forget(string $key, array $tags = []): bool
    {
        try {
            if (!empty($tags)) {
                return Cache::tags($tags)->forget($key);
            }

            return Cache::forget($key);
        } catch (\Exception $e) {
            Log::error('Cache forget failed', [
                'key' => $key,
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Flush cache by tags
     */
    public function flushTags(array $tags): bool
    {
        try {
            Cache::tags($tags)->flush();
            return true;
        } catch (\Exception $e) {
            Log::error('Cache flush tags failed', [
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Generate cache key for model
     */
    public function modelKey(Model $model, string $suffix = ''): string
    {
        $key = strtolower(class_basename($model)) . ':' . $model->getKey();
        return $suffix ? $key . ':' . $suffix : $key;
    }

    /**
     * Generate cache key for collection
     */
    public function collectionKey(string $model, array $params = []): string
    {
        $key = strtolower($model) . ':collection';
        
        if (!empty($params)) {
            ksort($params);
            $key .= ':' . md5(serialize($params));
        }

        return $key;
    }

    /**
     * Cache model data with automatic invalidation
     */
    public function cacheModel(Model $model, $ttl = null): bool
    {
        $key = $this->modelKey($model);
        $tags = $this->getModelTags($model);
        
        return $this->put($key, $model->toArray(), $ttl, $tags);
    }

    /**
     * Get cached model data
     */
    public function getCachedModel(string $modelClass, $id)
    {
        $key = strtolower(class_basename($modelClass)) . ':' . $id;
        $tags = [strtolower(class_basename($modelClass))];
        
        return $this->get($key, null, $tags);
    }

    /**
     * Invalidate model cache
     */
    public function invalidateModel(Model $model): bool
    {
        $tags = $this->getModelTags($model);
        return $this->flushTags($tags);
    }

    /**
     * Get cache tags for model
     */
    protected function getModelTags(Model $model): array
    {
        $baseTag = strtolower(class_basename($model));
        $tags = [$baseTag];

        // Add relationship tags if model has specific relationships
        if (method_exists($model, 'getCacheInvalidationTags')) {
            $tags = array_merge($tags, $model->getCacheInvalidationTags());
        }

        return $tags;
    }

    /**
     * Warm up frequently accessed cache data
     */
    public function warmCache(): void
    {
        try {
            Log::info('Starting cache warming process');

            // Warm up user data
            $this->warmUserCache();

            // Warm up property data
            $this->warmPropertyCache();

            // Warm up mission data
            $this->warmMissionCache();

            Log::info('Cache warming completed successfully');
        } catch (\Exception $e) {
            Log::error('Cache warming failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Warm up user cache
     */
    protected function warmUserCache(): void
    {
        $this->remember('users:active', 3600, function () {
            return \App\Models\User::where('created_at', '>', now()->subDays(30))->count();
        }, ['users']);

        $this->remember('users:by_role', 3600, function () {
            return \App\Models\User::selectRaw('role, count(*) as count')
                ->groupBy('role')
                ->pluck('count', 'role')
                ->toArray();
        }, ['users']);
    }

    /**
     * Warm up property cache
     */
    protected function warmPropertyCache(): void
    {
        $this->remember('properties:count', 3600, function () {
            return \App\Models\Property::count();
        }, ['properties']);

        $this->remember('properties:by_type', 3600, function () {
            return \App\Models\Property::selectRaw('property_type, count(*) as count')
                ->groupBy('property_type')
                ->pluck('count', 'property_type')
                ->toArray();
        }, ['properties']);
    }

    /**
     * Warm up mission cache
     */
    protected function warmMissionCache(): void
    {
        $this->remember('missions:active', 3600, function () {
            return \App\Models\Mission::where('status', 'active')->count();
        }, ['missions']);

        $this->remember('missions:completion_rate', 3600, function () {
            $total = \App\Models\Mission::count();
            $completed = \App\Models\Mission::where('status', 'completed')->count();
            
            return $total > 0 ? round(($completed / $total) * 100, 2) : 0;
        }, ['missions']);
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        try {
            // This would depend on the Redis driver being used
            $redis = Cache::getRedis();
            $info = $redis->info();

            return [
                'memory_used' => $info['used_memory_human'] ?? 'unknown',
                'connected_clients' => $info['connected_clients'] ?? 'unknown',
                'total_commands_processed' => $info['total_commands_processed'] ?? 'unknown',
                'keyspace_hits' => $info['keyspace_hits'] ?? 'unknown',
                'keyspace_misses' => $info['keyspace_misses'] ?? 'unknown',
                'hit_rate' => $this->calculateHitRate($info),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get cache stats', [
                'error' => $e->getMessage()
            ]);

            return [
                'error' => 'Unable to retrieve cache statistics'
            ];
        }
    }

    /**
     * Calculate cache hit rate
     */
    protected function calculateHitRate(array $info): string
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;

        if ($total === 0) {
            return '0%';
        }

        return round(($hits / $total) * 100, 2) . '%';
    }
}