<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ApplicationMonitoringService extends BaseService
{
    protected array $metrics = [];
    protected string $metricsPrefix = 'app_metrics:';

    /**
     * Record application performance metrics
     */
    public function recordMetrics(string $type, array $data): void
    {
        $timestamp = now()->timestamp;
        $key = $this->metricsPrefix . $type . ':' . date('Y-m-d-H', $timestamp);
        
        $existingMetrics = Cache::get($key, []);
        $existingMetrics[] = array_merge($data, ['timestamp' => $timestamp]);
        
        Cache::put($key, $existingMetrics, now()->addHours(48));
    }

    /**
     * Record HTTP request metrics
     */
    public function recordHttpRequest(Request $request, float $responseTime, int $statusCode, int $memoryUsage): void
    {
        $this->recordMetrics('http_requests', [
            'method' => $request->method(),
            'uri' => $request->getPathInfo(),
            'status_code' => $statusCode,
            'response_time' => $responseTime,
            'memory_usage' => $memoryUsage,
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
        ]);
    }

    /**
     * Record database query metrics
     */
    public function recordDatabaseQuery(string $sql, float $executionTime, string $connection = 'default'): void
    {
        $this->recordMetrics('database_queries', [
            'sql' => $this->normalizeSql($sql),
            'execution_time' => $executionTime,
            'connection' => $connection,
        ]);
    }

    /**
     * Record cache operation metrics
     */
    public function recordCacheOperation(string $operation, string $key, bool $hit = null, float $executionTime = null): void
    {
        $this->recordMetrics('cache_operations', [
            'operation' => $operation,
            'key_pattern' => $this->normalizeKey($key),
            'hit' => $hit,
            'execution_time' => $executionTime,
        ]);
    }

    /**
     * Record queue job metrics
     */
    public function recordQueueJob(string $jobClass, string $status, float $executionTime = null, string $error = null): void
    {
        $this->recordMetrics('queue_jobs', [
            'job_class' => $jobClass,
            'status' => $status, // started, completed, failed
            'execution_time' => $executionTime,
            'error' => $error,
        ]);
    }

    /**
     * Get system health status
     */
    public function getSystemHealth(): array
    {
        $health = [
            'status' => 'healthy',
            'checks' => [],
            'timestamp' => now()->toISOString(),
        ];

        // Database health check
        $health['checks']['database'] = $this->checkDatabaseHealth();
        
        // Cache health check
        $health['checks']['cache'] = $this->checkCacheHealth();
        
        // Queue health check
        $health['checks']['queue'] = $this->checkQueueHealth();
        
        // Storage health check
        $health['checks']['storage'] = $this->checkStorageHealth();
        
        // Memory health check
        $health['checks']['memory'] = $this->checkMemoryHealth();

        // Determine overall status
        $failedChecks = collect($health['checks'])->filter(fn($check) => $check['status'] !== 'healthy');
        if ($failedChecks->isNotEmpty()) {
            $health['status'] = $failedChecks->contains(fn($check) => $check['status'] === 'critical') ? 'critical' : 'warning';
        }

        return $health;
    }

    /**
     * Check database health
     */
    protected function checkDatabaseHealth(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $responseTime = (microtime(true) - $start) * 1000;

            $status = 'healthy';
            if ($responseTime > 1000) {
                $status = 'warning';
            } elseif ($responseTime > 5000) {
                $status = 'critical';
            }

            return [
                'status' => $status,
                'response_time' => round($responseTime, 2),
                'message' => $status === 'healthy' ? 'Database is responsive' : 'Database response time is slow',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'response_time' => null,
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache health
     */
    protected function checkCacheHealth(): array
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';
            
            $start = microtime(true);
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            $responseTime = (microtime(true) - $start) * 1000;

            if ($retrieved !== $testValue) {
                return [
                    'status' => 'critical',
                    'response_time' => round($responseTime, 2),
                    'message' => 'Cache read/write test failed',
                ];
            }

            $status = 'healthy';
            if ($responseTime > 100) {
                $status = 'warning';
            } elseif ($responseTime > 500) {
                $status = 'critical';
            }

            return [
                'status' => $status,
                'response_time' => round($responseTime, 2),
                'message' => $status === 'healthy' ? 'Cache is responsive' : 'Cache response time is slow',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'response_time' => null,
                'message' => 'Cache connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue health
     */
    protected function checkQueueHealth(): array
    {
        try {
            $queueSize = Queue::size();
            $failedJobs = DB::table('failed_jobs')->count();

            $status = 'healthy';
            $message = 'Queue is operating normally';

            if ($queueSize > 1000) {
                $status = 'warning';
                $message = 'Queue has high number of pending jobs';
            } elseif ($queueSize > 5000) {
                $status = 'critical';
                $message = 'Queue is severely backlogged';
            }

            if ($failedJobs > 10) {
                $status = $status === 'critical' ? 'critical' : 'warning';
                $message .= ". High number of failed jobs: {$failedJobs}";
            }

            return [
                'status' => $status,
                'queue_size' => $queueSize,
                'failed_jobs' => $failedJobs,
                'message' => $message,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'queue_size' => null,
                'failed_jobs' => null,
                'message' => 'Queue health check failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage health
     */
    protected function checkStorageHealth(): array
    {
        try {
            $storagePath = storage_path();
            $freeBytes = disk_free_space($storagePath);
            $totalBytes = disk_total_space($storagePath);
            $usedPercent = (($totalBytes - $freeBytes) / $totalBytes) * 100;

            $status = 'healthy';
            $message = 'Storage has adequate free space';

            if ($usedPercent > 80) {
                $status = 'warning';
                $message = 'Storage usage is high';
            } elseif ($usedPercent > 95) {
                $status = 'critical';
                $message = 'Storage is nearly full';
            }

            return [
                'status' => $status,
                'used_percent' => round($usedPercent, 2),
                'free_space' => $this->formatBytes($freeBytes),
                'total_space' => $this->formatBytes($totalBytes),
                'message' => $message,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'used_percent' => null,
                'message' => 'Storage health check failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check memory health
     */
    protected function checkMemoryHealth(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $usedPercent = ($memoryUsage / $memoryLimit) * 100;

        $status = 'healthy';
        $message = 'Memory usage is normal';

        if ($usedPercent > 80) {
            $status = 'warning';
            $message = 'Memory usage is high';
        } elseif ($usedPercent > 95) {
            $status = 'critical';
            $message = 'Memory usage is critical';
        }

        return [
            'status' => $status,
            'used_percent' => round($usedPercent, 2),
            'used_memory' => $this->formatBytes($memoryUsage),
            'memory_limit' => $this->formatBytes($memoryLimit),
            'message' => $message,
        ];
    }

    /**
     * Get performance metrics for a specific time period
     */
    public function getPerformanceMetrics(string $type, int $hours = 24): array
    {
        $metrics = [];
        
        for ($i = 0; $i < $hours; $i++) {
            $key = $this->metricsPrefix . $type . ':' . now()->subHours($i)->format('Y-m-d-H');
            $hourlyMetrics = Cache::get($key, []);
            $metrics = array_merge($metrics, $hourlyMetrics);
        }

        return $metrics;
    }

    /**
     * Get aggregated performance statistics
     */
    public function getPerformanceStats(int $hours = 24): array
    {
        $httpMetrics = $this->getPerformanceMetrics('http_requests', $hours);
        $dbMetrics = $this->getPerformanceMetrics('database_queries', $hours);
        $cacheMetrics = $this->getPerformanceMetrics('cache_operations', $hours);
        $queueMetrics = $this->getPerformanceMetrics('queue_jobs', $hours);

        return [
            'http_requests' => $this->aggregateHttpMetrics($httpMetrics),
            'database_queries' => $this->aggregateDatabaseMetrics($dbMetrics),
            'cache_operations' => $this->aggregateCacheMetrics($cacheMetrics),
            'queue_jobs' => $this->aggregateQueueMetrics($queueMetrics),
            'period' => [
                'hours' => $hours,
                'start' => now()->subHours($hours)->toISOString(),
                'end' => now()->toISOString(),
            ],
        ];
    }

    /**
     * Aggregate HTTP request metrics
     */
    protected function aggregateHttpMetrics(array $metrics): array
    {
        if (empty($metrics)) {
            return ['total_requests' => 0];
        }

        $totalRequests = count($metrics);
        $responseTimes = array_column($metrics, 'response_time');
        $statusCodes = array_column($metrics, 'status_code');
        $memoryUsages = array_column($metrics, 'memory_usage');

        return [
            'total_requests' => $totalRequests,
            'avg_response_time' => round(array_sum($responseTimes) / count($responseTimes), 2),
            'max_response_time' => max($responseTimes),
            'min_response_time' => min($responseTimes),
            'status_codes' => array_count_values($statusCodes),
            'avg_memory_usage' => round(array_sum($memoryUsages) / count($memoryUsages)),
            'max_memory_usage' => max($memoryUsages),
        ];
    }

    /**
     * Aggregate database query metrics
     */
    protected function aggregateDatabaseMetrics(array $metrics): array
    {
        if (empty($metrics)) {
            return ['total_queries' => 0];
        }

        $totalQueries = count($metrics);
        $executionTimes = array_column($metrics, 'execution_time');

        return [
            'total_queries' => $totalQueries,
            'avg_execution_time' => round(array_sum($executionTimes) / count($executionTimes), 2),
            'max_execution_time' => max($executionTimes),
            'min_execution_time' => min($executionTimes),
            'slow_queries' => count(array_filter($executionTimes, fn($time) => $time > 1000)),
        ];
    }

    /**
     * Aggregate cache operation metrics
     */
    protected function aggregateCacheMetrics(array $metrics): array
    {
        if (empty($metrics)) {
            return ['total_operations' => 0];
        }

        $totalOperations = count($metrics);
        $hits = array_filter($metrics, fn($m) => $m['hit'] === true);
        $misses = array_filter($metrics, fn($m) => $m['hit'] === false);

        return [
            'total_operations' => $totalOperations,
            'hit_rate' => round((count($hits) / $totalOperations) * 100, 2),
            'total_hits' => count($hits),
            'total_misses' => count($misses),
        ];
    }

    /**
     * Aggregate queue job metrics
     */
    protected function aggregateQueueMetrics(array $metrics): array
    {
        if (empty($metrics)) {
            return ['total_jobs' => 0];
        }

        $totalJobs = count($metrics);
        $completed = array_filter($metrics, fn($m) => $m['status'] === 'completed');
        $failed = array_filter($metrics, fn($m) => $m['status'] === 'failed');

        return [
            'total_jobs' => $totalJobs,
            'completed_jobs' => count($completed),
            'failed_jobs' => count($failed),
            'success_rate' => round((count($completed) / $totalJobs) * 100, 2),
        ];
    }

    /**
     * Clear metrics cache
     */
    public function clearMetrics(string $type = null, int $hours = null): void
    {
        if ($type && $hours) {
            // Clear specific type and time range
            for ($i = 0; $i < $hours; $i++) {
                $key = $this->metricsPrefix . $type . ':' . now()->subHours($i)->format('Y-m-d-H');
                Cache::forget($key);
            }
        } elseif ($type) {
            // Clear all metrics for a specific type
            $pattern = $this->metricsPrefix . $type . ':*';
            $this->clearCachePattern($pattern);
        } else {
            // Clear all metrics
            $pattern = $this->metricsPrefix . '*';
            $this->clearCachePattern($pattern);
        }
    }

    /**
     * Clear cache keys matching a pattern
     */
    protected function clearCachePattern(string $pattern): void
    {
        try {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $keys = Cache::getRedis()->keys($pattern);
                if (!empty($keys)) {
                    Cache::getRedis()->del($keys);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to clear cache pattern: ' . $pattern, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Normalize SQL query for metrics
     */
    protected function normalizeSql(string $sql): string
    {
        // Remove specific values and normalize for pattern matching
        $normalized = preg_replace('/\b\d+\b/', '?', $sql);
        $normalized = preg_replace('/\'[^\']*\'/', '?', $normalized);
        $normalized = preg_replace('/\"[^\"]*\"/', '?', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        return trim(substr($normalized, 0, 200));
    }

    /**
     * Normalize cache key for metrics
     */
    protected function normalizeKey(string $key): string
    {
        // Remove specific IDs and values for pattern matching
        $normalized = preg_replace('/\d+/', '*', $key);
        $normalized = preg_replace('/[a-f0-9]{32,}/', '*', $normalized); // Remove hashes
        
        return $normalized;
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Parse memory limit string to bytes
     */
    protected function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
}