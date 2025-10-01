<?php

namespace App\Console\Commands;

use App\Services\FileBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FileBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:files 
                            {--type=full : Backup type (full or incremental)}
                            {--encrypt : Encrypt the backup file}
                            {--key= : Encryption key (optional)}
                            {--verify : Verify backup after creation}
                            {--cleanup= : Clean up backups older than specified days}';

    /**
     * The console command description.
     */
    protected $description = 'Create a file backup with optional encryption';

    protected FileBackupService $backupService;

    public function __construct(FileBackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting file backup...');

        // Handle cleanup if requested
        if ($this->option('cleanup')) {
            $retentionDays = (int) $this->option('cleanup');
            $this->info("Cleaning up file backups older than {$retentionDays} days...");
            
            $cleanupResult = $this->backupService->cleanupOldBackups($retentionDays);
            
            if ($cleanupResult['success']) {
                $data = $cleanupResult['data'];
                $this->info("Cleanup completed: {$data['deleted_count']} files deleted ({$data['deleted_size_human']})");
            } else {
                $this->error("Cleanup failed: {$cleanupResult['message']}");
            }
        }

        // Create backup
        $type = $this->option('type');
        $options = [
            'encrypt' => $this->option('encrypt'),
            'encryption_key' => $this->option('key')
        ];

        if ($type === 'incremental') {
            $result = $this->backupService->createIncrementalBackup($options);
        } else {
            $result = $this->backupService->createFullBackup($options);
        }

        if (!$result['success']) {
            $this->error("File backup failed: {$result['message']}");
            return Command::FAILURE;
        }

        $metadata = $result['data'];
        
        // Handle case where no files changed in incremental backup
        if (isset($metadata['file_count']) && $metadata['file_count'] === 0) {
            $this->info("No files changed since last backup");
            return Command::SUCCESS;
        }

        $this->info("File backup created successfully!");
        $this->table(
            ['Property', 'Value'],
            [
                ['Filename', $metadata['filename']],
                ['Path', $metadata['path']],
                ['Type', ucfirst($metadata['type'])],
                ['Size', $this->formatBytes($metadata['size'])],
                ['File Count', number_format($metadata['file_count'])],
                ['Encrypted', $metadata['encrypted'] ? 'Yes' : 'No'],
                ['Checksum', substr($metadata['checksum'], 0, 16) . '...'],
                ['Created At', $metadata['created_at']->format('Y-m-d H:i:s')]
            ]
        );

        // Verify backup if requested
        if ($this->option('verify')) {
            $this->info('Verifying backup integrity...');
            
            $verifyResult = $this->backupService->verifyBackup($metadata['path']);
            
            if ($verifyResult['success']) {
                $verifyData = $verifyResult['data'];
                $this->info("✓ Backup verification successful ({$verifyData['file_count']} files)");
            } else {
                $this->error("✗ Backup verification failed: {$verifyResult['message']}");
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
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