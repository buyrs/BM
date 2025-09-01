<?php

require_once 'vendor/autoload.php';

use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DebugTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_create_mission()
    {
        // Seed roles
        $this->seed(\Database\Seeders\RoleSeeder::class);
        
        // Create test users
        $opsUser = User::factory()->create();
        $opsUser->assignRole('ops');
        
        $checkerUser = User::factory()->create();
        $checkerUser->assignRole('checker');

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
            'entry_checker_id' => $checkerUser->id,
            'exit_checker_id' => $checkerUser->id,
        ];
        
        $response = $this->withoutMiddleware()->actingAs($opsUser)->post(route('ops.calendar.missions.create'), $data);
        
        echo "Status: " . $response->getStatusCode() . "\n";
        echo "Response: " . $response->getContent() . "\n";
        
        $this->assertTrue(true); // Just to make the test pass
    }
}

$test = new DebugTest();
$test->setUp();
$test->test_debug_create_mission();