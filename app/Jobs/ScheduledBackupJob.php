<?php

namespace App\Jobs;

use App\Services\DatabaseBackupService;
use App\Services\FileBackupService;
use App\Services\BackupMonitoringService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ScheduledBackupJob extends BaseJob
{
    protected string $backupType;
    protected array $options;

    public function __construct(string $backupType, array $options = [])
    {
        parent::__construct();
        $this->backupType = $backupType;
        $this->options = $options;
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        try {
            Log::info('Starting scheduled backup job', [
                'type' => $this->backupType,
                'options' => $this->options
            ]);

            $result = match($this->backupType) {
                'database' => $this->createDatabaseBackup(),
                'files' => $this->createFileBackup(),
                'health_check' => $this->performHealthCheck(),
                'cleanup' => $this->performCleanup(),
                default => throw new \InvalidArgumentException("Unknown backup type: {$this->backupType}")
            };

            if ($result['success']) {
                Log::info('Scheduled backup job completed successfully', [
                    'type' => $this->backupType,
                    'result' => $result['data'] ?? null
                ]);

                // Send success notification if configured
                if (config('backup.notifications.events.backup_success', false)) {
                    $this->sendSuccessNotification($result);
                }
            } else {
                Log::error('Scheduled backup job failed', [
                    'type' => $this->backupType,
                    'error' => $result['message']
                ]);

                $this->sendFailureNotification($result);
                $this->fail(new \Exception($result['message']));
            }

        } catch (\Exception $e) {
            Log::error('Scheduled backup job exception', [
                'type' => $this->backupType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->sendFailureNotification([
                'success' => false,
                'message' => $e->getMessage()
            ]);

            $this->fail($e);
        }
    }

    /**
     * Create database backup
     */
    protected function createDatabaseBackup(): array
    {
        $service = app(DatabaseBackupService::class);
        
        $options = array_merge([
            'compress' => config('backup.options.compress', true),
            'encrypt' => config('backup.options.encrypt', false),
            'verify' => config('backup.options.verify', true)
        ], $this->options);

        $result = $service->createBackup($options);

        // Verify backup if requested and creation was successful
        if ($result['success'] && $options['verify']) {
            $verifyResult = $service->verifyBackup($result['data']['path']);
            
            if (!$verifyResult['success']) {
                return [
                    'success' => false,
                    'message' => 'Backup created but verification failed: ' . $verifyResult['message']
                ];
            }
            
            $result['data']['verification'] = $verifyResult['data'];
        }

        return $result;
    }

    /**
     * Create file backup
     */
    protected function createFileBackup(): array
    {
        $service = app(FileBackupService::class);
        
        $options = array_merge([
            'encrypt' => config('backup.options.encrypt', false)
        ], $this->options);

        // Determine backup type based on schedule or options
        $backupType = $this->options['file_backup_type'] ?? 'incremental';

        if ($backupType === 'full') {
            return $service->createFullBackup($options);
        } else {
            return $service->createIncrementalBackup($options);
        }
    }

    /**
     * Perform health check
     */
    protected function performHealthCheck(): array
    {
        $service = app(BackupMonitoringService::class);
        return $service->performHealthCheck();
    }

    /**
     * Perform cleanup
     */
    protected function performCleanup(): array
    {
        $results = [];
        
        // Cleanup database backups
        $dbService = app(DatabaseBackupService::class);
        $dbRetention = config('backup.retention.daily', 7);
        $dbResult = $dbService->cleanupOldBackups($dbRetention);
        $results['database'] = $dbResult;

        // Cleanup file backups
        $fileService = app(FileBackupService::class);
        $fileRetention = config('backup.retention.weekly', 4) * 7; // Convert weeks to days
        $fileResult = $fileService->cleanupOldBackups($fileRetention);
        $results['files'] = $fileResult;

        // Check if any cleanup failed
        $success = $dbResult['success'] && $fileResult['success'];
        
        return [
            'success' => $success,
            'message' => $success ? 'Cleanup completed successfully' : 'Some cleanup operations failed',
            'data' => $results
        ];
    }

    /**
     * Send success notification
     */
    protected function sendSuccessNotification(array $result): void
    {
        if (!config('backup.notifications.enabled', true)) {
            return;
        }

        try {
            $notificationService = app(NotificationService::class);
            $adminUsers = \App\Models\User::where('role', 'admin')->get();

            $title = "Backup Completed Successfully";
            $message = $this->formatSuccessMessage($result);

            foreach ($adminUsers as $user) {
                $notificationService->send($user, [
                    'type' => 'backup_success',
                    'title' => $title,
                    'message' => $message,
                    'priority' => 'low',
                    'data' => $result['data'] ?? [],
                    'channels' => ['database']
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send backup success notification', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send failure notification
     */
    protected function sendFailureNotification(array $result): void
    {
        if (!config('backup.notifications.enabled', true)) {
            return;
        }

        if (!config('backup.notifications.events.backup_failure', true)) {
            return;
        }

        try {
            $notificationService = app(NotificationService::class);
            $adminUsers = \App\Models\User::where('role', 'admin')->get();

            $title = "Backup Failed";
            $message = $this->formatFailureMessage($result);

            foreach ($adminUsers as $user) {
                $notificationService->send($user, [
                    'type' => 'backup_failure',
                    'title' => $title,
                    'message' => $message,
                    'priority' => 'high',
                    'data' => [
                        'backup_type' => $this->backupType,
                        'error' => $result['message'],
                        'failed_at' => Carbon::now()
                    ],
                    'channels' => ['database', 'email']
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send backup failure notification', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Format success message
     */
    protected function formatSuccessMessage(array $result): string
    {
        $type = ucfirst($this->backupType);
        $message = "{$type} backup completed successfully.";

        if (isset($result['data'])) {
            $data = $result['data'];
            
            switch ($this->backupType) {
                case 'database':
                    if (isset($data['filename'], $data['size'])) {
                        $size = $this->formatBytes($data['size']);
                        $message .= "\n\nFile: {$data['filename']}\nSize: {$size}";
                        
                        if (isset($data['verification'])) {
                            $message .= "\nVerification: ✓ Passed";
                        }
                    }
                    break;
                    
                case 'files':
                    if (isset($data['filename'], $data['file_count'])) {
                        $fileCount = number_format($data['file_count']);
                        $message .= "\n\nFile: {$data['filename']}\nFiles backed up: {$fileCount}";
                        
                        if (isset($data['type'])) {
                            $message .= "\nType: " . ucfirst($data['type']);
                        }
                    }
                    break;
                    
                case 'cleanup':
                    if (isset($data['database'], $data['files'])) {
                        $dbDeleted = $data['database']['data']['deleted_count'] ?? 0;
                        $fileDeleted = $data['files']['data']['deleted_count'] ?? 0;
                        $message .= "\n\nDatabase backups cleaned: {$dbDeleted}\nFile backups cleaned: {$fileDeleted}";
                    }
                    break;
            }
        }

        return $message;
    }

    /**
     * Format failure message
     */
    protected function formatFailureMessage(array $result): string
    {
        $type = ucfirst($this->backupType);
        $message = "{$type} backup failed.";
        
        if (isset($result['message'])) {
            $message .= "\n\nError: {$result['message']}";
        }
        
        $message .= "\n\nPlease check the backup system and try again.";
        
        return $message;
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

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Scheduled backup job failed permanently', [
            'type' => $this->backupType,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Send failure notification
        $this->sendFailureNotification([
            'success' => false,
            'message' => $exception->getMessage()
        ]);
    }
}