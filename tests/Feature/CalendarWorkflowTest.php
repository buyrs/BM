<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\BailMobilite;
use App\Models\Mission;
use App\Models\User;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class CalendarWorkflowTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_complete_mission_creation_workflow()
    {
        // Step 1: Access calendar page
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->get(route('ops.calendar.index'));
        
        $response->assertStatus(200);
        
        // Step 2: Create a new BM mission
        $missionData = [
            'start_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'end_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
            'address' => '123 Test Street',
            'tenant_name' => 'John Doe',
            'tenant_phone' => '0123456789',
            'tenant_email' => 'john@example.com',
            'notes' => 'Test mission creation',
            'entry_scheduled_time' => '10:00',
            'exit_scheduled_time' => '14:00',
            'entry_checker_id' => $this->checkerUser->id,
            'exit_checker_id' => $this->checkerUser->id,
        ];
        
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->post(route('ops.calendar.missions.create'), $missionData);
        
        $response->assertStatus(201);
        $responseData = $response->json();
        
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('bail_mobilite', $responseData);
        $this->assertArrayHasKey('missions', $responseData);
        $this->assertCount(2, $responseData['missions']); // Entry and exit missions
        
        // Verify database records
        $bailMobilite = BailMobilite::where('address', '123 Test Street')->first();
        $this->assertNotNull($bailMobilite);
        $this->assertEquals('assigned', $bailMobilite->status);
        
        $entryMission = Mission::where('bail_mobilite_id', $bailMobilite->id)
            ->where('mission_type', 'entry')->first();
        $exitMission = Mission::where('bail_mobilite_id', $bailMobilite->id)
            ->where('mission_type', 'exit')->first();
        
        $this->assertNotNull($entryMission);
        $this->assertNotNull($exitMission);
        $this->assertEquals('assigned', $entryMission->status);
        $this->assertEquals('assigned', $exitMission->status);
        
        // Step 3: Retrieve missions via calendar API
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->get(route('ops.calendar.missions') . '?' . http_build_query([
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]));
        
        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertEquals(2, $responseData['total']);
        $this->assertCount(2, $responseData['missions']);
        
        // Step 4: Get detailed mission information
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->get(route('ops.calendar.missions.details', $entryMission));
        
        $response->assertStatus(200);
        $missionDetails = $response->json();
        
        $this->assertArrayHasKey('mission', $missionDetails);
        $this->assertEquals($entryMission->id, $missionDetails['mission']['id']);
        
        // Step 5: Update mission details
        $updateData = [
            'scheduled_time' => '11:00',
            'notes' => 'Updated mission notes',
        ];
        
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->patch(route('ops.calendar.missions.update', $entryMission), $updateData);
        
        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertTrue($responseData['success']);
        $this->assertEquals('11:00', $responseData['mission']['scheduled_time']);
        
        // Verify database update
        $entryMission->refresh();
        $this->assertEquals('11:00:00', $entryMission->scheduled_time);
        $this->assertEquals('Updated mission notes', $entryMission->notes);
    }

    public function test_mission_assignment_workflow()
    {
        // Create an unassigned mission
        $bailMobilite = BailMobilite::factory()->create([
            'ops_user_id' => $this->opsUser->id,
        ]);
        
        $mission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'unassigned',
            'agent_id' => null,
        ]);
        
        // Step 1: Assign mission to checker
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->post(route('ops.calendar.missions.assign', $mission), [
                'agent_id' => $this->checkerUser->id,
                'notes' => 'Assigned via calendar',
            ]);
        
        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertTrue($responseData['success']);
        $this->assertEquals($this->checkerUser->id, $responseData['mission']['agent']['id']);
        $this->assertEquals('assigned', $responseData['mission']['status']);
        
        // Verify database update
        $mission->refresh();
        $this->assertEquals($this->checkerUser->id, $mission->agent_id);
        $this->assertEquals('assigned', $mission->status);
        $this->assertEquals($this->opsUser->id, $mission->ops_assigned_by);
        
        // Step 2: Update mission status to in_progress
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->patch(route('ops.calendar.missions.update-status', $mission), [
                'status' => 'in_progress',
            ]);
        
        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertTrue($responseData['success']);
        $this->assertEquals('in_progress', $responseData['mission']['status']);
        
        // Step 3: Complete the mission
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->patch(route('ops.calendar.missions.update-status', $mission), [
                'status' => 'completed',
                'notes' => 'Mission completed successfully',
            ]);
        
        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertTrue($responseData['success']);
        $this->assertEquals('completed', $responseData['mission']['status']);
        
        // Verify final state
        $mission->refresh();
        $this->assertEquals('completed', $mission->status);
        $this->assertEquals('Mission completed successfully', $mission->notes);
    }

    public function test_bulk_mission_operations_workflow()
    {
        // Create multiple missions
        $bailMobilite = BailMobilite::factory()->create([
            'ops_user_id' => $this->opsUser->id,
        ]);
        
        $missions = Mission::factory()->count(3)->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'unassigned',
            'agent_id' => null,
        ]);
        
        $missionIds = $missions->pluck('id')->toArray();
        
        // Step 1: Bulk assign missions
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->post(route('ops.calendar.missions.bulk-update'), [
                'mission_ids' => $missionIds,
                'action' => 'assign',
                'agent_id' => $this->checkerUser->id,
                'notes' => 'Bulk assignment',
            ]);
        
        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertTrue($responseData['success']);
        $this->assertCount(3, $responseData['missions']);
        $this->assertEmpty($responseData['errors']);
        
        // Verify all missions are assigned
        foreach ($missions as $mission) {
            $mission->refresh();
            $this->assertEquals($this->checkerUser->id, $mission->agent_id);
            $this->assertEquals('assigned', $mission->status);
        }
        
        // Step 2: Bulk status update
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->post(route('ops.calendar.missions.bulk-update'), [
                'mission_ids' => $missionIds,
                'action' => 'update_status',
                'status' => 'in_progress',
            ]);
        
        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertTrue($responseData['success']);
        
        // Verify all missions are in progress
        foreach ($missions as $mission) {
            $mission->refresh();
            $this->assertEquals('in_progress', $mission->status);
        }
        
        // Step 3: Bulk delete (should fail for in_progress missions)
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->post(route('ops.calendar.missions.bulk-update'), [
                'mission_ids' => $missionIds,
                'action' => 'delete',
            ]);
        
        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertTrue($responseData['success']);
        $this->assertNotEmpty($responseData['errors']); // Should have errors for in_progress missions
        $this->assertCount(3, $responseData['errors']); // All should fail
    }

    public function test_calendar_filtering_workflow()
    {
        // Create missions with different statuses and types
        $bailMobilite = BailMobilite::factory()->create([
            'tenant_name' => 'John Doe',
            'address' => '123 Main Street',
        ]);
        
        $assignedEntryMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'assigned',
            'mission_type' => 'entry',
            'agent_id' => $this->checkerUser->id,
            'tenant_name' => 'John Doe',
            'scheduled_at' => Carbon::now()->addDays(1),
        ]);
        
        $completedExitMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'completed',
            'mission_type' => 'exit',
            'agent_id' => $this->checkerUser->id,
            'tenant_name' => 'John Doe',
            'scheduled_at' => Carbon::now()->addDays(2),
        ]);
        
        $unassignedMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'unassigned',
            'mission_type' => 'entry',
            'agent_id' => null,
            'tenant_name' => 'Jane Smith',
            'scheduled_at' => Carbon::now()->addDays(3),
        ]);
        
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        // Step 1: Filter by status
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->get(route('ops.calendar.missions') . '?' . http_build_query([
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'status' => 'assigned',
            ]));
        
        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertEquals(1, $responseData['total']);
        $this->assertEquals($assignedEntryMission->id, $responseData['missions'][0]['id']);
        
        // Step 2: Filter by mission type
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->get(route('ops.calendar.missions') . '?' . http_build_query([
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'mission_type' => 'entry',
            ]));
        
        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertEquals(2, $responseData['total']); // Both entry missions
        
        // Step 3: Filter by checker
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->get(route('ops.calendar.missions') . '?' . http_build_query([
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'checker_id' => $this->checkerUser->id,
            ]));
        
        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertEquals(2, $responseData['total']); // Assigned and completed missions
        
        // Step 4: Search by tenant name
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->get(route('ops.calendar.missions') . '?' . http_build_query([
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'search' => 'John',
            ]));
        
        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertEquals(2, $responseData['total']); // Both John Doe missions
        
        // Step 5: Combined filters
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->get(route('ops.calendar.missions') . '?' . http_build_query([
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'status' => 'assigned',
                'mission_type' => 'entry',
                'checker_id' => $this->checkerUser->id,
                'search' => 'John',
            ]));
        
        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertEquals(1, $responseData['total']);
        $this->assertEquals($assignedEntryMission->id, $responseData['missions'][0]['id']);
    }

    public function test_conflict_detection_workflow()
    {
        // Create an existing mission
        $bailMobilite = BailMobilite::factory()->create();
        $existingMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'agent_id' => $this->checkerUser->id,
            'scheduled_at' => Carbon::now()->addDays(1),
            'scheduled_time' => '10:00:00',
            'tenant_name' => 'Existing Tenant',
            'address' => '456 Conflict Street',
        ]);
        
        $date = Carbon::now()->addDays(1);
        $time = '10:00';
        
        // Step 1: Check for conflicts
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->post(route('ops.calendar.conflicts'), [
                'date' => $date->format('Y-m-d'),
                'time' => $time,
                'checker_id' => $this->checkerUser->id,
            ]);
        
        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertTrue($responseData['has_conflicts']);
        $this->assertNotEmpty($responseData['conflicts']);
        $this->assertStringContainsString('Conflit avec mission', $responseData['conflicts'][0]);
        
        // Step 2: Check available time slots
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->get(route('ops.calendar.time-slots') . '?' . http_build_query([
                'date' => $date->format('Y-m-d'),
                'checker_id' => $this->checkerUser->id,
            ]));
        
        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertArrayHasKey('available_slots', $responseData);
        
        // Find the 10:00 slot and verify it's marked as unavailable
        $bookedSlot = collect($responseData['available_slots'])->firstWhere('time', '10:00');
        $this->assertNotNull($bookedSlot);
        $this->assertFalse($bookedSlot['available']);
        $this->assertNotEmpty($bookedSlot['conflicts']);
        
        // Step 3: Check conflicts excluding the existing mission (for editing)
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->post(route('ops.calendar.conflicts'), [
                'date' => $date->format('Y-m-d'),
                'time' => $time,
                'checker_id' => $this->checkerUser->id,
                'mission_id' => $existingMission->id,
            ]);
        
        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertFalse($responseData['has_conflicts']); // No conflicts when excluding self
        $this->assertEmpty($responseData['conflicts']);
    }

    public function test_mission_deletion_workflow()
    {
        // Create missions in different states
        $bailMobilite = BailMobilite::factory()->create([
            'ops_user_id' => $this->opsUser->id,
        ]);
        
        $unassignedMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'unassigned',
        ]);
        
        $assignedMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'assigned',
            'agent_id' => $this->checkerUser->id,
        ]);
        
        $inProgressMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'in_progress',
            'agent_id' => $this->checkerUser->id,
        ]);
        
        $completedMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'completed',
            'agent_id' => $this->checkerUser->id,
        ]);
        
        // Step 1: Delete unassigned mission (should succeed)
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->delete(route('ops.calendar.missions.delete', $unassignedMission));
        
        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertTrue($responseData['success']);
        $this->assertEquals($unassignedMission->id, $responseData['deleted_mission_id']);
        
        // Verify mission is deleted
        $this->assertDatabaseMissing('missions', ['id' => $unassignedMission->id]);
        
        // Step 2: Delete assigned mission (should succeed)
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->delete(route('ops.calendar.missions.delete', $assignedMission));
        
        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertTrue($responseData['success']);
        
        // Step 3: Try to delete in_progress mission (should fail)
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->delete(route('ops.calendar.missions.delete', $inProgressMission));
        
        $response->assertStatus(422);
        $responseData = $response->json();
        
        $this->assertFalse($responseData['success']);
        $this->assertStringContainsString('in progress', $responseData['message']);
        
        // Step 4: Try to delete completed mission (should fail)
        $response = $this->withoutMiddleware()->actingAs($this->opsUser)
            ->delete(route('ops.calendar.missions.delete', $completedMission));
        
        $response->assertStatus(422);
        $responseData = $response->json();
        
        $this->assertFalse($responseData['success']);
        $this->assertStringContainsString('completed', $responseData['message']);
        
        // Verify missions still exist
        $this->assertDatabaseHas('missions', ['id' => $inProgressMission->id]);
        $this->assertDatabaseHas('missions', ['id' => $completedMission->id]);
    }
}