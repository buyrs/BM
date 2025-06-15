<?php

namespace Tests\Feature;

use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissionTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $checker;
    private Mission $mission;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $this->artisan('db:seed');

        // Create users with roles
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super-admin');

        $this->checker = User::factory()->create();
        $this->checker->assignRole('checker');

        // Create a test mission
        $this->mission = Mission::factory()->create([
            'type' => 'checkin',
            'scheduled_at' => now()->addDays(2),
            'address' => '123 Test St',
            'tenant_name' => 'John Doe',
            'status' => 'unassigned'
        ]);
    }

    public function test_super_admin_can_create_mission()
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('missions.store'), [
                'type' => 'checkin',
                'scheduled_at' => now()->addDays(2),
                'address' => '123 Test St',
                'tenant_name' => 'John Doe',
                'tenant_phone' => '1234567890',
                'tenant_email' => 'john@example.com'
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('missions', [
            'type' => 'checkin',
            'address' => '123 Test St',
            'tenant_name' => 'John Doe'
        ]);
    }

    public function test_checker_cannot_create_mission()
    {
        $response = $this->actingAs($this->checker)
            ->post(route('missions.store'), [
                'type' => 'checkin',
                'scheduled_at' => now()->addDays(2),
                'address' => '123 Test St',
                'tenant_name' => 'John Doe'
            ]);

        $response->assertStatus(403);
    }

    public function test_super_admin_can_assign_mission()
    {
        $response = $this->actingAs($this->superAdmin)
            ->patch(route('missions.assign-agent', $this->mission->id), [
                'agent_id' => $this->checker->id
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('missions', [
            'id' => $this->mission->id,
            'agent_id' => $this->checker->id,
            'status' => 'assigned'
        ]);
    }

    public function test_checker_can_see_only_assigned_missions()
    {
        // Create multiple missions
        $assignedMission = Mission::factory()->create([
            'agent_id' => $this->checker->id,
            'status' => 'assigned'
        ]);

        $otherMission = Mission::factory()->create([
            'agent_id' => User::factory()->create()->id,
            'status' => 'assigned'
        ]);

        $response = $this->actingAs($this->checker)
            ->get(route('missions.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Missions/Index')
                ->has('missions.data', 1)
                ->where('missions.data.0.id', $assignedMission->id)
            );
    }

    public function test_checker_can_update_mission_status()
    {
        $mission = Mission::factory()->create([
            'agent_id' => $this->checker->id,
            'status' => 'assigned'
        ]);

        $response = $this->actingAs($this->checker)
            ->patch(route('missions.update-status', $mission->id), [
                'status' => 'in_progress'
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('missions', [
            'id' => $mission->id,
            'status' => 'in_progress'
        ]);
    }

    public function test_checker_cannot_update_unassigned_mission()
    {
        $mission = Mission::factory()->create([
            'status' => 'unassigned'
        ]);

        $response = $this->actingAs($this->checker)
            ->patch(route('missions.update-status', $mission->id), [
                'status' => 'in_progress'
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('missions', [
            'id' => $mission->id,
            'status' => 'unassigned'
        ]);
    }

    public function test_super_admin_can_delete_mission()
    {
        $response = $this->actingAs($this->superAdmin)
            ->delete(route('missions.destroy', $this->mission->id));

        $response->assertRedirect();
        $this->assertSoftDeleted($this->mission);
    }

    public function test_checker_cannot_delete_mission()
    {
        $response = $this->actingAs($this->checker)
            ->delete(route('missions.destroy', $this->mission->id));

        $response->assertStatus(403);
        $this->assertDatabaseHas('missions', [
            'id' => $this->mission->id,
            'deleted_at' => null
        ]);
    }
}