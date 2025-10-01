<?php

namespace Tests\Unit\Services;

use App\Services\PerformanceMonitoringService;
use Tests\TestCase;

class PerformanceMonitoringServiceTest extends TestCase
{
    private PerformanceMonitoringService $performanceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->performanceService = new PerformanceMonitoringService();
    }

    public function test_get_performance_metrics()
    {
        $metrics = $this->performanceService->getPerformanceMetrics();

        $this->assertArrayHasKey('cache', $metrics);
        $this->assertArrayHasKey('database', $metrics);
        $this->assertArrayHasKey('system', $metrics);
        $this->assertArrayHasKey('overall', $metrics);
    }

    public function test_cache_metrics()
    {
        $cacheMetrics = $this->performanceService->getPerformanceMetrics()['cache'];

        $this->assertArrayHasKey('hit_rate', $cacheMetrics);
        $this->assertArrayHasKey('miss_rate', $cacheMetrics);
        $this->assertArrayHasKey('total_keys', $cacheMetrics);
        $this->assertArrayHasKey('memory_usage', $cacheMetrics);
        $this->assertArrayHasKey('uptime', $cacheMetrics);
    }

    public function test_system_metrics()
    {
        $systemMetrics = $this->performanceService->getPerformanceMetrics()['system'];

        $this->assertArrayHasKey('memory_usage_mb', $systemMetrics);
        $this->assertArrayHasKey('memory_peak_mb', $systemMetrics);
        $this->assertArrayHasKey('load_average', $systemMetrics);
        $this->assertArrayHasKey('disk_usage_percent', $systemMetrics);
        $this->assertArrayHasKey('php_version', $systemMetrics);
        $this->assertArrayHasKey('laravel_version', $systemMetrics);
    }

    public function test_overall_performance_calculation()
    {
        $overall = $this->performanceService->getPerformanceMetrics()['overall'];

        $this->assertArrayHasKey('score', $overall);
        $this->assertArrayHasKey('status', $overall);
        $this->assertArrayHasKey('timestamp', $overall);

        // Score should be between 0 and 100
        $this->assertGreaterThanOrEqual(0, $overall['score']);
        $this->assertLessThanOrEqual(100, $overall['score']);

        // Status should be one of the expected values
        $this->assertContains($overall['status'], ['excellent', 'good', 'fair', 'poor']);
    }

    public function test_monitor_response_time()
    {
        $route = '/test-route';
        $responseTime = 0.5; // 500ms
        $statusCode = 200;

        // This should not throw any exceptions
        $this->performanceService->monitorResponseTime($route, $responseTime, $statusCode);

        $routePerformance = $this->performanceService->getRoutePerformance($route);

        $this->assertEquals($responseTime, $routePerformance['response_time']);
        $this->assertEquals($statusCode, $routePerformance['status_code']);
    }
}