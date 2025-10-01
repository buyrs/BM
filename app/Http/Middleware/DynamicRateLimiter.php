<?php

namespace App\Http\Middleware;

use App\Services\RateLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DynamicRateLimiter
{
    public function __construct(
        private RateLimitService $rateLimitService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()->getName() ?? 'unknown';
        $config = $this->rateLimitService->getRateLimitConfig($routeName);
        
        $maxAttempts = $config['attempts'];
        $decayMinutes = $config['decay'];
        $decaySeconds = $decayMinutes * 60;
        
        $key = $this->rateLimitService->generateUserKey($routeName);
        
        if ($this->rateLimitService->shouldRateLimit($key, $maxAttempts, $decayMinutes)) {
            $retryAfter = $this->rateLimitService->getRetryAfter($key, $decaySeconds);
            
            return response()->json([
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Too many requests. Please try again later.',
                    'retry_after' => $retryAfter,
                    'limit' => $maxAttempts,
                    'window' => $decayMinutes . ' minutes'
                ]
            ], 429, [
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
                'X-RateLimit-Window' => $decayMinutes * 60,
                'Retry-After' => $retryAfter,
            ]);
        }
        
        $this->rateLimitService->incrementAttempts($key, $decaySeconds);
        
        $response = $next($request);
        
        // Add rate limit headers to response
        $remaining = $this->rateLimitService->getRemainingAttempts($key, $maxAttempts);
        $resetTime = now()->addSeconds($decaySeconds)->timestamp;
        
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', max(0, $remaining - 1));
        $response->headers->set('X-RateLimit-Reset', $resetTime);
        $response->headers->set('X-RateLimit-Window', $decaySeconds);
        
        return $response;
    }
}