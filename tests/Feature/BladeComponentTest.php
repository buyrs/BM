<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_elements_render()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        
        $response->assertStatus(200)
                 ->assertSee('Admin Dashboard')
                 ->assertSee('Manage Users')
                 ->assertSee('Manage Missions');
    }

    public function test_ops_dashboard_elements_render()
    {
        $ops = User::factory()->create(['role' => 'ops']);

        $response = $this->actingAs($ops, 'ops')->get(route('ops.dashboard'));
        
        $response->assertStatus(200)
                 ->assertSee('Ops Dashboard')
                 ->assertSee('Manage Checkers')
                 ->assertSee('Manage Missions');
    }

    public function test_checker_dashboard_elements_render()
    {
        $checker = User::factory()->create(['role' => 'checker']);

        $response = $this->actingAs($checker, 'checker')->get(route('checker.dashboard'));
        
        $response->assertStatus(200)
                 ->assertSee('Checker Dashboard')
                 ->assertSee('Assigned Checklists');
    }

    public function test_checklist_form_render()
    {
        $checker = User::factory()->create(['role' => 'checker']);
        
        // Create a mission with checklists
        $mission = \App\Models\Mission::create([
            'title' => 'Test Mission',
            'description' => 'Test Description',
            'property_address' => '123 Test St',
            'checkin_date' => now()->addDay(),
            'checkout_date' => now()->addDays(2),
            'checker_id' => $checker->id,
            'status' => 'approved',
        ]);

        $checklist = $mission->checklists->first();

        $response = $this->actingAs($checker, 'checker')
            ->get(route('checklists.show', $checklist->id));
            
        $response->assertStatus(200)
                 ->assertSee($mission->title)
                 ->assertSee('Checklist for Mission')
                 ->assertSee('Save Checklist')
                 ->assertSee('Submit Checklist');
    }

    public function test_responsive_design_elements()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        
        // Check for responsive design elements
        $response->assertSee('grid-cols-1', false); // Mobile first layout
        $response->assertSee('md:grid-cols-2', false); // Tablet layout
        $response->assertSee('lg:grid-cols-5', false); // Desktop layout
    }

    public function test_mobile_navigation_render()
    {
        $checker = User::factory()->create(['role' => 'checker']);

        $response = $this->actingAs($checker, 'checker')->get(route('checker.dashboard'));
        
        // Check for mobile navigation presence
        $response->assertSee('fixed', false); // Fixed positioning for mobile nav
        $response->assertSee('bottom-0', false); // Bottom positioning
        $response->assertSee('sm:hidden', false); // Hidden on larger screens
    }

    public function test_pwa_meta_tags_present()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        
        $response->assertSee('apple-mobile-web-app-capable', false);
        $response->assertSee('mobile-web-app-capable', false);
        $response->assertSee('manifest.json', false);
    }
}