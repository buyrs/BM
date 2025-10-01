<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Auth;

class RateLimitService
{
    /**
     * Rate limit configurations for different endpoints.
     */
    protected array $rateLimits = [
        'api.auth.login' => ['attempts' => 5, 'decay' => 15], // 5 attempts per 15 minutes
        'api.auth.register' => ['attempts' => 3, 'decay' => 60], // 3 attempts per hour
        'api.password.reset' => ['attempts' => 3, 'decay' => 60], // 3 attempts per hour
        'api.properties.index' => ['attempts' => 100, 'decay' => 1], // 100 requests per minute
        'api.properties.store' => ['attempts' => 10, 'decay' => 1], // 10 requests per minute
        'api.missions.index' => ['attempts' => 100, 'decay' => 1], // 100 requests per minute
        'api.missions.store' => ['attempts' => 20, 'decay' => 1], // 20 requests per minute
        'api.checklists.index' => ['attempts' => 100, 'decay' => 1], // 100 requests per minute
        'api.checklists.update' => ['attempts' => 30, 'decay' => 1], // 30 requests per minute
        'api.files.upload' => ['attempts' => 20, 'decay' => 1], // 20 uploads per minute
        'default' => ['attempts' => 60, 'decay' => 1], // Default: 60 requests per minute
    ];

    /**
     * Get rate limit configuration for a specific route.
     */
    public function getRateLimitConfig(string $routeName): array
    {
        return $this->rateLimits[$routeName] ?? $this->rateLimits['default'];
    }

    /**
     * Check if a request should be rate limited.
     */
    public function shouldRateLimit(string $key, int $maxAttempts, int $decayMinutes): bool
    {
        $attempts = $this->getAttempts($key);
        return $attempts >= $maxAttempts;
    }

    /**
     * Get current attempts for a key.
     */
    public function getAttempts(string $key): int
    {
        return (int) Redis::get($key . ':attempts') ?: 0;
    }

    /**
     * Get remaining attempts for a key.
     */
    public function getRemainingAttempts(string $key, int $maxAttempts): int
    {
        $attempts = $this->getAttempts($key);
        return max(0, $maxAttempts - $attempts);
    }

    /**
     * Get retry after seconds for a key.
     */
    public function getRetryAfter(string $key, int $decaySeconds): int
    {
        $timestamp = Redis::get($key . ':timestamp');
        
        if (!$timestamp) {
            return $decaySeconds;
        }
        
        $elapsed = now()->timestamp - (int) $timestamp;
        return max(0, $decaySeconds - $elapsed);
    }

    /**
     * Increment attempts for a key.
     */
    public function incrementAttempts(string $key, int $decaySeconds): void
    {
        $attemptsKey = $key . ':attempts';
        $timestampKey = $key . ':timestamp';
        
        Redis::pipeline(function ($pipe) use ($attemptsKey, $timestampKey, $decaySeconds) {
            $pipe->incr($attemptsKey);
            $pipe->expire($attemptsKey, $decaySeconds);
            $pipe->set($timestampKey, now()->timestamp);
            $pipe->expire($timestampKey, $decaySeconds);
        });
    }

    /**
     * Clear rate limit for a key.
     */
    public function clearRateLimit(string $key): void
    {
        Redis::del($key . ':attempts', $key . ':timestamp');
    }

    /**
     * Generate rate limit key for a request.
     */
    public function generateKey(string $identifier, string $routeName): string
    {
        return "rate_limit:{$identifier}:{$routeName}";
    }

    /**
     * Generate user-based rate limit key.
     */
    public function generateUserKey(string $routeName): string
    {
        $user = Auth::user();
        $identifier = $user ? "user:{$user->id}" : "ip:" . request()->ip();
        
        return $this->generateKey($identifier, $routeName);
    }

    /**
     * Generate IP-based rate limit key.
     */
    public function generateIpKey(string $routeName): string
    {
        return $this->generateKey("ip:" . request()->ip(), $routeName);
    }

    /**
     * Get rate limit statistics for monitoring.
     */
    public function getRateLimitStats(): array
    {
        $keys = Redis::keys('rate_limit:*:attempts');
        $stats = [];
        
        foreach ($keys as $key) {
            $attempts = Redis::get($key);
            $ttl = Redis::ttl($key);
            
            // Extract identifier and route from key
            $parts = explode(':', str_replace(':attempts', '', $key));
            if (count($parts) >= 4) {
                $identifier = $parts[1] . ':' . $parts[2];
                $route = implode(':', array_slice($parts, 3));
                
                $stats[] = [
                    'identifier' => $identifier,
                    'route' => $route,
                    'attempts' => (int) $attempts,
                    'ttl' => (int) $ttl,
                    'expires_at' => now()->addSeconds($ttl)->toISOString(),
                ];
            }
        }
        
        return $stats;
    }

    /**
     * Get rate limit configuration as array.
     */
    public function getAllRateLimits(): array
    {
        return $this->rateLimits;
    }

    /**
     * Update rate limit configuration.
     */
    public function updateRateLimit(string $routeName, int $attempts, int $decayMinutes): void
    {
        $this->rateLimits[$routeName] = [
            'attempts' => $attempts,
            'decay' => $decayMinutes
        ];
    }
}