<?php

namespace App\Services;

use App\Services\DatabaseBackupService;
use App\Services\FileBackupService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Exception;

class BackupMonitoringService extends BaseService
{
    protected DatabaseBackupService $databaseBackupService;
    protected FileBackupService $fileBackupService;
    protected NotificationService $notificationService;
    protected array $config;

    public function __construct(
        DatabaseBackupService $databaseBackupService,
        FileBackupService $fileBackupService,
        NotificationService $notificationService
    ) {
        $this->databaseBackupService = $databaseBackupService;
        $this->fileBackupService = $fileBackupService;
        $this->notificationService = $notificationService;
        $this->config = config('backup.monitoring', []);
    }

    /**
     * Perform comprehensive backup health check
     */
    public function performHealthCheck(): array
    {
        try {
            $this->info('Starting backup health check...');
            
            $results = [
                'database_backups' => $this->checkDatabaseBackups(),
                'file_backups' => $this->checkFileBackups(),
                'storage_health' => $this->checkStorageHealth(),
                'retention_compliance' => $this->checkRetentionCompliance(),
                'overall_status' => 'healthy'
            ];
            
            // Determine overall status
            $hasErrors = false;
            $hasWarnings = false;
            
            foreach ($results as $key => $result) {
                if ($key === 'overall_status') continue;
                
                if (isset($result['status'])) {
                    if ($result['status'] === 'error') {
                        $hasErrors = true;
                    } elseif ($result['status'] === 'warning') {
                        $hasWarnings = true;
                    }
                }
            }
            
            if ($hasErrors) {
                $results['overall_status'] = 'error';
            } elseif ($hasWarnings) {
                $results['overall_status'] = 'warning';
            }
            
            $results['checked_at'] = Carbon::now();
            
            // Send notifications if needed
            $this->handleHealthCheckNotifications($results);
            
            // Send to external monitoring if configured
            $this->sendToExternalMonitoring($results);
            
            Log::info('Backup health check completed', [
                'overall_status' => $results['overall_status'],
                'checked_at' => $results['checked_at']
            ]);
            
            return $this->success('Backup health check completed', $results);
            
        } catch (Exception $e) {
            Log::error('Backup health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->error('Backup health check failed: ' . $e->getMessage());
        }
    }

    /**
     * Check database backup health
     */
    protected function checkDatabaseBackups(): array
    {
        $result = [
            'status' => 'healthy',
            'issues' => [],
            'metrics' => []
        ];
        
        try {
            $backupsResult = $this->databaseBackupService->listBackups();
            
            if (!$backupsResult['success']) {
                $result['status'] = 'error';
                $result['issues'][] = 'Failed to list database backups: ' . $backupsResult['message'];
                return $result;
            }
            
            $backups = $backupsResult['data'];
            $result['metrics']['total_backups'] = count($backups);
            
            if (empty($backups)) {
                $result['status'] = 'error';
                $result['issues'][] = 'No database backups found';
                return $result;
            }
            
            // Check backup age
            $latestBackup = $backups[0];
            $maxAgeHours = $this->config['max_backup_age_hours'] ?? 25;
            $backupAge = Carbon::now()->diffInHours($latestBackup['created_at']);
            
            $result['metrics']['latest_backup_age_hours'] = $backupAge;
            
            if ($backupAge > $maxAgeHours) {
                $result['status'] = 'warning';
                $result['issues'][] = "Latest database backup is {$backupAge} hours old (max: {$maxAgeHours})";
            }
            
            // Check backup size
            $minSizeMB = $this->config['min_backup_size_mb'] ?? 1;
            $latestSizeMB = $latestBackup['size'] / (1024 * 1024);
            
            $result['metrics']['latest_backup_size_mb'] = round($latestSizeMB, 2);
            
            if ($latestSizeMB < $minSizeMB) {
                $result['status'] = 'warning';
                $result['issues'][] = "Latest database backup is only {$latestSizeMB}MB (min: {$minSizeMB}MB)";
            }
            
            // Verify latest backup integrity
            $verifyResult = $this->databaseBackupService->verifyBackup($latestBackup['path']);
            
            if (!$verifyResult['success']) {
                $result['status'] = 'error';
                $result['issues'][] = 'Latest database backup failed integrity check: ' . $verifyResult['message'];
            } else {
                $result['metrics']['latest_backup_verified'] = true;
            }
            
        } catch (Exception $e) {
            $result['status'] = 'error';
            $result['issues'][] = 'Database backup check failed: ' . $e->getMessage();
        }
        
        return $result;
    }

    /**
     * Check file backup health
     */
    protected function checkFileBackups(): array
    {
        $result = [
            'status' => 'healthy',
            'issues' => [],
            'metrics' => []
        ];
        
        try {
            $backupsResult = $this->fileBackupService->listBackups();
            
            if (!$backupsResult['success']) {
                $result['status'] = 'error';
                $result['issues'][] = 'Failed to list file backups: ' . $backupsResult['message'];
                return $result;
            }
            
            $backups = $backupsResult['data'];
            $result['metrics']['total_backups'] = count($backups);
            
            if (empty($backups)) {
                $result['status'] = 'warning';
                $result['issues'][] = 'No file backups found';
                return $result;
            }
            
            // Check backup age
            $latestBackup = $backups[0];
            $maxAgeHours = ($this->config['max_backup_age_hours'] ?? 25) * 7; // Files backed up less frequently
            $backupAge = Carbon::now()->diffInHours($latestBackup['created_at']);
            
            $result['metrics']['latest_backup_age_hours'] = $backupAge;
            
            if ($backupAge > $maxAgeHours) {
                $result['status'] = 'warning';
                $result['issues'][] = "Latest file backup is {$backupAge} hours old (max: {$maxAgeHours})";
            }
            
            // Check backup size
            $latestSizeMB = $latestBackup['size'] / (1024 * 1024);
            $result['metrics']['latest_backup_size_mb'] = round($latestSizeMB, 2);
            
            // Verify latest backup integrity
            $verifyResult = $this->fileBackupService->verifyBackup($latestBackup['path']);
            
            if (!$verifyResult['success']) {
                $result['status'] = 'error';
                $result['issues'][] = 'Latest file backup failed integrity check: ' . $verifyResult['message'];
            } else {
                $result['metrics']['latest_backup_verified'] = true;
                $result['metrics']['latest_backup_file_count'] = $verifyResult['data']['file_count'];
            }
            
        } catch (Exception $e) {
            $result['status'] = 'error';
            $result['issues'][] = 'File backup check failed: ' . $e->getMessage();
        }
        
        return $result;
    }

    /**
     * Check storage health
     */
    protected function checkStorageHealth(): array
    {
        $result = [
            'status' => 'healthy',
            'issues' => [],
            'metrics' => []
        ];
        
        try {
            $backupDisk = config('backup.disk', 'local');
            
            // Check if backup disk is accessible
            try {
                $testFile = 'backup_health_check_' . time() . '.txt';
                Storage::disk($backupDisk)->put($testFile, 'health check');
                Storage::disk($backupDisk)->delete($testFile);
                
                $result['metrics']['storage_accessible'] = true;
            } catch (Exception $e) {
                $result['status'] = 'error';
                $result['issues'][] = 'Backup storage is not accessible: ' . $e->getMessage();
                return $result;
            }
            
            // Check available space (if possible)
            if ($backupDisk === 'local') {
                $storagePath = Storage::disk($backupDisk)->path('');
                $freeBytes = disk_free_space($storagePath);
                $totalBytes = disk_total_space($storagePath);
                
                if ($freeBytes !== false && $totalBytes !== false) {
                    $freeGB = $freeBytes / (1024 * 1024 * 1024);
                    $usedPercent = (($totalBytes - $freeBytes) / $totalBytes) * 100;
                    
                    $result['metrics']['free_space_gb'] = round($freeGB, 2);
                    $result['metrics']['used_percent'] = round($usedPercent, 2);
                    
                    if ($usedPercent > 90) {
                        $result['status'] = 'error';
                        $result['issues'][] = "Storage is {$usedPercent}% full";
                    } elseif ($usedPercent > 80) {
                        $result['status'] = 'warning';
                        $result['issues'][] = "Storage is {$usedPercent}% full";
                    }
                }
            }
            
            // Check cloud storage if configured
            if (config('backup.cloud.enabled')) {
                $cloudDisk = config('backup.cloud.disk');
                
                try {
                    $testFile = 'cloud_health_check_' . time() . '.txt';
                    Storage::disk($cloudDisk)->put($testFile, 'health check');
                    Storage::disk($cloudDisk)->delete($testFile);
                    
                    $result['metrics']['cloud_storage_accessible'] = true;
                } catch (Exception $e) {
                    $result['status'] = 'warning';
                    $result['issues'][] = 'Cloud backup storage is not accessible: ' . $e->getMessage();
                }
            }
            
        } catch (Exception $e) {
            $result['status'] = 'error';
            $result['issues'][] = 'Storage health check failed: ' . $e->getMessage();
        }
        
        return $result;
    }

    /**
     * Check retention policy compliance
     */
    protected function checkRetentionCompliance(): array
    {
        $result = [
            'status' => 'healthy',
            'issues' => [],
            'metrics' => []
        ];
        
        try {
            $retention = config('backup.retention', []);
            
            // Check database backup retention
            $dbBackupsResult = $this->databaseBackupService->listBackups();
            if ($dbBackupsResult['success']) {
                $dbBackups = $dbBackupsResult['data'];
                $result['metrics']['database_backups_count'] = count($dbBackups);
                
                // Check if we have backups within retention periods
                $dailyRetention = $retention['daily'] ?? 7;
                $weeklyRetention = $retention['weekly'] ?? 4;
                $monthlyRetention = $retention['monthly'] ?? 12;
                
                $now = Carbon::now();
                $dailyBackups = array_filter($dbBackups, fn($b) => $now->diffInDays($b['created_at']) <= $dailyRetention);
                $weeklyBackups = array_filter($dbBackups, fn($b) => $now->diffInWeeks($b['created_at']) <= $weeklyRetention);
                $monthlyBackups = array_filter($dbBackups, fn($b) => $now->diffInMonths($b['created_at']) <= $monthlyRetention);
                
                $result['metrics']['daily_backups_count'] = count($dailyBackups);
                $result['metrics']['weekly_backups_count'] = count($weeklyBackups);
                $result['metrics']['monthly_backups_count'] = count($monthlyBackups);
                
                if (count($dailyBackups) < min(3, $dailyRetention)) {
                    $result['status'] = 'warning';
                    $result['issues'][] = 'Insufficient daily database backups';
                }
            }
            
            // Check file backup retention
            $fileBackupsResult = $this->fileBackupService->listBackups();
            if ($fileBackupsResult['success']) {
                $fileBackups = $fileBackupsResult['data'];
                $result['metrics']['file_backups_count'] = count($fileBackups);
                
                if (count($fileBackups) < 2) {
                    $result['status'] = 'warning';
                    $result['issues'][] = 'Insufficient file backups';
                }
            }
            
        } catch (Exception $e) {
            $result['status'] = 'error';
            $result['issues'][] = 'Retention compliance check failed: ' . $e->getMessage();
        }
        
        return $result;
    }

    /**
     * Handle health check notifications
     */
    protected function handleHealthCheckNotifications(array $results): void
    {
        if (!config('backup.notifications.enabled', true)) {
            return;
        }
        
        $events = config('backup.notifications.events', []);
        $overallStatus = $results['overall_status'];
        
        // Send notification for errors or warnings
        if ($overallStatus === 'error' || $overallStatus === 'warning') {
            $this->sendHealthCheckNotification($results);
        }
    }

    /**
     * Send health check notification
     */
    protected function sendHealthCheckNotification(array $results): void
    {
        try {
            $status = $results['overall_status'];
            $title = $status === 'error' ? 'Backup System Alert' : 'Backup System Warning';
            
            $issues = [];
            foreach ($results as $key => $result) {
                if (isset($result['issues']) && !empty($result['issues'])) {
                    $issues = array_merge($issues, $result['issues']);
                }
            }
            
            $message = "Backup health check detected issues:\n\n" . implode("\n", $issues);
            
            // Send to admin users
            $adminUsers = \App\Models\User::where('role', 'admin')->get();
            
            foreach ($adminUsers as $user) {
                $this->notificationService->send($user, [
                    'type' => 'backup_health_alert',
                    'title' => $title,
                    'message' => $message,
                    'priority' => $status === 'error' ? 'high' : 'medium',
                    'data' => $results,
                    'channels' => ['database', 'email']
                ]);
            }
            
        } catch (Exception $e) {
            Log::error('Failed to send backup health notification', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send results to external monitoring service
     */
    protected function sendToExternalMonitoring(array $results): void
    {
        $healthCheckUrl = $this->config['health_check_url'] ?? null;
        
        if (!$healthCheckUrl) {
            return;
        }
        
        try {
            $payload = [
                'service' => 'backup_system',
                'status' => $results['overall_status'],
                'timestamp' => $results['checked_at']->toISOString(),
                'metrics' => $this->extractMetrics($results),
                'issues' => $this->extractIssues($results)
            ];
            
            $response = Http::timeout(10)->post($healthCheckUrl, $payload);
            
            if ($response->successful()) {
                Log::info('Backup health check sent to external monitoring');
            } else {
                Log::warning('Failed to send backup health check to external monitoring', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
            
        } catch (Exception $e) {
            Log::error('Error sending backup health check to external monitoring', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Extract metrics from health check results
     */
    protected function extractMetrics(array $results): array
    {
        $metrics = [];
        
        foreach ($results as $key => $result) {
            if (isset($result['metrics'])) {
                foreach ($result['metrics'] as $metricKey => $metricValue) {
                    $metrics["{$key}_{$metricKey}"] = $metricValue;
                }
            }
        }
        
        return $metrics;
    }

    /**
     * Extract issues from health check results
     */
    protected function extractIssues(array $results): array
    {
        $issues = [];
        
        foreach ($results as $key => $result) {
            if (isset($result['issues'])) {
                $issues = array_merge($issues, $result['issues']);
            }
        }
        
        return $issues;
    }

    /**
     * Get backup statistics
     */
    public function getBackupStatistics(): array
    {
        try {
            $stats = [
                'database' => $this->getDatabaseBackupStats(),
                'files' => $this->getFileBackupStats(),
                'storage' => $this->getStorageStats(),
                'generated_at' => Carbon::now()
            ];
            
            return $this->success('Backup statistics retrieved', $stats);
            
        } catch (Exception $e) {
            return $this->error('Failed to get backup statistics: ' . $e->getMessage());
        }
    }

    /**
     * Get database backup statistics
     */
    protected function getDatabaseBackupStats(): array
    {
        $backupsResult = $this->databaseBackupService->listBackups();
        
        if (!$backupsResult['success']) {
            return ['error' => $backupsResult['message']];
        }
        
        $backups = $backupsResult['data'];
        
        if (empty($backups)) {
            return [
                'total_count' => 0,
                'total_size' => 0,
                'latest_backup' => null
            ];
        }
        
        $totalSize = array_sum(array_column($backups, 'size'));
        $latest = $backups[0];
        
        return [
            'total_count' => count($backups),
            'total_size' => $totalSize,
            'total_size_human' => $this->formatBytes($totalSize),
            'latest_backup' => [
                'filename' => $latest['filename'],
                'size' => $latest['size'],
                'size_human' => $latest['size_human'],
                'created_at' => $latest['created_at'],
                'age_hours' => Carbon::now()->diffInHours($latest['created_at'])
            ]
        ];
    }

    /**
     * Get file backup statistics
     */
    protected function getFileBackupStats(): array
    {
        $backupsResult = $this->fileBackupService->listBackups();
        
        if (!$backupsResult['success']) {
            return ['error' => $backupsResult['message']];
        }
        
        $backups = $backupsResult['data'];
        
        if (empty($backups)) {
            return [
                'total_count' => 0,
                'total_size' => 0,
                'latest_backup' => null
            ];
        }
        
        $totalSize = array_sum(array_column($backups, 'size'));
        $latest = $backups[0];
        
        return [
            'total_count' => count($backups),
            'total_size' => $totalSize,
            'total_size_human' => $this->formatBytes($totalSize),
            'latest_backup' => [
                'filename' => $latest['filename'],
                'type' => $latest['type'],
                'size' => $latest['size'],
                'size_human' => $latest['size_human'],
                'file_count' => $latest['file_count'],
                'created_at' => $latest['created_at'],
                'age_hours' => Carbon::now()->diffInHours($latest['created_at'])
            ]
        ];
    }

    /**
     * Get storage statistics
     */
    protected function getStorageStats(): array
    {
        $backupDisk = config('backup.disk', 'local');
        
        $stats = [
            'disk' => $backupDisk,
            'accessible' => false
        ];
        
        try {
            // Test accessibility
            $testFile = 'storage_test_' . time() . '.txt';
            Storage::disk($backupDisk)->put($testFile, 'test');
            Storage::disk($backupDisk)->delete($testFile);
            $stats['accessible'] = true;
            
            // Get space info for local disk
            if ($backupDisk === 'local') {
                $storagePath = Storage::disk($backupDisk)->path('');
                $freeBytes = disk_free_space($storagePath);
                $totalBytes = disk_total_space($storagePath);
                
                if ($freeBytes !== false && $totalBytes !== false) {
                    $usedBytes = $totalBytes - $freeBytes;
                    
                    $stats['total_space'] = $totalBytes;
                    $stats['used_space'] = $usedBytes;
                    $stats['free_space'] = $freeBytes;
                    $stats['used_percent'] = round(($usedBytes / $totalBytes) * 100, 2);
                    $stats['total_space_human'] = $this->formatBytes($totalBytes);
                    $stats['used_space_human'] = $this->formatBytes($usedBytes);
                    $stats['free_space_human'] = $this->formatBytes($freeBytes);
                }
            }
            
        } catch (Exception $e) {
            $stats['error'] = $e->getMessage();
        }
        
        return $stats;
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}