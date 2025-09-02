<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\BailMobilite;
use App\Models\Mission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class CalendarErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'ops']);
        Role::create(['name' => 'checker']);
        
        // Create and authenticate ops user
        $this->user = User::factory()->create();
        $this->user->assignRole('ops');
        $this->actingAs($this->user);
    }

    public function test_health_endpoint_returns_ok_status()
    {
        $response = $this->get('/api/health');

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'ok',
                    'service' => 'calendar'
                ])
                ->assertJsonStructure([
                    'status',
                    'timestamp',
                    'service'
                ]);
    }

    public function test_get_missions_handles_validation_errors()
    {
        $response = $this->withoutMiddleware()
                        ->getJson(route('ops.calendar.missions', [
                            'start_date' => 'invalid-date',
                            'end_date' => '2024-01-31'
                        ]));

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid request parameters'
                ])
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors'
                ]);
    }

    public function test_get_missions_handles_invalid_date_range()
    {
        $response = $this->withoutMiddleware()
                        ->getJson(route('ops.calendar.missions', [
                            'start_date' => '2024-01-31',
                            'end_date' => '2024-01-01' // End before start
                        ]));

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid request parameters'
                ]);
    }

    public function test_get_missions_returns_success_response_structure()
    {
        $response = $this->withoutMiddleware()
                        ->getJson(route('ops.calendar.missions', [
                            'start_date' => '2024-01-01',
                            'end_date' => '2024-01-31'
                        ]));

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ])
                ->assertJsonStructure([
                    'success',
                    'missions',
                    'total',
                    'date_range' => [
                        'start',
                        'end'
                    ],
                    'applied_filters',
                    'cache_key'
                ]);
    }

    public function test_create_mission_handles_validation_errors()
    {
        $response = $this->withoutMiddleware()
                        ->postJson(route('ops.calendar.missions.create'), [
                            'start_date' => 'invalid-date',
                            'address' => 'Test Address'
                            // Missing required fields
                        ]);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors'
                ]);
    }

    public function test_update_mission_status_handles_invalid_mission()
    {
        $response = $this->withoutMiddleware()
                        ->patchJson(route('ops.calendar.missions.update-status', 99999), [
                            'status' => 'completed'
                        ]);

        // Laravel validation will return 422 for invalid mission ID in route model binding
        $response->assertStatus(422);
    }

    public function test_update_mission_status_validates_status_values()
    {
        $bailMobilite = BailMobilite::factory()->create([
            'ops_user_id' => $this->user->id
        ]);
        
        $mission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'assigned'
        ]);

        $response = $this->withoutMiddleware()
                        ->patchJson(route('ops.calendar.missions.update-status', $mission->id), [
                            'status' => 'invalid-status'
                        ]);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors'
                ]);
    }

    public function test_delete_mission_handles_invalid_mission()
    {
        $response = $this->withoutMiddleware()
                        ->deleteJson(route('ops.calendar.missions.delete', 99999));

        // The controller handles non-existent missions gracefully
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);
    }

    public function test_assign_mission_validates_checker_exists()
    {
        $bailMobilite = BailMobilite::factory()->create([
            'ops_user_id' => $this->user->id
        ]);
        
        $mission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'unassigned'
        ]);

        $response = $this->withoutMiddleware()
                        ->postJson(route('ops.calendar.missions.assign', $mission->id), [
                            'agent_id' => 99999 // Non-existent user
                        ]);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors'
                ]);
    }

    public function test_bulk_update_validates_mission_ids()
    {
        $response = $this->withoutMiddleware()
                        ->postJson(route('ops.calendar.missions.bulk-update'), [
                            'mission_ids' => [99999, 99998], // Non-existent missions
                            'action' => 'update_status',
                            'status' => 'cancelled'
                        ]);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors'
                ]);
    }

    public function test_bulk_update_validates_required_fields()
    {
        $response = $this->withoutMiddleware()
                        ->postJson(route('ops.calendar.missions.bulk-update'), [
                            'mission_ids' => [],
                            'action' => 'assign'
                            // Missing agent_id for assign action
                        ]);

        $response->assertStatus(422);
    }
}