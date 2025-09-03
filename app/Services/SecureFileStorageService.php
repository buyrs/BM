<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SecureFileStorageService
{
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/png',
        'image/jpeg',
        'image/jpg',
        'image/svg+xml',
        'text/plain',
        'application/json'
    ];

    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

    private const ENCRYPTION_ALGORITHM = 'AES-256-CBC';

    /**
     * Store a file securely with encryption and integrity verification
     */
    public function storeSecureFile(
        UploadedFile $file,
        string $directory,
        ?string $filename = null,
        bool $encrypt = true,
        array $metadata = []
    ): array {
        // Validate file
        $this->validateFile($file);

        // Generate secure filename
        $secureFilename = $filename ?? $this->generateSecureFilename($file);
        $fullPath = $directory . '/' . $secureFilename;

        // Read file content
        $content = file_get_contents($file->getRealPath());
        
        // Generate integrity hash before encryption
        $originalHash = hash('sha256', $content);

        // Encrypt content if requested
        if ($encrypt) {
            $content = $this->encryptContent($content);
        }

        // Store file
        Storage::disk('private')->put($fullPath, $content);

        // Generate post-storage hash for verification
        $storageHash = hash('sha256', $content);

        // Store metadata and hashes
        $metadataFile = $fullPath . '.meta';
        $fileMetadata = [
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_at' => now()->toISOString(),
            'uploaded_by' => auth()->user()?->id,
            'encrypted' => $encrypt,
            'original_hash' => $originalHash,
            'storage_hash' => $storageHash,
            'algorithm' => $encrypt ? self::ENCRYPTION_ALGORITHM : null,
            'metadata' => $metadata,
            'security_level' => $this->determineSecurityLevel($directory, $file),
            'retention_policy' => $this->getRetentionPolicy($directory),
            'access_restrictions' => $this->getAccessRestrictions($directory)
        ];

        Storage::disk('private')->put($metadataFile, encrypt(json_encode($fileMetadata, JSON_PRETTY_PRINT)));

        // Log file storage
        AuditService::logFileOperation(
            'upload',
            $fullPath,
            auth()->user(),
            [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'encrypted' => $encrypt,
                'security_level' => $fileMetadata['security_level']
            ]
        );

        Log::channel('security')->info('Secure file stored', [
            'path' => $fullPath,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'encrypted' => $encrypt,
            'uploaded_by' => auth()->user()?->email,
            'security_level' => $fileMetadata['security_level']
        ]);

        return [
            'path' => $fullPath,
            'metadata' => $fileMetadata,
            'success' => true
        ];
    }

    /**
     * Retrieve a secure file with integrity verification
     */
    public function retrieveSecureFile(string $path, ?User $user = null): array
    {
        // Check if file exists
        if (!Storage::disk('private')->exists($path)) {
            throw new \Exception("File not found: {$path}");
        }

        // Load metadata
        $metadataFile = $path . '.meta';
        if (!Storage::disk('private')->exists($metadataFile)) {
            throw new \Exception("File metadata not found: {$path}");
        }

        $metadata = json_decode(decrypt(Storage::disk('private')->get($metadataFile)), true);

        // Check access permissions
        $this->checkFileAccess($path, $metadata, $user);

        // Retrieve file content
        $content = Storage::disk('private')->get($path);

        // Verify storage integrity
        $currentHash = hash('sha256', $content);
        if ($currentHash !== $metadata['storage_hash']) {
            Log::channel('security')->error('File integrity verification failed', [
                'path' => $path,
                'expected_hash' => $metadata['storage_hash'],
                'actual_hash' => $currentHash,
                'accessed_by' => $user?->email
            ]);
            throw new \Exception("File integrity verification failed: {$path}");
        }

        // Decrypt content if encrypted
        if ($metadata['encrypted']) {
            $content = $this->decryptContent($content);
            
            // Verify original content integrity after decryption
            $decryptedHash = hash('sha256', $content);
            if ($decryptedHash !== $metadata['original_hash']) {
                Log::channel('security')->error('Decrypted file integrity verification failed', [
                    'path' => $path,
                    'expected_hash' => $metadata['original_hash'],
                    'actual_hash' => $decryptedHash,
                    'accessed_by' => $user?->email
                ]);
                throw new \Exception("Decrypted file integrity verification failed: {$path}");
            }
        }

        // Log file access
        AuditService::logFileOperation(
            'download',
            $path,
            $user,
            [
                'original_name' => $metadata['original_name'],
                'size' => $metadata['size'],
                'encrypted' => $metadata['encrypted'],
                'security_level' => $metadata['security_level']
            ]
        );

        Log::channel('security')->info('Secure file accessed', [
            'path' => $path,
            'accessed_by' => $user?->email,
            'security_level' => $metadata['security_level'],
            'integrity_verified' => true
        ]);

        return [
            'content' => $content,
            'metadata' => $metadata,
            'integrity_verified' => true
        ];
    }

    /**
     * Delete a secure file with audit trail
     */
    public function deleteSecureFile(string $path, ?User $user = null, string $reason = ''): bool
    {
        // Check if file exists
        if (!Storage::disk('private')->exists($path)) {
            return false;
        }

        // Load metadata for audit purposes
        $metadataFile = $path . '.meta';
        $metadata = [];
        if (Storage::disk('private')->exists($metadataFile)) {
            $metadata = json_decode(decrypt(Storage::disk('private')->get($metadataFile)), true);
        }

        // Check deletion permissions
        $this->checkFileDeletionAccess($path, $metadata, $user);

        // Archive file before deletion if it's sensitive
        if (isset($metadata['security_level']) && in_array($metadata['security_level'], ['high', 'critical'])) {
            $this->archiveFileBeforeDeletion($path, $metadata, $user, $reason);
        }

        // Delete file and metadata
        $fileDeleted = Storage::disk('private')->delete($path);
        $metaDeleted = Storage::disk('private')->delete($metadataFile);

        // Log deletion
        AuditService::logFileOperation(
            'delete',
            $path,
            $user,
            [
                'reason' => $reason,
                'original_name' => $metadata['original_name'] ?? 'unknown',
                'security_level' => $metadata['security_level'] ?? 'unknown',
                'archived' => isset($metadata['security_level']) && in_array($metadata['security_level'], ['high', 'critical'])
            ]
        );

        Log::channel('security')->warning('Secure file deleted', [
            'path' => $path,
            'deleted_by' => $user?->email,
            'reason' => $reason,
            'security_level' => $metadata['security_level'] ?? 'unknown'
        ]);

        return $fileDeleted && $metaDeleted;
    }

    /**
     * List files in a directory with security information
     */
    public function listSecureFiles(string $directory, ?User $user = null): array
    {
        $files = Storage::disk('private')->files($directory);
        $secureFiles = [];

        foreach ($files as $file) {
            // Skip metadata files
            if (str_ends_with($file, '.meta') || str_ends_with($file, '.hash')) {
                continue;
            }

            $metadataFile = $file . '.meta';
            if (Storage::disk('private')->exists($metadataFile)) {
                try {
                    $metadata = json_decode(decrypt(Storage::disk('private')->get($metadataFile)), true);
                    
                    // Check if user has access to view this file
                    if ($this->hasFileAccess($file, $metadata, $user)) {
                        $secureFiles[] = [
                            'path' => $file,
                            'name' => $metadata['original_name'],
                            'size' => $metadata['size'],
                            'mime_type' => $metadata['mime_type'],
                            'uploaded_at' => $metadata['uploaded_at'],
                            'encrypted' => $metadata['encrypted'],
                            'security_level' => $metadata['security_level'],
                            'can_download' => $this->canDownloadFile($file, $metadata, $user),
                            'can_delete' => $this->canDeleteFile($file, $metadata, $user)
                        ];
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to read file metadata', [
                        'file' => $file,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return $secureFiles;
    }

    /**
     * Verify file integrity
     */
    public function verifyFileIntegrity(string $path): array
    {
        $result = [
            'path' => $path,
            'exists' => false,
            'metadata_exists' => false,
            'storage_integrity' => false,
            'content_integrity' => false,
            'overall_status' => 'failed',
            'errors' => []
        ];

        // Check if file exists
        if (!Storage::disk('private')->exists($path)) {
            $result['errors'][] = 'File does not exist';
            return $result;
        }
        $result['exists'] = true;

        // Check metadata
        $metadataFile = $path . '.meta';
        if (!Storage::disk('private')->exists($metadataFile)) {
            $result['errors'][] = 'Metadata file does not exist';
            return $result;
        }
        $result['metadata_exists'] = true;

        try {
            $metadata = json_decode(decrypt(Storage::disk('private')->get($metadataFile)), true);
            $content = Storage::disk('private')->get($path);

            // Verify storage integrity
            $currentStorageHash = hash('sha256', $content);
            if ($currentStorageHash === $metadata['storage_hash']) {
                $result['storage_integrity'] = true;
            } else {
                $result['errors'][] = 'Storage integrity check failed';
            }

            // Verify content integrity (after decryption if encrypted)
            if ($metadata['encrypted']) {
                try {
                    $decryptedContent = $this->decryptContent($content);
                    $currentContentHash = hash('sha256', $decryptedContent);
                    if ($currentContentHash === $metadata['original_hash']) {
                        $result['content_integrity'] = true;
                    } else {
                        $result['errors'][] = 'Content integrity check failed after decryption';
                    }
                } catch (\Exception $e) {
                    $result['errors'][] = 'Failed to decrypt content: ' . $e->getMessage();
                }
            } else {
                // For unencrypted files, storage hash should match original hash
                if ($currentStorageHash === $metadata['original_hash']) {
                    $result['content_integrity'] = true;
                } else {
                    $result['errors'][] = 'Content integrity check failed';
                }
            }

            // Overall status
            if ($result['storage_integrity'] && $result['content_integrity']) {
                $result['overall_status'] = 'verified';
            } elseif ($result['storage_integrity'] || $result['content_integrity']) {
                $result['overall_status'] = 'partial';
            }

        } catch (\Exception $e) {
            $result['errors'][] = 'Failed to verify integrity: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Validate uploaded file
     */
    private function validateFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new \Exception('File size exceeds maximum allowed size of ' . (self::MAX_FILE_SIZE / 1024 / 1024) . 'MB');
        }

        // Check MIME type
        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw new \Exception('File type not allowed: ' . $file->getMimeType());
        }

        // Check for malicious content (basic check)
        $content = file_get_contents($file->getRealPath());
        if ($this->containsMaliciousContent($content)) {
            throw new \Exception('File contains potentially malicious content');
        }
    }

    /**
     * Generate secure filename
     */
    private function generateSecureFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(8);
        $hash = substr(hash('sha256', $file->getClientOriginalName() . $timestamp), 0, 8);
        
        return "{$timestamp}_{$hash}_{$random}.{$extension}";
    }

    /**
     * Encrypt file content
     */
    private function encryptContent(string $content): string
    {
        return encrypt($content);
    }

    /**
     * Decrypt file content
     */
    private function decryptContent(string $encryptedContent): string
    {
        try {
            return decrypt($encryptedContent);
        } catch (\Exception $e) {
            throw new \Exception('Failed to decrypt file content: ' . $e->getMessage());
        }
    }

    /**
     * Determine security level based on directory and file type
     */
    private function determineSecurityLevel(string $directory, UploadedFile $file): string
    {
        $highSecurityDirs = ['contracts', 'signatures', 'legal'];
        $mediumSecurityDirs = ['checklists', 'photos', 'reports'];
        
        $dirLower = strtolower($directory);
        
        foreach ($highSecurityDirs as $secureDir) {
            if (str_contains($dirLower, $secureDir)) {
                return 'critical';
            }
        }
        
        foreach ($mediumSecurityDirs as $mediumDir) {
            if (str_contains($dirLower, $mediumDir)) {
                return 'high';
            }
        }
        
        // Check file type
        if ($file->getMimeType() === 'application/pdf') {
            return 'high';
        }
        
        return 'medium';
    }

    /**
     * Get retention policy for directory
     */
    private function getRetentionPolicy(string $directory): array
    {
        $policies = [
            'contracts' => ['years' => 10, 'reason' => 'Legal requirement'],
            'signatures' => ['years' => 10, 'reason' => 'Legal requirement'],
            'checklists' => ['years' => 7, 'reason' => 'Business requirement'],
            'photos' => ['years' => 5, 'reason' => 'Evidence retention'],
            'reports' => ['years' => 3, 'reason' => 'Business requirement']
        ];

        $dirLower = strtolower($directory);
        
        foreach ($policies as $dir => $policy) {
            if (str_contains($dirLower, $dir)) {
                return $policy;
            }
        }

        return ['years' => 2, 'reason' => 'Default policy'];
    }

    /**
     * Get access restrictions for directory
     */
    private function getAccessRestrictions(string $directory): array
    {
        $restrictions = [
            'contracts' => ['roles' => ['admin', 'ops'], 'permissions' => ['view_contracts']],
            'signatures' => ['roles' => ['admin', 'ops'], 'permissions' => ['view_signatures']],
            'checklists' => ['roles' => ['admin', 'ops', 'checker'], 'permissions' => ['view_checklists']],
            'photos' => ['roles' => ['admin', 'ops', 'checker'], 'permissions' => ['view_photos']],
            'reports' => ['roles' => ['admin', 'ops'], 'permissions' => ['view_reports']]
        ];

        $dirLower = strtolower($directory);
        
        foreach ($restrictions as $dir => $restriction) {
            if (str_contains($dirLower, $dir)) {
                return $restriction;
            }
        }

        return ['roles' => ['admin'], 'permissions' => ['view_files']];
    }

    /**
     * Check if content contains malicious patterns
     */
    private function containsMaliciousContent(string $content): bool
    {
        $maliciousPatterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/onclick\s*=/i',
            '/<iframe[^>]*>/i',
            '/<object[^>]*>/i',
            '/<embed[^>]*>/i'
        ];

        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check file access permissions
     */
    private function checkFileAccess(string $path, array $metadata, ?User $user): void
    {
        if (!$this->hasFileAccess($path, $metadata, $user)) {
            throw new \Exception("Access denied to file: {$path}");
        }
    }

    /**
     * Check if user has access to file
     */
    private function hasFileAccess(string $path, array $metadata, ?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $restrictions = $metadata['access_restrictions'] ?? [];
        
        // Check roles
        if (isset($restrictions['roles'])) {
            $userRoles = $user->getRoleNames()->toArray();
            if (!array_intersect($userRoles, $restrictions['roles'])) {
                return false;
            }
        }

        // Check permissions
        if (isset($restrictions['permissions'])) {
            foreach ($restrictions['permissions'] as $permission) {
                if (!$user->can($permission)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check if user can download file
     */
    private function canDownloadFile(string $path, array $metadata, ?User $user): bool
    {
        return $this->hasFileAccess($path, $metadata, $user);
    }

    /**
     * Check if user can delete file
     */
    private function canDeleteFile(string $path, array $metadata, ?User $user): bool
    {
        if (!$user) {
            return false;
        }

        // Only admins can delete critical security level files
        if (($metadata['security_level'] ?? 'medium') === 'critical') {
            return $user->hasRole('admin');
        }

        return $this->hasFileAccess($path, $metadata, $user) && 
               ($user->hasRole('admin') || $user->hasRole('ops'));
    }

    /**
     * Check file deletion access
     */
    private function checkFileDeletionAccess(string $path, array $metadata, ?User $user): void
    {
        if (!$this->canDeleteFile($path, $metadata, $user)) {
            throw new \Exception("Delete access denied to file: {$path}");
        }
    }

    /**
     * Archive file before deletion
     */
    private function archiveFileBeforeDeletion(string $path, array $metadata, ?User $user, string $reason): void
    {
        $archiveDir = 'archives/deleted_files/' . date('Y/m');
        $archiveFilename = $archiveDir . '/' . basename($path) . '_' . now()->format('Y-m-d_H-i-s') . '.archive';
        
        $archiveData = [
            'original_path' => $path,
            'metadata' => $metadata,
            'deleted_by' => $user?->email,
            'deleted_at' => now()->toISOString(),
            'deletion_reason' => $reason,
            'file_content' => base64_encode(Storage::disk('private')->get($path))
        ];

        $encryptedArchive = encrypt(json_encode($archiveData, JSON_PRETTY_PRINT));
        Storage::disk('private')->put($archiveFilename, $encryptedArchive);

        // Create hash for archive integrity
        $archiveHash = hash('sha256', $encryptedArchive);
        Storage::disk('private')->put($archiveFilename . '.hash', $archiveHash);

        Log::channel('security')->info('File archived before deletion', [
            'original_path' => $path,
            'archive_path' => $archiveFilename,
            'deleted_by' => $user?->email,
            'reason' => $reason
        ]);
    }
}