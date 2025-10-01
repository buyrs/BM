<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PerformanceMonitoringService
{
    /**
     * Measure and log query performance
     *
     * @param string $query
     * @param float $executionTime
     * @param array $bindings
     * @return void
     */
    public function logQueryPerformance(string $query, float $executionTime, array $bindings = []): void
    {
        // Log slow queries (those taking more than 500ms)
        if ($executionTime > 0.5) {
            Log::warning('Slow Query Detected', [
                'query' => $query,
                'execution_time' => $executionTime,
                'bindings' => $bindings,
                'timestamp' => now()->toISOString()
            ]);
        }

        // Store performance metrics in cache for dashboard display
        $slowQueryCount = Cache::increment('slow_query_count');
        Cache::put("slow_query_latest", [
            'query' => Str::limit($query, 200),
            'execution_time' => $executionTime,
            'timestamp' => now()
        ], 3600); // 1 hour
    }

    /**
     * Get application performance metrics
     *
     * @return array
     */
    public function getPerformanceMetrics(): array
    {
        $cache = $this->getCacheMetrics();
        $database = $this->getDatabaseMetrics();
        $system = $this->getSystemMetrics();
        
        return [
            'cache' => $cache,
            'database' => $database,
            'system' => $system,
            'overall' => $this->calculateOverallPerformance($cache, $database, $system)
        ];
    }

    /**
     * Get cache performance metrics
     *
     * @return array
     */
    private function getCacheMetrics(): array
    {
        // For Redis, we could get more detailed metrics
        // But for now, we'll return basic cache stats
        return [
            'hit_rate' => Cache::get('cache_hit_rate', 0),
            'miss_rate' => Cache::get('cache_miss_rate', 0),
            'total_keys' => Cache::get('total_cache_keys', 0),
            'memory_usage' => Cache::get('cache_memory_usage', 0),
            'uptime' => Cache::get('cache_uptime', 0),
        ];
    }

    /**
     * Get database performance metrics
     *
     * @return array
     */
    private function getDatabaseMetrics(): array
    {
        try {
            // Get database connection info
            $connection = DB::connection();
            $pdo = $connection->getPdo();
            
            // Get basic database metrics
            $size = $this->getDatabaseSize();
            $connectionCount = $this->getActiveConnections();
            $slowQueryCount = Cache::get('slow_query_count', 0);
            
            return [
                'size_mb' => $size,
                'connections' => $connectionCount,
                'slow_queries' => $slowQueryCount,
                'uptime' => $connection->getPdo()->getAttribute(\PDO::ATTR_CLIENT_VERSION),
                'version' => $connection->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION) ?? 'unknown',
            ];
        } catch (\Exception $e) {
            Log::error('Database metrics error: ' . $e->getMessage());
            return [
                'size_mb' => 0,
                'connections' => 0,
                'slow_queries' => 0,
                'uptime' => 'error',
                'version' => 'error',
            ];
        }
    }

    /**
     * Get system performance metrics
     *
     * @return array
     */
    private function getSystemMetrics(): array
    {
        return [
            'memory_usage_mb' => round(memory_get_usage() / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage() / 1024 / 1024, 2),
            'load_average' => $this->getSystemLoad(),
            'disk_usage_percent' => $this->getDiskUsage(),
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
        ];
    }

    /**
     * Calculate overall performance score
     *
     * @param array $cache
     * @param array $database
     * @param array $system
     * @return array
     */
    private function calculateOverallPerformance(array $cache, array $database, array $system): array
    {
        $score = 100;
        
        // Adjust score based on metrics
        if ($cache['hit_rate'] < 0.7) {
            $score -= 20;
        }
        
        if ($database['slow_queries'] > 10) {
            $score -= 30;
        }
        
        if ($system['memory_peak_mb'] > 256) {
            $score -= 10;
        }
        
        $status = 'excellent';
        if ($score < 80) $status = 'good';
        if ($score < 60) $status = 'fair';
        if ($score < 40) $status = 'poor';
        
        return [
            'score' => max(0, min(100, $score)),
            'status' => $status,
            'timestamp' => now(),
        ];
    }

    /**
     * Get database size in MB
     *
     * @return float
     */
    private function getDatabaseSize(): float
    {
        try {
            $connection = DB::connection();
            
            if ($connection->getDriverName() === 'sqlite') {
                $databasePath = $connection->getDatabaseName();
                return round(filesize($databasePath) / 1024 / 1024, 2);
            } elseif ($connection->getDriverName() === 'mysql') {
                $result = DB::select("
                    SELECT SUM(data_length + index_length) / 1024 / 1024 AS size_mb
                    FROM information_schema.tables
                    WHERE table_schema = database()
                ");
                return isset($result[0]) ? round($result[0]->size_mb, 2) : 0;
            } elseif ($connection->getDriverName() === 'pgsql') {
                $databaseName = DB::getDatabaseName();
                $result = DB::select("
                    SELECT pg_size_pretty(pg_database_size(?)) as size,
                           pg_database_size(?) as size_bytes
                ", [$databaseName, $databaseName]);
                
                if (isset($result[0])) {
                    return round($result[0]->size_bytes / 1024 / 1024, 2);
                }
            }
        } catch (\Exception $e) {
            Log::error('Database size calculation error: ' . $e->getMessage());
        }
        
        return 0;
    }

    /**
     * Get active database connections count
     *
     * @return int
     */
    private function getActiveConnections(): int
    {
        try {
            $connection = DB::connection();
            
            if ($connection->getDriverName() === 'mysql') {
                $result = DB::select('SHOW STATUS LIKE "Threads_connected"');
                return isset($result[0]) ? (int)$result[0]->Value : 0;
            } elseif ($connection->getDriverName() === 'pgsql') {
                $result = DB::select('SELECT COUNT(*) as count FROM pg_stat_activity');
                return isset($result[0]) ? (int)$result[0]->count : 0;
            }
        } catch (\Exception $e) {
            Log::error('Active connections count error: ' . $e->getMessage());
        }
        
        return 0;
    }

    /**
     * Get system load average
     *
     * @return array
     */
    private function getSystemLoad(): array
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return [
                '1_min' => $load[0] ?? 0,
                '5_min' => $load[1] ?? 0,
                '15_min' => $load[2] ?? 0,
            ];
        }
        
        return [
            '1_min' => 0,
            '5_min' => 0,
            '15_min' => 0,
        ];
    }

    /**
     * Get disk usage percentage
     *
     * @return float
     */
    private function getDiskUsage(): float
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $total - $free;
        
        if ($total > 0) {
            return round(($used / $total) * 100, 2);
        }
        
        return 0;
    }

    /**
     * Monitor application response time
     *
     * @param string $route
     * @param float $responseTime
     * @param int $statusCode
     * @return void
     */
    public function monitorResponseTime(string $route, float $responseTime, int $statusCode): void
    {
        // Log slow responses (those taking more than 1 second)
        if ($responseTime > 1.0) {
            Log::warning('Slow Response Detected', [
                'route' => $route,
                'response_time' => $responseTime,
                'status_code' => $statusCode,
                'timestamp' => now()->toISOString()
            ]);
        }

        // Store response time metrics
        Cache::put("response_time_{$route}", [
            'response_time' => $responseTime,
            'status_code' => $statusCode,
            'timestamp' => now()
        ], 3600); // 1 hour
    }

    /**
     * Get route-specific performance metrics
     *
     * @param string $route
     * @return array
     */
    public function getRoutePerformance(string $route): array
    {
        return Cache::get("response_time_{$route}", [
            'response_time' => 0,
            'status_code' => 0,
            'timestamp' => null
        ]);
    }

    /**
     * Get all monitored routes performance
     *
     * @return array
     */
    public function getAllRoutePerformance(): array
    {
        // This would need to be implemented with Redis KEYS or a more sophisticated tracking system
        // For now, return empty - in a real implementation, we'd want to track all routes
        return [];
    }

    /**
     * Clear performance monitoring cache
     *
     * @return void
     */
    public function clearPerformanceCache(): void
    {
        // Clear specific performance-related cache keys
        Cache::forget('slow_query_count');
        Cache::forget('slow_query_latest');
        Cache::forget('cache_hit_rate');
        Cache::forget('cache_miss_rate');
        Cache::forget('total_cache_keys');
        Cache::forget('cache_memory_usage');
        Cache::forget('cache_uptime');
    }

    /**
     * Generate performance report
     *
     * @param string $period (daily, weekly, monthly)
     * @return array
     */
    public function generatePerformanceReport(string $period = 'daily'): array
    {
        $report = [
            'period' => $period,
            'generated_at' => now(),
            'metrics' => $this->getPerformanceMetrics()
        ];

        // Save report to cache with longer TTL for historical data
        $reportKey = "performance_report_{$period}_" . now()->format('Y-m-d');
        Cache::put($reportKey, $report, 86400 * 30); // Keep for 30 days

        return $report;
    }

    /**
     * Get historical performance report
     *
     * @param string $period
     * @param string|null $date
     * @return array|null
     */
    public function getHistoricalPerformanceReport(string $period, string $date = null): ?array
    {
        $date = $date ?? now()->format('Y-m-d');
        $reportKey = "performance_report_{$period}_{$date}";
        
        return Cache::get($reportKey);
    }
}