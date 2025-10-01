<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ApiAnalyticsService extends BaseService
{
    /**
     * Track API request
     */
    public function trackRequest(Request $request, $response = null): void
    {
        try {
            $endpoint = $request->path();
            $method = $request->method();
            $userId = $request->user()?->id;
            $statusCode = $response ? $response->getStatusCode() : null;
            $responseTime = $this->getResponseTime($request);

            // Store in cache for real-time analytics
            $this->updateRealTimeStats($endpoint, $method, $statusCode, $userId);

            // Store detailed analytics (could be moved to a queue for better performance)
            $this->storeDetailedAnalytics([
                'endpoint' => $endpoint,
                'method' => $method,
                'user_id' => $userId,
                'status_code' => $statusCode,
                'response_time' => $responseTime,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now(),
            ]);

        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Log::error('API Analytics tracking failed: ' . $e->getMessage());
        }
    }

    /**
     * Get API usage statistics
     */
    public function getUsageStatistics(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? now()->subDays(30);
        $dateTo = $filters['date_to'] ?? now();

        return [
            'total_requests' => $this->getTotalRequests($dateFrom, $dateTo),
            'requests_by_endpoint' => $this->getRequestsByEndpoint($dateFrom, $dateTo),
            'requests_by_method' => $this->getRequestsByMethod($dateFrom, $dateTo),
            'requests_by_status' => $this->getRequestsByStatus($dateFrom, $dateTo),
            'requests_by_user' => $this->getRequestsByUser($dateFrom, $dateTo),
            'average_response_time' => $this->getAverageResponseTime($dateFrom, $dateTo),
            'error_rate' => $this->getErrorRate($dateFrom, $dateTo),
            'top_users' => $this->getTopUsers($dateFrom, $dateTo),
            'hourly_distribution' => $this->getHourlyDistribution($dateFrom, $dateTo),
        ];
    }

    /**
     * Get real-time API metrics
     */
    public function getRealTimeMetrics(): array
    {
        return [
            'requests_last_hour' => Cache::get('api_requests_last_hour', 0),
            'requests_last_minute' => Cache::get('api_requests_last_minute', 0),
            'active_users' => Cache::get('api_active_users', 0),
            'error_rate_last_hour' => Cache::get('api_error_rate_last_hour', 0),
            'average_response_time' => Cache::get('api_avg_response_time', 0),
            'top_endpoints' => Cache::get('api_top_endpoints', []),
        ];
    }

    /**
     * Update real-time statistics in cache
     */
    private function updateRealTimeStats(string $endpoint, string $method, ?int $statusCode, ?int $userId): void
    {
        $now = now();
        $hourKey = $now->format('Y-m-d-H');
        $minuteKey = $now->format('Y-m-d-H-i');

        // Increment request counters
        Cache::increment('api_requests_last_hour');
        Cache::increment('api_requests_last_minute');
        Cache::increment("api_requests_hour_{$hourKey}");
        Cache::increment("api_requests_minute_{$minuteKey}");

        // Track endpoint usage
        $endpointKey = "api_endpoint_{$endpoint}_{$method}";
        Cache::increment($endpointKey);

        // Track user activity
        if ($userId) {
            Cache::put("api_user_active_{$userId}", $now, now()->addMinutes(5));
            $activeUsers = Cache::get('api_active_users', 0);
            Cache::put('api_active_users', $activeUsers + 1, now()->addMinutes(1));
        }

        // Track errors
        if ($statusCode && $statusCode >= 400) {
            Cache::increment('api_errors_last_hour');
        }

        // Set expiration for counters
        Cache::put('api_requests_last_hour', Cache::get('api_requests_last_hour', 0), now()->addHour());
        Cache::put('api_requests_last_minute', Cache::get('api_requests_last_minute', 0), now()->addMinute());
    }

    /**
     * Store detailed analytics data
     */
    private function storeDetailedAnalytics(array $data): void
    {
        // This could be stored in a dedicated analytics table or external service
        // For now, we'll use the audit log system
        DB::table('audit_logs')->insert([
            'user_id' => $data['user_id'],
            'action' => 'api_request',
            'resource_type' => 'api',
            'resource_id' => null,
            'changes' => json_encode([
                'endpoint' => $data['endpoint'],
                'method' => $data['method'],
                'status_code' => $data['status_code'],
                'response_time' => $data['response_time'],
            ]),
            'ip_address' => $data['ip_address'],
            'user_agent' => $data['user_agent'],
            'created_at' => $data['timestamp'],
        ]);
    }

    /**
     * Get response time from request
     */
    private function getResponseTime(Request $request): ?float
    {
        $startTime = $request->server('REQUEST_TIME_FLOAT');
        return $startTime ? (microtime(true) - $startTime) * 1000 : null; // Convert to milliseconds
    }

    /**
     * Get total requests for date range
     */
    private function getTotalRequests($dateFrom, $dateTo): int
    {
        return DB::table('audit_logs')
            ->where('action', 'api_request')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->count();
    }

    /**
     * Get requests grouped by endpoint
     */
    private function getRequestsByEndpoint($dateFrom, $dateTo): array
    {
        return DB::table('audit_logs')
            ->where('action', 'api_request')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(changes, '$.endpoint')) as endpoint, COUNT(*) as count")
            ->groupBy('endpoint')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'endpoint')
            ->toArray();
    }

    /**
     * Get requests grouped by HTTP method
     */
    private function getRequestsByMethod($dateFrom, $dateTo): array
    {
        return DB::table('audit_logs')
            ->where('action', 'api_request')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(changes, '$.method')) as method, COUNT(*) as count")
            ->groupBy('method')
            ->pluck('count', 'method')
            ->toArray();
    }

    /**
     * Get requests grouped by status code
     */
    private function getRequestsByStatus($dateFrom, $dateTo): array
    {
        return DB::table('audit_logs')
            ->where('action', 'api_request')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(changes, '$.status_code')) as status_code, COUNT(*) as count")
            ->groupBy('status_code')
            ->pluck('count', 'status_code')
            ->toArray();
    }

    /**
     * Get requests grouped by user
     */
    private function getRequestsByUser($dateFrom, $dateTo): array
    {
        return DB::table('audit_logs')
            ->join('users', 'audit_logs.user_id', '=', 'users.id')
            ->where('audit_logs.action', 'api_request')
            ->whereBetween('audit_logs.created_at', [$dateFrom, $dateTo])
            ->selectRaw('users.name, COUNT(*) as count')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'name')
            ->toArray();
    }

    /**
     * Get average response time
     */
    private function getAverageResponseTime($dateFrom, $dateTo): float
    {
        $result = DB::table('audit_logs')
            ->where('action', 'api_request')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw("AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(changes, '$.response_time')) AS DECIMAL(10,2))) as avg_time")
            ->first();

        return $result ? round($result->avg_time, 2) : 0;
    }

    /**
     * Get error rate percentage
     */
    private function getErrorRate($dateFrom, $dateTo): float
    {
        $totalRequests = $this->getTotalRequests($dateFrom, $dateTo);
        
        if ($totalRequests === 0) {
            return 0;
        }

        $errorRequests = DB::table('audit_logs')
            ->where('action', 'api_request')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(changes, '$.status_code')) AS UNSIGNED) >= 400")
            ->count();

        return round(($errorRequests / $totalRequests) * 100, 2);
    }

    /**
     * Get top API users
     */
    private function getTopUsers($dateFrom, $dateTo): array
    {
        return DB::table('audit_logs')
            ->join('users', 'audit_logs.user_id', '=', 'users.id')
            ->where('audit_logs.action', 'api_request')
            ->whereBetween('audit_logs.created_at', [$dateFrom, $dateTo])
            ->select('users.name', 'users.email', 'users.role')
            ->selectRaw('COUNT(*) as request_count')
            ->groupBy('users.id', 'users.name', 'users.email', 'users.role')
            ->orderByDesc('request_count')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get hourly request distribution
     */
    private function getHourlyDistribution($dateFrom, $dateTo): array
    {
        return DB::table('audit_logs')
            ->where('action', 'api_request')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();
    }
}