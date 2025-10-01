<?php

namespace Tests\Feature;

use App\Console\Commands\AuditRetentionCommand;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuditRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected AuditLogger $auditLogger;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditLogger = new AuditLogger();
        $this->user = User::factory()->create();
        
        // Use fake storage for testing
        Storage::fake('local');
    }

    #[Test]
    public function it_can_clean_up_old_audit_logs()
    {
        // Create old logs (older than 365 days)
        AuditLog::factory()->count(5)->create([
            'created_at' => now()->subDays(400)
        ]);

        // Create recent logs (within 365 days)
        AuditLog::factory()->count(3)->create([
            'created_at' => now()->subDays(30)
        ]);

        $this->assertEquals(8, AuditLog::count());

        $deletedCount = $this->auditLogger->cleanupOldLogs(365);

        $this->assertEquals(5, $deletedCount);
        // Should have 3 recent logs + 1 cleanup log = 4 total
        $this->assertEquals(4, AuditLog::count());
    }

    #[Test]
    public function it_creates_audit_log_for_cleanup_operation()
    {
        // Create old logs
        AuditLog::factory()->count(3)->create([
            'created_at' => now()->subDays(400)
        ]);

        $initialCount = AuditLog::count();
        $deletedCount = $this->auditLogger->cleanupOldLogs(365);

        // Should have deleted 3 logs but created 1 cleanup log
        $this->assertEquals(3, $deletedCount);
        $this->assertEquals($initialCount - 3 + 1, AuditLog::count());

        // Check cleanup log was created
        $cleanupLog = AuditLog::where('action', 'audit_log_cleanup')->first();
        $this->assertNotNull($cleanupLog);
        $this->assertEquals(3, $cleanupLog->changes['deleted_count']);
        $this->assertEquals(365, $cleanupLog->changes['retention_days']);
        $this->assertArrayHasKey('cutoff_date', $cleanupLog->changes);
    }

    #[Test]
    public function it_does_not_create_cleanup_log_when_no_logs_deleted()
    {
        // Create only recent logs
        AuditLog::factory()->count(3)->create([
            'created_at' => now()->subDays(30)
        ]);

        $deletedCount = $this->auditLogger->cleanupOldLogs(365);

        $this->assertEquals(0, $deletedCount);
        $this->assertEquals(3, AuditLog::count());

        // Should not create cleanup log
        $cleanupLog = AuditLog::where('action', 'audit_log_cleanup')->first();
        $this->assertNull($cleanupLog);
    }

    #[Test]
    public function it_respects_custom_retention_period()
    {
        // Create logs at different ages
        AuditLog::factory()->create(['created_at' => now()->subDays(50)]);  // Should be deleted with 30-day retention
        AuditLog::factory()->create(['created_at' => now()->subDays(20)]);  // Should be kept
        AuditLog::factory()->create(['created_at' => now()->subDays(10)]);  // Should be kept

        $deletedCount = $this->auditLogger->cleanupOldLogs(30);

        $this->assertEquals(1, $deletedCount);
        // Should have 2 recent logs + 1 cleanup log = 3 total
        $this->assertEquals(3, AuditLog::count());
    }

    #[Test]
    public function audit_retention_command_shows_dry_run_information()
    {
        // Create old logs
        AuditLog::factory()->count(5)->create([
            'created_at' => now()->subDays(400)
        ]);

        $this->artisan('audit:retention --dry-run')
            ->expectsOutput('DRY RUN MODE - No changes will be made')
            ->expectsOutput('Found 5 audit logs older than 365 days')
            ->expectsOutput('DRY RUN: Would delete 5 audit log records')
            ->assertExitCode(0);

        // Logs should not be deleted in dry run
        $this->assertEquals(5, AuditLog::count());
    }

    #[Test]
    public function audit_retention_command_deletes_old_logs()
    {
        // Create old and new logs
        AuditLog::factory()->count(3)->create([
            'created_at' => now()->subDays(400)
        ]);
        AuditLog::factory()->count(2)->create([
            'created_at' => now()->subDays(30)
        ]);

        $this->artisan('audit:retention')
            ->expectsOutput('Found 3 audit logs older than 365 days')
            ->expectsOutput('Successfully deleted 3 old audit log records')
            ->assertExitCode(0);

        // Should have 2 recent logs + 1 cleanup log
        $this->assertEquals(3, AuditLog::count());
    }

    #[Test]
    public function audit_retention_command_accepts_custom_retention_days()
    {
        AuditLog::factory()->count(2)->create([
            'created_at' => now()->subDays(100)
        ]);
        AuditLog::factory()->count(3)->create([
            'created_at' => now()->subDays(20)
        ]);

        $this->artisan('audit:retention --retention-days=50')
            ->expectsOutput('Retention period: 50 days')
            ->expectsOutput('Found 2 audit logs older than 50 days')
            ->expectsOutput('Successfully deleted 2 old audit log records')
            ->assertExitCode(0);

        // Should have 3 recent logs + 1 cleanup log
        $this->assertEquals(4, AuditLog::count());
    }

    #[Test]
    public function audit_retention_command_handles_no_old_logs()
    {
        // Create only recent logs
        AuditLog::factory()->count(3)->create([
            'created_at' => now()->subDays(30)
        ]);

        $this->artisan('audit:retention')
            ->expectsOutput('No audit logs found that exceed the retention period.')
            ->assertExitCode(0);

        $this->assertEquals(3, AuditLog::count());
    }

    #[Test]
    public function audit_retention_command_can_archive_before_deletion()
    {
        // Create old logs with user relationships
        $user = User::factory()->create(['name' => 'Test User']);
        AuditLog::factory()->count(3)->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(400)
        ]);

        $this->artisan('audit:retention --archive')
            ->expectsOutput('Archive before deletion: Yes')
            ->expectsOutput('Archiving old audit logs...')
            ->expectsOutput('Successfully archived 3 logs to')
            ->expectsOutput('Successfully deleted 3 old audit log records')
            ->assertExitCode(0);

        // Check that archive file was created
        $files = Storage::files('audit-archives');
        $this->assertCount(1, $files);
        
        $archiveFile = $files[0];
        $this->assertStringContainsString('audit_logs_archive_', $archiveFile);
    }

    #[Test]
    public function audit_retention_command_dry_run_with_archive()
    {
        AuditLog::factory()->count(2)->create([
            'created_at' => now()->subDays(400)
        ]);

        $this->artisan('audit:retention --archive --dry-run')
            ->expectsOutput('DRY RUN: Would archive 2 logs to audit-archives')
            ->expectsOutput('DRY RUN: Would delete 2 audit log records')
            ->assertExitCode(0);

        // No files should be created in dry run
        $files = Storage::files('audit-archives');
        $this->assertCount(0, $files);
    }

    #[Test]
    public function audit_retention_command_uses_custom_archive_path()
    {
        AuditLog::factory()->count(2)->create([
            'created_at' => now()->subDays(400)
        ]);

        $this->artisan('audit:retention --archive --archive-path=custom-archives')
            ->assertExitCode(0);

        $files = Storage::files('custom-archives');
        $this->assertCount(1, $files);
    }

    #[Test]
    public function archive_contains_correct_metadata_and_log_data()
    {
        $user = User::factory()->create(['name' => 'Archive Test User']);
        
        $oldLog = AuditLog::create([
            'user_id' => $user->id,
            'action' => 'test_action',
            'resource_type' => 'App\Models\Property',
            'resource_id' => 123,
            'changes' => ['name' => 'Test Property'],
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser',
            'created_at' => now()->subDays(400)
        ]);

        $this->artisan('audit:retention --archive')
            ->assertExitCode(0);

        $files = Storage::files('audit-archives');
        $archiveContent = Storage::get($files[0]);
        $archiveData = json_decode($archiveContent, true);

        // Check metadata
        $this->assertArrayHasKey('metadata', $archiveData);
        $this->assertEquals(1, $archiveData['metadata']['total_records']);
        $this->assertEquals('1.0', $archiveData['metadata']['version']);
        $this->assertArrayHasKey('created_at', $archiveData['metadata']);

        // Check log data
        $this->assertArrayHasKey('logs', $archiveData);
        $this->assertCount(1, $archiveData['logs']);
        
        $archivedLog = $archiveData['logs'][0];
        $this->assertEquals($oldLog->id, $archivedLog['id']);
        $this->assertEquals($user->id, $archivedLog['user_id']);
        $this->assertEquals('Archive Test User', $archivedLog['user_name']);
        $this->assertEquals('test_action', $archivedLog['action']);
        $this->assertEquals(['name' => 'Test Property'], $archivedLog['changes']);
    }

    #[Test]
    public function retention_command_displays_summary_table()
    {
        AuditLog::factory()->count(3)->create([
            'created_at' => now()->subDays(400)
        ]);

        $this->artisan('audit:retention --retention-days=200')
            ->expectsTable(
                ['Metric', 'Value'],
                [
                    ['Retention Period', '200 days'],
                    ['Records Processed', '3'],
                    ['Archived', 'No'],
                    ['Status', 'Completed'],
                ]
            )
            ->assertExitCode(0);
    }
}