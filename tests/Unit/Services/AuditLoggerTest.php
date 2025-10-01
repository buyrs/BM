<?php

namespace Tests\Unit\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Property;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    protected AuditLogger $auditLogger;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditLogger = new AuditLogger();
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'admin'
        ]);
    }

    #[Test]
    public function it_can_log_basic_user_actions()
    {
        $property = Property::factory()->create();
        
        $auditLog = $this->auditLogger->log(
            'view_property',
            $property,
            ['viewed_at' => now()->toISOString()],
            $this->user
        );

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals('view_property', $auditLog->action);
        $this->assertEquals($this->user->id, $auditLog->user_id);
        $this->assertEquals(Property::class, $auditLog->resource_type);
        $this->assertEquals($property->id, $auditLog->resource_id);
        $this->assertNotNull($auditLog->changes);
    }

    #[Test]
    public function it_can_log_model_creation()
    {
        $property = Property::factory()->create([
            'owner_name' => 'Test Owner',
            'property_address' => '123 Test St'
        ]);

        $auditLog = $this->auditLogger->logCreated($property, $this->user);

        $this->assertEquals('created', $auditLog->action);
        $this->assertEquals(Property::class, $auditLog->resource_type);
        $this->assertEquals($property->id, $auditLog->resource_id);
        $this->assertArrayHasKey('owner_name', $auditLog->changes);
        $this->assertEquals('Test Owner', $auditLog->changes['owner_name']);
    }

    #[Test]
    public function it_can_log_model_updates()
    {
        $property = Property::factory()->create(['owner_name' => 'Original Name']);
        $originalAttributes = $property->getAttributes();
        
        $property->owner_name = 'Updated Name';
        // Don't save yet - we need to capture the dirty state
        
        $auditLog = $this->auditLogger->logUpdated($property, $originalAttributes, $this->user);

        $this->assertEquals('updated', $auditLog->action);
        $this->assertEquals(Property::class, $auditLog->resource_type);
        $this->assertEquals($property->id, $auditLog->resource_id);
        $this->assertNotNull($auditLog->changes);
        $this->assertArrayHasKey('owner_name', $auditLog->changes);
        $this->assertEquals('Original Name', $auditLog->changes['owner_name']['old']);
        $this->assertEquals('Updated Name', $auditLog->changes['owner_name']['new']);
    }

    #[Test]
    public function it_can_log_model_deletion()
    {
        $property = Property::factory()->create(['owner_name' => 'To Be Deleted']);

        $auditLog = $this->auditLogger->logDeleted($property, $this->user);

        $this->assertEquals('deleted', $auditLog->action);
        $this->assertEquals(Property::class, $auditLog->resource_type);
        $this->assertEquals($property->id, $auditLog->resource_id);
        $this->assertArrayHasKey('owner_name', $auditLog->changes);
        $this->assertEquals('To Be Deleted', $auditLog->changes['owner_name']);
    }

    #[Test]
    public function it_can_log_successful_login()
    {
        $auditLog = $this->auditLogger->logLogin($this->user, true);

        $this->assertEquals('login_successful', $auditLog->action);
        $this->assertEquals($this->user->id, $auditLog->user_id);
        $this->assertEquals(User::class, $auditLog->resource_type);
        $this->assertEquals($this->user->id, $auditLog->resource_id);
        $this->assertTrue($auditLog->changes['successful']);
    }

    #[Test]
    public function it_can_log_failed_login()
    {
        $auditLog = $this->auditLogger->logLogin($this->user, false);

        $this->assertEquals('login_failed', $auditLog->action);
        $this->assertEquals($this->user->id, $auditLog->user_id);
        $this->assertFalse($auditLog->changes['successful']);
    }

    #[Test]
    public function it_can_log_logout()
    {
        $auditLog = $this->auditLogger->logLogout($this->user);

        $this->assertEquals('logout', $auditLog->action);
        $this->assertEquals($this->user->id, $auditLog->user_id);
        $this->assertEquals(User::class, $auditLog->resource_type);
        $this->assertArrayHasKey('timestamp', $auditLog->changes);
    }

    #[Test]
    public function it_can_log_sensitive_data_access()
    {
        $auditLog = $this->auditLogger->logSensitiveAccess('user_data', $this->user, $this->user);

        $this->assertEquals('sensitive_data_access', $auditLog->action);
        $this->assertEquals($this->user->id, $auditLog->user_id);
        $this->assertEquals('user_data', $auditLog->changes['data_type']);
    }

    #[Test]
    public function it_can_log_bulk_operations()
    {
        $auditLog = $this->auditLogger->logBulkOperation('delete', 'Property', 25, $this->user);

        $this->assertEquals('bulk_delete', $auditLog->action);
        $this->assertEquals($this->user->id, $auditLog->user_id);
        $this->assertEquals('Property', $auditLog->changes['resource_type']);
        $this->assertEquals(25, $auditLog->changes['count']);
    }

    #[Test]
    public function it_can_log_permission_changes()
    {
        $targetUser = User::factory()->create(['role' => 'checker']);
        $changes = [
            'role' => ['old' => 'checker', 'new' => 'admin']
        ];

        $auditLog = $this->auditLogger->logPermissionChange($targetUser, $changes, $this->user);

        $this->assertEquals('permission_change', $auditLog->action);
        $this->assertEquals($this->user->id, $auditLog->user_id);
        $this->assertEquals(User::class, $auditLog->resource_type);
        $this->assertEquals($targetUser->id, $auditLog->resource_id);
        $this->assertArrayHasKey('permission_changes', $auditLog->changes);
    }

    #[Test]
    public function it_sanitizes_sensitive_fields_in_changes()
    {
        $changes = [
            'name' => 'John Doe',
            'password' => 'secret123',
            'two_factor_secret' => 'ABCD1234',
            'email' => 'john@example.com'
        ];

        $auditLog = $this->auditLogger->log('update_user', $this->user, $changes, $this->user);

        $this->assertEquals('John Doe', $auditLog->changes['name']);
        $this->assertEquals('[REDACTED]', $auditLog->changes['password']);
        $this->assertEquals('[REDACTED]', $auditLog->changes['two_factor_secret']);
        $this->assertEquals('john@example.com', $auditLog->changes['email']);
    }

    #[Test]
    public function it_sanitizes_nested_sensitive_fields_in_updates()
    {
        $changes = [
            'name' => ['old' => 'Old Name', 'new' => 'New Name'],
            'password' => ['old' => 'oldpass', 'new' => 'newpass']
        ];

        $auditLog = $this->auditLogger->log('update_user', $this->user, $changes, $this->user);

        $this->assertEquals('Old Name', $auditLog->changes['name']['old']);
        $this->assertEquals('New Name', $auditLog->changes['name']['new']);
        $this->assertEquals('[REDACTED]', $auditLog->changes['password']);
    }

    #[Test]
    public function it_captures_request_information()
    {
        $request = Request::create('/test', 'POST', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.1',
            'HTTP_USER_AGENT' => 'Test Browser'
        ]);

        $auditLog = $this->auditLogger->log('test_action', null, [], $this->user, $request);

        $this->assertEquals('192.168.1.1', $auditLog->ip_address);
        $this->assertEquals('Test Browser', $auditLog->user_agent);
    }

    #[Test]
    public function it_works_without_authenticated_user()
    {
        $auditLog = $this->auditLogger->log('anonymous_action', null, ['test' => 'data']);

        $this->assertNull($auditLog->user_id);
        $this->assertEquals('anonymous_action', $auditLog->action);
        $this->assertEquals('data', $auditLog->changes['test']);
    }

    #[Test]
    public function it_detects_multiple_failed_login_attempts()
    {
        // Create 5 failed login attempts within an hour
        for ($i = 0; $i < 5; $i++) {
            $this->auditLogger->logLogin($this->user, false);
        }

        // The 5th failed attempt should trigger suspicious activity detection
        $suspiciousLogs = AuditLog::where('action', 'multiple_failed_logins')
            ->where('user_id', $this->user->id)
            ->get();

        $this->assertCount(1, $suspiciousLogs);
        $this->assertEquals(5, $suspiciousLogs->first()->changes['failed_attempts']);
    }

    #[Test]
    public function it_can_clean_up_old_logs()
    {
        // Create some old audit logs
        AuditLog::factory()->count(5)->create([
            'created_at' => now()->subDays(400)
        ]);

        // Create some recent audit logs
        AuditLog::factory()->count(3)->create([
            'created_at' => now()->subDays(30)
        ]);

        $deletedCount = $this->auditLogger->cleanupOldLogs(365);

        $this->assertEquals(5, $deletedCount);
        // Should have 3 recent logs + 1 cleanup log = 4 total
        $this->assertEquals(4, AuditLog::count());
    }

    #[Test]
    public function cleanup_logs_creates_audit_entry()
    {
        // Create old logs
        AuditLog::factory()->count(3)->create([
            'created_at' => now()->subDays(400)
        ]);

        $this->auditLogger->cleanupOldLogs(365);

        // Check that cleanup was logged
        $cleanupLog = AuditLog::where('action', 'audit_log_cleanup')->first();
        $this->assertNotNull($cleanupLog);
        $this->assertEquals(3, $cleanupLog->changes['deleted_count']);
        $this->assertEquals(365, $cleanupLog->changes['retention_days']);
    }
}