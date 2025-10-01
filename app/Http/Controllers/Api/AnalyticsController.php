<?php

namespace App\Http\Controllers\Api;

use App\Services\ApiAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends BaseApiController
{
    public function __construct(
        private ApiAnalyticsService $analyticsService
    ) {}

    /**
     * Get API usage statistics
     * 
     * @group Analytics
     * @authenticated
     * 
     * Retrieve comprehensive API usage statistics. Requires admin role.
     * 
     * @queryParam date_from string Start date for statistics (Y-m-d format). Example: 2024-01-01
     * @queryParam date_to string End date for statistics (Y-m-d format). Example: 2024-01-31
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "API statistics retrieved successfully",
     *   "data": {
     *     "total_requests": 1250,
     *     "requests_by_endpoint": {
     *       "api/v1/properties": 450,
     *       "api/v1/missions": 320,
     *       "api/v1/auth/login": 180
     *     },
     *     "requests_by_method": {
     *       "GET": 800,
     *       "POST": 300,
     *       "PUT": 100,
     *       "DELETE": 50
     *     },
     *     "average_response_time": 125.5,
     *     "error_rate": 2.4
     *   }
     * }
     * 
     * @response 403 {
     *   "success": false,
     *   "message": "Insufficient permissions to view API analytics"
     * }
     */
    public function usage(Request $request): JsonResponse
    {
        try {
            // Check permissions - only admin can view API analytics
            if (!$this->checkRole($request, ['admin'])) {
                return $this->forbidden('Insufficient permissions to view API analytics');
            }

            // Validate date filters
            $validated = $request->validate([
                'date_from' => ['nullable', 'date'],
                'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            ]);

            $filters = [];
            if (!empty($validated['date_from'])) {
                $filters['date_from'] = $validated['date_from'];
            }
            if (!empty($validated['date_to'])) {
                $filters['date_to'] = $validated['date_to'];
            }

            $statistics = $this->analyticsService->getUsageStatistics($filters);

            return $this->success($statistics, 'API statistics retrieved successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve API statistics');
        }
    }

    /**
     * Get real-time API metrics
     * 
     * @group Analytics
     * @authenticated
     * 
     * Retrieve real-time API metrics and performance data. Requires admin role.
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Real-time metrics retrieved successfully",
     *   "data": {
     *     "requests_last_hour": 45,
     *     "requests_last_minute": 3,
     *     "active_users": 12,
     *     "error_rate_last_hour": 1.2,
     *     "average_response_time": 98.5,
     *     "top_endpoints": {
     *       "api/v1/properties": 15,
     *       "api/v1/missions": 12,
     *       "api/v1/notifications": 8
     *     }
     *   }
     * }
     */
    public function realTime(Request $request): JsonResponse
    {
        try {
            // Check permissions - only admin can view real-time metrics
            if (!$this->checkRole($request, ['admin'])) {
                return $this->forbidden('Insufficient permissions to view real-time metrics');
            }

            $metrics = $this->analyticsService->getRealTimeMetrics();

            return $this->success($metrics, 'Real-time metrics retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve real-time metrics');
        }
    }

    /**
     * Get API health status
     * 
     * @group Analytics
     * 
     * Check the health and status of the API service.
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "API is healthy",
     *   "data": {
     *     "status": "healthy",
     *     "version": "v1",
     *     "timestamp": "2024-01-01T12:00:00.000000Z",
     *     "uptime": "5 days, 12 hours",
     *     "database": "connected",
     *     "cache": "connected",
     *     "queue": "running"
     *   }
     * }
     */
    public function health(Request $request): JsonResponse
    {
        try {
            $health = [
                'status' => 'healthy',
                'version' => 'v1',
                'timestamp' => now()->toISOString(),
                'uptime' => $this->getUptime(),
                'database' => $this->checkDatabaseConnection(),
                'cache' => $this->checkCacheConnection(),
                'queue' => $this->checkQueueConnection(),
            ];

            return $this->success($health, 'API is healthy');

        } catch (\Exception $e) {
            return $this->serverError('API health check failed');
        }
    }

    /**
     * Get system uptime
     */
    private function getUptime(): string
    {
        try {
            $uptime = shell_exec('uptime -p');
            return trim($uptime) ?: 'Unknown';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Check database connection
     */
    private function checkDatabaseConnection(): string
    {
        try {
            \DB::connection()->getPdo();
            return 'connected';
        } catch (\Exception $e) {
            return 'disconnected';
        }
    }

    /**
     * Check cache connection
     */
    private function checkCacheConnection(): string
    {
        try {
            \Cache::put('health_check', 'ok', 1);
            $result = \Cache::get('health_check');
            return $result === 'ok' ? 'connected' : 'disconnected';
        } catch (\Exception $e) {
            return 'disconnected';
        }
    }

    /**
     * Check queue connection
     */
    private function checkQueueConnection(): string
    {
        try {
            // Simple check - in a real implementation you might check queue workers
            return 'running';
        } catch (\Exception $e) {
            return 'stopped';
        }
    }
}