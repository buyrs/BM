<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DatabaseBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:database 
                            {--compress : Compress the backup file}
                            {--encrypt : Encrypt the backup file}
                            {--key= : Encryption key (optional)}
                            {--verify : Verify backup after creation}
                            {--cleanup= : Clean up backups older than specified days}';

    /**
     * The console command description.
     */
    protected $description = 'Create a database backup with optional compression and encryption';

    protected DatabaseBackupService $backupService;

    public function __construct(DatabaseBackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting database backup...');

        // Handle cleanup if requested
        if ($this->option('cleanup')) {
            $retentionDays = (int) $this->option('cleanup');
            $this->info("Cleaning up backups older than {$retentionDays} days...");
            
            $cleanupResult = $this->backupService->cleanupOldBackups($retentionDays);
            
            if ($cleanupResult['success']) {
                $data = $cleanupResult['data'];
                $this->info("Cleanup completed: {$data['deleted_count']} files deleted ({$data['deleted_size_human']})");
            } else {
                $this->error("Cleanup failed: {$cleanupResult['message']}");
            }
        }

        // Create backup
        $options = [
            'compress' => $this->option('compress'),
            'encrypt' => $this->option('encrypt'),
            'encryption_key' => $this->option('key')
        ];

        $result = $this->backupService->createBackup($options);

        if (!$result['success']) {
            $this->error("Backup failed: {$result['message']}");
            return Command::FAILURE;
        }

        $metadata = $result['data'];
        $this->info("Backup created successfully!");
        $this->table(
            ['Property', 'Value'],
            [
                ['Filename', $metadata['filename']],
                ['Path', $metadata['path']],
                ['Size', $this->formatBytes($metadata['size'])],
                ['Compressed', $metadata['compressed'] ? 'Yes' : 'No'],
                ['Encrypted', $metadata['encrypted'] ? 'Yes' : 'No'],
                ['Database Driver', $metadata['database_driver']],
                ['Checksum', substr($metadata['checksum'], 0, 16) . '...'],
                ['Created At', $metadata['created_at']->format('Y-m-d H:i:s')]
            ]
        );

        // Verify backup if requested
        if ($this->option('verify')) {
            $this->info('Verifying backup integrity...');
            
            $verifyResult = $this->backupService->verifyBackup($metadata['path']);
            
            if ($verifyResult['success']) {
                $this->info('✓ Backup verification successful');
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