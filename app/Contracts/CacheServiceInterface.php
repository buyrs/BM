<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;

interface CacheServiceInterface
{
    /**
     * Get cached data or execute callback and cache result
     */
    public function remember(string $key, $ttl, callable $callback, array $tags = []);

    /**
     * Store data in cache
     */
    public function put(string $key, $value, $ttl = null, array $tags = []): bool;

    /**
     * Get data from cache
     */
    public function get(string $key, $default = null, array $tags = []);

    /**
     * Remove data from cache
     */
    public function forget(string $key, array $tags = []): bool;

    /**
     * Flush cache by tags
     */
    public function flushTags(array $tags): bool;

    /**
     * Generate cache key for model
     */
    public function modelKey(Model $model, string $suffix = ''): string;

    /**
     * Cache model data with automatic invalidation
     */
    public function cacheModel(Model $model, $ttl = null): bool;

    /**
     * Invalidate model cache
     */
    public function invalidateModel(Model $model): bool;

    /**
     * Warm up frequently accessed cache data
     */
    public function warmCache(): void;

    /**
     * Get cache statistics
     */
    public function getStats(): array;
}