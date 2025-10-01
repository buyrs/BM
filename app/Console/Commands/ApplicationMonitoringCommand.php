<?php

namespace App\Console\Commands;

use App\Services\ApplicationMonitoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ApplicationMonitoringCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:monitor 
                            {action : The action to perform (health|metrics|clear|alert)}
                            {--hours=24 : Hours of data to analyze}
                            {--type= : Type of metrics to show (http_requests|database_queries|cache_operations|queue_jobs)}
                            {--format=table : Output format (table|json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor application performance and health';

    protected ApplicationMonitoringService $monitoringService;

    public function __construct(ApplicationMonitoringService $monitoringService)
    {
        parent::__construct();
        $this->monitoringService = $monitoringService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'health':
                return $this->showHealth();
            case 'metrics':
                return $this->showMetrics();
            case 'clear':
                return $this->clearMetrics();
            case 'alert':
                return $this->checkAlerts();
            default:
                $this->error("Unknown action: {$action}");
                $this->info('Available actions: health, metrics, clear, alert');
                return 1;
        }
    }

    /**
     * Show system health status
     */
    protected function showHealth(): int
    {
        $this->info('System Health Check');
        $this->info('==================');

        try {
            $health = $this->monitoringService->getSystemHealth();

            $this->info("Overall Status: " . strtoupper($health['status']));
            $this->info("Timestamp: {$health['timestamp']}");
            $this->newLine();

            $headers = ['Component', 'Status', 'Message', 'Response Time'];
            $rows = [];

            foreach ($health['checks'] as $component => $check) {
                $rows[] = [
                    ucfirst($component),
                    strtoupper($check['status']),
                    $check['message'] ?? '',
                    isset($check['response_time']) ? $check['response_time'] . 'ms' : 'N/A',
                ];
            }

            $this->table($headers, $rows);

            // Return appropriate exit code based on health status
            return match($health['status']) {
                'healthy' => 0,
                'warning' => 1,
                'critical' => 2,
                default => 3,
            };

        } catch (\Exception $e) {
            $this->error('Failed to check system health: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show performance metrics
     */
    protected function showMetrics(): int
    {
        $hours = (int) $this->option('hours');
        $type = $this->option('type');
        $format = $this->option('format');

        $this->info("Performance Metrics (Last {$hours} hours)");
        $this->info('=====================================');

        try {
            if ($type) {
                $metrics = $this->monitoringService->getPerformanceMetrics($type, $hours);
                $this->showSpecificMetrics($type, $metrics, $format);
            } else {
                $stats = $this->monitoringService->getPerformanceStats($hours);
                $this->showAllMetrics($stats, $format);
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('Failed to retrieve metrics: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show all performance metrics
     */
    protected function showAllMetrics(array $stats, string $format): void
    {
        if ($format === 'json') {
            $this->line(json_encode($stats, JSON_PRETTY_PRINT));
            return;
        }

        // HTTP Requests
        if (isset($stats['http_requests']) && $stats['http_requests']['total_requests'] > 0) {
            $this->info('HTTP Requests:');
            $http = $stats['http_requests'];
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Requests', number_format($http['total_requests'])],
                    ['Avg Response Time', $http['avg_response_time'] . 'ms'],
                    ['Max Response Time', $http['max_response_time'] . 'ms'],
                    ['Avg Memory Usage', $this->formatBytes($http['avg_memory_usage'])],
                ]
            );
            $this->newLine();
        }

        // Database Queries
        if (isset($stats['database_queries']) && $stats['database_queries']['total_queries'] > 0) {
            $this->info('Database Queries:');
            $db = $stats['database_queries'];
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Queries', number_format($db['total_queries'])],
                    ['Avg Execution Time', $db['avg_execution_time'] . 'ms'],
                    ['Max Execution Time', $db['max_execution_time'] . 'ms'],
                    ['Slow Queries', $db['slow_queries']],
                ]
            );
            $this->newLine();
        }

        // Cache Operations
        if (isset($stats['cache_operations']) && $stats['cache_operations']['total_operations'] > 0) {
            $this->info('Cache Operations:');
            $cache = $stats['cache_operations'];
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Operations', number_format($cache['total_operations'])],
                    ['Hit Rate', $cache['hit_rate'] . '%'],
                    ['Total Hits', number_format($cache['total_hits'])],
                    ['Total Misses', number_format($cache['total_misses'])],
                ]
            );
            $this->newLine();
        }

        // Queue Jobs
        if (isset($stats['queue_jobs']) && $stats['queue_jobs']['total_jobs'] > 0) {
            $this->info('Queue Jobs:');
            $queue = $stats['queue_jobs'];
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Jobs', number_format($queue['total_jobs'])],
                    ['Success Rate', $queue['success_rate'] . '%'],
                    ['Completed Jobs', number_format($queue['completed_jobs'])],
                    ['Failed Jobs', number_format($queue['failed_jobs'])],
                ]
            );
        }
    }

    /**
     * Show specific metrics type
     */
    protected function showSpecificMetrics(string $type, array $metrics, string $format): void
    {
        if ($format === 'json') {
            $this->line(json_encode($metrics, JSON_PRETTY_PRINT));
            return;
        }

        if (empty($metrics)) {
            $this->info("No {$type} metrics found for the specified period.");
            return;
        }

        $this->info("Found " . count($metrics) . " {$type} entries");
        
        // Show sample of recent metrics
        $recent = array_slice($metrics, -10);
        
        switch ($type) {
            case 'http_requests':
                $this->table(
                    ['Method', 'URI', 'Status', 'Response Time', 'Memory Usage'],
                    array_map(fn($m) => [
                        $m['method'],
                        substr($m['uri'], 0, 50),
                        $m['status_code'],
                        round($m['response_time'], 2) . 'ms',
                        $this->formatBytes($m['memory_usage']),
                    ], $recent)
                );
                break;
                
            case 'database_queries':
                $this->table(
                    ['SQL', 'Execution Time', 'Connection'],
                    array_map(fn($m) => [
                        substr($m['sql'], 0, 80) . '...',
                        round($m['execution_time'], 2) . 'ms',
                        $m['connection'],
                    ], $recent)
                );
                break;
        }
    }

    /**
     * Clear metrics data
     */
    protected function clearMetrics(): int
    {
        $type = $this->option('type');
        $hours = $this->option('hours');

        try {
            $this->monitoringService->clearMetrics($type, $hours);
            
            $message = 'Metrics cleared successfully';
            if ($type) {
                $message .= " for type: {$type}";
            }
            if ($hours) {
                $message .= " for last {$hours} hours";
            }
            
            $this->info($message);
            return 0;

        } catch (\Exception $e) {
            $this->error('Failed to clear metrics: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Check for alerts and critical conditions
     */
    protected function checkAlerts(): int
    {
        $this->info('Checking for alerts...');

        try {
            $health = $this->monitoringService->getSystemHealth();
            $stats = $this->monitoringService->getPerformanceStats(1); // Last hour

            $alerts = [];

            // Check system health
            foreach ($health['checks'] as $component => $check) {
                if ($check['status'] === 'critical') {
                    $alerts[] = [
                        'type' => 'critical',
                        'component' => $component,
                        'message' => $check['message'],
                    ];
                } elseif ($check['status'] === 'warning') {
                    $alerts[] = [
                        'type' => 'warning',
                        'component' => $component,
                        'message' => $check['message'],
                    ];
                }
            }

            // Check performance thresholds
            if (isset($stats['http_requests']['avg_response_time']) && $stats['http_requests']['avg_response_time'] > 2000) {
                $alerts[] = [
                    'type' => 'warning',
                    'component' => 'http_performance',
                    'message' => 'Average HTTP response time is high: ' . $stats['http_requests']['avg_response_time'] . 'ms',
                ];
            }

            if (isset($stats['database_queries']['slow_queries']) && $stats['database_queries']['slow_queries'] > 10) {
                $alerts[] = [
                    'type' => 'warning',
                    'component' => 'database_performance',
                    'message' => 'High number of slow queries: ' . $stats['database_queries']['slow_queries'],
                ];
            }

            if (empty($alerts)) {
                $this->info('No alerts found. System is operating normally.');
                return 0;
            }

            $this->warn('Found ' . count($alerts) . ' alert(s):');
            
            foreach ($alerts as $alert) {
                $prefix = $alert['type'] === 'critical' ? '🔴' : '🟡';
                $this->line("{$prefix} [{$alert['component']}] {$alert['message']}");
                
                // Log critical alerts
                if ($alert['type'] === 'critical') {
                    Log::critical('System alert', $alert);
                }
            }

            // Return exit code based on highest severity
            $hasCritical = collect($alerts)->contains('type', 'critical');
            return $hasCritical ? 2 : 1;

        } catch (\Exception $e) {
            $this->error('Failed to check alerts: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
