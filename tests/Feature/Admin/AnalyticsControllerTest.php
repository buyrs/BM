<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\IncidentReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Carbon\Carbon;

class AnalyticsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'checker']);
        Role::create(['name' => 'ops']);
        Role::create(['name' => 'super-admin']);

        // Create an admin user
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');
    }

    public function test_it_returns_analytics_data_with_avg_resolution_time()
    {
        // Acting as the admin user
        $this->actingAs($this->adminUser);

        // Create test data for Incident Reports
        IncidentReport::factory()->create([
            'status' => 'resolved',
            'detected_at' => Carbon::now()->subHours(10),
            'resolved_at' => Carbon::now()->subHours(8), // 2 hours resolution time
        ]);

        IncidentReport::factory()->create([
            'status' => 'closed',
            'detected_at' => Carbon::now()->subHours(6),
            'resolved_at' => Carbon::now()->subHours(2), // 4 hours resolution time
        ]);

        IncidentReport::factory()->create([
            'status' => 'open', // This one should not be included in the calculation
            'detected_at' => Carbon::now()->subHours(1),
            'resolved_at' => null,
        ]);

        // Expected average resolution time in hours: (2 + 4) / 2 = 3
        $expectedAvgTime = 3.0;

        // Make a GET request to the analytics endpoint
        $response = $this->getJson(route('admin.analytics.data'));

        // Assert the response is successful
        $response->assertStatus(200);

        // Assert the JSON response contains the avgResolutionTime key
        $response->assertJsonStructure([
            'missionsCreated',
            'missionsCompleted',
            'statusDistribution',
            'checkerPerformance',
            'assignmentEfficiency',
            'avgResolutionTime',
        ]);

        // Assert the value of avgResolutionTime is correct
        $response->assertJson([
            'avgResolutionTime' => $expectedAvgTime,
        ]);
    }
}
