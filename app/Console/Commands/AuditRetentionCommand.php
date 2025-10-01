<?php

namespace App\Console\Commands;

use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class AuditRetentionCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'audit:retention 
                            {--retention-days=365 : Number of days to retain audit logs}
                            {--archive : Archive old logs before deletion}
                            {--archive-path=audit-archives : Path for archived logs}
                            {--dry-run : Show what would be done without actually doing it}';

    /**
     * The console command description.
     */
    protected $description = 'Manage audit log retention and archival';

    protected AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        parent::__construct();
        $this->auditLogger = $auditLogger;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $retentionDays = (int) $this->option('retention-days');
        $archive = $this->option('archive');
        $archivePath = $this->option('archive-path');
        $dryRun = $this->option('dry-run');

        $this->info("Audit Log Retention Management");
        $this->info("Retention period: {$retentionDays} days");
        $this->info("Archive before deletion: " . ($archive ? 'Yes' : 'No'));
        
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No changes will be made");
        }

        $this->newLine();

        // Get logs that will be affected
        $cutoffDate = now()->subDays($retentionDays);
        $oldLogs = \App\Models\AuditLog::where('created_at', '<', $cutoffDate);
        $count = $oldLogs->count();

        if ($count === 0) {
            $this->info('No audit logs found that exceed the retention period.');
            return 0;
        }

        $this->info("Found {$count} audit logs older than {$retentionDays} days (before {$cutoffDate->format('Y-m-d H:i:s')})");

        // Archive if requested
        if ($archive) {
            $this->info('Archiving old audit logs...');
            
            if (!$dryRun) {
                $archiveResult = $this->archiveLogs($oldLogs->get(), $archivePath);
                
                if ($archiveResult['success']) {
                    $this->info("Successfully archived {$archiveResult['count']} logs to {$archiveResult['filename']}");
                } else {
                    $this->error("Failed to archive logs: {$archiveResult['error']}");
                    return 1;
                }
            } else {
                $this->info("DRY RUN: Would archive {$count} logs to {$archivePath}");
            }
        }

        // Delete old logs
        $this->info('Deleting old audit logs...');
        
        if (!$dryRun) {
            $deletedCount = $this->auditLogger->cleanupOldLogs($retentionDays);
            $this->info("Successfully deleted {$deletedCount} old audit log records");
        } else {
            $this->info("DRY RUN: Would delete {$count} audit log records");
        }

        // Show retention summary
        $this->newLine();
        $this->info('Retention Summary:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Retention Period', "{$retentionDays} days"],
                ['Cutoff Date', $cutoffDate->format('Y-m-d H:i:s')],
                ['Records Processed', $count],
                ['Archived', $archive ? 'Yes' : 'No'],
                ['Status', $dryRun ? 'Dry Run' : 'Completed'],
            ]
        );

        return 0;
    }

    /**
     * Archive audit logs to storage
     */
    protected function archiveLogs($logs, string $archivePath): array
    {
        try {
            $filename = 'audit_logs_archive_' . now()->format('Y-m-d_H-i-s') . '.json';
            $fullPath = $archivePath . '/' . $filename;

            // Prepare archive data
            $archiveData = [
                'metadata' => [
                    'created_at' => now()->toISOString(),
                    'total_records' => $logs->count(),
                    'date_range' => [
                        'from' => $logs->min('created_at'),
                        'to' => $logs->max('created_at'),
                    ],
                    'version' => '1.0',
                ],
                'logs' => $logs->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'user_id' => $log->user_id,
                        'user_name' => $log->user->name ?? null,
                        'action' => $log->action,
                        'resource_type' => $log->resource_type,
                        'resource_id' => $log->resource_id,
                        'changes' => $log->changes,
                        'ip_address' => $log->ip_address,
                        'user_agent' => $log->user_agent,
                        'created_at' => $log->created_at->toISOString(),
                    ];
                })->toArray(),
            ];

            // Store the archive
            $jsonData = json_encode($archiveData, JSON_PRETTY_PRINT);
            Storage::put($fullPath, $jsonData);

            // Compress the archive if possible
            if (extension_loaded('zlib')) {
                $compressedPath = $fullPath . '.gz';
                $compressedData = gzencode($jsonData, 9);
                Storage::put($compressedPath, $compressedData);
                
                // Remove uncompressed version
                Storage::delete($fullPath);
                $filename .= '.gz';
            }

            return [
                'success' => true,
                'count' => $logs->count(),
                'filename' => $filename,
                'path' => $fullPath,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}