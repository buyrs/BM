<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Mission;
use App\Models\Checklist;

class MonitoringController extends Controller
{
    public function index()
    {
        $metrics = $this->getApplicationMetrics();
        $systemHealth = $this->getSystemHealth();
        $queueStatus = $this->getQueueStatus();
        $databaseMetrics = $this->getDatabaseMetrics();
        
        return view('admin.monitoring.index', compact(
            'metrics', 
            'systemHealth', 
            'queueStatus', 
            'databaseMetrics'
        ));
    }

    public function getApplicationMetrics()
    {
        return [
            'total_users' => User::count(),
            'total_missions' => Mission::count(),
            'total_checklists' => Checklist::count(),
            'active_users_last_hour' => User::where('last_activity', '>', now()->subHour()->timestamp)->count(),
            'pending_missions' => Mission::where('status', 'pending')->count(),
            'completed_checklists_today' => Checklist::where('status', 'completed')
                ->whereDate('updated_at', today())->count(),
        ];
    }

    public function getSystemHealth()
    {
        try {
            // Check database connection
            $dbHealthy = DB::connection()->getPdo() !== null;
            
            // Check Redis connection
            $redisHealthy = Redis::connection()->ping() === true;
            
            // Check disk space
            $diskSpace = disk_free_space('/') / disk_total_space('/');
            $diskHealthy = $diskSpace > 0.1; // At least 10% free space
            
            // Check memory usage
            $memoryUsage = memory_get_usage(true) / 1024 / 1024; // MB
            $memoryHealthy = $memoryUsage < 512; // Less than 512 MB
            
            return [
                'database' => $dbHealthy,
                'redis' => $redisHealthy,
                'disk_space' => $diskHealthy,
                'memory' => $memoryHealthy,
                'overall' => $dbHealthy && $redisHealthy && $diskHealthy && $memoryHealthy,
                'disk_percentage' => round((1 - $diskSpace) * 100, 2),
                'memory_usage_mb' => round($memoryUsage, 2),
            ];
        } catch (\Exception $e) {
            return [
                'database' => false,
                'redis' => false,
                'disk_space' => false,
                'memory' => false,
                'overall' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getQueueStatus()
    {
        try {
            // Get queue statistics from Redis
            $queueLength = Redis::llen('queues:default');
            $failedJobs = Redis::llen('queues:failed');
            
            return [
                'pending_jobs' => $queueLength,
                'failed_jobs' => $failedJobs,
                'queue_health' => $queueLength < 100, // Healthy if less than 100 pending jobs
            ];
        } catch (\Exception $e) {
            return [
                'pending_jobs' => 0,
                'failed_jobs' => 0,
                'queue_health' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getDatabaseMetrics()
    {
        try {
            // Get database size
            $dbSize = DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'size_mb' 
                                 FROM information_schema.tables 
                                 WHERE table_schema = DATABASE()")[0]->size_mb ?? 0;
                                 
            // Get slow queries count (if you have slow query logging)
            $slowQueries = DB::select("SHOW GLOBAL STATUS LIKE 'Slow_queries'")[0]->Value ?? 0;
            
            // Get connection count
            $connections = DB::select("SHOW STATUS LIKE 'Threads_connected'")[0]->Value ?? 0;
            
            return [
                'size_mb' => $dbSize,
                'slow_queries' => $slowQueries,
                'active_connections' => $connections,
                'connection_health' => $connections < 100, // Healthy if less than 100 connections
            ];
        } catch (\Exception $e) {
            return [
                'size_mb' => 0,
                'slow_queries' => 0,
                'active_connections' => 0,
                'connection_health' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getPerformanceMetrics()
    {
        // Get response time metrics from cache (would be populated by middleware)
        $avgResponseTime = cache()->get('avg_response_time', 0);
        $requestsPerMinute = cache()->get('requests_per_minute', 0);
        $errorRate = cache()->get('error_rate', 0);
        
        return [
            'avg_response_time_ms' => round($avgResponseTime * 1000, 2),
            'requests_per_minute' => $requestsPerMinute,
            'error_rate_percent' => round($errorRate * 100, 2),
            'performance_health' => $avgResponseTime < 2 && $errorRate < 0.05, // Healthy if <2s response and <5% errors
        ];
    }

    public function clearCache()
    {
        cache()->flush();
        return back()->with('success', 'Cache cleared successfully.');
    }

    public function restartQueueWorkers()
    {
        // This would typically be handled by a deployment script or supervisor
        // For demonstration, we'll just return a success message
        return back()->with('success', 'Queue workers restart initiated.');
    }
}