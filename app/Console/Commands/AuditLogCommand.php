<?php

namespace App\Console\Commands;

use App\Services\AuditLogger;
use Illuminate\Console\Command;

class AuditLogCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'audit:manage 
                            {action : The action to perform (cleanup, stats, suspicious)}
                            {--days=365 : Number of days for retention (cleanup) or analysis (stats)}
                            {--dry-run : Show what would be done without actually doing it}';

    /**
     * The console command description.
     */
    protected $description = 'Manage audit logs - cleanup old logs, show statistics, or find suspicious activity';

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
        $action = $this->argument('action');

        switch ($action) {
            case 'cleanup':
                return $this->handleCleanup();
            case 'stats':
                return $this->handleStats();
            case 'suspicious':
                return $this->handleSuspicious();
            default:
                $this->error("Unknown action: {$action}");
                $this->info('Available actions: cleanup, stats, suspicious');
                return 1;
        }
    }

    /**
     * Handle cleanup of old audit logs
     */
    protected function handleCleanup(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("Audit log cleanup - retention: {$days} days");

        if ($dryRun) {
            $count = \App\Models\AuditLog::where('created_at', '<', now()->subDays($days))->count();
            $this->info("DRY RUN: Would delete {$count} audit log records");
            return 0;
        }

        $this->info('Starting cleanup...');
        $deletedCount = $this->auditLogger->cleanupOldLogs($days);

        if ($deletedCount > 0) {
            $this->info("Successfully deleted {$deletedCount} old audit log records");
        } else {
            $this->info('No old audit logs found to delete');
        }

        return 0;
    }

    /**
     * Handle audit log statistics
     */
    protected function handleStats(): int
    {
        $days = (int) $this->option('days');
        $startDate = now()->subDays($days);

        $this->info("Audit log statistics for the last {$days} days:");
        $this->newLine();

        // Total logs
        $totalLogs = \App\Models\AuditLog::where('created_at', '>=', $startDate)->count();
        $this->info("Total audit logs: {$totalLogs}");

        // Logs by action
        $actionStats = \App\Models\AuditLog::where('created_at', '>=', $startDate)
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        $this->newLine();
        $this->info('Top 10 actions:');
        $this->table(['Action', 'Count'], $actionStats->map(function ($stat) {
            return [$stat->action, $stat->count];
        })->toArray());

        // Logs by user
        $userStats = \App\Models\AuditLog::with('user')
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('user_id')
            ->selectRaw('user_id, COUNT(*) as count')
            ->groupBy('user_id')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        $this->newLine();
        $this->info('Top 10 most active users:');
        $this->table(['User', 'Count'], $userStats->map(function ($stat) {
            return [$stat->user->name ?? 'Unknown', $stat->count];
        })->toArray());

        // Logs by resource type
        $resourceStats = \App\Models\AuditLog::where('created_at', '>=', $startDate)
            ->whereNotNull('resource_type')
            ->selectRaw('resource_type, COUNT(*) as count')
            ->groupBy('resource_type')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        $this->newLine();
        $this->info('Top 10 resource types:');
        $this->table(['Resource Type', 'Count'], $resourceStats->map(function ($stat) {
            return [class_basename($stat->resource_type), $stat->count];
        })->toArray());

        return 0;
    }

    /**
     * Handle suspicious activity detection
     */
    protected function handleSuspicious(): int
    {
        $days = (int) $this->option('days');
        
        $this->info("Suspicious activity analysis for the last {$days} days:");
        $this->newLine();

        $suspiciousLogs = $this->auditLogger->getSuspiciousActivity($days);

        if ($suspiciousLogs->isEmpty()) {
            $this->info('No suspicious activity detected');
            return 0;
        }

        $this->warn("Found {$suspiciousLogs->count()} suspicious activities:");
        $this->newLine();

        $tableData = $suspiciousLogs->map(function ($log) {
            return [
                $log->created_at->format('Y-m-d H:i:s'),
                $log->user->name ?? 'Unknown',
                $log->action,
                $log->ip_address ?? 'N/A',
            ];
        })->toArray();

        $this->table(['Timestamp', 'User', 'Action', 'IP Address'], $tableData);

        // Show details for each suspicious activity
        foreach ($suspiciousLogs as $log) {
            $this->newLine();
            $this->warn("Suspicious Activity Details:");
            $this->info("ID: {$log->id}");
            $this->info("User: " . ($log->user->name ?? 'Unknown'));
            $this->info("Action: {$log->action}");
            $this->info("Time: {$log->created_at->format('Y-m-d H:i:s')}");
            $this->info("IP: " . ($log->ip_address ?? 'N/A'));
            
            if ($log->changes) {
                $this->info("Details: " . json_encode($log->changes, JSON_PRETTY_PRINT));
            }
            
            $this->info(str_repeat('-', 50));
        }

        return 0;
    }
}