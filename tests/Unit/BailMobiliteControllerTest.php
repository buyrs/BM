<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\BailMobiliteController;
use App\Models\BailMobilite;
use App\Models\User;
use App\Models\Mission;
use App\Models\Agent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class BailMobiliteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected BailMobiliteController $controller;
    protected User $opsUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new BailMobiliteController();
        
        // Create ops role and user
        Role::create(['name' => 'ops']);
        $this->opsUser = User::factory()->create();
        $this->opsUser->assignRole('ops');
        
        $this->actingAs($this->opsUser);
    }

    /** @test */
    public function it_can_create_bail_mobilite_with_missions()
    {
        $data = [
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28',
            'address' => '123 Test Street',
            'tenant_name' => 'John Doe',
            'tenant_phone' => '+1234567890',
            'tenant_email' => 'john@example.com',
            'notes' => 'Test notes'
        ];

        $request = new Request($data);
        $response = $this->controller->store($request);

        $this->assertDatabaseHas('bail_mobilites', [
            'address' => '123 Test Street',
            'tenant_name' => 'John Doe',
            'status' => 'assigned',
            'ops_user_id' => $this->opsUser->id
        ]);

        $bailMobilite = BailMobilite::where('tenant_name', 'John Doe')->first();
        
        // Check that entry and exit missions were created
        $this->assertDatabaseHas('missions', [
            'bail_mobilite_id' => $bailMobilite->id,
            'mission_type' => 'entry',
            'scheduled_date' => '2025-02-01'
        ]);

        $this->assertDatabaseHas('missions', [
            'bail_mobilite_id' => $bailMobilite->id,
            'mission_type' => 'exit',
            'scheduled_date' => '2025-02-28'
        ]);
    }

    /** @test */
    public function it_can_assign_entry_mission_to_checker()
    {
        $bailMobilite = BailMobilite::factory()->create(['status' => 'assigned']);
        $checker = User::factory()->create();
        $agent = Agent::factory()->create(['user_id' => $checker->id]);
        $entryMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'mission_type' => 'entry'
        ]);
        $bailMobilite->update(['entry_mission_id' => $entryMission->id]);

        $request = new Request([
            'checker_id' => $checker->id,
            'scheduled_time' => '14:00'
        ]);

        $response = $this->controller->assignEntry($bailMobilite, $request);

        $this->assertDatabaseHas('missions', [
            'id' => $entryMission->id,
            'agent_id' => $agent->id,
            'scheduled_time' => '14:00:00',
            'ops_assigned_by' => $this->opsUser->id
        ]);
    }

    /** @test */
    public function it_can_assign_exit_mission_to_checker()
    {
        $bailMobilite = BailMobilite::factory()->create(['status' => 'in_progress']);
        $checker = User::factory()->create();
        $agent = Agent::factory()->create(['user_id' => $checker->id]);
        $exitMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'mission_type' => 'exit'
        ]);
        $bailMobilite->update(['exit_mission_id' => $exitMission->id]);

        $request = new Request([
            'checker_id' => $checker->id,
            'scheduled_time' => '11:00'
        ]);

        $response = $this->controller->assignExit($bailMobilite, $request);

        $this->assertDatabaseHas('missions', [
            'id' => $exitMission->id,
            'agent_id' => $agent->id,
            'scheduled_time' => '11:00:00',
            'ops_assigned_by' => $this->opsUser->id
        ]);
    }

    /** @test */
    public function it_can_validate_entry_and_transition_to_in_progress()
    {
        $bailMobilite = BailMobilite::factory()->create(['status' => 'assigned']);
        $entryMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'mission_type' => 'entry',
            'status' => 'completed'
        ]);
        $bailMobilite->update(['entry_mission_id' => $entryMission->id]);

        $request = new Request([
            'validation_notes' => 'Entry validated successfully'
        ]);

        $response = $this->controller->validateEntry($bailMobilite, $request);

        $this->assertEquals('in_progress', $bailMobilite->fresh()->status);
        
        // Check that exit reminder notification was scheduled
        $this->assertDatabaseHas('notifications', [
            'type' => 'EXIT_REMINDER',
            'bail_mobilite_id' => $bailMobilite->id,
            'recipient_id' => $this->opsUser->id,
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function it_can_validate_exit_and_transition_to_completed()
    {
        $bailMobilite = BailMobilite::factory()->create(['status' => 'in_progress']);
        $exitMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'mission_type' => 'exit',
            'status' => 'completed'
        ]);
        $bailMobilite->update(['exit_mission_id' => $exitMission->id]);

        $request = new Request([
            'validation_notes' => 'Exit validated successfully'
        ]);

        $response = $this->controller->validateExit($bailMobilite, $request);

        $this->assertEquals('completed', $bailMobilite->fresh()->status);
    }

    /** @test */
    public function it_can_handle_incident_and_create_corrective_actions()
    {
        $bailMobilite = BailMobilite::factory()->create(['status' => 'incident']);

        $request = new Request([
            'incident_type' => 'keys_not_returned',
            'description' => 'Tenant did not return keys',
            'corrective_actions' => [
                'Contact tenant immediately',
                'Schedule key recovery'
            ]
        ]);

        $response = $this->controller->handleIncident($bailMobilite, $request);

        $this->assertDatabaseHas('incident_reports', [
            'bail_mobilite_id' => $bailMobilite->id,
            'incident_type' => 'keys_not_returned',
            'description' => 'Tenant did not return keys',
            'reported_by' => $this->opsUser->id
        ]);

        $this->assertDatabaseHas('corrective_actions', [
            'bail_mobilite_id' => $bailMobilite->id,
            'action' => 'Contact tenant immediately',
            'assigned_to' => $this->opsUser->id,
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function it_can_update_bail_mobilite_dates()
    {
        $bailMobilite = BailMobilite::factory()->create([
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28'
        ]);

        $request = new Request([
            'start_date' => '2025-02-05',
            'end_date' => '2025-03-05',
            'address' => $bailMobilite->address,
            'tenant_name' => $bailMobilite->tenant_name,
            'tenant_phone' => $bailMobilite->tenant_phone,
            'tenant_email' => $bailMobilite->tenant_email
        ]);

        $response = $this->controller->update($bailMobilite, $request);

        $bailMobilite->refresh();
        $this->assertEquals('2025-02-05', $bailMobilite->start_date->format('Y-m-d'));
        $this->assertEquals('2025-03-05', $bailMobilite->end_date->format('Y-m-d'));

        // Check that missions were updated
        if ($bailMobilite->entryMission) {
            $this->assertEquals('2025-02-05', $bailMobilite->entryMission->scheduled_date->format('Y-m-d'));
        }
        if ($bailMobilite->exitMission) {
            $this->assertEquals('2025-03-05', $bailMobilite->exitMission->scheduled_date->format('Y-m-d'));
        }
    }

    /** @test */
    public function it_returns_kanban_data_correctly()
    {
        // Create BM in different statuses
        $assigned = BailMobilite::factory()->create(['status' => 'assigned']);
        $inProgress = BailMobilite::factory()->create(['status' => 'in_progress']);
        $completed = BailMobilite::factory()->create(['status' => 'completed']);
        $incident = BailMobilite::factory()->create(['status' => 'incident']);

        $response = $this->controller->index();
        $data = $response->getData();

        $this->assertArrayHasKey('kanbanData', $data);
        $kanban = $data['kanbanData'];

        $this->assertArrayHasKey('assigned', $kanban);
        $this->assertArrayHasKey('in_progress', $kanban);
        $this->assertArrayHasKey('completed', $kanban);
        $this->assertArrayHasKey('incident', $kanban);

        $this->assertCount(1, $kanban['assigned']);
        $this->assertCount(1, $kanban['in_progress']);
        $this->assertCount(1, $kanban['completed']);
        $this->assertCount(1, $kanban['incident']);
    }

    /** @test */
    public function it_requires_ops_permission_for_actions()
    {
        // Create a user without ops role
        $regularUser = User::factory()->create();
        $this->actingAs($regularUser);

        $bailMobilite = BailMobilite::factory()->create();

        $request = new Request([
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28',
            'address' => '123 Test Street',
            'tenant_name' => 'John Doe'
        ]);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->controller->store($request);
    }
}