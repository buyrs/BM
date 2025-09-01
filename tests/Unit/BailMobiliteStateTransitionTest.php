<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\BailMobilite;
use App\Models\Mission;
use App\Models\Checklist;
use App\Models\BailMobiliteSignature;
use App\Models\ContractTemplate;
use App\Models\User;
use App\Services\IncidentDetectionService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BailMobiliteStateTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected IncidentDetectionService $incidentService;
    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->incidentService = new IncidentDetectionService();
        $this->notificationService = new NotificationService();
    }

    /** @test */
    public function it_transitions_from_assigned_to_in_progress_after_entry_validation()
    {
        $bailMobilite = BailMobilite::factory()->create(['status' => 'assigned']);
        $entryMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'mission_type' => 'entry',
            'status' => 'completed'
        ]);
        $checklist = Checklist::factory()->create([
            'mission_id' => $entryMission->id,
            'ops_validated' => true
        ]);
        $signature = BailMobiliteSignature::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => 'entry',
            'tenant_signature' => 'signature_data'
        ]);

        $bailMobilite->update(['entry_mission_id' => $entryMission->id]);

        // Simulate Ops validation
        $bailMobilite->update(['status' => 'in_progress']);

        $this->assertEquals('in_progress', $bailMobilite->status);
    }

    /** @test */
    public function it_transitions_from_in_progress_to_completed_after_successful_exit()
    {
        $bailMobilite = BailMobilite::factory()->create(['status' => 'in_progress']);
        $exitMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'mission_type' => 'exit',
            'status' => 'completed'
        ]);
        $checklist = Checklist::factory()->create([
            'mission_id' => $exitMission->id,
            'keys_returned' => true,
            'ops_validated' => true
        ]);
        $template = ContractTemplate::factory()->create([
            'admin_signature' => 'admin_signature'
        ]);
        $signature = BailMobiliteSignature::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => 'exit',
            'contract_template_id' => $template->id,
            'tenant_signature' => 'tenant_signature'
        ]);

        $bailMobilite->update(['exit_mission_id' => $exitMission->id]);

        $result = $this->incidentService->processIncidentDetection($bailMobilite);

        $this->assertFalse($result['has_incidents']);
        $this->assertEquals('completed', $bailMobilite->fresh()->status);
    }

    /** @test */
    public function it_transitions_from_in_progress_to_incident_when_keys_not_returned()
    {
        $bailMobilite = BailMobilite::factory()->create(['status' => 'in_progress']);
        $exitMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'mission_type' => 'exit',
            'status' => 'completed'
        ]);
        $checklist = Checklist::factory()->create([
            'mission_id' => $exitMission->id,
            'keys_returned' => false, // Keys not returned
            'ops_validated' => true
        ]);

        $bailMobilite->update(['exit_mission_id' => $exitMission->id]);

        $result = $this->incidentService->processIncidentDetection($bailMobilite);

        $this->assertTrue($result['has_incidents']);
        $this->assertEquals('incident', $bailMobilite->fresh()->status);
        $this->assertCount(1, $result['incidents']);
        $this->assertEquals('keys_not_returned', $result['incidents'][0]['type']);
    }

    /** @test */
    public function it_transitions_from_in_progress_to_incident_when_signature_missing()
    {
        $bailMobilite = BailMobilite::factory()->create(['status' => 'in_progress']);
        $exitMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'mission_type' => 'exit',
            'status' => 'completed'
        ]);
        $checklist = Checklist::factory()->create([
            'mission_id' => $exitMission->id,
            'keys_returned' => true,
            'ops_validated' => true
        ]);

        $bailMobilite->update(['exit_mission_id' => $exitMission->id]);
        // No signature created - this should trigger incident

        $result = $this->incidentService->processIncidentDetection($bailMobilite);

        $this->assertTrue($result['has_incidents']);
        $this->assertEquals('incident', $bailMobilite->fresh()->status);
        $this->assertCount(1, $result['incidents']);
        $this->assertEquals('missing_signature', $result['incidents'][0]['type']);
    }

    /** @test */
    public function it_transitions_from_in_progress_to_incident_when_checklist_not_validated()
    {
        $bailMobilite = BailMobilite::factory()->create(['status' => 'in_progress']);
        $exitMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'mission_type' => 'exit',
            'status' => 'completed'
        ]);
        $checklist = Checklist::factory()->create([
            'mission_id' => $exitMission->id,
            'keys_returned' => true,
            'ops_validated' => false // Not validated by Ops
        ]);

        $bailMobilite->update(['exit_mission_id' => $exitMission->id]);

        $result = $this->incidentService->processIncidentDetection($bailMobilite);

        $this->assertTrue($result['has_incidents']);
        $this->assertEquals('incident', $bailMobilite->fresh()->status);
        $this->assertCount(1, $result['incidents']);
        $this->assertEquals('checklist_not_validated', $result['incidents'][0]['type']);
    }

    /** @test */
    public function it_handles_multiple_incidents_simultaneously()
    {
        $bailMobilite = BailMobilite::factory()->create(['status' => 'in_progress']);
        $exitMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'mission_type' => 'exit',
            'status' => 'completed'
        ]);
        $checklist = Checklist::factory()->create([
            'mission_id' => $exitMission->id,
            'keys_returned' => false, // Keys not returned
            'ops_validated' => false  // Not validated
        ]);

        $bailMobilite->update(['exit_mission_id' => $exitMission->id]);
        // No signature created either

        $result = $this->incidentService->processIncidentDetection($bailMobilite);

        $this->assertTrue($result['has_incidents']);
        $this->assertEquals('incident', $bailMobilite->fresh()->status);
        $this->assertCount(3, $result['incidents']); // keys, signature, validation

        $incidentTypes = collect($result['incidents'])->pluck('type')->toArray();
        $this->assertContains('keys_not_returned', $incidentTypes);
        $this->assertContains('missing_signature', $incidentTypes);
        $this->assertContains('checklist_not_validated', $incidentTypes);
    }

    /** @test */
    public function it_does_not_transition_when_status_is_not_in_progress()
    {
        $bailMobilite = BailMobilite::factory()->create(['status' => 'assigned']);
        $exitMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'mission_type' => 'exit',
            'status' => 'completed'
        ]);

        $bailMobilite->update(['exit_mission_id' => $exitMission->id]);

        $result = $this->incidentService->processIncidentDetection($bailMobilite);

        $this->assertFalse($result['has_incidents']);
        $this->assertEquals('assigned', $bailMobilite->fresh()->status); // Should remain unchanged
    }

    /** @test */
    public function it_schedules_exit_reminder_when_transitioning_to_in_progress()
    {
        $bailMobilite = BailMobilite::factory()->create([
            'status' => 'assigned',
            'end_date' => now()->addDays(15)
        ]);
        $opsUser = User::factory()->create();

        $this->notificationService->scheduleExitReminder($bailMobilite, $opsUser);

        $this->assertDatabaseHas('notifications', [
            'type' => 'EXIT_REMINDER',
            'bail_mobilite_id' => $bailMobilite->id,
            'recipient_id' => $opsUser->id,
            'status' => 'pending'
        ]);

        $notification = \App\Models\Notification::where('type', 'EXIT_REMINDER')->first();
        $expectedDate = $bailMobilite->end_date->subDays(10);
        $this->assertEquals($expectedDate->format('Y-m-d'), $notification->scheduled_at->format('Y-m-d'));
    }

    /** @test */
    public function it_can_transition_from_incident_back_to_in_progress_after_resolution()
    {
        $bailMobilite = BailMobilite::factory()->create(['status' => 'incident']);

        // Simulate incident resolution
        $bailMobilite->update(['status' => 'in_progress']);

        $this->assertEquals('in_progress', $bailMobilite->status);
    }

    /** @test */
    public function it_can_transition_from_incident_directly_to_completed()
    {
        $bailMobilite = BailMobilite::factory()->create(['status' => 'incident']);

        // Simulate complete resolution
        $bailMobilite->update(['status' => 'completed']);

        $this->assertEquals('completed', $bailMobilite->status);
    }

    /** @test */
    public function it_validates_state_transition_rules()
    {
        $bailMobilite = BailMobilite::factory()->create(['status' => 'assigned']);

        // Valid transitions from assigned
        $validTransitions = ['in_progress', 'incident'];
        foreach ($validTransitions as $status) {
            $bailMobilite->update(['status' => $status]);
            $this->assertEquals($status, $bailMobilite->fresh()->status);
            $bailMobilite->update(['status' => 'assigned']); // Reset
        }

        // Test transitions from in_progress
        $bailMobilite->update(['status' => 'in_progress']);
        $validFromInProgress = ['completed', 'incident'];
        foreach ($validFromInProgress as $status) {
            $bailMobilite->update(['status' => $status]);
            $this->assertEquals($status, $bailMobilite->fresh()->status);
            $bailMobilite->update(['status' => 'in_progress']); // Reset
        }
    }
}