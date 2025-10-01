<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class AuthWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_and_access()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => Hash::make('password'),
            'email' => 'admin@example.com'
        ]);

        // Test admin login
        $response = $this->post(route('admin.login'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');

        // Test access to admin dashboard
        $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard'));
        $response->assertStatus(200);
    }

    public function test_ops_login_and_access()
    {
        $ops = User::factory()->create([
            'role' => 'ops',
            'password' => Hash::make('password'),
            'email' => 'ops@example.com'
        ]);

        // Test ops login
        $response = $this->post(route('ops.login'), [
            'email' => 'ops@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('ops.dashboard'));
        $this->assertAuthenticatedAs($ops, 'ops');

        // Test access to ops dashboard
        $response = $this->actingAs($ops, 'ops')->get(route('ops.dashboard'));
        $response->assertStatus(200);
    }

    public function test_checker_login_and_access()
    {
        $checker = User::factory()->create([
            'role' => 'checker',
            'password' => Hash::make('password'),
            'email' => 'checker@example.com'
        ]);

        // Test checker login
        $response = $this->post(route('checker.login'), [
            'email' => 'checker@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('checker.dashboard'));
        $this->assertAuthenticatedAs($checker, 'checker');

        // Test access to checker dashboard
        $response = $this->actingAs($checker, 'checker')->get(route('checker.dashboard'));
        $response->assertStatus(200);
    }

    public function test_role_based_mission_access()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ops = User::factory()->create(['role' => 'ops']);
        $checker = User::factory()->create(['role' => 'checker']);

        // Create a mission assigned to the checker
        $mission = \App\Models\Mission::create([
            'title' => 'Test Mission',
            'description' => 'Test Description',
            'property_address' => '123 Test St',
            'checkin_date' => now()->addDay(),
            'checkout_date' => now()->addDays(2),
            'ops_id' => $ops->id,
            'checker_id' => $checker->id,
            'status' => 'approved',
        ]);

        // Test that checker can only access their own missions
        $response = $this->actingAs($checker, 'checker')->get(route('checklists.show', $mission->checklists->first()->id));
        $response->assertStatus(200);

        // Test that admin can access any mission
        $response = $this->actingAs($admin)->get(route('admin.missions.show', $mission->id));
        $response->assertStatus(200);

        // Test that ops can access missions they created
        $response = $this->actingAs($ops, 'ops')->get(route('ops.missions.show', $mission->id));
        $response->assertStatus(200);
    }

    public function test_permission_based_access()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ops = User::factory()->create(['role' => 'ops']);
        $checker = User::factory()->create(['role' => 'checker']);

        // Test that checker cannot access ops routes
        $response = $this->actingAs($checker, 'checker')->get(route('ops.missions.index'));
        $response->assertStatus(403);

        // Test that ops cannot access admin user management
        $response = $this->actingAs($ops, 'ops')->get(route('admin.users.index'));
        $response->assertStatus(403);

        // Test that admin can access all routes
        $response = $this->actingAs($admin)->get(route('admin.users.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($admin)->get(route('admin.missions.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($admin)->get(route('ops.missions.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($admin)->get(route('checker.dashboard'));
        $response->assertStatus(200);
    }
}