<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DatabaseRestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:restore 
                            {backup? : Path to backup file (optional, will show list if not provided)}
                            {--key= : Decryption key for encrypted backups}
                            {--force : Skip confirmation prompt}
                            {--list : List available backups}';

    /**
     * The console command description.
     */
    protected $description = 'Restore database from backup';

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
        // List backups if requested or no backup specified
        if ($this->option('list') || !$this->argument('backup')) {
            return $this->listBackups();
        }

        $backupPath = $this->argument('backup');

        // Show warning about destructive operation
        if (!$this->option('force')) {
            $this->warn('⚠️  WARNING: This operation will replace your current database!');
            $this->warn('Make sure you have a recent backup before proceeding.');
            
            if (!$this->confirm('Are you sure you want to restore from backup?')) {
                $this->info('Restore operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $this->info("Restoring database from backup: {$backupPath}");

        $options = [
            'encryption_key' => $this->option('key')
        ];

        $result = $this->backupService->restoreBackup($backupPath, $options);

        if (!$result['success']) {
            $this->error("Restore failed: {$result['message']}");
            return Command::FAILURE;
        }

        $this->info('✓ Database restored successfully!');
        $this->warn('Please verify your application is working correctly.');

        return Command::SUCCESS;
    }

    /**
     * List available backups
     */
    protected function listBackups(): int
    {
        $this->info('Fetching available backups...');

        $result = $this->backupService->listBackups();

        if (!$result['success']) {
            $this->error("Failed to list backups: {$result['message']}");
            return Command::FAILURE;
        }

        $backups = $result['data'];

        if (empty($backups)) {
            $this->warn('No backups found.');
            return Command::SUCCESS;
        }

        $this->info('Available backups:');
        
        $tableData = [];
        foreach ($backups as $backup) {
            $tableData[] = [
                $backup['filename'],
                $backup['size_human'],
                $backup['created_at']->format('Y-m-d H:i:s'),
                $backup['compressed'] ? '✓' : '✗',
                $backup['encrypted'] ? '✓' : '✗'
            ];
        }

        $this->table(
            ['Filename', 'Size', 'Created At', 'Compressed', 'Encrypted'],
            $tableData
        );

        $this->info('To restore from a backup, run:');
        $this->line('php artisan backup:restore <backup-path>');
        
        if (!empty(array_filter($backups, fn($b) => $b['encrypted']))) {
            $this->info('For encrypted backups, add: --key=<encryption-key>');
        }

        return Command::SUCCESS;
    }
}