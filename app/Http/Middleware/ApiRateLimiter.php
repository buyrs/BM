<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimiter
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $maxAttempts = '60', string $decayMinutes = '1'): Response
    {
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = (int) $maxAttempts;
        $decayMinutes = (int) $decayMinutes;
        $decaySeconds = $decayMinutes * 60;

        $attempts = $this->getAttempts($key);
        
        if ($attempts >= $maxAttempts) {
            $retryAfter = $this->getRetryAfter($key, $decaySeconds);
            
            return response()->json([
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Too many requests. Please try again later.',
                    'retry_after' => $retryAfter
                ]
            ], 429, [
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
                'Retry-After' => $retryAfter,
            ]);
        }

        $this->incrementAttempts($key, $decaySeconds);
        
        $response = $next($request);
        
        // Add rate limit headers to successful responses
        $remaining = max(0, $maxAttempts - $attempts - 1);
        $resetTime = now()->addSeconds($decaySeconds)->timestamp;
        
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', $remaining);
        $response->headers->set('X-RateLimit-Reset', $resetTime);

        return $response;
    }

    /**
     * Resolve the request signature for rate limiting.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $user = Auth::user();
        
        if ($user) {
            // User-based rate limiting
            return 'rate_limit:user:' . $user->id . ':' . $request->route()->getName();
        }
        
        // IP-based rate limiting for unauthenticated requests
        return 'rate_limit:ip:' . $request->ip() . ':' . $request->route()->getName();
    }

    /**
     * Get the current number of attempts for the given key.
     */
    protected function getAttempts(string $key): int
    {
        return (int) Redis::get($key . ':attempts') ?: 0;
    }

    /**
     * Increment the attempts for the given key.
     */
    protected function incrementAttempts(string $key, int $decaySeconds): void
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
     * Get the number of seconds until the rate limit resets.
     */
    protected function getRetryAfter(string $key, int $decaySeconds): int
    {
        $timestamp = Redis::get($key . ':timestamp');
        
        if (!$timestamp) {
            return $decaySeconds;
        }
        
        $elapsed = now()->timestamp - (int) $timestamp;
        
        return max(0, $decaySeconds - $elapsed);
    }

    /**
     * Clear the rate limit for the given key.
     */
    public function clearRateLimit(string $key): void
    {
        Redis::del($key . ':attempts', $key . ':timestamp');
    }
}