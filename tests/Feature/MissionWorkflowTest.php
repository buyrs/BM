<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Mission;
use App\Models\Checklist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_mission_workflow()
    {
        // Create users
        $admin = User::factory()->create(['role' => 'admin']);
        $ops = User::factory()->create(['role' => 'ops']);
        $checker = User::factory()->create(['role' => 'checker']);

        // Create a mission through the API (this simulates the ops creating a mission)
        $mission = Mission::create([
            'title' => 'Test Mission',
            'description' => 'Test Description',
            'property_address' => '123 Test St',
            'checkin_date' => now()->addDay(),
            'checkout_date' => now()->addDays(2),
            'ops_id' => $ops->id,
            'checker_id' => $checker->id,
            'status' => 'pending',
        ]);

        // Admin approves the mission
        $this->actingAs($admin)->put(route('admin.missions.approve', $mission->id));
        
        $mission->refresh();
        $this->assertEquals('approved', $mission->status);
        $this->assertEquals($admin->id, $mission->admin_id);

        // Verify checklists were automatically created
        $this->assertCount(2, $mission->checklists);
        $checklistTypes = $mission->checklists->pluck('type')->sort()->values();
        $this->assertEquals(['checkin', 'checkout'], $checklistTypes->toArray());

        // Check that both checklists are in pending status
        foreach ($mission->checklists as $checklist) {
            $this->assertEquals('pending', $checklist->status);
        }
    }

    public function test_checklist_completion_workflow()
    {
        $checker = User::factory()->create(['role' => 'checker']);
        
        $mission = Mission::create([
            'title' => 'Test Mission',
            'description' => 'Test Description',
            'property_address' => '123 Test St',
            'checkin_date' => now()->addDay(),
            'checkout_date' => now()->addDays(2),
            'checker_id' => $checker->id,
            'status' => 'approved',
        ]);

        $checklist = $mission->checklists->first();
        
        // Check the checker can access their checklist
        $response = $this->actingAs($checker, 'checker')->get(route('checklists.show', $checklist->id));
        $response->assertStatus(200);

        // Update checklist (simulating filling out the checklist)
        $response = $this->actingAs($checker, 'checker')
            ->put(route('checklists.update', $checklist->id), [
                'items' => [
                    1 => [
                        'state' => 'good',
                        'comment' => 'Item is in good condition'
                    ]
                ]
            ]);
            
        $response->assertSessionHasNoErrors();
        $checklist->refresh();
        $this->assertNotEquals('completed', $checklist->status); // Should not be completed yet

        // Submit checklist
        $response = $this->actingAs($checker, 'checker')
            ->post(route('checklists.submit', $checklist->id));
            
        $response->assertRedirect(route('checker.dashboard'));
        $checklist->refresh();
        $this->assertEquals('completed', $checklist->status);
    }

    public function test_role_based_access_control()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ops = User::factory()->create(['role' => 'ops']);
        $checker = User::factory()->create(['role' => 'checker']);

        // Test that checker cannot access admin routes
        $response = $this->actingAs($checker, 'checker')
            ->get(route('admin.users.index'));
        $response->assertStatus(403);

        // Test that ops cannot access admin-only routes
        $response = $this->actingAs($ops, 'ops')
            ->get(route('admin.users.index'));
        $response->assertStatus(403);

        // Test that admin can access admin routes
        $response = $this->actingAs($admin)
            ->get(route('admin.users.index'));
        $response->assertStatus(200);
        
        // Test that admin can access ops routes too (higher permission)
        $response = $this->actingAs($admin)
            ->get(route('ops.missions.index'));
        $response->assertStatus(200);
    }
}