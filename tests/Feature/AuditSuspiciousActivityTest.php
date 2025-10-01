<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Property;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuditSuspiciousActivityTest extends TestCase
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
    public function it_detects_multiple_failed_login_attempts()
    {
        // Create 4 failed login attempts (should not trigger)
        for ($i = 0; $i < 4; $i++) {
            $this->auditLogger->logLogin($this->user, false);
        }

        $suspiciousLogs = AuditLog::where('action', 'multiple_failed_logins')->get();
        $this->assertCount(0, $suspiciousLogs);

        // 5th failed attempt should trigger detection
        $this->auditLogger->logLogin($this->user, false);

        $suspiciousLogs = AuditLog::where('action', 'multiple_failed_logins')
            ->where('user_id', $this->user->id)
            ->get();

        $this->assertCount(1, $suspiciousLogs);
        $this->assertEquals(5, $suspiciousLogs->first()->changes['failed_attempts']);
        $this->assertEquals('1 hour', $suspiciousLogs->first()->changes['time_window']);
    }

    #[Test]
    public function it_only_counts_recent_failed_login_attempts()
    {
        // Create old failed login attempts (more than 1 hour ago)
        AuditLog::create([
            'user_id' => $this->user->id,
            'action' => 'login_failed',
            'resource_type' => User::class,
            'resource_id' => $this->user->id,
            'changes' => ['successful' => false],
            'created_at' => now()->subHours(2)
        ]);

        // Create 4 recent failed attempts
        for ($i = 0; $i < 4; $i++) {
            $this->auditLogger->logLogin($this->user, false);
        }

        // Should not trigger because old attempts are not counted
        $suspiciousLogs = AuditLog::where('action', 'multiple_failed_logins')->get();
        $this->assertCount(0, $suspiciousLogs);

        // One more recent attempt should trigger (total 5 recent)
        $this->auditLogger->logLogin($this->user, false);

        $suspiciousLogs = AuditLog::where('action', 'multiple_failed_logins')->get();
        $this->assertCount(1, $suspiciousLogs);
    }

    #[Test]
    public function it_can_retrieve_suspicious_activity_logs()
    {
        // Create some suspicious activities by triggering failed logins
        for ($i = 0; $i < 5; $i++) {
            $this->auditLogger->logLogin($this->user, false);
        }

        // Create some normal activities
        $this->auditLogger->logLogin($this->user, true);
        $this->auditLogger->logLogout($this->user);

        $suspiciousActivities = $this->auditLogger->getSuspiciousActivity();

        $this->assertGreaterThan(0, $suspiciousActivities->count());
        
        $suspiciousActions = $suspiciousActivities->pluck('action')->toArray();
        $this->assertContains('multiple_failed_logins', $suspiciousActions);
    }

    #[Test]
    public function it_can_filter_suspicious_activity_by_days()
    {
        // Create old suspicious activity
        AuditLog::create([
            'user_id' => $this->user->id,
            'action' => 'multiple_failed_logins',
            'changes' => ['failed_attempts' => 5],
            'created_at' => now()->subDays(10)
        ]);

        // Create recent suspicious activity by triggering failed logins
        for ($i = 0; $i < 5; $i++) {
            $this->auditLogger->logLogin($this->user, false);
        }

        // Get suspicious activity from last 7 days
        $recentSuspicious = $this->auditLogger->getSuspiciousActivity(7);
        $this->assertCount(1, $recentSuspicious);

        // Get suspicious activity from last 15 days
        $allSuspicious = $this->auditLogger->getSuspiciousActivity(15);
        $this->assertCount(2, $allSuspicious);
    }

    #[Test]
    public function it_does_not_flag_normal_activity_as_suspicious()
    {
        // Normal activities that should not trigger suspicious activity detection
        $this->auditLogger->logLogin($this->user, true);
        $this->auditLogger->logLogout($this->user);
        
        $property = Property::factory()->create();
        $this->auditLogger->logCreated($property, $this->user);
        $this->auditLogger->logUpdated($property, $property->getOriginal(), $this->user);
        
        $this->auditLogger->logBulkOperation('update', 'Property', 10, $this->user); // Small bulk operation
        
        // Should not create any suspicious activity logs
        $suspiciousLogs = AuditLog::whereIn('action', [
            'multiple_failed_logins',
            'privilege_escalation',
            'bulk_delete',
            'sensitive_data_access',
            'unusual_activity_pattern'
        ])->get();

        $this->assertCount(0, $suspiciousLogs);
    }

    #[Test]
    public function it_tracks_suspicious_activity_with_user_relationship()
    {
        // Trigger suspicious activity
        for ($i = 0; $i < 5; $i++) {
            $this->auditLogger->logLogin($this->user, false);
        }

        $suspiciousActivities = $this->auditLogger->getSuspiciousActivity();
        $suspiciousLog = $suspiciousActivities->first();

        $this->assertTrue($suspiciousLog->relationLoaded('user'));
        $this->assertEquals($this->user->name, $suspiciousLog->user->name);
    }
}