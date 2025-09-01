<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\CalendarService;
use App\Models\BailMobilite;
use App\Models\Mission;
use App\Models\User;
use App\Models\Agent;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class CalendarServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CalendarService $calendarService;
    protected User $opsUser;
    protected User $checkerUser;
    protected Agent $agent;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles
        $this->seed(\Database\Seeders\RoleSeeder::class);
        
        $this->calendarService = new CalendarService();
        
        // Create test users
        $this->opsUser = User::factory()->create();
        $this->opsUser->assignRole('ops');
        
        $this->checkerUser = User::factory()->create();
        $this->checkerUser->assignRole('checker');
        
        // Note: agent_id in missions refers directly to user_id, not to Agent model
        
        Auth::login($this->opsUser);
    }

    public function test_get_missions_for_date_range_returns_missions_within_range()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        // Create a bail mobilité with missions
        $bailMobilite = BailMobilite::factory()->create([
            'start_date' => $startDate->copy()->addDays(5),
            'end_date' => $startDate->copy()->addDays(10),
        ]);
        
        $entryMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'mission_type' => 'entry',
            'scheduled_at' => $startDate->copy()->addDays(5),
            'agent_id' => $this->checkerUser->id,
        ]);
        
        $exitMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'mission_type' => 'exit',
            'scheduled_at' => $startDate->copy()->addDays(10),
            'agent_id' => $this->checkerUser->id,
        ]);
        
        // Create a mission outside the range (should not be returned)
        Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'mission_type' => 'entry',
            'scheduled_at' => $endDate->copy()->addDays(5),
        ]);
        
        $missions = $this->calendarService->getMissionsForDateRange($startDate, $endDate);
        
        $this->assertCount(2, $missions);
        $this->assertTrue($missions->contains('id', $entryMission->id));
        $this->assertTrue($missions->contains('id', $exitMission->id));
    }

    public function test_get_missions_for_date_range_applies_status_filter()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        $bailMobilite = BailMobilite::factory()->create();
        
        $assignedMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => $startDate->copy()->addDays(5),
            'status' => 'assigned',
        ]);
        
        $completedMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => $startDate->copy()->addDays(6),
            'status' => 'completed',
        ]);
        
        $missions = $this->calendarService->getMissionsForDateRange(
            $startDate, 
            $endDate, 
            ['status' => ['assigned']]
        );
        
        $this->assertCount(1, $missions);
        $this->assertTrue($missions->contains('id', $assignedMission->id));
        $this->assertFalse($missions->contains('id', $completedMission->id));
    }

    public function test_get_missions_for_date_range_applies_checker_filter()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        $bailMobilite = BailMobilite::factory()->create();
        
        $missionWithChecker = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => $startDate->copy()->addDays(5),
            'agent_id' => $this->checkerUser->id,
        ]);
        
        $missionWithoutChecker = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => $startDate->copy()->addDays(6),
            'agent_id' => null,
        ]);
        
        $missions = $this->calendarService->getMissionsForDateRange(
            $startDate, 
            $endDate, 
            ['checker_id' => $this->checkerUser->id]
        );
        
        $this->assertCount(1, $missions);
        $this->assertTrue($missions->contains('id', $missionWithChecker->id));
        $this->assertFalse($missions->contains('id', $missionWithoutChecker->id));
    }

    public function test_get_missions_for_date_range_applies_search_filter()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        $bailMobilite = BailMobilite::factory()->create([
            'tenant_name' => 'John Doe',
            'address' => '123 Main Street',
        ]);
        
        $matchingMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => $startDate->copy()->addDays(5),
            'tenant_name' => 'John Doe',
            'address' => '123 Main Street',
        ]);
        
        $nonMatchingMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => $startDate->copy()->addDays(6),
            'tenant_name' => 'Jane Smith',
            'address' => '456 Oak Avenue',
        ]);
        
        $missions = $this->calendarService->getMissionsForDateRange(
            $startDate, 
            $endDate, 
            ['search' => 'John']
        );
        
        $this->assertCount(1, $missions);
        $this->assertTrue($missions->contains('id', $matchingMission->id));
        $this->assertFalse($missions->contains('id', $nonMatchingMission->id));
    }

    public function test_format_missions_for_calendar_returns_correct_structure()
    {
        $bailMobilite = BailMobilite::factory()->create([
            'start_date' => Carbon::now()->addDays(1),
            'end_date' => Carbon::now()->addDays(5),
        ]);
        
        $mission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'mission_type' => 'entry',
            'scheduled_at' => Carbon::now()->addDays(1),
            'scheduled_time' => '10:00',
            'status' => 'assigned',
            'agent_id' => $this->checkerUser->id,
        ]);
        
        $mission->load(['agent', 'bailMobilite', 'checklist', 'opsAssignedBy']);
        
        $formatted = $this->calendarService->formatMissionsForCalendar(collect([$mission]));
        
        $this->assertCount(1, $formatted);
        $formattedMission = $formatted[0];
        
        $this->assertEquals($mission->id, $formattedMission['id']);
        $this->assertEquals('entry', $formattedMission['type']);
        $this->assertEquals($mission->scheduled_at->format('Y-m-d'), $formattedMission['scheduled_at']);
        $this->assertEquals('10:00', $formattedMission['scheduled_time']);
        $this->assertEquals('assigned', $formattedMission['status']);
        $this->assertArrayHasKey('agent', $formattedMission);
        $this->assertArrayHasKey('bail_mobilite', $formattedMission);
        $this->assertArrayHasKey('conflicts', $formattedMission);
        $this->assertArrayHasKey('can_edit', $formattedMission);
        $this->assertArrayHasKey('can_assign', $formattedMission);
    }

    public function test_create_bail_mobilite_mission_creates_entry_and_exit_missions()
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
        
        $bailMobilite = $this->calendarService->createBailMobiliteMission($data);
        
        $this->assertInstanceOf(BailMobilite::class, $bailMobilite);
        $this->assertEquals($data['address'], $bailMobilite->address);
        $this->assertEquals($data['tenant_name'], $bailMobilite->tenant_name);
        $this->assertEquals('assigned', $bailMobilite->status);
        
        // Check entry mission
        $this->assertNotNull($bailMobilite->entryMission);
        $this->assertEquals('entry', $bailMobilite->entryMission->mission_type);
        $this->assertEquals('10:00:00', $bailMobilite->entryMission->scheduled_time);
        $this->assertEquals('assigned', $bailMobilite->entryMission->status);
        
        // Check exit mission
        $this->assertNotNull($bailMobilite->exitMission);
        $this->assertEquals('exit', $bailMobilite->exitMission->mission_type);
        $this->assertEquals('14:00:00', $bailMobilite->exitMission->scheduled_time);
        $this->assertEquals('assigned', $bailMobilite->exitMission->status);
    }

    public function test_get_available_time_slots_returns_standard_slots_when_no_checker()
    {
        $date = Carbon::now()->addDays(1);
        
        $slots = $this->calendarService->getAvailableTimeSlots($date);
        
        $this->assertIsArray($slots);
        $this->assertGreaterThan(0, count($slots));
        
        // Check that all slots are marked as available
        foreach ($slots as $slot) {
            $this->assertArrayHasKey('time', $slot);
            $this->assertArrayHasKey('available', $slot);
            $this->assertArrayHasKey('conflicts', $slot);
            $this->assertTrue($slot['available']);
        }
    }

    public function test_get_available_time_slots_marks_booked_slots_unavailable()
    {
        $date = Carbon::now()->addDays(1);
        $bookedTime = '10:00';
        
        // Create a mission at the booked time
        $bailMobilite = BailMobilite::factory()->create();
        Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'agent_id' => $this->checkerUser->id,
            'scheduled_at' => $date,
            'scheduled_time' => $bookedTime,
        ]);
        
        $slots = $this->calendarService->getAvailableTimeSlots($date, $this->checkerUser->id);
        
        $bookedSlot = collect($slots)->firstWhere('time', $bookedTime);
        $this->assertNotNull($bookedSlot);
        $this->assertFalse($bookedSlot['available']);
        $this->assertNotEmpty($bookedSlot['conflicts']);
    }

    public function test_detect_scheduling_conflicts_finds_time_conflicts()
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
            'tenant_name' => 'Existing Tenant',
            'address' => '456 Conflict Street',
        ]);
        
        $conflicts = $this->calendarService->detectSchedulingConflicts($date, $time, $this->checkerUser->id);
        
        $this->assertNotEmpty($conflicts);
        $this->assertStringContainsString('Conflit avec mission', $conflicts[0]);
    }

    public function test_detect_scheduling_conflicts_detects_outside_business_hours()
    {
        $date = Carbon::now()->addDays(1);
        $earlyTime = '08:00'; // Before 9 AM
        $lateTime = '20:00';  // After 7 PM
        
        $earlyConflicts = $this->calendarService->detectSchedulingConflicts($date, $earlyTime, $this->checkerUser->id);
        $lateConflicts = $this->calendarService->detectSchedulingConflicts($date, $lateTime, $this->checkerUser->id);
        
        $this->assertNotEmpty($earlyConflicts);
        $this->assertNotEmpty($lateConflicts);
        $this->assertStringContainsString('heures d\'ouverture', $earlyConflicts[0]);
        $this->assertStringContainsString('heures d\'ouverture', $lateConflicts[0]);
    }

    public function test_detect_scheduling_conflicts_detects_weekend_scheduling()
    {
        // Find next Saturday
        $saturday = Carbon::now()->next(Carbon::SATURDAY);
        $time = '10:00';
        
        $conflicts = $this->calendarService->detectSchedulingConflicts($saturday, $time, $this->checkerUser->id);
        
        $this->assertNotEmpty($conflicts);
        $this->assertStringContainsString('week-end', $conflicts[0]);
    }
}