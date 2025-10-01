<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;
use ZipArchive;

class DatabaseBackupService extends BaseService
{
    protected string $backupDisk;
    protected string $backupPath;
    protected array $config;

    public function __construct()
    {
        $this->backupDisk = config('backup.disk', 'local');
        $this->backupPath = config('backup.path', 'backups/database');
        $this->config = config('database.connections.' . config('database.default'));
    }

    /**
     * Create a database backup
     */
    public function createBackup(array $options = []): array
    {
        try {
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "database_backup_{$timestamp}";
            
            $backupData = $this->performBackup($filename, $options);
            
            if ($options['compress'] ?? true) {
                $backupData = $this->compressBackup($backupData);
            }
            
            if ($options['encrypt'] ?? false) {
                $backupData = $this->encryptBackup($backupData, $options['encryption_key'] ?? null);
            }
            
            $storedPath = $this->storeBackup($backupData, $filename);
            
            $metadata = [
                'filename' => $filename,
                'path' => $storedPath,
                'size' => strlen($backupData['content']),
                'compressed' => $options['compress'] ?? true,
                'encrypted' => $options['encrypt'] ?? false,
                'created_at' => Carbon::now(),
                'database_driver' => $this->config['driver'],
                'checksum' => hash('sha256', $backupData['content'])
            ];
            
            Log::info('Database backup created successfully', $metadata);
            
            return $this->success('Database backup created successfully', $metadata);
            
        } catch (Exception $e) {
            Log::error('Database backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->error('Database backup failed: ' . $e->getMessage());
        }
    }

    /**
     * Perform the actual backup based on database driver
     */
    protected function performBackup(string $filename, array $options): array
    {
        $driver = $this->config['driver'];
        
        switch ($driver) {
            case 'sqlite':
                return $this->backupSqlite($filename, $options);
            case 'mysql':
            case 'mariadb':
                return $this->backupMysql($filename, $options);
            case 'pgsql':
                return $this->backupPostgres($filename, $options);
            default:
                throw new Exception("Backup not supported for database driver: {$driver}");
        }
    }

    /**
     * Backup SQLite database
     */
    protected function backupSqlite(string $filename, array $options): array
    {
        $databasePath = $this->config['database'];
        
        if (!file_exists($databasePath)) {
            throw new Exception("SQLite database file not found: {$databasePath}");
        }
        
        $content = file_get_contents($databasePath);
        
        return [
            'content' => $content,
            'type' => 'sqlite_file',
            'original_size' => strlen($content)
        ];
    }

    /**
     * Backup MySQL/MariaDB database
     */
    protected function backupMysql(string $filename, array $options): array
    {
        $host = $this->config['host'];
        $port = $this->config['port'];
        $database = $this->config['database'];
        $username = $this->config['username'];
        $password = $this->config['password'];
        
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database)
        );
        
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception('MySQL backup failed with return code: ' . $returnCode);
        }
        
        $content = implode("\n", $output);
        
        return [
            'content' => $content,
            'type' => 'mysql_dump',
            'original_size' => strlen($content)
        ];
    }

    /**
     * Backup PostgreSQL database
     */
    protected function backupPostgres(string $filename, array $options): array
    {
        $host = $this->config['host'];
        $port = $this->config['port'];
        $database = $this->config['database'];
        $username = $this->config['username'];
        $password = $this->config['password'];
        
        // Set environment variables for pg_dump
        $env = [
            'PGPASSWORD' => $password
        ];
        
        $command = sprintf(
            'pg_dump --host=%s --port=%s --username=%s --no-password --format=custom %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($database)
        );
        
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        
        $process = proc_open($command, $descriptors, $pipes, null, $env);
        
        if (!is_resource($process)) {
            throw new Exception('Failed to start PostgreSQL backup process');
        }
        
        fclose($pipes[0]);
        $content = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $returnCode = proc_close($process);
        
        if ($returnCode !== 0) {
            throw new Exception('PostgreSQL backup failed: ' . $error);
        }
        
        return [
            'content' => $content,
            'type' => 'postgres_dump',
            'original_size' => strlen($content)
        ];
    }

    /**
     * Compress backup data
     */
    protected function compressBackup(array $backupData): array
    {
        $compressed = gzcompress($backupData['content'], 9);
        
        if ($compressed === false) {
            throw new Exception('Failed to compress backup data');
        }
        
        $backupData['content'] = $compressed;
        $backupData['compressed'] = true;
        $backupData['compressed_size'] = strlen($compressed);
        $backupData['compression_ratio'] = round((1 - strlen($compressed) / $backupData['original_size']) * 100, 2);
        
        return $backupData;
    }

    /**
     * Encrypt backup data
     */
    protected function encryptBackup(array $backupData, ?string $encryptionKey = null): array
    {
        $key = $encryptionKey ?: config('app.key');
        
        if (!$key) {
            throw new Exception('Encryption key not provided');
        }
        
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($backupData['content'], 'AES-256-CBC', $key, 0, $iv);
        
        if ($encrypted === false) {
            throw new Exception('Failed to encrypt backup data');
        }
        
        $backupData['content'] = base64_encode($iv . $encrypted);
        $backupData['encrypted'] = true;
        $backupData['encryption_method'] = 'AES-256-CBC';
        
        return $backupData;
    }

    /**
     * Store backup to configured storage
     */
    protected function storeBackup(array $backupData, string $filename): string
    {
        $extension = $backupData['compressed'] ? '.gz' : '';
        $extension .= $backupData['encrypted'] ? '.enc' : '';
        
        switch ($backupData['type']) {
            case 'sqlite_file':
                $extension = '.sqlite' . $extension;
                break;
            case 'mysql_dump':
                $extension = '.sql' . $extension;
                break;
            case 'postgres_dump':
                $extension = '.dump' . $extension;
                break;
        }
        
        $fullFilename = $filename . $extension;
        $path = $this->backupPath . '/' . $fullFilename;
        
        Storage::disk($this->backupDisk)->put($path, $backupData['content']);
        
        return $path;
    }

    /**
     * Restore database from backup
     */
    public function restoreBackup(string $backupPath, array $options = []): array
    {
        try {
            if (!Storage::disk($this->backupDisk)->exists($backupPath)) {
                throw new Exception("Backup file not found: {$backupPath}");
            }
            
            $content = Storage::disk($this->backupDisk)->get($backupPath);
            
            // Determine if backup is encrypted/compressed based on file extension
            $isEncrypted = str_contains($backupPath, '.enc');
            $isCompressed = str_contains($backupPath, '.gz');
            
            if ($isEncrypted) {
                $content = $this->decryptBackup($content, $options['encryption_key'] ?? null);
            }
            
            if ($isCompressed) {
                $content = $this->decompressBackup($content);
            }
            
            $this->performRestore($content, $backupPath);
            
            Log::info('Database restored successfully from backup', [
                'backup_path' => $backupPath
            ]);
            
            return $this->success('Database restored successfully');
            
        } catch (Exception $e) {
            Log::error('Database restore failed', [
                'backup_path' => $backupPath,
                'error' => $e->getMessage()
            ]);
            
            return $this->error('Database restore failed: ' . $e->getMessage());
        }
    }

    /**
     * Decrypt backup content
     */
    protected function decryptBackup(string $content, ?string $encryptionKey = null): string
    {
        $key = $encryptionKey ?: config('app.key');
        
        if (!$key) {
            throw new Exception('Encryption key not provided for decryption');
        }
        
        $data = base64_decode($content);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
        
        if ($decrypted === false) {
            throw new Exception('Failed to decrypt backup data');
        }
        
        return $decrypted;
    }

    /**
     * Decompress backup content
     */
    protected function decompressBackup(string $content): string
    {
        $decompressed = gzuncompress($content);
        
        if ($decompressed === false) {
            throw new Exception('Failed to decompress backup data');
        }
        
        return $decompressed;
    }

    /**
     * Perform database restore based on driver
     */
    protected function performRestore(string $content, string $backupPath): void
    {
        $driver = $this->config['driver'];
        
        switch ($driver) {
            case 'sqlite':
                $this->restoreSqlite($content);
                break;
            case 'mysql':
            case 'mariadb':
                $this->restoreMysql($content);
                break;
            case 'pgsql':
                $this->restorePostgres($content, $backupPath);
                break;
            default:
                throw new Exception("Restore not supported for database driver: {$driver}");
        }
    }

    /**
     * Restore SQLite database
     */
    protected function restoreSqlite(string $content): void
    {
        $databasePath = $this->config['database'];
        $backupPath = $databasePath . '.backup.' . time();
        
        // Create backup of current database
        if (file_exists($databasePath)) {
            copy($databasePath, $backupPath);
        }
        
        // Write new database content
        if (file_put_contents($databasePath, $content) === false) {
            // Restore original if write failed
            if (file_exists($backupPath)) {
                copy($backupPath, $databasePath);
                unlink($backupPath);
            }
            throw new Exception('Failed to write restored SQLite database');
        }
        
        // Clean up backup
        if (file_exists($backupPath)) {
            unlink($backupPath);
        }
    }

    /**
     * Restore MySQL database
     */
    protected function restoreMysql(string $content): void
    {
        $host = $this->config['host'];
        $port = $this->config['port'];
        $database = $this->config['database'];
        $username = $this->config['username'];
        $password = $this->config['password'];
        
        $tempFile = tempnam(sys_get_temp_dir(), 'mysql_restore_');
        file_put_contents($tempFile, $content);
        
        $command = sprintf(
            'mysql --host=%s --port=%s --user=%s --password=%s %s < %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($tempFile)
        );
        
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        unlink($tempFile);
        
        if ($returnCode !== 0) {
            throw new Exception('MySQL restore failed with return code: ' . $returnCode);
        }
    }

    /**
     * Restore PostgreSQL database
     */
    protected function restorePostgres(string $content, string $backupPath): void
    {
        $host = $this->config['host'];
        $port = $this->config['port'];
        $database = $this->config['database'];
        $username = $this->config['username'];
        $password = $this->config['password'];
        
        // For PostgreSQL custom format, we need to use the original file
        $tempFile = tempnam(sys_get_temp_dir(), 'postgres_restore_');
        file_put_contents($tempFile, $content);
        
        $env = [
            'PGPASSWORD' => $password
        ];
        
        $command = sprintf(
            'pg_restore --host=%s --port=%s --username=%s --no-password --dbname=%s --clean --if-exists %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($tempFile)
        );
        
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        
        $process = proc_open($command, $descriptors, $pipes, null, $env);
        
        if (!is_resource($process)) {
            unlink($tempFile);
            throw new Exception('Failed to start PostgreSQL restore process');
        }
        
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $returnCode = proc_close($process);
        unlink($tempFile);
        
        if ($returnCode !== 0 && !empty($error)) {
            throw new Exception('PostgreSQL restore failed: ' . $error);
        }
    }

    /**
     * Verify backup integrity
     */
    public function verifyBackup(string $backupPath): array
    {
        try {
            if (!Storage::disk($this->backupDisk)->exists($backupPath)) {
                throw new Exception("Backup file not found: {$backupPath}");
            }
            
            $content = Storage::disk($this->backupDisk)->get($backupPath);
            $size = Storage::disk($this->backupDisk)->size($backupPath);
            $checksum = hash('sha256', $content);
            
            // Try to read/decompress/decrypt to verify integrity
            $isEncrypted = str_contains($backupPath, '.enc');
            $isCompressed = str_contains($backupPath, '.gz');
            
            if ($isEncrypted) {
                $content = $this->decryptBackup($content);
            }
            
            if ($isCompressed) {
                $content = $this->decompressBackup($content);
            }
            
            return $this->success('Backup verification successful', [
                'path' => $backupPath,
                'size' => $size,
                'checksum' => $checksum,
                'encrypted' => $isEncrypted,
                'compressed' => $isCompressed,
                'verified_at' => Carbon::now()
            ]);
            
        } catch (Exception $e) {
            return $this->error('Backup verification failed: ' . $e->getMessage());
        }
    }

    /**
     * List available backups
     */
    public function listBackups(): array
    {
        try {
            $files = Storage::disk($this->backupDisk)->files($this->backupPath);
            $backups = [];
            
            foreach ($files as $file) {
                $size = Storage::disk($this->backupDisk)->size($file);
                $lastModified = Storage::disk($this->backupDisk)->lastModified($file);
                
                $backups[] = [
                    'path' => $file,
                    'filename' => basename($file),
                    'size' => $size,
                    'size_human' => $this->formatBytes($size),
                    'created_at' => Carbon::createFromTimestamp($lastModified),
                    'encrypted' => str_contains($file, '.enc'),
                    'compressed' => str_contains($file, '.gz')
                ];
            }
            
            // Sort by creation date (newest first)
            usort($backups, function ($a, $b) {
                return $b['created_at']->timestamp - $a['created_at']->timestamp;
            });
            
            return $this->success('Backups retrieved successfully', $backups);
            
        } catch (Exception $e) {
            return $this->error('Failed to list backups: ' . $e->getMessage());
        }
    }

    /**
     * Delete old backups based on retention policy
     */
    public function cleanupOldBackups(int $retentionDays = 30): array
    {
        try {
            $cutoffDate = Carbon::now()->subDays($retentionDays);
            $files = Storage::disk($this->backupDisk)->files($this->backupPath);
            $deletedCount = 0;
            $deletedSize = 0;
            
            foreach ($files as $file) {
                $lastModified = Storage::disk($this->backupDisk)->lastModified($file);
                $fileDate = Carbon::createFromTimestamp($lastModified);
                
                if ($fileDate->lt($cutoffDate)) {
                    $size = Storage::disk($this->backupDisk)->size($file);
                    Storage::disk($this->backupDisk)->delete($file);
                    $deletedCount++;
                    $deletedSize += $size;
                }
            }
            
            Log::info('Backup cleanup completed', [
                'retention_days' => $retentionDays,
                'deleted_count' => $deletedCount,
                'deleted_size' => $deletedSize
            ]);
            
            return $this->success('Backup cleanup completed', [
                'deleted_count' => $deletedCount,
                'deleted_size' => $deletedSize,
                'deleted_size_human' => $this->formatBytes($deletedSize)
            ]);
            
        } catch (Exception $e) {
            return $this->error('Backup cleanup failed: ' . $e->getMessage());
        }
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