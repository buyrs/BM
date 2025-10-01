<?php

namespace App\Console\Commands;

use App\Services\BackupMonitoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackupMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:monitor 
                            {--health-check : Perform comprehensive health check}
                            {--stats : Show backup statistics}
                            {--notify : Send notifications for issues}';

    /**
     * The console command description.
     */
    protected $description = 'Monitor backup system health and performance';

    protected BackupMonitoringService $monitoringService;

    public function __construct(BackupMonitoringService $monitoringService)
    {
        parent::__construct();
        $this->monitoringService = $monitoringService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('stats')) {
            return $this->showStatistics();
        }

        if ($this->option('health-check')) {
            return $this->performHealthCheck();
        }

        // Default: show basic status
        return $this->showBasicStatus();
    }

    /**
     * Show backup statistics
     */
    protected function showStatistics(): int
    {
        $this->info('Fetching backup statistics...');

        $result = $this->monitoringService->getBackupStatistics();

        if (!$result['success']) {
            $this->error("Failed to get statistics: {$result['message']}");
            return Command::FAILURE;
        }

        $stats = $result['data'];

        $this->info('=== Backup System Statistics ===');
        $this->newLine();

        // Database backup stats
        $this->line('<fg=cyan>Database Backups:</fg=cyan>');
        if (isset($stats['database']['error'])) {
            $this->error("  Error: {$stats['database']['error']}");
        } else {
            $dbStats = $stats['database'];
            $this->line("  Total Backups: {$dbStats['total_count']}");
            $this->line("  Total Size: {$dbStats['total_size_human']}");
            
            if ($dbStats['latest_backup']) {
                $latest = $dbStats['latest_backup'];
                $this->line("  Latest Backup:");
                $this->line("    File: {$latest['filename']}");
                $this->line("    Size: {$latest['size_human']}");
                $this->line("    Age: {$latest['age_hours']} hours");
                $this->line("    Created: {$latest['created_at']->format('Y-m-d H:i:s')}");
            }
        }

        $this->newLine();

        // File backup stats
        $this->line('<fg=cyan>File Backups:</fg=cyan>');
        if (isset($stats['files']['error'])) {
            $this->error("  Error: {$stats['files']['error']}");
        } else {
            $fileStats = $stats['files'];
            $this->line("  Total Backups: {$fileStats['total_count']}");
            $this->line("  Total Size: {$fileStats['total_size_human']}");
            
            if ($fileStats['latest_backup']) {
                $latest = $fileStats['latest_backup'];
                $this->line("  Latest Backup:");
                $this->line("    File: {$latest['filename']}");
                $this->line("    Type: " . ucfirst($latest['type']));
                $this->line("    Size: {$latest['size_human']}");
                $this->line("    Files: " . number_format($latest['file_count']));
                $this->line("    Age: {$latest['age_hours']} hours");
                $this->line("    Created: {$latest['created_at']->format('Y-m-d H:i:s')}");
            }
        }

        $this->newLine();

        // Storage stats
        $this->line('<fg=cyan>Storage:</fg=cyan>');
        $storageStats = $stats['storage'];
        $this->line("  Disk: {$storageStats['disk']}");
        $this->line("  Accessible: " . ($storageStats['accessible'] ? '✓' : '✗'));
        
        if (isset($storageStats['error'])) {
            $this->error("  Error: {$storageStats['error']}");
        } elseif (isset($storageStats['total_space'])) {
            $this->line("  Total Space: {$storageStats['total_space_human']}");
            $this->line("  Used Space: {$storageStats['used_space_human']} ({$storageStats['used_percent']}%)");
            $this->line("  Free Space: {$storageStats['free_space_human']}");
        }

        $this->newLine();
        $this->line("Generated at: {$stats['generated_at']->format('Y-m-d H:i:s')}");

        return Command::SUCCESS;
    }

    /**
     * Perform comprehensive health check
     */
    protected function performHealthCheck(): int
    {
        $this->info('Performing backup system health check...');

        $result = $this->monitoringService->performHealthCheck();

        if (!$result['success']) {
            $this->error("Health check failed: {$result['message']}");
            return Command::FAILURE;
        }

        $healthData = $result['data'];
        $overallStatus = $healthData['overall_status'];

        $this->newLine();
        $this->line('=== Backup System Health Check ===');
        $this->newLine();

        // Overall status
        $statusColor = match($overallStatus) {
            'healthy' => 'green',
            'warning' => 'yellow',
            'error' => 'red',
            default => 'white'
        };

        $statusIcon = match($overallStatus) {
            'healthy' => '✓',
            'warning' => '⚠',
            'error' => '✗',
            default => '?'
        };

        $this->line("<fg={$statusColor}>Overall Status: {$statusIcon} " . ucfirst($overallStatus) . "</fg={$statusColor}>");
        $this->newLine();

        // Detailed results
        foreach ($healthData as $component => $data) {
            if ($component === 'overall_status' || $component === 'checked_at') {
                continue;
            }

            $componentStatus = $data['status'] ?? 'unknown';
            $componentColor = match($componentStatus) {
                'healthy' => 'green',
                'warning' => 'yellow',
                'error' => 'red',
                default => 'white'
            };

            $componentIcon = match($componentStatus) {
                'healthy' => '✓',
                'warning' => '⚠',
                'error' => '✗',
                default => '?'
            };

            $this->line("<fg={$componentColor}>{$componentIcon} " . ucwords(str_replace('_', ' ', $component)) . "</fg={$componentColor}>");

            // Show issues
            if (!empty($data['issues'])) {
                foreach ($data['issues'] as $issue) {
                    $this->line("  <fg=red>• {$issue}</fg=red>");
                }
            }

            // Show key metrics
            if (!empty($data['metrics'])) {
                $importantMetrics = $this->getImportantMetrics($component, $data['metrics']);
                foreach ($importantMetrics as $metric => $value) {
                    $this->line("  <fg=gray>{$metric}: {$value}</fg=gray>");
                }
            }

            $this->newLine();
        }

        $this->line("Checked at: {$healthData['checked_at']->format('Y-m-d H:i:s')}");

        // Return appropriate exit code
        return match($overallStatus) {
            'healthy' => Command::SUCCESS,
            'warning' => 1,
            'error' => 2,
            default => Command::FAILURE
        };
    }

    /**
     * Show basic backup status
     */
    protected function showBasicStatus(): int
    {
        $this->info('Checking backup system status...');

        // Quick health check
        $result = $this->monitoringService->performHealthCheck();

        if (!$result['success']) {
            $this->error("Status check failed: {$result['message']}");
            return Command::FAILURE;
        }

        $healthData = $result['data'];
        $overallStatus = $healthData['overall_status'];

        $statusColor = match($overallStatus) {
            'healthy' => 'green',
            'warning' => 'yellow',
            'error' => 'red',
            default => 'white'
        };

        $statusIcon = match($overallStatus) {
            'healthy' => '✓',
            'warning' => '⚠',
            'error' => '✗',
            default => '?'
        };

        $this->line("<fg={$statusColor}>Backup System Status: {$statusIcon} " . ucfirst($overallStatus) . "</fg={$statusColor}>");

        // Show summary of issues
        $totalIssues = 0;
        foreach ($healthData as $component => $data) {
            if (isset($data['issues'])) {
                $totalIssues += count($data['issues']);
            }
        }

        if ($totalIssues > 0) {
            $this->line("<fg=yellow>Total Issues Found: {$totalIssues}</fg=yellow>");
            $this->line('Run with --health-check for detailed information');
        }

        $this->line('Run with --stats for detailed statistics');

        return Command::SUCCESS;
    }

    /**
     * Get important metrics to display
     */
    protected function getImportantMetrics(string $component, array $metrics): array
    {
        $important = [];

        switch ($component) {
            case 'database_backups':
                if (isset($metrics['total_backups'])) {
                    $important['Total Backups'] = $metrics['total_backups'];
                }
                if (isset($metrics['latest_backup_age_hours'])) {
                    $important['Latest Backup Age'] = $metrics['latest_backup_age_hours'] . ' hours';
                }
                if (isset($metrics['latest_backup_size_mb'])) {
                    $important['Latest Backup Size'] = $metrics['latest_backup_size_mb'] . ' MB';
                }
                break;

            case 'file_backups':
                if (isset($metrics['total_backups'])) {
                    $important['Total Backups'] = $metrics['total_backups'];
                }
                if (isset($metrics['latest_backup_age_hours'])) {
                    $important['Latest Backup Age'] = $metrics['latest_backup_age_hours'] . ' hours';
                }
                if (isset($metrics['latest_backup_file_count'])) {
                    $important['Files in Latest Backup'] = number_format($metrics['latest_backup_file_count']);
                }
                break;

            case 'storage_health':
                if (isset($metrics['free_space_gb'])) {
                    $important['Free Space'] = $metrics['free_space_gb'] . ' GB';
                }
                if (isset($metrics['used_percent'])) {
                    $important['Storage Used'] = $metrics['used_percent'] . '%';
                }
                break;

            case 'retention_compliance':
                if (isset($metrics['daily_backups_count'])) {
                    $important['Daily Backups'] = $metrics['daily_backups_count'];
                }
                if (isset($metrics['weekly_backups_count'])) {
                    $important['Weekly Backups'] = $metrics['weekly_backups_count'];
                }
                break;
        }

        return $important;
    }
}