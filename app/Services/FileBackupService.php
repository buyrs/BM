<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;
use ZipArchive;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;

class FileBackupService extends BaseService
{
    protected string $backupDisk;
    protected string $backupPath;
    protected array $sourcePaths;
    protected array $excludePatterns;

    public function __construct()
    {
        $this->backupDisk = config('backup.disk', 'local');
        $this->backupPath = config('backup.file_backup_path', 'backups/files');
        $this->sourcePaths = $this->getSourcePaths();
        $this->excludePatterns = $this->getExcludePatterns();
    }

    /**
     * Get source paths to backup
     */
    protected function getSourcePaths(): array
    {
        return [
            'storage/app/public' => 'public_files',
            'storage/app/private' => 'private_files',
            'public/uploads' => 'uploads',
            // Add more paths as needed
        ];
    }

    /**
     * Get patterns to exclude from backup
     */
    protected function getExcludePatterns(): array
    {
        return [
            '*.tmp',
            '*.log',
            '*.cache',
            'cache/*',
            'logs/*',
            'sessions/*',
            'framework/cache/*',
            'framework/sessions/*',
            'framework/views/*',
            '.DS_Store',
            'Thumbs.db'
        ];
    }

    /**
     * Create a full file backup
     */
    public function createFullBackup(array $options = []): array
    {
        try {
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "files_full_backup_{$timestamp}";
            
            $this->info('Starting full file backup...');
            
            $backupData = $this->createBackupArchive($filename, 'full', $options);
            
            if ($options['encrypt'] ?? false) {
                $backupData = $this->encryptBackup($backupData, $options['encryption_key'] ?? null);
            }
            
            $storedPath = $this->storeBackup($backupData, $filename);
            
            // Store backup metadata
            $metadata = [
                'filename' => $filename,
                'path' => $storedPath,
                'type' => 'full',
                'size' => $backupData['size'],
                'file_count' => $backupData['file_count'],
                'encrypted' => $options['encrypt'] ?? false,
                'created_at' => Carbon::now(),
                'checksum' => $backupData['checksum']
            ];
            
            $this->storeBackupMetadata($metadata);
            
            Log::info('Full file backup created successfully', $metadata);
            
            return $this->success('Full file backup created successfully', $metadata);
            
        } catch (Exception $e) {
            Log::error('Full file backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->error('Full file backup failed: ' . $e->getMessage());
        }
    }

    /**
     * Create an incremental file backup
     */
    public function createIncrementalBackup(array $options = []): array
    {
        try {
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "files_incremental_backup_{$timestamp}";
            
            $this->info('Starting incremental file backup...');
            
            // Get last backup timestamp
            $lastBackupTime = $this->getLastBackupTime();
            
            if (!$lastBackupTime) {
                $this->info('No previous backup found, creating full backup instead');
                return $this->createFullBackup($options);
            }
            
            $backupData = $this->createBackupArchive($filename, 'incremental', $options, $lastBackupTime);
            
            if ($backupData['file_count'] === 0) {
                return $this->success('No files changed since last backup', [
                    'type' => 'incremental',
                    'file_count' => 0,
                    'last_backup' => $lastBackupTime
                ]);
            }
            
            if ($options['encrypt'] ?? false) {
                $backupData = $this->encryptBackup($backupData, $options['encryption_key'] ?? null);
            }
            
            $storedPath = $this->storeBackup($backupData, $filename);
            
            $metadata = [
                'filename' => $filename,
                'path' => $storedPath,
                'type' => 'incremental',
                'size' => $backupData['size'],
                'file_count' => $backupData['file_count'],
                'encrypted' => $options['encrypt'] ?? false,
                'created_at' => Carbon::now(),
                'checksum' => $backupData['checksum'],
                'base_backup_time' => $lastBackupTime
            ];
            
            $this->storeBackupMetadata($metadata);
            
            Log::info('Incremental file backup created successfully', $metadata);
            
            return $this->success('Incremental file backup created successfully', $metadata);
            
        } catch (Exception $e) {
            Log::error('Incremental file backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->error('Incremental file backup failed: ' . $e->getMessage());
        }
    }

    /**
     * Create backup archive
     */
    protected function createBackupArchive(string $filename, string $type, array $options, ?Carbon $sinceTime = null): array
    {
        $tempPath = sys_get_temp_dir() . '/' . $filename . '.zip';
        $zip = new ZipArchive();
        
        if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new Exception('Cannot create backup archive');
        }
        
        $fileCount = 0;
        $totalSize = 0;
        
        foreach ($this->sourcePaths as $sourcePath => $archivePath) {
            $fullSourcePath = base_path($sourcePath);
            
            if (!is_dir($fullSourcePath)) {
                continue;
            }
            
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($fullSourcePath, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            
            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }
                
                $filePath = $file->getRealPath();
                $relativePath = str_replace($fullSourcePath . DIRECTORY_SEPARATOR, '', $filePath);
                
                // Check exclude patterns
                if ($this->shouldExcludeFile($relativePath)) {
                    continue;
                }
                
                // For incremental backups, only include files modified since last backup
                if ($type === 'incremental' && $sinceTime) {
                    $fileModTime = Carbon::createFromTimestamp($file->getMTime());
                    if ($fileModTime->lte($sinceTime)) {
                        continue;
                    }
                }
                
                $archiveFilePath = $archivePath . '/' . $relativePath;
                $zip->addFile($filePath, $archiveFilePath);
                
                $fileCount++;
                $totalSize += $file->getSize();
            }
        }
        
        $zip->close();
        
        if (!file_exists($tempPath)) {
            throw new Exception('Failed to create backup archive');
        }
        
        $content = file_get_contents($tempPath);
        $checksum = hash('sha256', $content);
        
        unlink($tempPath);
        
        return [
            'content' => $content,
            'size' => strlen($content),
            'file_count' => $fileCount,
            'total_file_size' => $totalSize,
            'checksum' => $checksum
        ];
    }

    /**
     * Check if file should be excluded from backup
     */
    protected function shouldExcludeFile(string $filePath): bool
    {
        foreach ($this->excludePatterns as $pattern) {
            if (fnmatch($pattern, $filePath)) {
                return true;
            }
        }
        
        return false;
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
        $extension = '.zip';
        $extension .= $backupData['encrypted'] ?? false ? '.enc' : '';
        
        $fullFilename = $filename . $extension;
        $path = $this->backupPath . '/' . $fullFilename;
        
        Storage::disk($this->backupDisk)->put($path, $backupData['content']);
        
        // Also store to cloud if configured
        if (config('backup.cloud.enabled')) {
            $this->syncToCloud($path, $backupData['content']);
        }
        
        return $path;
    }

    /**
     * Sync backup to cloud storage
     */
    protected function syncToCloud(string $path, string $content): void
    {
        try {
            $cloudDisk = config('backup.cloud.disk');
            $cloudPath = config('backup.cloud.path') . '/' . basename($path);
            
            Storage::disk($cloudDisk)->put($cloudPath, $content);
            
            Log::info('Backup synced to cloud storage', [
                'local_path' => $path,
                'cloud_path' => $cloudPath,
                'cloud_disk' => $cloudDisk
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to sync backup to cloud', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Store backup metadata
     */
    protected function storeBackupMetadata(array $metadata): void
    {
        $metadataPath = $this->backupPath . '/metadata.json';
        
        $existingMetadata = [];
        if (Storage::disk($this->backupDisk)->exists($metadataPath)) {
            $existingMetadata = json_decode(
                Storage::disk($this->backupDisk)->get($metadataPath),
                true
            ) ?: [];
        }
        
        $existingMetadata[] = $metadata;
        
        // Keep only last 100 metadata entries
        if (count($existingMetadata) > 100) {
            $existingMetadata = array_slice($existingMetadata, -100);
        }
        
        Storage::disk($this->backupDisk)->put(
            $metadataPath,
            json_encode($existingMetadata, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Get last backup time
     */
    protected function getLastBackupTime(): ?Carbon
    {
        $metadataPath = $this->backupPath . '/metadata.json';
        
        if (!Storage::disk($this->backupDisk)->exists($metadataPath)) {
            return null;
        }
        
        $metadata = json_decode(
            Storage::disk($this->backupDisk)->get($metadataPath),
            true
        );
        
        if (empty($metadata)) {
            return null;
        }
        
        $lastBackup = end($metadata);
        
        return Carbon::parse($lastBackup['created_at']);
    }

    /**
     * Restore files from backup
     */
    public function restoreBackup(string $backupPath, array $options = []): array
    {
        try {
            if (!Storage::disk($this->backupDisk)->exists($backupPath)) {
                throw new Exception("Backup file not found: {$backupPath}");
            }
            
            $content = Storage::disk($this->backupDisk)->get($backupPath);
            
            // Decrypt if needed
            if (str_contains($backupPath, '.enc')) {
                $content = $this->decryptBackup($content, $options['encryption_key'] ?? null);
            }
            
            $this->extractBackup($content, $options);
            
            Log::info('Files restored successfully from backup', [
                'backup_path' => $backupPath
            ]);
            
            return $this->success('Files restored successfully');
            
        } catch (Exception $e) {
            Log::error('File restore failed', [
                'backup_path' => $backupPath,
                'error' => $e->getMessage()
            ]);
            
            return $this->error('File restore failed: ' . $e->getMessage());
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
     * Extract backup archive
     */
    protected function extractBackup(string $content, array $options): void
    {
        $tempPath = sys_get_temp_dir() . '/restore_' . uniqid() . '.zip';
        file_put_contents($tempPath, $content);
        
        $zip = new ZipArchive();
        if ($zip->open($tempPath) !== TRUE) {
            unlink($tempPath);
            throw new Exception('Cannot open backup archive for extraction');
        }
        
        $extractPath = $options['extract_path'] ?? base_path();
        
        // Create backup of existing files if requested
        if ($options['backup_existing'] ?? true) {
            $this->backupExistingFiles($zip, $extractPath);
        }
        
        if (!$zip->extractTo($extractPath)) {
            $zip->close();
            unlink($tempPath);
            throw new Exception('Failed to extract backup archive');
        }
        
        $zip->close();
        unlink($tempPath);
    }

    /**
     * Backup existing files before restore
     */
    protected function backupExistingFiles(ZipArchive $zip, string $extractPath): void
    {
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $backupDir = $extractPath . '/backup_before_restore_' . $timestamp;
        
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $fullPath = $extractPath . '/' . $filename;
            
            if (file_exists($fullPath)) {
                $backupPath = $backupDir . '/' . $filename;
                $backupDirPath = dirname($backupPath);
                
                if (!is_dir($backupDirPath)) {
                    mkdir($backupDirPath, 0755, true);
                }
                
                copy($fullPath, $backupPath);
            }
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
            
            // Try to decrypt if encrypted
            $isEncrypted = str_contains($backupPath, '.enc');
            if ($isEncrypted) {
                $content = $this->decryptBackup($content);
            }
            
            // Try to open as ZIP archive
            $tempPath = sys_get_temp_dir() . '/verify_' . uniqid() . '.zip';
            file_put_contents($tempPath, $content);
            
            $zip = new ZipArchive();
            $result = $zip->open($tempPath);
            
            if ($result !== TRUE) {
                unlink($tempPath);
                throw new Exception('Backup archive is corrupted or invalid');
            }
            
            $fileCount = $zip->numFiles;
            $zip->close();
            unlink($tempPath);
            
            return $this->success('Backup verification successful', [
                'path' => $backupPath,
                'size' => $size,
                'checksum' => $checksum,
                'encrypted' => $isEncrypted,
                'file_count' => $fileCount,
                'verified_at' => Carbon::now()
            ]);
            
        } catch (Exception $e) {
            return $this->error('Backup verification failed: ' . $e->getMessage());
        }
    }

    /**
     * List available file backups
     */
    public function listBackups(): array
    {
        try {
            $files = Storage::disk($this->backupDisk)->files($this->backupPath);
            $backups = [];
            
            // Load metadata
            $metadata = $this->getBackupMetadata();
            
            foreach ($files as $file) {
                if (basename($file) === 'metadata.json') {
                    continue;
                }
                
                $size = Storage::disk($this->backupDisk)->size($file);
                $lastModified = Storage::disk($this->backupDisk)->lastModified($file);
                
                // Find metadata for this backup
                $fileMetadata = collect($metadata)->firstWhere('path', $file);
                
                $backups[] = [
                    'path' => $file,
                    'filename' => basename($file),
                    'size' => $size,
                    'size_human' => $this->formatBytes($size),
                    'created_at' => Carbon::createFromTimestamp($lastModified),
                    'encrypted' => str_contains($file, '.enc'),
                    'type' => $fileMetadata['type'] ?? 'unknown',
                    'file_count' => $fileMetadata['file_count'] ?? null,
                ];
            }
            
            // Sort by creation date (newest first)
            usort($backups, function ($a, $b) {
                return $b['created_at']->timestamp - $a['created_at']->timestamp;
            });
            
            return $this->success('File backups retrieved successfully', $backups);
            
        } catch (Exception $e) {
            return $this->error('Failed to list file backups: ' . $e->getMessage());
        }
    }

    /**
     * Get backup metadata
     */
    protected function getBackupMetadata(): array
    {
        $metadataPath = $this->backupPath . '/metadata.json';
        
        if (!Storage::disk($this->backupDisk)->exists($metadataPath)) {
            return [];
        }
        
        return json_decode(
            Storage::disk($this->backupDisk)->get($metadataPath),
            true
        ) ?: [];
    }

    /**
     * Clean up old file backups
     */
    public function cleanupOldBackups(int $retentionDays = 30): array
    {
        try {
            $cutoffDate = Carbon::now()->subDays($retentionDays);
            $files = Storage::disk($this->backupDisk)->files($this->backupPath);
            $deletedCount = 0;
            $deletedSize = 0;
            
            foreach ($files as $file) {
                if (basename($file) === 'metadata.json') {
                    continue;
                }
                
                $lastModified = Storage::disk($this->backupDisk)->lastModified($file);
                $fileDate = Carbon::createFromTimestamp($lastModified);
                
                if ($fileDate->lt($cutoffDate)) {
                    $size = Storage::disk($this->backupDisk)->size($file);
                    Storage::disk($this->backupDisk)->delete($file);
                    $deletedCount++;
                    $deletedSize += $size;
                }
            }
            
            // Clean up metadata
            $this->cleanupMetadata($cutoffDate);
            
            Log::info('File backup cleanup completed', [
                'retention_days' => $retentionDays,
                'deleted_count' => $deletedCount,
                'deleted_size' => $deletedSize
            ]);
            
            return $this->success('File backup cleanup completed', [
                'deleted_count' => $deletedCount,
                'deleted_size' => $deletedSize,
                'deleted_size_human' => $this->formatBytes($deletedSize)
            ]);
            
        } catch (Exception $e) {
            return $this->error('File backup cleanup failed: ' . $e->getMessage());
        }
    }

    /**
     * Clean up old metadata entries
     */
    protected function cleanupMetadata(Carbon $cutoffDate): void
    {
        $metadata = $this->getBackupMetadata();
        
        $filteredMetadata = array_filter($metadata, function ($entry) use ($cutoffDate) {
            $createdAt = Carbon::parse($entry['created_at']);
            return $createdAt->gte($cutoffDate);
        });
        
        $metadataPath = $this->backupPath . '/metadata.json';
        Storage::disk($this->backupDisk)->put(
            $metadataPath,
            json_encode(array_values($filteredMetadata), JSON_PRETTY_PRINT)
        );
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