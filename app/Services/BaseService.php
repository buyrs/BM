<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

abstract class BaseService
{
    protected array $config = [];
    protected ?CacheService $cache = null;

    public function __construct()
    {
        $this->config = $this->getDefaultConfig();
    }

    /**
     * Get cache service instance (lazy loading to avoid circular dependency)
     */
    protected function getCache(): CacheService
    {
        if ($this->cache === null) {
            $this->cache = app(CacheService::class);
        }
        return $this->cache;
    }

    /**
     * Get default configuration for the service
     */
    protected function getDefaultConfig(): array
    {
        return [];
    }

    /**
     * Log service activity
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        $context['service'] = static::class;
        Log::log($level, $message, $context);
    }

    /**
     * Log info message
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * Log error message
     */
    protected function logError(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * Log warning message
     */
    protected function logWarning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Handle service exceptions
     */
    protected function handleException(\Throwable $exception, string $operation = 'unknown'): void
    {
        $this->logError("Service operation failed: {$operation}", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }

    /**
     * Get cached data with service-specific prefix
     */
    protected function getCached(string $key, $default = null, int $ttl = 3600)
    {
        $serviceKey = $this->getServiceCacheKey($key);
        return $this->getCache()->get($serviceKey, $default);
    }

    /**
     * Store data in cache with service-specific prefix
     */
    protected function putCached(string $key, $value, int $ttl = 3600): bool
    {
        $serviceKey = $this->getServiceCacheKey($key);
        return $this->getCache()->put($serviceKey, $value, $ttl);
    }

    /**
     * Remove data from cache with service-specific prefix
     */
    protected function forgetCached(string $key): bool
    {
        $serviceKey = $this->getServiceCacheKey($key);
        return $this->getCache()->forget($serviceKey);
    }

    /**
     * Get service-specific cache key
     */
    protected function getServiceCacheKey(string $key): string
    {
        $serviceName = strtolower(class_basename(static::class));
        return "{$serviceName}:{$key}";
    }

    /**
     * Validate required configuration
     */
    protected function validateConfig(array $required): bool
    {
        foreach ($required as $key) {
            if (!isset($this->config[$key]) || empty($this->config[$key])) {
                $this->logError("Missing required configuration: {$key}");
                return false;
            }
        }
        return true;
    }

    /**
     * Get service health status
     */
    public function getHealthStatus(): array
    {
        return [
            'service' => static::class,
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'config_valid' => $this->isConfigValid(),
        ];
    }

    /**
     * Check if service configuration is valid
     */
    protected function isConfigValid(): bool
    {
        return true; // Override in child classes
    }

    /**
     * Get service metrics
     */
    public function getMetrics(): array
    {
        return [
            'service' => static::class,
            'timestamp' => now()->toISOString(),
        ];
    }
}