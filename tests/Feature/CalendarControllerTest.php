<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\BailMobilite;
use App\Models\Mission;
use App\Models\User;
use App\Models\Agent;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CalendarControllerTest extends TestCase
{
    use RefreshDatabase;
    
    // Note: These tests bypass middleware to focus on API functionality
    // Middleware testing should be done separately

    protected User $opsUser;
    protected User $adminUser;
    protected User $checkerUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles
        $this->seed(\Database\Seeders\RoleSeeder::class);
        
        // Create test users
        $this->opsUser = User::factory()->create();
        $this->opsUser->assignRole('ops');
        
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');
        
        $this->checkerUser = User::factory()->create();
        $this->checkerUser->assignRole('checker');
    }

    public function test_calendar_index_returns_response()
    {
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)->get(route('ops.calendar.index'));
        
        // Just test that the route exists and returns a response
        // Frontend component testing would be done separately
        $response->assertStatus(200);
    }

    public function test_calendar_index_handles_view_mode_parameter()
    {
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->get(route('ops.calendar.index', ['view' => 'week', 'date' => '2024-01-15']));

        $response->assertStatus(200);
    }

    public function test_calendar_index_validates_view_mode()
    {
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->get(route('ops.calendar.index', ['view' => 'invalid']));

        $response->assertStatus(302); // Redirect due to validation error
    }

    public function test_calendar_index_handles_date_boundaries()
    {
        // Test with different view modes and dates
        $testCases = [
            ['view' => 'month', 'date' => '2024-01-15'],
            ['view' => 'week', 'date' => '2024-01-15'],
            ['view' => 'day', 'date' => '2024-01-15'],
        ];

        foreach ($testCases as $params) {
            $response = $this->withoutMiddleware()->actingAs($this->opsUser)
                ->get(route('ops.calendar.index', $params));

            $response->assertStatus(200);
        }
    }

    public function test_get_missions_endpoint_returns_missions_for_date_range()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        $bailMobilite = BailMobilite::factory()->create();
        $mission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => $startDate->copy()->addDays(5),
            'agent_id' => $this->checkerUser->id,
        ]);
        
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)->get(route('ops.calendar.missions') . '?' . http_build_query([
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]));
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'missions' => [
                '*' => [
                    'id',
                    'type',
                    'scheduled_at',
                    'status',
                    'tenant_name',
                    'address',
                    'agent',
                    'bail_mobilite',
                    'conflicts',
                    'can_edit',
                    'can_assign',
                ]
            ],
            'total',
            'date_range' => [
                'start',
                'end',
            ],
        ]);
        
        $responseData = $response->json();
        $this->assertEquals(1, $responseData['total']);
        $this->assertEquals($mission->id, $responseData['missions'][0]['id']);
    }

    public function test_get_missions_endpoint_validates_required_parameters()
    {
        $response = $this->withoutMiddleware()
            ->actingAs($this->opsUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->get(route('ops.calendar.missions'));
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    public function test_get_missions_endpoint_applies_filters()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        $bailMobilite = BailMobilite::factory()->create();
        
        $assignedMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => $startDate->copy()->addDays(5),
            'status' => 'assigned',
            'agent_id' => $this->checkerUser->id,
        ]);
        
        $completedMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => $startDate->copy()->addDays(6),
            'status' => 'completed',
            'agent_id' => $this->checkerUser->id,
        ]);
        
        // Test status filter
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)->get(route('ops.calendar.missions') . '?' . http_build_query([
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'status' => 'assigned',
        ]));
        
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertEquals(1, $responseData['total']);
        $this->assertEquals($assignedMission->id, $responseData['missions'][0]['id']);
    }

    public function test_create_mission_endpoint_creates_bail_mobilite_with_missions()
    {
        $data = [
            'start_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'end_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
            'address' => '123 Test Street',
            'tenant_name' => 'Test Tenant',
            'tenant_phone' => '0123456789',
            'tenant_email' => 'test@example.com',
            'notes' => 'Test notes',
            'entry_scheduled_time' => '10:00',
            'exit_scheduled_time' => '14:00',
            'entry_checker_id' => $this->checkerUser->id,
            'exit_checker_id' => $this->checkerUser->id,
        ];
        
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)->post(route('ops.calendar.missions.create'), $data);
        
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'bail_mobilite' => [
                'id',
                'start_date',
                'end_date',
                'address',
                'tenant_name',
                'entry_mission',
                'exit_mission',
            ],
            'missions' => [
                '*' => [
                    'id',
                    'type',
                    'scheduled_at',
                    'status',
                ]
            ],
        ]);
        
        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        
        // Verify bail mobilité was created
        $this->assertDatabaseHas('bail_mobilites', [
            'address' => $data['address'],
            'tenant_name' => $data['tenant_name'],
            'status' => 'assigned',
        ]);
        
        // Verify missions were created
        $this->assertDatabaseHas('missions', [
            'mission_type' => 'entry',
            'scheduled_time' => '10:00:00',
            'status' => 'assigned',
            'agent_id' => $this->checkerUser->id,
        ]);
        
        $this->assertDatabaseHas('missions', [
            'mission_type' => 'exit',
            'scheduled_time' => '14:00:00',
            'status' => 'assigned',
            'agent_id' => $this->checkerUser->id,
        ]);
    }

    public function test_create_mission_endpoint_validates_required_fields()
    {
        $response = $this->withoutMiddleware()
            ->actingAs($this->opsUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('ops.calendar.missions.create'), []);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'start_date',
            'end_date',
            'address',
            'tenant_name',
        ]);
    }

    public function test_create_mission_endpoint_validates_date_logic()
    {
        $data = [
            'start_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
            'end_date' => Carbon::now()->addDays(1)->format('Y-m-d'), // End before start
            'address' => '123 Test Street',
            'tenant_name' => 'Test Tenant',
        ];
        
        $response = $this->withoutMiddleware()
            ->actingAs($this->opsUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('ops.calendar.missions.create'), $data);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_date']);
    }

    public function test_update_mission_endpoint_updates_mission_details()
    {
        $bailMobilite = BailMobilite::factory()->create();
        $mission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'unassigned',
            'agent_id' => null,
            'mission_type' => 'entry',
        ]);
        
        $updateData = [
            'scheduled_at' => Carbon::now()->addDays(2)->format('Y-m-d'),
            'scheduled_time' => '15:00',
            'agent_id' => $this->checkerUser->id,
            'notes' => 'Updated notes',
        ];
        
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)->patch(
            route('ops.calendar.missions.update', $mission),
            $updateData
        );
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'mission' => [
                'id',
                'scheduled_at',
                'scheduled_time',
                'status',
                'agent',
            ],
        ]);
        
        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        
        // Verify mission was updated
        $mission->refresh();
        
        $this->assertEquals($updateData['scheduled_time'] . ':00', $mission->scheduled_time);
        $this->assertEquals($updateData['notes'], $mission->notes);
        $this->assertEquals('assigned', $mission->status); // Should be auto-assigned
    }

    public function test_get_mission_details_endpoint_returns_detailed_information()
    {
        $bailMobilite = BailMobilite::factory()->create();
        $mission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'agent_id' => $this->checkerUser->id,
        ]);
        
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)->get(
            route('ops.calendar.missions.details', $mission)
        );
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'mission' => [
                'id',
                'type',
                'scheduled_at',
                'status',
                'tenant_name',
                'address',
                'agent',
                'bail_mobilite',
                'checklist',
                'permissions',
                'indicators',
            ],
        ]);
    }

    public function test_get_available_time_slots_endpoint_returns_time_slots()
    {
        $date = Carbon::now()->addDays(1)->format('Y-m-d');
        
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)->get(route('ops.calendar.time-slots') . '?' . http_build_query([
            'date' => $date,
        ]));
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'date',
            'available_slots' => [
                '*' => [
                    'time',
                    'available',
                    'conflicts',
                ],
            ],
        ]);
        
        $responseData = $response->json();
        $this->assertEquals($date, $responseData['date']);
        $this->assertNotEmpty($responseData['available_slots']);
    }

    public function test_detect_conflicts_endpoint_detects_scheduling_conflicts()
    {
        $date = Carbon::now()->addDays(1);
        $time = '10:00';
        
        // Create an existing mission at the same time
        $bailMobilite = BailMobilite::factory()->create();
        Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'agent_id' => $this->checkerUser->id,
            'scheduled_at' => $date,
            'scheduled_time' => $time,
        ]);
        
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)->post(route('ops.calendar.conflicts'), [
            'date' => $date->format('Y-m-d'),
            'time' => $time,
            'checker_id' => $this->checkerUser->id,
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'has_conflicts',
            'conflicts',
        ]);
        
        $responseData = $response->json();
        $this->assertTrue($responseData['has_conflicts']);
        $this->assertNotEmpty($responseData['conflicts']);
    }

    public function test_get_missions_endpoint_handles_pagination()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        // Create multiple missions
        $bailMobilite = BailMobilite::factory()->create();
        $missions = Mission::factory()->count(5)->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => $startDate->copy()->addDays(5),
        ]);
        
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)->get(route('ops.calendar.missions') . '?' . http_build_query([
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]));
        
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertEquals(5, $responseData['total']);
        $this->assertCount(5, $responseData['missions']);
    }

    public function test_get_missions_endpoint_handles_empty_results()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)->get(route('ops.calendar.missions') . '?' . http_build_query([
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]));
        
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertEquals(0, $responseData['total']);
        $this->assertEmpty($responseData['missions']);
    }

    public function test_get_missions_endpoint_applies_multiple_filters()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        $bailMobilite = BailMobilite::factory()->create([
            'tenant_name' => 'John Doe',
        ]);
        
        $matchingMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => $startDate->copy()->addDays(5),
            'status' => 'assigned',
            'mission_type' => 'entry',
            'agent_id' => $this->checkerUser->id,
            'tenant_name' => 'John Doe',
        ]);
        
        $nonMatchingMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => $startDate->copy()->addDays(6),
            'status' => 'completed',
            'mission_type' => 'exit',
            'agent_id' => $this->checkerUser->id,
        ]);
        
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)->get(route('ops.calendar.missions') . '?' . http_build_query([
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'status' => 'assigned',
            'mission_type' => 'entry',
            'search' => 'John',
        ]));
        
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertEquals(1, $responseData['total']);
        $this->assertEquals($matchingMission->id, $responseData['missions'][0]['id']);
    }

    public function test_create_mission_endpoint_handles_scheduling_conflicts()
    {
        // Create an existing mission
        $date = Carbon::now()->addDays(1);
        $bailMobilite = BailMobilite::factory()->create();
        Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'agent_id' => $this->checkerUser->id,
            'scheduled_at' => $date,
            'scheduled_time' => '10:00:00',
        ]);
        
        // Try to create a new mission with the same checker at the same time
        $data = [
            'start_date' => $date->format('Y-m-d'),
            'end_date' => $date->copy()->addDays(4)->format('Y-m-d'),
            'address' => '123 Test Street',
            'tenant_name' => 'Test Tenant',
            'entry_scheduled_time' => '10:00',
            'entry_checker_id' => $this->checkerUser->id,
        ];
        
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)->post(route('ops.calendar.missions.create'), $data);
        
        // Should still create the mission but with conflicts noted
        $response->assertStatus(201);
        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        
        // Check that conflicts are detected in the formatted mission
        $entryMission = collect($responseData['missions'])->firstWhere('type', 'entry');
        $this->assertNotNull($entryMission);
        $this->assertNotEmpty($entryMission['conflicts']);
    }

    public function test_update_mission_endpoint_validates_mission_exists()
    {
        $response = $this->withoutMiddleware()
            ->actingAs($this->opsUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->patch(route('ops.calendar.missions.update', 99999), [
                'scheduled_time' => '15:00',
            ]);
        
        $response->assertStatus(404);
    }

    public function test_update_mission_endpoint_handles_partial_updates()
    {
        $bailMobilite = BailMobilite::factory()->create();
        $mission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_time' => '10:00:00',
            'notes' => 'Original notes',
        ]);
        
        // Update only the time
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)->patch(
            route('ops.calendar.missions.update', $mission),
            ['scheduled_time' => '15:00']
        );
        
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        $this->assertEquals('15:00', $responseData['mission']['scheduled_time']);
        
        // Verify original notes are preserved
        $mission->refresh();
        $this->assertEquals('Original notes', $mission->notes);
    }

    public function test_get_mission_details_endpoint_includes_all_relationships()
    {
        $bailMobilite = BailMobilite::factory()->create();
        $mission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'agent_id' => $this->checkerUser->id,
        ]);
        
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)->get(
            route('ops.calendar.missions.details', $mission)
        );
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'mission' => [
                'id',
                'type',
                'scheduled_at',
                'status',
                'tenant_name',
                'address',
                'agent',
                'bail_mobilite',
                'checklist',
                'permissions',
                'indicators',
            ],
        ]);
        
        $responseData = $response->json();
        // Agent and bail_mobilite should be present since we created them
        $this->assertArrayHasKey('agent', $responseData['mission']);
        $this->assertArrayHasKey('bail_mobilite', $responseData['mission']);
    }

    public function test_get_available_time_slots_endpoint_validates_date()
    {
        $response = $this->withoutMiddleware()
            ->actingAs($this->opsUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->get(route('ops.calendar.time-slots') . '?date=invalid-date');
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['date']);
    }

    public function test_get_available_time_slots_endpoint_handles_checker_filter()
    {
        $date = Carbon::now()->addDays(1);
        
        // Create a mission for the checker
        $bailMobilite = BailMobilite::factory()->create();
        Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'agent_id' => $this->checkerUser->id,
            'scheduled_at' => $date,
            'scheduled_time' => '10:00:00',
        ]);
        
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)->get(route('ops.calendar.time-slots') . '?' . http_build_query([
            'date' => $date->format('Y-m-d'),
            'checker_id' => $this->checkerUser->id,
        ]));
        
        $response->assertStatus(200);
        $responseData = $response->json();
        
        // Find the 10:00 slot and verify it's marked as unavailable
        $bookedSlot = collect($responseData['available_slots'])->firstWhere('time', '10:00');
        $this->assertNotNull($bookedSlot);
        $this->assertFalse($bookedSlot['available']);
        $this->assertNotEmpty($bookedSlot['conflicts']);
    }

    public function test_detect_conflicts_endpoint_validates_required_fields()
    {
        $response = $this->withoutMiddleware()
            ->actingAs($this->opsUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('ops.calendar.conflicts'), []);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['date', 'time']);
    }

    public function test_detect_conflicts_endpoint_handles_no_conflicts()
    {
        $date = Carbon::now()->addDays(1);
        $time = '10:00';
        
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)->post(route('ops.calendar.conflicts'), [
            'date' => $date->format('Y-m-d'),
            'time' => $time,
            'checker_id' => $this->checkerUser->id,
        ]);
        
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertFalse($responseData['has_conflicts']);
        $this->assertEmpty($responseData['conflicts']);
    }

    public function test_calendar_index_handles_invalid_view_mode()
    {
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->get(route('ops.calendar.index', ['view' => 'invalid']));

        $response->assertStatus(302); // Redirect due to validation error
    }

    public function test_calendar_index_calculates_correct_date_ranges()
    {
        $testDate = '2024-01-15';
        
        // Test month view
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->get(route('ops.calendar.index', ['view' => 'month', 'date' => $testDate]));
        
        $response->assertStatus(200);
        
        // Test week view
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->get(route('ops.calendar.index', ['view' => 'week', 'date' => $testDate]));
        
        $response->assertStatus(200);
        
        // Test day view
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->get(route('ops.calendar.index', ['view' => 'day', 'date' => $testDate]));
        
        $response->assertStatus(200);
    }

    public function test_create_mission_endpoint_validates_time_format()
    {
        $data = [
            'start_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'end_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
            'address' => '123 Test Street',
            'tenant_name' => 'Test Tenant',
            'entry_scheduled_time' => '25:00', // Invalid time
        ];
        
        $response = $this->withoutMiddleware()
            ->actingAs($this->opsUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('ops.calendar.missions.create'), $data);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['entry_scheduled_time']);
    }

    public function test_create_mission_endpoint_validates_checker_exists()
    {
        $data = [
            'start_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'end_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
            'address' => '123 Test Street',
            'tenant_name' => 'Test Tenant',
            'entry_checker_id' => 99999, // Non-existent user
        ];
        
        $response = $this->withoutMiddleware()
            ->actingAs($this->opsUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('ops.calendar.missions.create'), $data);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['entry_checker_id']);
    }

    public function test_update_mission_endpoint_validates_time_format()
    {
        $bailMobilite = BailMobilite::factory()->create();
        $mission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
        ]);
        
        $response = $this->withoutMiddleware()
            ->actingAs($this->opsUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->patch(route('ops.calendar.missions.update', $mission), [
                'scheduled_time' => '25:00', // Invalid time
            ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['scheduled_time']);
    }

    public function test_get_missions_endpoint_returns_cache_key()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)->get(route('ops.calendar.missions') . '?' . http_build_query([
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]));
        
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertArrayHasKey('cache_key', $responseData);
        $this->assertIsString($responseData['cache_key']);
    }

    public function test_get_missions_endpoint_filters_applied_filters()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)->get(route('ops.calendar.missions') . '?' . http_build_query([
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'status' => 'assigned',
            'search' => 'test',
            'empty_filter' => '', // Should be filtered out
        ]));
        
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertArrayHasKey('applied_filters', $responseData);
        $this->assertArrayHasKey('status', $responseData['applied_filters']);
        $this->assertArrayHasKey('search', $responseData['applied_filters']);
        $this->assertArrayNotHasKey('empty_filter', $responseData['applied_filters']);
    }}
