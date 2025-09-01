<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\IncidentDetectionService;
use App\Models\BailMobilite;
use App\Models\Mission;
use App\Models\Checklist;
use App\Models\BailMobiliteSignature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IncidentDetectionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected IncidentDetectionService $incidentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->incidentService = new IncidentDetectionService();
    }

    /** @test */
    public function it_detects_missing_keys_incident()
    {
        $bailMobilite = BailMobilite::factory()->create(['status' => 'in_progress']);
        $exitMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'mission_type' => 'exit',
            'status' => 'completed'
        ]);
        $checklist = Checklist::factory()->create([
            'mission_id' => $exitMission->id,
            'keys_returned' => false
        ]);

        $bailMobilite->update(['exit_mission_id' => $exitMission->id]);

        $incidents = $this->incidentService->detectIncidents($bailMobilite);

        $this->assertCount(1, $incidents);
        $this->assertEquals('keys_not_returned', $incidents[0]['type']);
        $this->assertEquals('Keys were not returned by tenant', $incidents[0]['description']);
    }

    /** @test */
    public function it_detects_missing_signature_incident()
    {
        $bailMobilite = BailMobilite::factory()->create(['status' => 'in_progress']);
        $exitMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'mission_type' => 'exit',
            'status' => 'completed'
        ]);
        $checklist = Checklist::factory()->create([
            'mission_id' => $exitMission->id,
            'keys_returned' => true
        ]);

        $bailMobilite->update(['exit_mission_id' => $exitMission->id]);

        // No signature created for exit
        $incidents = $this->incidentService->detectIncidents($bailMobilite);

        $this->assertCount(1, $incidents);
        $this->assertEquals('missing_signature', $incidents[0]['type']);
        $this->assertEquals('Exit signature is missing', $incidents[0]['description']);
    }

    /** @test */
    public function it_detects_incomplete_checklist_incident()
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
            'ops_validated' => false
        ]);

        $bailMobilite->update(['exit_mission_id' => $exitMission->id]);

        $incidents = $this->incidentService->detectIncidents($bailMobilite);

        $this->assertCount(1, $incidents);
        $this->assertEquals('checklist_not_validated', $incidents[0]['type']);
        $this->assertEquals('Exit checklist was not validated by Ops', $incidents[0]['description']);
    }

    /** @test */
    public function it_detects_multiple_incidents()
    {
        $bailMobilite = BailMobilite::factory()->create(['status' => 'in_progress']);
        $exitMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'mission_type' => 'exit',
            'status' => 'completed'
        ]);
        $checklist = Checklist::factory()->create([
            'mission_id' => $exitMission->id,
            'keys_returned' => false,
            'ops_validated' => false
        ]);

        $bailMobilite->update(['exit_mission_id' => $exitMission->id]);

        $incidents = $this->incidentService->detectIncidents($bailMobilite);

        $this->assertCount(3, $incidents); // keys_not_returned, missing_signature, checklist_not_validated
        
        $incidentTypes = collect($incidents)->pluck('type')->toArray();
        $this->assertContains('keys_not_returned', $incidentTypes);
        $this->assertContains('missing_signature', $incidentTypes);
        $this->assertContains('checklist_not_validated', $incidentTypes);
    }

    /** @test */
    public function it_returns_no_incidents_when_everything_is_complete()
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
        $signature = BailMobiliteSignature::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => 'exit',
            'tenant_signature' => 'signature_data'
        ]);

        $bailMobilite->update(['exit_mission_id' => $exitMission->id]);

        $incidents = $this->incidentService->detectIncidents($bailMobilite);

        $this->assertCount(0, $incidents);
    }

    /** @test */
    public function it_can_auto_transition_to_incident_status()
    {
        $bailMobilite = BailMobilite::factory()->create(['status' => 'in_progress']);
        $exitMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'mission_type' => 'exit',
            'status' => 'completed'
        ]);
        $checklist = Checklist::factory()->create([
            'mission_id' => $exitMission->id,
            'keys_returned' => false
        ]);

        $bailMobilite->update(['exit_mission_id' => $exitMission->id]);

        $result = $this->incidentService->processIncidentDetection($bailMobilite);

        $this->assertTrue($result['has_incidents']);
        $this->assertEquals('incident', $bailMobilite->fresh()->status);
        $this->assertCount(1, $result['incidents']);
    }

    /** @test */
    public function it_can_auto_transition_to_completed_status()
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
        $signature = BailMobiliteSignature::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => 'exit',
            'tenant_signature' => 'signature_data'
        ]);

        $bailMobilite->update(['exit_mission_id' => $exitMission->id]);

        $result = $this->incidentService->processIncidentDetection($bailMobilite);

        $this->assertFalse($result['has_incidents']);
        $this->assertEquals('completed', $bailMobilite->fresh()->status);
        $this->assertCount(0, $result['incidents']);
    }

    /** @test */
    public function it_does_not_process_incidents_for_non_in_progress_bail_mobilite()
    {
        $bailMobilite = BailMobilite::factory()->create(['status' => 'assigned']);

        $result = $this->incidentService->processIncidentDetection($bailMobilite);

        $this->assertFalse($result['has_incidents']);
        $this->assertEquals('assigned', $bailMobilite->fresh()->status);
    }

    /** @test */
    public function it_can_create_corrective_actions_for_incidents()
    {
        $bailMobilite = BailMobilite::factory()->create(['status' => 'incident']);
        $opsUser = User::factory()->create();
        
        $incidents = [
            ['type' => 'keys_not_returned', 'description' => 'Keys not returned'],
            ['type' => 'missing_signature', 'description' => 'Signature missing']
        ];

        $this->incidentService->createCorrectiveActions($bailMobilite, $incidents, $opsUser);

        $this->assertDatabaseHas('corrective_actions', [
            'bail_mobilite_id' => $bailMobilite->id,
            'incident_type' => 'keys_not_returned',
            'assigned_to' => $opsUser->id,
            'status' => 'pending'
        ]);

        $this->assertDatabaseHas('corrective_actions', [
            'bail_mobilite_id' => $bailMobilite->id,
            'incident_type' => 'missing_signature',
            'assigned_to' => $opsUser->id,
            'status' => 'pending'
        ]);
    }
}