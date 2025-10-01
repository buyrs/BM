<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Property;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuditLogSearchAndFilteringTest extends TestCase
{
    use RefreshDatabase;

    protected AuditLogger $auditLogger;
    protected User $adminUser;
    protected User $checkerUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditLogger = new AuditLogger();
        
        $this->adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin'
        ]);
        
        $this->checkerUser = User::factory()->create([
            'name' => 'Checker User',
            'email' => 'checker@example.com',
            'role' => 'checker'
        ]);
    }

    #[Test]
    public function it_can_get_audit_logs_without_filters()
    {
        // Create test audit logs
        $this->auditLogger->logLogin($this->adminUser, true);
        $this->auditLogger->logLogin($this->checkerUser, true);
        $this->auditLogger->logLogout($this->adminUser);

        $logs = $this->auditLogger->getAuditLogs();

        $this->assertCount(3, $logs);
        $this->assertEquals('logout', $logs->first()->action); // Most recent first
    }

    #[Test]
    public function it_can_filter_audit_logs_by_user()
    {
        $this->auditLogger->logLogin($this->adminUser, true);
        $this->auditLogger->logLogin($this->checkerUser, true);
        $this->auditLogger->logLogout($this->adminUser);

        $adminLogs = $this->auditLogger->getAuditLogs(['user_id' => $this->adminUser->id]);
        $checkerLogs = $this->auditLogger->getAuditLogs(['user_id' => $this->checkerUser->id]);

        $this->assertCount(2, $adminLogs);
        $this->assertCount(1, $checkerLogs);
        
        foreach ($adminLogs as $log) {
            $this->assertEquals($this->adminUser->id, $log->user_id);
        }
    }

    #[Test]
    public function it_can_filter_audit_logs_by_action()
    {
        $this->auditLogger->logLogin($this->adminUser, true);
        $this->auditLogger->logLogin($this->checkerUser, false);
        $this->auditLogger->logLogout($this->adminUser);

        $loginLogs = $this->auditLogger->getAuditLogs(['action' => 'login_successful']);
        $failedLoginLogs = $this->auditLogger->getAuditLogs(['action' => 'login_failed']);
        $logoutLogs = $this->auditLogger->getAuditLogs(['action' => 'logout']);

        $this->assertCount(1, $loginLogs);
        $this->assertCount(1, $failedLoginLogs);
        $this->assertCount(1, $logoutLogs);
    }

    #[Test]
    public function it_can_filter_audit_logs_by_resource_type()
    {
        $property = Property::factory()->create();
        
        $this->auditLogger->logCreated($property, $this->adminUser);
        $this->auditLogger->logLogin($this->adminUser, true);
        $this->auditLogger->logUpdated($property, $property->getOriginal(), $this->adminUser);

        $propertyLogs = $this->auditLogger->getAuditLogs(['resource_type' => Property::class]);
        $userLogs = $this->auditLogger->getAuditLogs(['resource_type' => User::class]);

        $this->assertCount(2, $propertyLogs);
        $this->assertCount(1, $userLogs);
    }

    #[Test]
    public function it_can_filter_audit_logs_by_date_range()
    {
        $startDate = now()->subDays(7)->startOfDay();
        $endDate = now()->subDays(1)->endOfDay();

        // Create logs within range
        AuditLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'test_action_1',
            'created_at' => now()->subDays(5)
        ]);
        AuditLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'test_action_2',
            'created_at' => now()->subDays(3)
        ]);

        // Create logs outside range
        AuditLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'test_action_3',
            'created_at' => now()->subDays(10)
        ]);
        AuditLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'test_action_4',
            'created_at' => now()
        ]);

        $logsInRange = $this->auditLogger->getAuditLogs([
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        $this->assertCount(2, $logsInRange);
    }

    #[Test]
    public function it_can_search_audit_logs_by_text()
    {
        $property = Property::factory()->create(['owner_name' => 'Luxury Apartment']);
        
        $this->auditLogger->logCreated($property, $this->adminUser);
        $this->auditLogger->log('custom_action', null, ['description' => 'Updated apartment details'], $this->adminUser);
        $this->auditLogger->logLogin($this->adminUser, true);

        $apartmentLogs = $this->auditLogger->getAuditLogs(['search' => 'apartment']);
        $luxuryLogs = $this->auditLogger->getAuditLogs(['search' => 'Luxury']);
        $loginLogs = $this->auditLogger->getAuditLogs(['search' => 'login']);

        $this->assertCount(2, $apartmentLogs); // Should find both apartment-related logs
        $this->assertCount(1, $luxuryLogs);
        $this->assertCount(1, $loginLogs);
    }

    #[Test]
    public function it_can_combine_multiple_filters()
    {
        $property = Property::factory()->create();
        $startDate = now()->subDays(7);
        $endDate = now()->subDays(1);

        // Create matching log
        AuditLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'created',
            'resource_type' => Property::class,
            'resource_id' => $property->id,
            'changes' => ['name' => 'Test Property'],
            'created_at' => now()->subDays(3)
        ]);

        // Create non-matching logs
        AuditLog::create([
            'user_id' => $this->checkerUser->id, // Different user
            'action' => 'created',
            'resource_type' => Property::class,
            'created_at' => now()->subDays(3)
        ]);
        AuditLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'updated', // Different action
            'resource_type' => Property::class,
            'created_at' => now()->subDays(3)
        ]);
        AuditLog::create([
            'user_id' => $this->adminUser->id,
            'action' => 'created',
            'resource_type' => User::class, // Different resource type
            'created_at' => now()->subDays(3)
        ]);

        $filteredLogs = $this->auditLogger->getAuditLogs([
            'user_id' => $this->adminUser->id,
            'action' => 'created',
            'resource_type' => Property::class,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'search' => 'Test'
        ]);

        $this->assertCount(1, $filteredLogs);
        $this->assertEquals('created', $filteredLogs->first()->action);
        $this->assertEquals($this->adminUser->id, $filteredLogs->first()->user_id);
    }

    #[Test]
    public function it_paginates_audit_logs()
    {
        // Create 25 audit logs
        for ($i = 0; $i < 25; $i++) {
            AuditLog::create([
                'user_id' => $this->adminUser->id,
                'action' => "test_action_{$i}",
                'created_at' => now()->subMinutes($i)
            ]);
        }

        $logs = $this->auditLogger->getAuditLogs([], 10);

        $this->assertEquals(10, $logs->perPage());
        $this->assertEquals(25, $logs->total());
        $this->assertEquals(3, $logs->lastPage());
    }

    #[Test]
    public function it_orders_audit_logs_by_created_at_desc()
    {
        $firstLog = AuditLog::create([
            'action' => 'first_action',
            'created_at' => now()->subHours(2)
        ]);
        $secondLog = AuditLog::create([
            'action' => 'second_action',
            'created_at' => now()->subHour()
        ]);
        $thirdLog = AuditLog::create([
            'action' => 'third_action',
            'created_at' => now()
        ]);

        $logs = $this->auditLogger->getAuditLogs();

        $this->assertEquals('third_action', $logs->first()->action);
        $this->assertEquals('first_action', $logs->last()->action);
    }

    #[Test]
    public function it_includes_user_relationship_in_results()
    {
        $this->auditLogger->logLogin($this->adminUser, true);

        $logs = $this->auditLogger->getAuditLogs();

        $this->assertTrue($logs->first()->relationLoaded('user'));
        $this->assertEquals($this->adminUser->name, $logs->first()->user->name);
    }

    #[Test]
    public function it_handles_empty_search_results()
    {
        $this->auditLogger->logLogin($this->adminUser, true);

        $logs = $this->auditLogger->getAuditLogs(['search' => 'nonexistent']);

        $this->assertCount(0, $logs);
    }

    #[Test]
    public function it_handles_invalid_date_ranges()
    {
        $this->auditLogger->logLogin($this->adminUser, true);

        // End date before start date
        $logs = $this->auditLogger->getAuditLogs([
            'start_date' => now(),
            'end_date' => now()->subDay()
        ]);

        $this->assertCount(0, $logs);
    }

    #[Test]
    public function it_can_search_in_nested_changes()
    {
        AuditLog::create([
            'action' => 'updated',
            'changes' => [
                'name' => ['old' => 'Old Name', 'new' => 'New Name'],
                'email' => ['old' => 'old@example.com', 'new' => 'new@example.com']
            ],
            'created_at' => now()
        ]);

        $oldNameLogs = $this->auditLogger->getAuditLogs(['search' => 'Old Name']);
        $newEmailLogs = $this->auditLogger->getAuditLogs(['search' => 'new@example.com']);

        $this->assertCount(1, $oldNameLogs);
        $this->assertCount(1, $newEmailLogs);
    }
}