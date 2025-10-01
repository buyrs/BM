<?php

namespace App\Http\Middleware;

use App\Services\ApplicationMonitoringService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplicationMonitoringMiddleware
{
    protected ApplicationMonitoringService $monitoringService;

    public function __construct(ApplicationMonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $response = $next($request);

        // Calculate metrics
        $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        $memoryUsage = memory_get_usage(true) - $startMemory;

        // Skip monitoring for certain routes to avoid noise
        if (!$this->shouldMonitor($request)) {
            return $response;
        }

        // Record metrics asynchronously to avoid impacting response time
        try {
            $this->monitoringService->recordHttpRequest(
                $request,
                $responseTime,
                $response->getStatusCode(),
                $memoryUsage
            );
        } catch (\Exception $e) {
            // Silently fail to avoid impacting the request
            \Log::debug('Failed to record HTTP metrics', ['error' => $e->getMessage()]);
        }

        return $response;
    }

    /**
     * Determine if the request should be monitored
     */
    protected function shouldMonitor(Request $request): bool
    {
        $uri = $request->getPathInfo();
        
        // Skip monitoring for these routes
        $skipRoutes = [
            '/health',
            '/ping',
            '/metrics',
            '/telescope',
            '/_debugbar',
            '/favicon.ico',
        ];

        foreach ($skipRoutes as $skipRoute) {
            if (str_starts_with($uri, $skipRoute)) {
                return false;
            }
        }

        // Skip static assets
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$/', $uri)) {
            return false;
        }

        return true;
    }
}
