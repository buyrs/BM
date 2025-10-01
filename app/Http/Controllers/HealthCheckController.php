<?php

namespace App\Http\Controllers;

use App\Services\ApplicationMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthCheckController extends Controller
{
    protected ApplicationMonitoringService $monitoringService;

    public function __construct(ApplicationMonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
    }

    /**
     * Basic health check endpoint
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'service' => config('app.name'),
            'version' => config('app.version', '1.0.0'),
        ]);
    }

    /**
     * Detailed health check with system status
     */
    public function detailed(): JsonResponse
    {
        $health = $this->monitoringService->getSystemHealth();
        
        $statusCode = match($health['status']) {
            'healthy' => 200,
            'warning' => 200,
            'critical' => 503,
            default => 500,
        };

        return response()->json($health, $statusCode);
    }

    /**
     * Readiness check for load balancers
     */
    public function ready(): JsonResponse
    {
        $health = $this->monitoringService->getSystemHealth();
        
        // Service is ready if database and cache are healthy
        $isReady = $health['checks']['database']['status'] === 'healthy' && 
                   $health['checks']['cache']['status'] === 'healthy';

        $statusCode = $isReady ? 200 : 503;

        return response()->json([
            'ready' => $isReady,
            'timestamp' => now()->toISOString(),
            'checks' => [
                'database' => $health['checks']['database'],
                'cache' => $health['checks']['cache'],
            ],
        ], $statusCode);
    }

    /**
     * Liveness check for container orchestration
     */
    public function live(): JsonResponse
    {
        // Simple liveness check - if we can respond, we're alive
        return response()->json([
            'alive' => true,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get performance metrics
     */
    public function metrics(Request $request): JsonResponse
    {
        $hours = (int) $request->get('hours', 24);
        $hours = min(max($hours, 1), 168); // Limit between 1 hour and 1 week

        $stats = $this->monitoringService->getPerformanceStats($hours);

        return response()->json($stats);
    }

    /**
     * Get system information
     */
    public function info(): JsonResponse
    {
        return response()->json([
            'app' => [
                'name' => config('app.name'),
                'version' => config('app.version', '1.0.0'),
                'environment' => config('app.env'),
                'debug' => config('app.debug'),
                'timezone' => config('app.timezone'),
            ],
            'php' => [
                'version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
            ],
            'database' => [
                'driver' => config('database.default'),
                'connection' => config('database.connections.' . config('database.default') . '.driver'),
            ],
            'cache' => [
                'driver' => config('cache.default'),
            ],
            'queue' => [
                'driver' => config('queue.default'),
            ],
            'session' => [
                'driver' => config('session.driver'),
            ],
        ]);
    }
}
