<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ApplicationMonitoringService;
use App\Services\DatabasePerformanceService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class PerformanceMonitoringController extends Controller
{
    protected ApplicationMonitoringService $monitoringService;
    protected DatabasePerformanceService $databaseService;

    public function __construct(
        ApplicationMonitoringService $monitoringService,
        DatabasePerformanceService $databaseService
    ) {
        $this->monitoringService = $monitoringService;
        $this->databaseService = $databaseService;
    }

    /**
     * Display the performance monitoring dashboard
     */
    public function index(): View
    {
        $systemHealth = $this->monitoringService->getSystemHealth();
        $performanceStats = $this->monitoringService->getPerformanceStats(24);
        $databaseMetrics = $this->databaseService->getPerformanceMetrics();

        return view('admin.performance.index', compact(
            'systemHealth',
            'performanceStats',
            'databaseMetrics'
        ));
    }

    /**
     * Get real-time system health data
     */
    public function health(): JsonResponse
    {
        $health = $this->monitoringService->getSystemHealth();
        return response()->json($health);
    }

    /**
     * Get performance metrics for a specific time period
     */
    public function metrics(Request $request): JsonResponse
    {
        $hours = (int) $request->get('hours', 24);
        $type = $request->get('type', 'all');

        if ($type === 'all') {
            $stats = $this->monitoringService->getPerformanceStats($hours);
        } else {
            $stats = $this->monitoringService->getMetrics($type, $hours);
        }

        return response()->json($stats);
    }

    /**
     * Get database performance analysis
     */
    public function databaseAnalysis(): JsonResponse
    {
        $analysis = $this->databaseService->analyzeQueryPerformance();
        return response()->json($analysis);
    }

    /**
     * Clear performance metrics
     */
    public function clearMetrics(Request $request): JsonResponse
    {
        $type = $request->get('type');
        $hours = $request->get('hours');

        $this->monitoringService->clearMetrics($type, $hours);

        return response()->json([
            'success' => true,
            'message' => 'Metrics cleared successfully',
        ]);
    }

    /**
     * Export performance report
     */
    public function exportReport(Request $request)
    {
        $hours = (int) $request->get('hours', 24);
        $format = $request->get('format', 'json');

        $data = [
            'system_health' => $this->monitoringService->getSystemHealth(),
            'performance_stats' => $this->monitoringService->getPerformanceStats($hours),
            'database_analysis' => $this->databaseService->analyzeQueryPerformance(),
            'generated_at' => now()->toISOString(),
            'period_hours' => $hours,
        ];

        switch ($format) {
            case 'json':
                return response()->json($data)
                    ->header('Content-Disposition', 'attachment; filename="performance-report-' . date('Y-m-d-H-i-s') . '.json"');

            case 'csv':
                return $this->exportCsv($data);

            default:
                return response()->json(['error' => 'Unsupported format'], 400);
        }
    }

    /**
     * Export data as CSV
     */
    protected function exportCsv(array $data)
    {
        $filename = 'performance-report-' . date('Y-m-d-H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Write system health
            fputcsv($file, ['System Health']);
            fputcsv($file, ['Component', 'Status', 'Message']);
            foreach ($data['system_health']['checks'] as $component => $check) {
                fputcsv($file, [$component, $check['status'], $check['message'] ?? '']);
            }
            
            fputcsv($file, []); // Empty row
            
            // Write HTTP metrics
            if (isset($data['performance_stats']['http_requests'])) {
                $http = $data['performance_stats']['http_requests'];
                fputcsv($file, ['HTTP Requests']);
                fputcsv($file, ['Metric', 'Value']);
                fputcsv($file, ['Total Requests', $http['total_requests'] ?? 0]);
                fputcsv($file, ['Avg Response Time (ms)', $http['avg_response_time'] ?? 0]);
                fputcsv($file, ['Max Response Time (ms)', $http['max_response_time'] ?? 0]);
                fputcsv($file, ['Avg Memory Usage (bytes)', $http['avg_memory_usage'] ?? 0]);
            }
            
            fputcsv($file, []); // Empty row
            
            // Write database metrics
            if (isset($data['performance_stats']['database_queries'])) {
                $db = $data['performance_stats']['database_queries'];
                fputcsv($file, ['Database Queries']);
                fputcsv($file, ['Metric', 'Value']);
                fputcsv($file, ['Total Queries', $db['total_queries'] ?? 0]);
                fputcsv($file, ['Avg Execution Time (ms)', $db['avg_execution_time'] ?? 0]);
                fputcsv($file, ['Slow Queries', $db['slow_queries'] ?? 0]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
