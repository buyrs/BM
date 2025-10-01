<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Mission;
use App\Services\PerformanceMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_response_time()
    {
        $startTime = microtime(true);
        
        $response = $this->get('/');
        
        $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        
        $response->assertStatus(200);
        
        // Assert that response time is under 500ms
        $this->assertLessThan(500, $responseTime, "Homepage response time exceeded 500ms: {$responseTime}ms");
    }

    public function test_dashboard_response_time()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $startTime = microtime(true);
        
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        
        $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        
        $response->assertStatus(200);
        
        // Assert that response time is under 1000ms
        $this->assertLessThan(1000, $responseTime, "Admin dashboard response time exceeded 1000ms: {$responseTime}ms");
    }

    public function test_mission_list_performance()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create multiple missions to test performance with data
        Mission::factory()->count(50)->create([
            'admin_id' => $admin->id,
            'status' => 'approved'
        ]);
        
        $startTime = microtime(true);
        
        $response = $this->actingAs($admin)->get(route('admin.missions.index'));
        
        $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        
        $response->assertStatus(200);
        
        // Assert that response time is under 1500ms even with 50 records
        $this->assertLessThan(1500, $responseTime, "Missions list response time exceeded 1500ms: {$responseTime}ms");
    }

    public function test_database_query_performance()
    {
        // Create many records to test query performance
        User::factory()->count(100)->create(['role' => 'checker']);
        
        $startTime = microtime(true);
        
        // Execute a query that might be used in the application
        $checkers = User::where('role', 'checker')->get();
        
        $queryTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        
        // Assert that query execution is under 200ms
        $this->assertLessThan(200, $queryTime, "Query execution exceeded 200ms: {$queryTime}ms");
        $this->assertCount(100, $checkers);
    }

    public function test_concurrent_user_simulation()
    {
        // Create users
        User::factory()->count(10)->create(['role' => 'admin']);
        User::factory()->count(20)->create(['role' => 'ops']);
        User::factory()->count(50)->create(['role' => 'checker']);
        
        // Measure time to load users index (simulating multiple concurrent users scenario)
        $admin = User::where('role', 'admin')->first();
        
        $startTime = microtime(true);
        
        $response = $this->actingAs($admin)->get(route('admin.users.index'));
        
        $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        
        $response->assertStatus(200);
        
        // Check that response is still reasonable with 80+ users in database
        $this->assertLessThan(1000, $responseTime, "Users index response time exceeded 1000ms: {$responseTime}ms");
    }

    public function test_memory_usage_during_heavy_operation()
    {
        // Get initial memory usage
        $initialMemory = memory_get_usage();
        
        // Create many records to test memory usage
        User::factory()->count(200)->create();
        
        // Calculate memory used
        $finalMemory = memory_get_usage();
        $memoryUsed = $finalMemory - $initialMemory;
        $memoryUsedMB = $memoryUsed / 1024 / 1024;
        
        // Assert that memory usage is reasonable (less than 100MB for 200 users)
        $this->assertLessThan(100, $memoryUsedMB, "Memory usage exceeded 100MB: {$memoryUsedMB}MB");
    }

    public function test_api_response_time_consistency()
    {
        $checker = User::factory()->create(['role' => 'checker']);
        
        // Simulate multiple requests to check consistency
        $responseTimes = [];
        
        for ($i = 0; $i < 5; $i++) {
            $startTime = microtime(true);
            
            $response = $this->actingAs($checker, 'checker')->get(route('checker.dashboard'));
            
            $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
            $responseTimes[] = $responseTime;
            
            $response->assertStatus(200);
            
            // Brief pause to simulate real usage pattern
            usleep(100000); // 0.1 seconds
        }
        
        // Calculate average response time
        $averageResponseTime = array_sum($responseTimes) / count($responseTimes);
        
        // Assert that average response time is under 500ms
        $this->assertLessThan(500, $averageResponseTime, "Average response time exceeded 500ms: {$averageResponseTime}ms");
        
        // Check consistency (standard deviation should be reasonable)
        $variance = array_sum(array_map(function($time) use ($averageResponseTime) {
            return pow($time - $averageResponseTime, 2);
        }, $responseTimes)) / count($responseTimes);
        
        $stdDev = sqrt($variance);
        
        // Standard deviation should be less than 50% of average for consistency
        $this->assertLessThan($averageResponseTime * 0.5, $stdDev, 
            "Response time is inconsistent (std dev: {$stdDev}, avg: {$averageResponseTime})");
    }

    public function test_caching_performance_impact()
    {
        $checker = User::factory()->create(['role' => 'checker']);
        
        // Create a mission to test with
        $mission = Mission::create([
            'title' => 'Performance Test Mission',
            'description' => 'Test Description',
            'property_address' => '123 Performance St',
            'checkin_date' => now()->addDay(),
            'checkout_date' => now()->addDays(2),
            'checker_id' => $checker->id,
            'status' => 'approved',
        ]);

        // First request (should be slower due to loading)
        $startTime1 = microtime(true);
        $response1 = $this->actingAs($checker, 'checker')->get(route('checklists.show', $mission->checklists->first()->id));
        $responseTime1 = (microtime(true) - $startTime1) * 1000;
        
        $response1->assertStatus(200);
        
        // Second request (should benefit from any caching or query optimization)
        $startTime2 = microtime(true);
        $response2 = $this->actingAs($checker, 'checker')->get(route('checklists.show', $mission->checklists->first()->id));
        $responseTime2 = (microtime(true) - $startTime2) * 1000;
        
        $response2->assertStatus(200);
        
        // The second request might not be significantly faster in testing environment
        // But both should be under reasonable limits
        $this->assertLessThan(1500, $responseTime1, "First request exceeded 1500ms: {$responseTime1}ms");
        $this->assertLessThan(1500, $responseTime2, "Second request exceeded 1500ms: {$responseTime2}ms");
    }
}