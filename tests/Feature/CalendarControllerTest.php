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
            'status' => ['assigned'],
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
            'scheduled_time' => '10:00',
            'status' => 'assigned',
            'agent_id' => $this->checkerUser->id,
        ]);
        
        $this->assertDatabaseHas('missions', [
            'mission_type' => 'exit',
            'scheduled_time' => '14:00',
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
        
        // Debug: Check what's actually in the database
        if (empty($mission->scheduled_time)) {
            $this->fail('scheduled_time is empty. Mission data: ' . json_encode($mission->toArray()));
        }
        
        $this->assertEquals($updateData['scheduled_time'], substr($mission->scheduled_time, 0, 5)); // Compare HH:MM part only
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
}