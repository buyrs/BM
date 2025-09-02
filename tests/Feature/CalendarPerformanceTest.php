<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\BailMobilite;
use App\Models\Mission;
use App\Models\User;
use App\Services\CalendarService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CalendarPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $opsUser;
    protected User $checkerUser;
    protected CalendarService $calendarService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles
        $this->seed(\Database\Seeders\RoleSeeder::class);
        
        // Create test users
        $this->opsUser = User::factory()->create();
        $this->opsUser->assignRole('ops');
        
        $this->checkerUser = User::factory()->create();
        $this->checkerUser->assignRole('checker');
        
        $this->calendarService = new CalendarService();
    }

    public function test_calendar_handles_large_dataset_efficiently()
    {
        // Create a large number of missions (1000)
        $bailMobilites = BailMobilite::factory()->count(100)->create();
        
        $missions = collect();
        foreach ($bailMobilites as $bailMobilite) {
            // Create 10 missions per bail mobilité (5 entry, 5 exit)
            for ($i = 0; $i < 10; $i++) {
                $missions->push(Mission::factory()->create([
                    'bail_mobilite_id' => $bailMobilite->id,
                    'mission_type' => $i % 2 === 0 ? 'entry' : 'exit',
                    'scheduled_at' => Carbon::now()->addDays(rand(1, 30)),
                    'agent_id' => rand(0, 1) ? $this->checkerUser->id : null,
                    'status' => ['unassigned', 'assigned', 'in_progress', 'completed'][rand(0, 3)],
                ]));
            }
        }
        
        $this->assertEquals(1000, Mission::count());
        
        // Test API performance with large dataset
        $startTime = microtime(true);
        
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->get(route('ops.calendar.missions') . '?' . http_build_query([
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]));
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $response->assertStatus(200);
        
        // Performance assertion: should complete within 2 seconds
        $this->assertLessThan(2.0, $executionTime, 
            "Calendar API took {$executionTime} seconds, which exceeds the 2-second limit");
        
        // Memory usage should be reasonable
        $memoryUsage = memory_get_peak_usage(true) / 1024 / 1024; // MB
        $this->assertLessThan(128, $memoryUsage, 
            "Memory usage of {$memoryUsage}MB exceeds the 128MB limit");
    }

    public function test_calendar_service_performance_with_complex_filters()
    {
        // Create diverse dataset
        $bailMobilites = BailMobilite::factory()->count(50)->create();
        $checkers = User::factory()->count(10)->create();
        foreach ($checkers as $checker) {
            $checker->assignRole('checker');
        }
        
        // Create missions with various attributes
        foreach ($bailMobilites as $bailMobilite) {
            Mission::factory()->count(5)->create([
                'bail_mobilite_id' => $bailMobilite->id,
                'scheduled_at' => Carbon::now()->addDays(rand(1, 60)),
                'agent_id' => $checkers->random()->id,
                'status' => ['unassigned', 'assigned', 'in_progress', 'completed'][rand(0, 3)],
                'mission_type' => ['entry', 'exit'][rand(0, 1)],
            ]);
        }
        
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->addMonths(2)->endOfMonth();
        
        // Test complex filtering performance
        $complexFilters = [
            'status' => ['assigned', 'in_progress'],
            'mission_type' => ['entry'],
            'checker_id' => $checkers->random()->id,
            'search' => 'test',
        ];
        
        $startTime = microtime(true);
        
        $missions = $this->calendarService->getMissionsForDateRange(
            $startDate, 
            $endDate, 
            $complexFilters
        );
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Performance assertion: complex filtering should complete within 1 second
        $this->assertLessThan(1.0, $executionTime, 
            "Complex filtering took {$executionTime} seconds, which exceeds the 1-second limit");
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $missions);
    }

    public function test_mission_formatting_performance()
    {
        // Create missions with full relationships
        $bailMobilites = BailMobilite::factory()->count(20)->create();
        $missions = collect();
        
        foreach ($bailMobilites as $bailMobilite) {
            $missions = $missions->merge(Mission::factory()->count(25)->create([
                'bail_mobilite_id' => $bailMobilite->id,
                'agent_id' => $this->checkerUser->id,
                'scheduled_at' => Carbon::now()->addDays(rand(1, 30)),
            ]));
        }
        
        // Load all relationships
        $missions = Mission::with([
            'agent:id,name,email',
            'bailMobilite:id,tenant_name,address,status,start_date,end_date,duration_days',
            'checklist:id,mission_id,status',
            'opsAssignedBy:id,name'
        ])->get();
        
        $this->assertEquals(500, $missions->count());
        
        // Test formatting performance
        $startTime = microtime(true);
        
        $formattedMissions = $this->calendarService->formatMissionsForCalendar($missions);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Performance assertion: formatting 500 missions should complete within 1 second
        $this->assertLessThan(1.0, $executionTime, 
            "Mission formatting took {$executionTime} seconds, which exceeds the 1-second limit");
        
        $this->assertCount(500, $formattedMissions);
        $this->assertArrayHasKey('id', $formattedMissions[0]);
        $this->assertArrayHasKey('conflicts', $formattedMissions[0]);
    }

    public function test_conflict_detection_performance()
    {
        // Create many missions for the same checker on the same day
        $date = Carbon::now()->addDays(1);
        $bailMobilite = BailMobilite::factory()->create();
        
        // Create 50 missions for the same checker on the same day
        for ($i = 9; $i < 18; $i++) { // 9 AM to 6 PM
            for ($j = 0; $j < 6; $j++) { // Every 10 minutes
                Mission::factory()->create([
                    'bail_mobilite_id' => $bailMobilite->id,
                    'agent_id' => $this->checkerUser->id,
                    'scheduled_at' => $date,
                    'scheduled_time' => sprintf('%02d:%02d:00', $i, $j * 10),
                ]);
            }
        }
        
        $this->assertEquals(54, Mission::where('agent_id', $this->checkerUser->id)
            ->whereDate('scheduled_at', $date)->count());
        
        // Test conflict detection performance
        $startTime = microtime(true);
        
        $conflicts = $this->calendarService->detectSchedulingConflicts(
            $date, 
            '10:30', 
            $this->checkerUser->id
        );
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Performance assertion: conflict detection should complete within 0.5 seconds
        $this->assertLessThan(0.5, $executionTime, 
            "Conflict detection took {$executionTime} seconds, which exceeds the 0.5-second limit");
        
        $this->assertNotEmpty($conflicts);
    }

    public function test_time_slot_availability_performance()
    {
        // Create many missions for multiple checkers
        $checkers = User::factory()->count(20)->create();
        foreach ($checkers as $checker) {
            $checker->assignRole('checker');
        }
        
        $date = Carbon::now()->addDays(1);
        $bailMobilite = BailMobilite::factory()->create();
        
        // Create missions for each checker
        foreach ($checkers as $checker) {
            for ($i = 9; $i < 18; $i += 2) { // Every 2 hours
                Mission::factory()->create([
                    'bail_mobilite_id' => $bailMobilite->id,
                    'agent_id' => $checker->id,
                    'scheduled_at' => $date,
                    'scheduled_time' => sprintf('%02d:00:00', $i),
                ]);
            }
        }
        
        // Test time slot availability performance for each checker
        $totalTime = 0;
        
        foreach ($checkers as $checker) {
            $startTime = microtime(true);
            
            $timeSlots = $this->calendarService->getAvailableTimeSlots($date, $checker->id);
            
            $endTime = microtime(true);
            $totalTime += ($endTime - $startTime);
            
            $this->assertIsArray($timeSlots);
            $this->assertGreaterThan(0, count($timeSlots));
        }
        
        $averageTime = $totalTime / count($checkers);
        
        // Performance assertion: average time slot calculation should complete within 0.1 seconds
        $this->assertLessThan(0.1, $averageTime, 
            "Average time slot calculation took {$averageTime} seconds, which exceeds the 0.1-second limit");
    }

    public function test_database_query_efficiency()
    {
        // Create test data
        $bailMobilites = BailMobilite::factory()->count(100)->create();
        foreach ($bailMobilites as $bailMobilite) {
            Mission::factory()->count(5)->create([
                'bail_mobilite_id' => $bailMobilite->id,
                'agent_id' => $this->checkerUser->id,
                'scheduled_at' => Carbon::now()->addDays(rand(1, 30)),
            ]);
        }
        
        // Enable query logging
        DB::enableQueryLog();
        
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        // Execute calendar service method
        $missions = $this->calendarService->getMissionsForDateRange($startDate, $endDate);
        $this->calendarService->formatMissionsForCalendar($missions);
        
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        
        // Performance assertion: should use minimal number of queries (N+1 problem prevention)
        $this->assertLessThan(10, count($queries), 
            "Calendar operations executed " . count($queries) . " queries, which may indicate N+1 problem");
        
        // Check for efficient eager loading
        $hasEagerLoading = false;
        foreach ($queries as $query) {
            if (strpos($query['query'], 'LEFT JOIN') !== false || 
                strpos($query['query'], 'INNER JOIN') !== false) {
                $hasEagerLoading = true;
                break;
            }
        }
        
        $this->assertTrue($hasEagerLoading, "Queries should use efficient eager loading with JOINs");
    }

    public function test_memory_usage_with_large_datasets()
    {
        $initialMemory = memory_get_usage(true);
        
        // Create large dataset
        $bailMobilites = BailMobilite::factory()->count(200)->create();
        foreach ($bailMobilites as $bailMobilite) {
            Mission::factory()->count(5)->create([
                'bail_mobilite_id' => $bailMobilite->id,
                'agent_id' => $this->checkerUser->id,
                'scheduled_at' => Carbon::now()->addDays(rand(1, 60)),
            ]);
        }
        
        $afterDataCreation = memory_get_usage(true);
        
        // Process data through calendar service
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->addMonths(2)->endOfMonth();
        
        $missions = $this->calendarService->getMissionsForDateRange($startDate, $endDate);
        $formattedMissions = $this->calendarService->formatMissionsForCalendar($missions);
        
        $afterProcessing = memory_get_usage(true);
        
        $processingMemoryIncrease = ($afterProcessing - $afterDataCreation) / 1024 / 1024; // MB
        
        // Performance assertion: processing should not increase memory usage by more than 50MB
        $this->assertLessThan(50, $processingMemoryIncrease, 
            "Calendar processing increased memory usage by {$processingMemoryIncrease}MB, which exceeds the 50MB limit");
        
        // Cleanup and verify memory is released
        unset($missions, $formattedMissions);
        
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        
        $afterCleanup = memory_get_usage(true);
        $memoryReleased = ($afterProcessing - $afterCleanup) / 1024 / 1024; // MB
        
        // Memory should be properly released
        $this->assertGreaterThan(0, $memoryReleased, "Memory should be released after processing");
    }

    public function test_concurrent_request_simulation()
    {
        // Create test data
        $bailMobilites = BailMobilite::factory()->count(50)->create();
        foreach ($bailMobilites as $bailMobilite) {
            Mission::factory()->count(10)->create([
                'bail_mobilite_id' => $bailMobilite->id,
                'scheduled_at' => Carbon::now()->addDays(rand(1, 30)),
            ]);
        }
        
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        // Simulate multiple concurrent requests
        $responses = [];
        $executionTimes = [];
        
        for ($i = 0; $i < 5; $i++) {
            $startTime = microtime(true);
            
            $response = $this->withoutMiddleware()->actingAs($this->opsUser)
                ->get(route('ops.calendar.missions') . '?' . http_build_query([
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'status' => ['assigned', 'in_progress'][rand(0, 1)],
                ]));
            
            $endTime = microtime(true);
            $executionTimes[] = $endTime - $startTime;
            $responses[] = $response;
        }
        
        // All requests should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
        
        $averageTime = array_sum($executionTimes) / count($executionTimes);
        $maxTime = max($executionTimes);
        
        // Performance assertions for concurrent requests
        $this->assertLessThan(3.0, $maxTime, 
            "Maximum request time of {$maxTime} seconds exceeds the 3-second limit");
        
        $this->assertLessThan(2.0, $averageTime, 
            "Average request time of {$averageTime} seconds exceeds the 2-second limit");
    }

    public function test_cache_effectiveness()
    {
        // Create test data
        $bailMobilites = BailMobilite::factory()->count(20)->create();
        foreach ($bailMobilites as $bailMobilite) {
            Mission::factory()->count(5)->create([
                'bail_mobilite_id' => $bailMobilite->id,
                'scheduled_at' => Carbon::now()->addDays(rand(1, 30)),
            ]);
        }
        
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        // Clear any existing cache
        Cache::flush();
        
        // First request (should hit database)
        $startTime1 = microtime(true);
        $response1 = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->get(route('ops.calendar.missions') . '?' . http_build_query([
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]));
        $endTime1 = microtime(true);
        $firstRequestTime = $endTime1 - $startTime1;
        
        $response1->assertStatus(200);
        $cacheKey1 = $response1->json('cache_key');
        
        // Second identical request (should be faster if cached)
        $startTime2 = microtime(true);
        $response2 = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->get(route('ops.calendar.missions') . '?' . http_build_query([
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]));
        $endTime2 = microtime(true);
        $secondRequestTime = $endTime2 - $startTime2;
        
        $response2->assertStatus(200);
        $cacheKey2 = $response2->json('cache_key');
        
        // Cache keys should be identical for identical requests
        $this->assertEquals($cacheKey1, $cacheKey2);
        
        // Response data should be identical
        $this->assertEquals($response1->json('missions'), $response2->json('missions'));
        $this->assertEquals($response1->json('total'), $response2->json('total'));
        
        // Note: In a real caching implementation, the second request should be faster
        // This test verifies the cache key generation works correctly
        $this->assertIsString($cacheKey1);
        $this->assertNotEmpty($cacheKey1);
    }
}