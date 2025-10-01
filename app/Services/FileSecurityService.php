<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileSecurityService
{
    /**
     * Allowed MIME types for different file categories.
     */
    protected array $allowedMimeTypes = [
        'images' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml'
        ],
        'documents' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv'
        ],
        'archives' => [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed'
        ]
    ];

    /**
     * Maximum file sizes in bytes for different categories.
     */
    protected array $maxFileSizes = [
        'images' => 10 * 1024 * 1024, // 10MB
        'documents' => 50 * 1024 * 1024, // 50MB
        'archives' => 100 * 1024 * 1024, // 100MB
        'default' => 5 * 1024 * 1024 // 5MB
    ];

    /**
     * Dangerous file extensions that should never be allowed.
     */
    protected array $dangerousExtensions = [
        'php', 'php3', 'php4', 'php5', 'phtml', 'pht',
        'exe', 'com', 'bat', 'cmd', 'scr', 'vbs', 'vbe',
        'js', 'jar', 'app', 'deb', 'rpm', 'dmg',
        'asp', 'aspx', 'jsp', 'cfm', 'cgi', 'pl', 'py',
        'sh', 'bash', 'zsh', 'fish'
    ];

    /**
     * Validate uploaded file security.
     */
    public function validateFile(UploadedFile $file, string $category = 'default'): array
    {
        $errors = [];

        // Check if file is valid
        if (!$file->isValid()) {
            $errors[] = 'File upload failed or file is corrupted.';
            return ['valid' => false, 'errors' => $errors];
        }

        // Check file size
        $maxSize = $this->getMaxFileSize($category);
        if ($file->getSize() > $maxSize) {
            $errors[] = "File size exceeds maximum allowed size of " . $this->formatBytes($maxSize) . ".";
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, $this->dangerousExtensions)) {
            $errors[] = "File extension '{$extension}' is not allowed for security reasons.";
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        if (!$this->isMimeTypeAllowed($mimeType, $category)) {
            $errors[] = "File type '{$mimeType}' is not allowed.";
        }

        // Verify MIME type matches extension
        if (!$this->verifyMimeTypeMatchesExtension($file)) {
            $errors[] = "File content does not match its extension. Possible file type spoofing detected.";
        }

        // Check for malicious content
        $maliciousCheck = $this->scanForMaliciousContent($file);
        if (!$maliciousCheck['safe']) {
            $errors = array_merge($errors, $maliciousCheck['threats']);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'file_info' => [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $mimeType,
                'extension' => $extension
            ]
        ];
    }

    /**
     * Generate a secure filename.
     */
    public function generateSecureFilename(UploadedFile $file, string $prefix = ''): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $hash = hash('sha256', $file->getClientOriginalName() . time() . random_bytes(16));
        $shortHash = substr($hash, 0, 16);
        
        $filename = $prefix ? "{$prefix}_{$shortHash}" : $shortHash;
        
        return $extension ? "{$filename}.{$extension}" : $filename;
    }

    /**
     * Generate secure storage path.
     */
    public function generateSecurePath(string $category, int $userId = null, int $propertyId = null, int $missionId = null): string
    {
        $path = "secure/{$category}";
        
        if ($propertyId) {
            $path .= "/properties/{$propertyId}";
        }
        
        if ($missionId) {
            $path .= "/missions/{$missionId}";
        }
        
        if ($userId) {
            $path .= "/users/{$userId}";
        }
        
        $path .= "/" . date('Y/m/d');
        
        return $path;
    }

    /**
     * Store file securely.
     */
    public function storeSecurely(UploadedFile $file, string $path, string $filename): array
    {
        try {
            // Ensure directory exists and has proper permissions
            $fullPath = storage_path("app/{$path}");
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }

            // Store the file
            $storedPath = $file->storeAs($path, $filename, 'local');
            
            // Set proper file permissions
            $fullFilePath = storage_path("app/{$storedPath}");
            chmod($fullFilePath, 0644);
            
            return [
                'success' => true,
                'path' => $storedPath,
                'url' => Storage::url($storedPath),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to store file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if user has permission to access file.
     */
    public function hasFileAccess(string $filePath, int $userId, string $userRole): bool
    {
        // Admin can access all files
        if ($userRole === 'admin') {
            return true;
        }

        // Check if file path contains user ID for user-specific files
        if (str_contains($filePath, "/users/{$userId}/")) {
            return true;
        }

        // Ops can access property and mission files
        if ($userRole === 'ops' && (str_contains($filePath, '/properties/') || str_contains($filePath, '/missions/'))) {
            return true;
        }

        // Checker can access mission files they're assigned to
        if ($userRole === 'checker' && str_contains($filePath, '/missions/')) {
            // Additional logic would be needed to check mission assignment
            return true;
        }

        return false;
    }

    /**
     * Get maximum file size for category.
     */
    protected function getMaxFileSize(string $category): int
    {
        return $this->maxFileSizes[$category] ?? $this->maxFileSizes['default'];
    }

    /**
     * Check if MIME type is allowed for category.
     */
    protected function isMimeTypeAllowed(string $mimeType, string $category): bool
    {
        if ($category === 'default') {
            // For default category, check all allowed types
            foreach ($this->allowedMimeTypes as $types) {
                if (in_array($mimeType, $types)) {
                    return true;
                }
            }
            return false;
        }

        return in_array($mimeType, $this->allowedMimeTypes[$category] ?? []);
    }

    /**
     * Verify MIME type matches file extension.
     */
    protected function verifyMimeTypeMatchesExtension(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();

        $expectedMimeTypes = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            'svg' => ['image/svg+xml'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'txt' => ['text/plain'],
            'csv' => ['text/csv', 'text/plain'],
            'zip' => ['application/zip'],
            'rar' => ['application/x-rar-compressed'],
            '7z' => ['application/x-7z-compressed']
        ];

        if (!isset($expectedMimeTypes[$extension])) {
            return false;
        }

        return in_array($mimeType, $expectedMimeTypes[$extension]);
    }

    /**
     * Scan file for malicious content.
     */
    protected function scanForMaliciousContent(UploadedFile $file): array
    {
        $threats = [];
        $content = file_get_contents($file->getPathname());

        // Check for PHP code in non-PHP files
        if (!in_array(strtolower($file->getClientOriginalExtension()), ['php', 'phtml'])) {
            if (preg_match('/<\?php|<\?=|\<\%|\<script/i', $content)) {
                $threats[] = 'Suspicious script content detected in file.';
            }
        }

        // Check for suspicious patterns
        $suspiciousPatterns = [
            '/eval\s*\(/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/shell_exec\s*\(/i',
            '/passthru\s*\(/i',
            '/base64_decode\s*\(/i',
            '/file_get_contents\s*\(/i',
            '/curl_exec\s*\(/i'
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $threats[] = 'Potentially malicious code pattern detected.';
                break;
            }
        }

        // Check file size vs content (detect zip bombs, etc.)
        if ($file->getSize() > 0 && strlen($content) / $file->getSize() > 100) {
            $threats[] = 'Suspicious compression ratio detected. Possible zip bomb.';
        }

        return [
            'safe' => empty($threats),
            'threats' => $threats
        ];
    }

    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get allowed file types for a category.
     */
    public function getAllowedTypes(string $category): array
    {
        return $this->allowedMimeTypes[$category] ?? [];
    }

    /**
     * Get all allowed categories.
     */
    public function getAllowedCategories(): array
    {
        return array_keys($this->allowedMimeTypes);
    }
}