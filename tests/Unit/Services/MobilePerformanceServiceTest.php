<?php

namespace Tests\Unit\Services;

use App\Services\MobilePerformanceService;
use Tests\TestCase;

class MobilePerformanceServiceTest extends TestCase
{
    private MobilePerformanceService $mobilePerformanceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mobilePerformanceService = new MobilePerformanceService();
    }

    public function test_get_progressive_loading_config()
    {
        $config = $this->mobilePerformanceService->getProgressiveLoadingConfig();

        $this->assertArrayHasKey('items_per_page', $config);
        $this->assertArrayHasKey('progressive_threshold', $config);
        $this->assertArrayHasKey('mobile_threshold', $config);
        $this->assertArrayHasKey('preload_count', $config);
        $this->assertArrayHasKey('infinite_scroll', $config);
        $this->assertArrayHasKey('debounce_time', $config);

        // Test default values
        $this->assertEquals(10, $config['items_per_page']);
    }

    public function test_get_progressive_loading_config_with_custom_values()
    {
        $config = $this->mobilePerformanceService->getProgressiveLoadingConfig(5, 'checklist');

        $this->assertEquals(5, $config['items_per_page']);
    }

    public function test_generate_mobile_optimized_css()
    {
        $css = $this->mobilePerformanceService->generateMobileOptimizedCSS();

        $this->assertIsString($css);
        $this->assertStringContainsString('touch-target', $css);
        $this->assertStringContainsString('scroll-behavior', $css);
        $this->assertStringContainsString('safe-area', $css);
    }

    public function test_generate_mobile_optimized_js()
    {
        $js = $this->mobilePerformanceService->generateMobileOptimizedJS();

        $this->assertIsString($js);
        $this->assertStringContainsString('debounce', $js);
        $this->assertStringContainsString('isInViewport', $js);
        $this->assertStringContainsString('touchAction', $js);
    }

    public function test_get_mobile_performance_metrics()
    {
        $metrics = $this->mobilePerformanceService->getMobilePerformanceMetrics();

        $this->assertArrayHasKey('first_contentful_paint', $metrics);
        $this->assertArrayHasKey('largest_contentful_paint', $metrics);
        $this->assertArrayHasKey('cumulative_layout_shift', $metrics);
        $this->assertArrayHasKey('first_input_delay', $metrics);
        $this->assertArrayHasKey('total_blocking_time', $metrics);
        $this->assertArrayHasKey('time_to_interactive', $metrics);
        $this->assertArrayHasKey('mobile_friendly', $metrics);
        $this->assertArrayHasKey('page_size_kb', $metrics);
        $this->assertArrayHasKey('optimized_images', $metrics);
        $this->assertArrayHasKey('lazy_loading_enabled', $metrics);
        $this->assertArrayHasKey('progressive_loading', $metrics);
    }
}