<?php

namespace Tests\Unit\Models;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_correct_fillable_attributes()
    {
        $fillable = [
            'user_id',
            'action',
            'resource_type',
            'resource_id',
            'changes',
            'ip_address',
            'user_agent',
            'created_at'
        ];

        $auditLog = new AuditLog();
        $this->assertEquals($fillable, $auditLog->getFillable());
    }

    #[Test]
    public function it_casts_changes_to_array()
    {
        $auditLog = AuditLog::create([
            'action' => 'test',
            'changes' => ['key' => 'value'],
            'created_at' => now()
        ]);

        $this->assertIsArray($auditLog->changes);
        $this->assertEquals('value', $auditLog->changes['key']);
    }

    #[Test]
    public function it_belongs_to_user()
    {
        $user = User::factory()->create();
        $auditLog = AuditLog::create([
            'user_id' => $user->id,
            'action' => 'test',
            'created_at' => now()
        ]);

        $this->assertInstanceOf(User::class, $auditLog->user);
        $this->assertEquals($user->id, $auditLog->user->id);
    }

    #[Test]
    public function it_can_filter_by_action()
    {
        AuditLog::create(['action' => 'login', 'created_at' => now()]);
        AuditLog::create(['action' => 'logout', 'created_at' => now()]);
        AuditLog::create(['action' => 'login', 'created_at' => now()]);

        $loginLogs = AuditLog::byAction('login')->get();
        $this->assertCount(2, $loginLogs);
        
        $logoutLogs = AuditLog::byAction('logout')->get();
        $this->assertCount(1, $logoutLogs);
    }

    #[Test]
    public function it_can_filter_by_resource_type()
    {
        AuditLog::create([
            'action' => 'test',
            'resource_type' => 'App\Models\User',
            'created_at' => now()
        ]);
        AuditLog::create([
            'action' => 'test',
            'resource_type' => 'App\Models\Property',
            'created_at' => now()
        ]);

        $userLogs = AuditLog::byResourceType('App\Models\User')->get();
        $this->assertCount(1, $userLogs);
        
        $propertyLogs = AuditLog::byResourceType('App\Models\Property')->get();
        $this->assertCount(1, $propertyLogs);
    }

    #[Test]
    public function it_can_filter_by_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        AuditLog::create(['user_id' => $user1->id, 'action' => 'test', 'created_at' => now()]);
        AuditLog::create(['user_id' => $user2->id, 'action' => 'test', 'created_at' => now()]);
        AuditLog::create(['user_id' => $user1->id, 'action' => 'test', 'created_at' => now()]);

        $user1Logs = AuditLog::byUser($user1->id)->get();
        $this->assertCount(2, $user1Logs);
        
        $user2Logs = AuditLog::byUser($user2->id)->get();
        $this->assertCount(1, $user2Logs);
    }

    #[Test]
    public function it_can_filter_by_date_range()
    {
        $startDate = now()->subDays(7);
        $endDate = now()->subDays(1);

        // Create logs within range
        AuditLog::create(['action' => 'test', 'created_at' => now()->subDays(5)]);
        AuditLog::create(['action' => 'test', 'created_at' => now()->subDays(3)]);
        
        // Create logs outside range
        AuditLog::create(['action' => 'test', 'created_at' => now()->subDays(10)]);
        AuditLog::create(['action' => 'test', 'created_at' => now()]);

        $logsInRange = AuditLog::byDateRange($startDate, $endDate)->get();
        $this->assertCount(2, $logsInRange);
    }

    #[Test]
    public function it_can_search_in_changes()
    {
        AuditLog::create([
            'action' => 'test',
            'changes' => ['name' => 'John Doe', 'email' => 'john@example.com'],
            'created_at' => now()
        ]);
        AuditLog::create([
            'action' => 'test',
            'changes' => ['name' => 'Jane Smith', 'email' => 'jane@example.com'],
            'created_at' => now()
        ]);

        $johnLogs = AuditLog::searchChanges('John')->get();
        $this->assertCount(1, $johnLogs);
        
        $emailLogs = AuditLog::searchChanges('example.com')->get();
        $this->assertCount(2, $emailLogs);
    }

    #[Test]
    public function it_can_combine_multiple_scopes()
    {
        $user = User::factory()->create();
        $startDate = now()->subDays(7);
        $endDate = now()->subDays(1);

        // Create matching log
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'resource_type' => 'App\Models\User',
            'created_at' => now()->subDays(3)
        ]);

        // Create non-matching logs
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'logout',
            'resource_type' => 'App\Models\User',
            'created_at' => now()->subDays(3)
        ]);
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'resource_type' => 'App\Models\Property',
            'created_at' => now()->subDays(3)
        ]);

        $filteredLogs = AuditLog::byUser($user->id)
            ->byAction('login')
            ->byResourceType('App\Models\User')
            ->byDateRange($startDate, $endDate)
            ->get();

        $this->assertCount(1, $filteredLogs);
    }

    #[Test]
    public function it_handles_null_changes()
    {
        $auditLog = AuditLog::create([
            'action' => 'test',
            'changes' => null,
            'created_at' => now()
        ]);

        $this->assertNull($auditLog->changes);
    }

    #[Test]
    public function it_handles_empty_changes_array()
    {
        $auditLog = AuditLog::create([
            'action' => 'test',
            'changes' => [],
            'created_at' => now()
        ]);

        $this->assertIsArray($auditLog->changes);
        $this->assertEmpty($auditLog->changes);
    }
}