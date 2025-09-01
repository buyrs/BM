<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Agent;
use App\Models\BailMobilite;
use App\Models\Mission;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\ContractTemplate;
use App\Models\BailMobiliteSignature;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Services\IncidentDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class BailMobiliteWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $opsUser;
    protected User $checker;
    protected User $admin;
    protected Agent $checkerAgent;
    protected ContractTemplate $entryTemplate;
    protected ContractTemplate $exitTemplate;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles and permissions
        $this->createRolesAndPermissions();
        
        // Create users
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
        
        $this->opsUser = User::factory()->create();
        $this->opsUser->assignRole('ops');
        
        $this->checker = User::factory()->create();
        $this->checker->assignRole('checker');
        
        // Create checker agent
        $this->checkerAgent = Agent::factory()->create(['user_id' => $this->checker->id]);
        
        // Create contract templates
        $this->entryTemplate = ContractTemplate::factory()->create([
            'type' => 'entry',
            'admin_signature' => 'admin_signature_data',
            'admin_signed_at' => now(),
            'is_active' => true,
            'created_by' => $this->admin->id
        ]);
        
        $this->exitTemplate = ContractTemplate::factory()->create([
            'type' => 'exit',
            'admin_signature' => 'admin_signature_data',
            'admin_signed_at' => now(),
            'is_active' => true,
            'created_by' => $this->admin->id
        ]);
    }

    protected function createRolesAndPermissions(): void
    {
        // Create roles
        Role::create(['name' => 'super-admin']);
        Role::create(['name' => 'ops']);
        Role::create(['name' => 'checker']);
        
        // Create permissions
        $permissions = [
            'create_bail_mobilite',
            'edit_bail_mobilite',
            'assign_missions',
            'validate_checklists',
            'view_ops_dashboard',
            'manage_incidents',
            'complete_missions',
            'fill_checklists'
        ];
        
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
        
        // Assign permissions to roles
        $opsRole = Role::findByName('ops');
        $opsRole->givePermissionTo([
            'create_bail_mobilite',
            'edit_bail_mobilite',
            'assign_missions',
            'validate_checklists',
            'view_ops_dashboard',
            'manage_incidents'
        ]);
        
        $checkerRole = Role::findByName('checker');
        $checkerRole->givePermissionTo([
            'complete_missions',
            'fill_checklists'
        ]);
    }

    /** @test */
    public function it_can_complete_full_bail_mobilite_workflow_successfully()
    {
        // Step 1: Ops creates a Bail Mobilité
        $this->actingAs($this->opsUser);
        
        $bailMobiliteData = [
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28',
            'address' => '123 Test Street, Paris',
            'tenant_name' => 'John Doe',
            'tenant_phone' => '+33123456789',
            'tenant_email' => 'john.doe@example.com',
            'notes' => 'Test bail mobilité'
        ];
        
        $response = $this->post('/bail-mobilites', $bailMobiliteData);
        $response->assertStatus(302); // Redirect after creation
        
        $bailMobilite = BailMobilite::where('tenant_name', 'John Doe')->first();
        $this->assertNotNull($bailMobilite);
        $this->assertEquals('assigned', $bailMobilite->status);
        $this->assertEquals($this->opsUser->id, $bailMobilite->ops_user_id);
        
        // Check that entry and exit missions were created
        $this->assertNotNull($bailMobilite->entry_mission_id);
        $this->assertNotNull($bailMobilite->exit_mission_id);
        
        $entryMission = $bailMobilite->entryMission;
        $exitMission = $bailMobilite->exitMission;
        
        $this->assertEquals('entry', $entryMission->mission_type);
        $this->assertEquals('exit', $exitMission->mission_type);
        $this->assertEquals('2025-02-01', $entryMission->scheduled_date->format('Y-m-d'));
        $this->assertEquals('2025-02-28', $exitMission->scheduled_date->format('Y-m-d'));

        // Step 2: Ops assigns entry mission to checker
        $assignmentData = [
            'checker_id' => $this->checker->id,
            'scheduled_time' => '14:00'
        ];
        
        $response = $this->post("/bail-mobilites/{$bailMobilite->id}/assign-entry", $assignmentData);
        $response->assertStatus(200);
        
        $entryMission->refresh();
        $this->assertEquals($this->checkerAgent->id, $entryMission->agent_id);
        $this->assertEquals('14:00:00', $entryMission->scheduled_time);
        $this->assertEquals($this->opsUser->id, $entryMission->ops_assigned_by);
        
        // Check that checker was notified
        $this->assertDatabaseHas('notifications', [
            'type' => 'MISSION_ASSIGNED',
            'recipient_id' => $this->checker->id,
            'bail_mobilite_id' => $bailMobilite->id
        ]);

        // Step 3: Checker completes entry mission with checklist and signature
        $this->actingAs($this->checker);
        
        // Create checklist for the mission
        $checklist = Checklist::factory()->create([
            'mission_id' => $entryMission->id,
            'tenant_comment' => 'Everything looks good',
            'ops_validated' => false
        ]);
        
        // Add checklist items
        ChecklistItem::factory()->create([
            'checklist_id' => $checklist->id,
            'item_name' => 'Keys received',
            'status' => 'ok',
            'comment' => 'All keys received'
        ]);
        
        // Complete the mission
        $response = $this->patch("/missions/{$entryMission->id}/complete", [
            'completion_notes' => 'Entry completed successfully'
        ]);
        $response->assertStatus(200);
        
        $entryMission->refresh();
        $this->assertEquals('completed', $entryMission->status);
        
        // Create entry signature
        $entrySignature = BailMobiliteSignature::create([
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => 'entry',
            'contract_template_id' => $this->entryTemplate->id,
            'tenant_signature' => 'tenant_signature_data',
            'tenant_signed_at' => now(),
            'contract_pdf_path' => 'contracts/entry_' . $bailMobilite->id . '.pdf'
        ]);
        
        // Check that Ops was notified for validation
        $this->assertDatabaseHas('notifications', [
            'type' => 'CHECKLIST_VALIDATION',
            'recipient_id' => $this->opsUser->id,
            'bail_mobilite_id' => $bailMobilite->id
        ]);

        // Step 4: Ops validates entry and transitions to in_progress
        $this->actingAs($this->opsUser);
        
        $response = $this->post("/bail-mobilites/{$bailMobilite->id}/validate-entry", [
            'validation_notes' => 'Entry validated successfully'
        ]);
        $response->assertStatus(200);
        
        $bailMobilite->refresh();
        $this->assertEquals('in_progress', $bailMobilite->status);
        
        $checklist->refresh();
        $this->assertTrue($checklist->ops_validated);
        
        // Check that exit reminder notification was scheduled
        $this->assertDatabaseHas('notifications', [
            'type' => 'EXIT_REMINDER',
            'bail_mobilite_id' => $bailMobilite->id,
            'recipient_id' => $this->opsUser->id,
            'status' => 'pending'
        ]);

        // Step 5: Simulate time passing - exit reminder notification
        $notificationService = new NotificationService();
        
        // Update the scheduled notification to be due now
        $exitReminder = Notification::where('type', 'EXIT_REMINDER')
            ->where('bail_mobilite_id', $bailMobilite->id)
            ->first();
        $exitReminder->update(['scheduled_at' => now()->subMinute()]);
        
        $notificationService->processScheduledNotifications();
        
        $exitReminder->refresh();
        $this->assertEquals('sent', $exitReminder->status);

        // Step 6: Ops assigns exit mission to checker
        $exitAssignmentData = [
            'checker_id' => $this->checker->id,
            'scheduled_time' => '11:00'
        ];
        
        $response = $this->post("/bail-mobilites/{$bailMobilite->id}/assign-exit", $exitAssignmentData);
        $response->assertStatus(200);
        
        $exitMission->refresh();
        $this->assertEquals($this->checkerAgent->id, $exitMission->agent_id);
        $this->assertEquals('11:00:00', $exitMission->scheduled_time);

        // Step 7: Checker completes exit mission
        $this->actingAs($this->checker);
        
        // Create exit checklist
        $exitChecklist = Checklist::factory()->create([
            'mission_id' => $exitMission->id,
            'tenant_comment' => 'Exit completed',
            'keys_returned' => true,
            'ops_validated' => false
        ]);
        
        ChecklistItem::factory()->create([
            'checklist_id' => $exitChecklist->id,
            'item_name' => 'Keys returned',
            'status' => 'ok',
            'comment' => 'All keys returned'
        ]);
        
        // Complete exit mission
        $response = $this->patch("/missions/{$exitMission->id}/complete", [
            'completion_notes' => 'Exit completed successfully'
        ]);
        $response->assertStatus(200);
        
        $exitMission->refresh();
        $this->assertEquals('completed', $exitMission->status);
        
        // Create exit signature
        $exitSignature = BailMobiliteSignature::create([
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => 'exit',
            'contract_template_id' => $this->exitTemplate->id,
            'tenant_signature' => 'tenant_exit_signature_data',
            'tenant_signed_at' => now(),
            'contract_pdf_path' => 'contracts/exit_' . $bailMobilite->id . '.pdf'
        ]);

        // Step 8: Ops validates exit and completes the Bail Mobilité
        $this->actingAs($this->opsUser);
        
        $response = $this->post("/bail-mobilites/{$bailMobilite->id}/validate-exit", [
            'validation_notes' => 'Exit validated successfully'
        ]);
        $response->assertStatus(200);
        
        $exitChecklist->refresh();
        $this->assertTrue($exitChecklist->ops_validated);
        
        // Step 9: Auto-transition to completed status
        $incidentService = new IncidentDetectionService();
        $result = $incidentService->processIncidentDetection($bailMobilite);
        
        $this->assertFalse($result['has_incidents']);
        $this->assertCount(0, $result['incidents']);
        
        $bailMobilite->refresh();
        $this->assertEquals('completed', $bailMobilite->status);
        
        // Verify all signatures are complete
        $this->assertTrue($entrySignature->isComplete());
        $this->assertTrue($exitSignature->isComplete());
        
        // Verify both contracts have been generated
        $this->assertNotNull($entrySignature->contract_pdf_path);
        $this->assertNotNull($exitSignature->contract_pdf_path);
    }

    /** @test */
    public function it_handles_incident_workflow_when_keys_not_returned()
    {
        // Create a Bail Mobilité in progress
        $bailMobilite = BailMobilite::factory()->create([
            'status' => 'in_progress',
            'ops_user_id' => $this->opsUser->id
        ]);
        
        $exitMission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'mission_type' => 'exit',
            'status' => 'completed',
            'agent_id' => $this->checkerAgent->id
        ]);
        
        $bailMobilite->update(['exit_mission_id' => $exitMission->id]);
        
        // Create exit checklist with keys NOT returned
        $exitChecklist = Checklist::factory()->create([
            'mission_id' => $exitMission->id,
            'keys_returned' => false, // This should trigger incident
            'ops_validated' => true
        ]);
        
        // Process incident detection
        $incidentService = new IncidentDetectionService();
        $result = $incidentService->processIncidentDetection($bailMobilite);
        
        $this->assertTrue($result['has_incidents']);
        $this->assertCount(1, $result['incidents']);
        $this->assertEquals('keys_not_returned', $result['incidents'][0]['type']);
        
        $bailMobilite->refresh();
        $this->assertEquals('incident', $bailMobilite->status);
        
        // Check that incident alert was sent to Ops
        $this->assertDatabaseHas('notifications', [
            'type' => 'INCIDENT_ALERT',
            'recipient_id' => $this->opsUser->id,
            'bail_mobilite_id' => $bailMobilite->id
        ]);
        
        // Ops can handle the incident
        $this->actingAs($this->opsUser);
        
        $response = $this->post("/bail-mobilites/{$bailMobilite->id}/handle-incident", [
            'incident_type' => 'keys_not_returned',
            'description' => 'Tenant did not return keys',
            'corrective_actions' => [
                'Contact tenant immediately',
                'Schedule key recovery meeting'
            ]
        ]);
        $response->assertStatus(200);
        
        // Check that incident report was created
        $this->assertDatabaseHas('incident_reports', [
            'bail_mobilite_id' => $bailMobilite->id,
            'incident_type' => 'keys_not_returned',
            'reported_by' => $this->opsUser->id
        ]);
        
        // Check that corrective actions were created
        $this->assertDatabaseHas('corrective_actions', [
            'bail_mobilite_id' => $bailMobilite->id,
            'action' => 'Contact tenant immediately',
            'assigned_to' => $this->opsUser->id,
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function it_handles_notification_system_correctly()
    {
        $notificationService = new NotificationService();
        
        // Test exit reminder scheduling
        $bailMobilite = BailMobilite::factory()->create([
            'end_date' => now()->addDays(15),
            'status' => 'in_progress'
        ]);
        
        $notificationService->scheduleExitReminder($bailMobilite, $this->opsUser);
        
        $this->assertDatabaseHas('notifications', [
            'type' => 'EXIT_REMINDER',
            'bail_mobilite_id' => $bailMobilite->id,
            'recipient_id' => $this->opsUser->id,
            'status' => 'pending'
        ]);
        
        $notification = Notification::where('type', 'EXIT_REMINDER')->first();
        $expectedDate = $bailMobilite->end_date->subDays(10);
        $this->assertEquals($expectedDate->format('Y-m-d'), $notification->scheduled_at->format('Y-m-d'));
        
        // Test notification cancellation when dates change
        $notificationService->cancelScheduledNotifications($bailMobilite);
        
        $notification->refresh();
        $this->assertEquals('cancelled', $notification->status);
        
        // Test ops alert notifications
        $notificationService->sendOpsAlert(
            $this->opsUser,
            'CHECKLIST_VALIDATION',
            $bailMobilite,
            ['message' => 'Checklist needs validation']
        );
        
        $this->assertDatabaseHas('notifications', [
            'type' => 'CHECKLIST_VALIDATION',
            'recipient_id' => $this->opsUser->id,
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'sent'
        ]);
        
        // Test checker notification
        $notificationService->notifyChecker(
            $this->checker,
            $bailMobilite,
            ['mission_type' => 'entry', 'scheduled_time' => '14:00']
        );
        
        $this->assertDatabaseHas('notifications', [
            'type' => 'MISSION_ASSIGNED',
            'recipient_id' => $this->checker->id,
            'bail_mobilite_id' => $bailMobilite->id,
            'status' => 'sent'
        ]);
    }

    /** @test */
    public function it_enforces_role_based_permissions_correctly()
    {
        $bailMobilite = BailMobilite::factory()->create();
        
        // Test that checker cannot create Bail Mobilité
        $this->actingAs($this->checker);
        
        $response = $this->post('/bail-mobilites', [
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28',
            'address' => '123 Test Street',
            'tenant_name' => 'John Doe'
        ]);
        $response->assertStatus(403); // Forbidden
        
        // Test that checker cannot assign missions
        $response = $this->post("/bail-mobilites/{$bailMobilite->id}/assign-entry", [
            'checker_id' => $this->checker->id,
            'scheduled_time' => '14:00'
        ]);
        $response->assertStatus(403); // Forbidden
        
        // Test that ops can create Bail Mobilité
        $this->actingAs($this->opsUser);
        
        $response = $this->post('/bail-mobilites', [
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28',
            'address' => '123 Test Street',
            'tenant_name' => 'Jane Doe',
            'tenant_email' => 'jane@example.com'
        ]);
        $response->assertStatus(302); // Redirect after successful creation
        
        // Test that ops can assign missions
        $newBailMobilite = BailMobilite::where('tenant_name', 'Jane Doe')->first();
        
        $response = $this->post("/bail-mobilites/{$newBailMobilite->id}/assign-entry", [
            'checker_id' => $this->checker->id,
            'scheduled_time' => '14:00'
        ]);
        $response->assertStatus(200);
    }

    /** @test */
    public function it_generates_and_validates_pdf_contracts_correctly()
    {
        $bailMobilite = BailMobilite::factory()->create();
        
        // Create entry signature
        $entrySignature = BailMobiliteSignature::create([
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => 'entry',
            'contract_template_id' => $this->entryTemplate->id,
            'tenant_signature' => 'tenant_signature_data',
            'tenant_signed_at' => now()
        ]);
        
        // Test signature completion validation
        $this->assertTrue($entrySignature->isComplete());
        $this->assertTrue($entrySignature->isTenantSigned());
        $this->assertTrue($entrySignature->isAdminSigned());
        
        // Test PDF generation path
        $entrySignature->update([
            'contract_pdf_path' => 'contracts/entry_' . $bailMobilite->id . '.pdf'
        ]);
        
        $this->assertTrue($entrySignature->hasPdfGenerated());
        
        // Test validation status
        $status = $entrySignature->getValidationStatus();
        $this->assertTrue($status['tenant_signed']);
        $this->assertTrue($status['admin_signed']);
        $this->assertTrue($status['pdf_generated']);
        $this->assertTrue($status['complete']);
        
        // Test incomplete signature
        $incompleteSignature = BailMobiliteSignature::create([
            'bail_mobilite_id' => $bailMobilite->id,
            'signature_type' => 'exit',
            'contract_template_id' => $this->exitTemplate->id,
            'tenant_signature' => null,
            'tenant_signed_at' => null
        ]);
        
        $this->assertFalse($incompleteSignature->isComplete());
        $this->assertFalse($incompleteSignature->isTenantSigned());
        $this->assertTrue($incompleteSignature->isAdminSigned()); // Template is signed
        
        $incompleteStatus = $incompleteSignature->getValidationStatus();
        $this->assertFalse($incompleteStatus['tenant_signed']);
        $this->assertTrue($incompleteStatus['admin_signed']);
        $this->assertFalse($incompleteStatus['pdf_generated']);
        $this->assertFalse($incompleteStatus['complete']);
    }
}