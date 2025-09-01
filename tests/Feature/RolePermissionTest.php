<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\BailMobilite;
use App\Models\ContractTemplate;
use App\Models\Mission;
use App\Models\Agent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $opsUser;
    protected User $checker;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->createRolesAndPermissions();
        $this->createUsers();
    }

    protected function createRolesAndPermissions(): void
    {
        // Create roles
        Role::create(['name' => 'super-admin']);
        Role::create(['name' => 'ops']);
        Role::create(['name' => 'checker']);
        
        // Create permissions
        $permissions = [
            // Admin permissions
            'manage_contract_templates',
            'manage_users',
            'view_all_data',
            
            // Ops permissions
            'create_bail_mobilite',
            'edit_bail_mobilite',
            'assign_missions',
            'validate_checklists',
            'view_ops_dashboard',
            'manage_incidents',
            
            // Checker permissions
            'complete_missions',
            'fill_checklists',
            'view_assigned_missions'
        ];
        
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
        
        // Assign permissions to roles
        $superAdminRole = Role::findByName('super-admin');
        $superAdminRole->givePermissionTo(Permission::all());
        
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
            'fill_checklists',
            'view_assigned_missions'
        ]);
    }

    protected function createUsers(): void
    {
        $this->superAdmin = User::factory()->create(['name' => 'Super Admin']);
        $this->superAdmin->assignRole('super-admin');
        
        $this->opsUser = User::factory()->create(['name' => 'Ops User']);
        $this->opsUser->assignRole('ops');
        
        $this->checker = User::factory()->create(['name' => 'Checker']);
        $this->checker->assignRole('checker');
        
        $this->regularUser = User::factory()->create(['name' => 'Regular User']);
        // No role assigned - should have no permissions
        
        // Create agent for checker
        Agent::factory()->create(['user_id' => $this->checker->id]);
    }

    /** @test */
    public function super_admin_can_access_all_features()
    {
        $this->actingAs($this->superAdmin);
        
        // Can manage contract templates
        $response = $this->get('/admin/contract-templates');
        $response->assertStatus(200);
        
        $response = $this->post('/admin/contract-templates', [
            'name' => 'Test Template',
            'type' => 'entry',
            'content' => 'Test content',
            'is_active' => true
        ]);
        $response->assertStatus(302); // Redirect after creation
        
        // Can create bail mobilités
        $response = $this->post('/bail-mobilites', [
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28',
            'address' => '123 Test Street',
            'tenant_name' => 'John Doe',
            'tenant_email' => 'john@example.com'
        ]);
        $response->assertStatus(302);
        
        // Can access ops dashboard
        $response = $this->get('/ops/dashboard');
        $response->assertStatus(200);
        
        // Can access admin analytics
        $response = $this->get('/admin/analytics');
        $response->assertStatus(200);
    }

    /** @test */
    public function ops_user_can_manage_bail_mobilites_and_missions()
    {
        $this->actingAs($this->opsUser);
        
        // Can create bail mobilités
        $response = $this->post('/bail-mobilites', [
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28',
            'address' => '123 Test Street',
            'tenant_name' => 'John Doe',
            'tenant_email' => 'john@example.com'
        ]);
        $response->assertStatus(302);
        
        $bailMobilite = BailMobilite::where('tenant_name', 'John Doe')->first();
        
        // Can assign missions
        $response = $this->post("/bail-mobilites/{$bailMobilite->id}/assign-entry", [
            'checker_id' => $this->checker->id,
            'scheduled_time' => '14:00'
        ]);
        $response->assertStatus(200);
        
        // Can validate checklists
        $response = $this->post("/bail-mobilites/{$bailMobilite->id}/validate-entry", [
            'validation_notes' => 'Validated successfully'
        ]);
        $response->assertStatus(200);
        
        // Can access ops dashboard
        $response = $this->get('/ops/dashboard');
        $response->assertStatus(200);
        
        // Can manage incidents
        $bailMobilite->update(['status' => 'incident']);
        $response = $this->post("/bail-mobilites/{$bailMobilite->id}/handle-incident", [
            'incident_type' => 'keys_not_returned',
            'description' => 'Test incident',
            'corrective_actions' => ['Contact tenant']
        ]);
        $response->assertStatus(200);
    }

    /** @test */
    public function ops_user_cannot_access_admin_features()
    {
        $this->actingAs($this->opsUser);
        
        // Cannot manage contract templates
        $response = $this->get('/admin/contract-templates');
        $response->assertStatus(403);
        
        $response = $this->post('/admin/contract-templates', [
            'name' => 'Test Template',
            'type' => 'entry',
            'content' => 'Test content'
        ]);
        $response->assertStatus(403);
        
        // Cannot access admin analytics
        $response = $this->get('/admin/analytics');
        $response->assertStatus(403);
        
        // Cannot manage users
        $response = $this->get('/admin/users');
        $response->assertStatus(403);
    }

    /** @test */
    public function checker_can_complete_assigned_missions()
    {
        $bailMobilite = BailMobilite::factory()->create();
        $mission = Mission::factory()->create([
            'bail_mobilite_id' => $bailMobilite->id,
            'agent_id' => Agent::where('user_id', $this->checker->id)->first()->id,
            'status' => 'assigned'
        ]);
        
        $this->actingAs($this->checker);
        
        // Can view assigned missions
        $response = $this->get('/checker/missions');
        $response->assertStatus(200);
        
        // Can complete missions
        $response = $this->patch("/missions/{$mission->id}/complete", [
            'completion_notes' => 'Mission completed successfully'
        ]);
        $response->assertStatus(200);
        
        // Can fill checklists
        $response = $this->get("/missions/{$mission->id}/checklist");
        $response->assertStatus(200);
    }

    /** @test */
    public function checker_cannot_access_ops_or_admin_features()
    {
        $this->actingAs($this->checker);
        
        // Cannot create bail mobilités
        $response = $this->post('/bail-mobilites', [
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28',
            'address' => '123 Test Street',
            'tenant_name' => 'John Doe'
        ]);
        $response->assertStatus(403);
        
        // Cannot access ops dashboard
        $response = $this->get('/ops/dashboard');
        $response->assertStatus(403);
        
        // Cannot assign missions
        $bailMobilite = BailMobilite::factory()->create();
        $response = $this->post("/bail-mobilites/{$bailMobilite->id}/assign-entry", [
            'checker_id' => $this->checker->id,
            'scheduled_time' => '14:00'
        ]);
        $response->assertStatus(403);
        
        // Cannot validate checklists
        $response = $this->post("/bail-mobilites/{$bailMobilite->id}/validate-entry", [
            'validation_notes' => 'Validation attempt'
        ]);
        $response->assertStatus(403);
        
        // Cannot manage contract templates
        $response = $this->get('/admin/contract-templates');
        $response->assertStatus(403);
    }

    /** @test */
    public function regular_user_cannot_access_any_protected_features()
    {
        $this->actingAs($this->regularUser);
        
        // Cannot create bail mobilités
        $response = $this->post('/bail-mobilites', [
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28',
            'address' => '123 Test Street',
            'tenant_name' => 'John Doe'
        ]);
        $response->assertStatus(403);
        
        // Cannot access ops dashboard
        $response = $this->get('/ops/dashboard');
        $response->assertStatus(403);
        
        // Cannot access checker dashboard
        $response = $this->get('/checker/dashboard');
        $response->assertStatus(403);
        
        // Cannot access admin features
        $response = $this->get('/admin/contract-templates');
        $response->assertStatus(403);
        
        // Cannot complete missions
        $mission = Mission::factory()->create();
        $response = $this->patch("/missions/{$mission->id}/complete", [
            'completion_notes' => 'Unauthorized attempt'
        ]);
        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_users_are_redirected_to_login()
    {
        // Test various protected routes
        $protectedRoutes = [
            '/bail-mobilites',
            '/ops/dashboard',
            '/checker/dashboard',
            '/admin/contract-templates',
            '/admin/analytics'
        ];
        
        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/login');
        }
        
        // Test POST requests
        $response = $this->post('/bail-mobilites', [
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28',
            'address' => '123 Test Street',
            'tenant_name' => 'John Doe'
        ]);
        $response->assertRedirect('/login');
    }

    /** @test */
    public function role_permissions_are_enforced_at_model_level()
    {
        // Test that users can only see their own data or data they have permission to see
        $opsUser1 = User::factory()->create();
        $opsUser1->assignRole('ops');
        
        $opsUser2 = User::factory()->create();
        $opsUser2->assignRole('ops');
        
        $bailMobilite1 = BailMobilite::factory()->create(['ops_user_id' => $opsUser1->id]);
        $bailMobilite2 = BailMobilite::factory()->create(['ops_user_id' => $opsUser2->id]);
        
        $this->actingAs($opsUser1);
        
        // Ops user should be able to see all bail mobilités (not restricted by ownership in this system)
        $response = $this->get('/bail-mobilites');
        $response->assertStatus(200);
        
        // But specific actions might be restricted
        $response = $this->get("/bail-mobilites/{$bailMobilite1->id}");
        $response->assertStatus(200);
        
        $response = $this->get("/bail-mobilites/{$bailMobilite2->id}");
        $response->assertStatus(200); // In this system, ops can see all BM
    }

    /** @test */
    public function middleware_correctly_blocks_unauthorized_access()
    {
        $bailMobilite = BailMobilite::factory()->create();
        
        // Test CheckOpsAccess middleware
        $this->actingAs($this->checker);
        
        $response = $this->post("/bail-mobilites/{$bailMobilite->id}/assign-entry", [
            'checker_id' => $this->checker->id,
            'scheduled_time' => '14:00'
        ]);
        $response->assertStatus(403);
        
        // Test that the middleware logs unauthorized access attempts
        $this->assertDatabaseHas('access_logs', [
            'user_id' => $this->checker->id,
            'action' => 'unauthorized_access_attempt',
            'resource' => 'bail_mobilite_assign_entry'
        ]);
    }

    /** @test */
    public function permissions_can_be_dynamically_assigned_and_revoked()
    {
        $this->actingAs($this->superAdmin);
        
        // Create a new permission
        $newPermission = Permission::create(['name' => 'special_feature']);
        
        // Assign it to ops role
        $opsRole = Role::findByName('ops');
        $opsRole->givePermissionTo($newPermission);
        
        // Ops user should now have this permission
        $this->assertTrue($this->opsUser->hasPermissionTo('special_feature'));
        
        // Revoke the permission
        $opsRole->revokePermissionTo($newPermission);
        
        // Refresh the user to clear cached permissions
        $this->opsUser->refresh();
        $this->opsUser->load('roles.permissions');
        
        // Ops user should no longer have this permission
        $this->assertFalse($this->opsUser->hasPermissionTo('special_feature'));
    }

    /** @test */
    public function users_can_have_multiple_roles()
    {
        // Create a user with both ops and checker roles
        $multiRoleUser = User::factory()->create();
        $multiRoleUser->assignRole(['ops', 'checker']);
        
        // User should have permissions from both roles
        $this->assertTrue($multiRoleUser->hasPermissionTo('create_bail_mobilite')); // ops permission
        $this->assertTrue($multiRoleUser->hasPermissionTo('complete_missions')); // checker permission
        
        // User should be able to access features from both roles
        $this->actingAs($multiRoleUser);
        
        $response = $this->get('/ops/dashboard');
        $response->assertStatus(200);
        
        $response = $this->get('/checker/missions');
        $response->assertStatus(200);
    }

    /** @test */
    public function permission_checks_work_with_api_routes()
    {
        // Test API endpoints with different user roles
        $bailMobilite = BailMobilite::factory()->create();
        
        // Ops user can access API
        $this->actingAs($this->opsUser);
        $response = $this->getJson("/api/bail-mobilites/{$bailMobilite->id}");
        $response->assertStatus(200);
        
        // Checker cannot access ops API endpoints
        $this->actingAs($this->checker);
        $response = $this->getJson("/api/bail-mobilites/{$bailMobilite->id}");
        $response->assertStatus(403);
        
        // Unauthenticated requests should be rejected
        $this->actingAs(null);
        $response = $this->getJson("/api/bail-mobilites/{$bailMobilite->id}");
        $response->assertStatus(401);
    }
}