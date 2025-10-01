<?php

namespace App\Console\Commands;

use App\Services\FileBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FileRestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:restore-files 
                            {backup? : Path to backup file (optional, will show list if not provided)}
                            {--key= : Decryption key for encrypted backups}
                            {--path= : Custom extraction path}
                            {--no-backup : Skip backing up existing files}
                            {--force : Skip confirmation prompt}
                            {--list : List available backups}';

    /**
     * The console command description.
     */
    protected $description = 'Restore files from backup';

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
        // List backups if requested or no backup specified
        if ($this->option('list') || !$this->argument('backup')) {
            return $this->listBackups();
        }

        $backupPath = $this->argument('backup');

        // Show warning about destructive operation
        if (!$this->option('force')) {
            $this->warn('⚠️  WARNING: This operation will overwrite existing files!');
            
            if (!$this->option('no-backup')) {
                $this->info('Existing files will be backed up before restoration.');
            } else {
                $this->warn('Existing files will NOT be backed up!');
            }
            
            if (!$this->confirm('Are you sure you want to restore from backup?')) {
                $this->info('Restore operation cancelled.');
                return Command::SUCCESS;
            }
        }

        $this->info("Restoring files from backup: {$backupPath}");

        $options = [
            'encryption_key' => $this->option('key'),
            'extract_path' => $this->option('path'),
            'backup_existing' => !$this->option('no-backup')
        ];

        $result = $this->backupService->restoreBackup($backupPath, $options);

        if (!$result['success']) {
            $this->error("Restore failed: {$result['message']}");
            return Command::FAILURE;
        }

        $this->info('✓ Files restored successfully!');
        
        if (!$this->option('no-backup')) {
            $this->info('Original files have been backed up with timestamp.');
        }
        
        $this->warn('Please verify your application is working correctly.');

        return Command::SUCCESS;
    }

    /**
     * List available file backups
     */
    protected function listBackups(): int
    {
        $this->info('Fetching available file backups...');

        $result = $this->backupService->listBackups();

        if (!$result['success']) {
            $this->error("Failed to list backups: {$result['message']}");
            return Command::FAILURE;
        }

        $backups = $result['data'];

        if (empty($backups)) {
            $this->warn('No file backups found.');
            return Command::SUCCESS;
        }

        $this->info('Available file backups:');
        
        $tableData = [];
        foreach ($backups as $backup) {
            $tableData[] = [
                $backup['filename'],
                ucfirst($backup['type']),
                $backup['size_human'],
                $backup['file_count'] ? number_format($backup['file_count']) : 'N/A',
                $backup['created_at']->format('Y-m-d H:i:s'),
                $backup['encrypted'] ? '✓' : '✗'
            ];
        }

        $this->table(
            ['Filename', 'Type', 'Size', 'Files', 'Created At', 'Encrypted'],
            $tableData
        );

        $this->info('To restore from a backup, run:');
        $this->line('php artisan backup:restore-files <backup-path>');
        
        if (!empty(array_filter($backups, fn($b) => $b['encrypted']))) {
            $this->info('For encrypted backups, add: --key=<encryption-key>');
        }

        return Command::SUCCESS;
    }
}