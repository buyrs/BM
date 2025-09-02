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
        
        $bailMobilite1 = BailMobilite::factory()->create([
            'tenant_name' => 'John Doe',
            'address' => '123 Main Street',
        ]);
        
        $bailMobilite2 = BailMobilite::factory()->create([
            'tenant_name' => 'Jane Smith',
            'address' => '456 Oak Avenue',
        ]);
        
        $matchingMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite1->id,
            'scheduled_at' => $startDate->copy()->addDays(5),
            'tenant_name' => 'John Doe',
            'address' => '123 Main Street',
        ]);
        
        $nonMatchingMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite2->id,
            'scheduled_at' => $startDate->copy()->addDays(6),
            'tenant_name' => 'Jane Smith',
            'address' => '456 Oak Avenue',
        ]);
        
        $missions = $this->calendarService->getMissionsForDateRange(
            $startDate, 
            $endDate, 
            ['search' => 'John']
        );
        
        // The search should find missions that match 'John' in tenant_name, address, or related models
        // Since both missions have BailMobilite records, and one BailMobilite has 'John Doe', 
        // the search will find the mission associated with that BailMobilite
        $this->assertGreaterThanOrEqual(1, $missions->count());
        $this->assertTrue($missions->contains('id', $matchingMission->id));
    }

    public function test_get_missions_for_date_range_applies_date_range_filter_today()
    {
        $today = Carbon::now()->startOfDay();
        $tomorrow = $today->copy()->addDay();
        
        $bailMobilite = BailMobilite::factory()->create();
        
        $todayMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => $today,
        ]);
        
        $tomorrowMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => $tomorrow,
        ]);
        
        $missions = $this->calendarService->getMissionsForDateRange(
            $today->copy()->startOfMonth(),
            $today->copy()->endOfMonth(),
            ['date_range' => 'today']
        );
        
        $this->assertCount(1, $missions);
        $this->assertTrue($missions->contains('id', $todayMission->id));
        $this->assertFalse($missions->contains('id', $tomorrowMission->id));
    }

    public function test_get_missions_for_date_range_applies_date_range_filter_overdue()
    {
        $yesterday = Carbon::now()->subDay();
        $today = Carbon::now();
        
        $bailMobilite = BailMobilite::factory()->create();
        
        $overdueMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => $yesterday,
            'status' => 'assigned', // Not completed
        ]);
        
        $completedOverdueMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => $yesterday,
            'status' => 'completed', // Should not be included
        ]);
        
        $todayMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => $today,
            'status' => 'assigned',
        ]);
        
        $missions = $this->calendarService->getMissionsForDateRange(
            $yesterday->copy()->startOfMonth(),
            $today->copy()->endOfMonth(),
            ['date_range' => 'overdue']
        );
        
        $this->assertCount(1, $missions);
        $this->assertTrue($missions->contains('id', $overdueMission->id));
        $this->assertFalse($missions->contains('id', $completedOverdueMission->id));
        $this->assertFalse($missions->contains('id', $todayMission->id));
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

    public function test_format_missions_for_calendar_handles_empty_collection()
    {
        $formatted = $this->calendarService->formatMissionsForCalendar(collect());
        
        $this->assertIsArray($formatted);
        $this->assertEmpty($formatted);
    }

    public function test_format_missions_for_calendar_handles_mission_without_agent()
    {
        $bailMobilite = BailMobilite::factory()->create();
        $mission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'agent_id' => null,
        ]);
        
        $mission->load(['agent', 'bailMobilite', 'checklist', 'opsAssignedBy']);
        
        $formatted = $this->calendarService->formatMissionsForCalendar(collect([$mission]));
        
        $this->assertCount(1, $formatted);
        $this->assertNull($formatted[0]['agent']);
    }

    public function test_format_missions_for_calendar_handles_mission_without_bail_mobilite()
    {
        $mission = Mission::factory()->create([
            'bail_mobilite_id' => null,
        ]);
        
        $mission->load(['agent', 'bailMobilite', 'checklist', 'opsAssignedBy']);
        
        $formatted = $this->calendarService->formatMissionsForCalendar(collect([$mission]));
        
        $this->assertCount(1, $formatted);
        $this->assertNull($formatted[0]['bail_mobilite']);
    }

    public function test_format_missions_for_calendar_handles_time_formats()
    {
        $bailMobilite = BailMobilite::factory()->create();
        
        // Test with string time format
        $mission1 = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_time' => '10:30:00',
        ]);
        
        // Test with null time
        $mission2 = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_time' => null,
        ]);
        
        $missions = collect([$mission1, $mission2]);
        $missions->each(function ($mission) {
            $mission->load(['agent', 'bailMobilite', 'checklist', 'opsAssignedBy']);
        });
        
        $formatted = $this->calendarService->formatMissionsForCalendar($missions);
        
        $this->assertCount(2, $formatted);
        $this->assertEquals('10:30', $formatted[0]['scheduled_time']);
        $this->assertNull($formatted[1]['scheduled_time']);
    }

    public function test_create_bail_mobilite_mission_handles_minimal_data()
    {
        $data = [
            'start_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'end_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
            'address' => '123 Test Street',
            'tenant_name' => 'Test Tenant',
        ];
        
        $bailMobilite = $this->calendarService->createBailMobiliteMission($data);
        
        $this->assertInstanceOf(BailMobilite::class, $bailMobilite);
        $this->assertEquals('assigned', $bailMobilite->status);
        $this->assertEquals(Auth::id(), $bailMobilite->ops_user_id);
        
        // Check missions were created with unassigned status
        $this->assertEquals('unassigned', $bailMobilite->entryMission->status);
        $this->assertEquals('unassigned', $bailMobilite->exitMission->status);
        $this->assertNull($bailMobilite->entryMission->agent_id);
        $this->assertNull($bailMobilite->exitMission->agent_id);
    }

    public function test_get_missions_for_date_range_handles_complex_filters()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        $bailMobilite = BailMobilite::factory()->create([
            'tenant_name' => 'John Doe',
            'address' => '123 Main Street',
        ]);
        
        $mission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => $startDate->copy()->addDays(5),
            'status' => 'assigned',
            'mission_type' => 'entry',
            'agent_id' => $this->checkerUser->id,
            'tenant_name' => 'John Doe',
            'address' => '123 Main Street',
        ]);
        
        // Test multiple filters combined
        $missions = $this->calendarService->getMissionsForDateRange(
            $startDate, 
            $endDate, 
            [
                'status' => ['assigned'],
                'checker_id' => $this->checkerUser->id,
                'mission_type' => ['entry'],
                'search' => 'John'
            ]
        );
        
        $this->assertCount(1, $missions);
        $this->assertTrue($missions->contains('id', $mission->id));
    }

    public function test_get_missions_for_date_range_handles_array_status_filter()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        $bailMobilite = BailMobilite::factory()->create();
        
        $assignedMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => $startDate->copy()->addDays(5),
            'status' => 'assigned',
        ]);
        
        $inProgressMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => $startDate->copy()->addDays(6),
            'status' => 'in_progress',
        ]);
        
        $completedMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => $startDate->copy()->addDays(7),
            'status' => 'completed',
        ]);
        
        $missions = $this->calendarService->getMissionsForDateRange(
            $startDate, 
            $endDate, 
            ['status' => ['assigned', 'in_progress']]
        );
        
        $this->assertCount(2, $missions);
        $this->assertTrue($missions->contains('id', $assignedMission->id));
        $this->assertTrue($missions->contains('id', $inProgressMission->id));
        $this->assertFalse($missions->contains('id', $completedMission->id));
    }

    public function test_get_missions_for_date_range_handles_mission_type_array_filter()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        $bailMobilite = BailMobilite::factory()->create();
        
        $entryMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => $startDate->copy()->addDays(5),
            'mission_type' => 'entry',
        ]);
        
        $exitMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => $startDate->copy()->addDays(6),
            'mission_type' => 'exit',
        ]);
        
        $missions = $this->calendarService->getMissionsForDateRange(
            $startDate, 
            $endDate, 
            ['mission_type' => ['entry']]
        );
        
        $this->assertCount(1, $missions);
        $this->assertTrue($missions->contains('id', $entryMission->id));
        $this->assertFalse($missions->contains('id', $exitMission->id));
    }

    public function test_get_missions_for_date_range_handles_search_in_related_models()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        $bailMobilite = BailMobilite::factory()->create([
            'tenant_name' => 'Jane Smith',
            'address' => '456 Oak Avenue',
        ]);
        
        $mission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'scheduled_at' => $startDate->copy()->addDays(5),
            'agent_id' => $this->checkerUser->id,
        ]);
        
        // Search by agent name
        $missions = $this->calendarService->getMissionsForDateRange(
            $startDate, 
            $endDate, 
            ['search' => $this->checkerUser->name]
        );
        
        $this->assertCount(1, $missions);
        $this->assertTrue($missions->contains('id', $mission->id));
        
        // Search by bail mobilite tenant name
        $missions = $this->calendarService->getMissionsForDateRange(
            $startDate, 
            $endDate, 
            ['search' => 'Jane']
        );
        
        $this->assertCount(1, $missions);
        $this->assertTrue($missions->contains('id', $mission->id));
    }

    public function test_detect_scheduling_conflicts_excludes_specified_mission()
    {
        $date = Carbon::now()->addDays(1);
        $time = '10:00';
        
        // Create an existing mission at the same time
        $bailMobilite = BailMobilite::factory()->create();
        $existingMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'agent_id' => $this->checkerUser->id,
            'scheduled_at' => $date,
            'scheduled_time' => $time,
        ]);
        
        // Test without exclusion - should find conflict
        $conflicts = $this->calendarService->detectSchedulingConflicts($date, $time, $this->checkerUser->id);
        $this->assertNotEmpty($conflicts);
        
        // Test with exclusion - should not find conflict
        $conflicts = $this->calendarService->detectSchedulingConflicts($date, $time, $this->checkerUser->id, $existingMission->id);
        $this->assertEmpty($conflicts);
    }

    public function test_get_available_time_slots_handles_null_checker()
    {
        $date = Carbon::now()->addDays(1);
        
        $slots = $this->calendarService->getAvailableTimeSlots($date, null);
        
        $this->assertIsArray($slots);
        $this->assertGreaterThan(0, count($slots));
        
        // All slots should be available when no checker specified
        foreach ($slots as $slot) {
            $this->assertTrue($slot['available']);
            $this->assertEmpty($slot['conflicts']);
        }
    }

    public function test_format_missions_for_calendar_includes_permissions()
    {
        $bailMobilite = BailMobilite::factory()->create();
        $mission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'unassigned',
        ]);
        
        $mission->load(['agent', 'bailMobilite', 'checklist', 'opsAssignedBy']);
        
        $formatted = $this->calendarService->formatMissionsForCalendar(collect([$mission]));
        
        $this->assertCount(1, $formatted);
        $this->assertArrayHasKey('can_edit', $formatted[0]);
        $this->assertArrayHasKey('can_assign', $formatted[0]);
        $this->assertIsBool($formatted[0]['can_edit']);
        $this->assertIsBool($formatted[0]['can_assign']);
    }
}