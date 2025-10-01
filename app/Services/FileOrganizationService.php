<?php

namespace App\Services;

use App\Models\FileMetadata;
use App\Models\Property;
use App\Models\Mission;
use App\Models\Checklist;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class FileOrganizationService extends BaseService
{
    public function __construct(
        private ?ImageOptimizationService $imageOptimizationService = null
    ) {
        $this->imageOptimizationService = $imageOptimizationService ?? app(ImageOptimizationService::class);
    }
    /**
     * Upload and organize a file.
     */
    public function uploadFile(
        UploadedFile $file,
        ?int $propertyId = null,
        ?int $missionId = null,
        ?int $checklistId = null,
        bool $isPublic = false,
        string $disk = 'local'
    ): FileMetadata {
        // Generate organized path
        $path = $this->generateOrganizedPath($file, $propertyId, $missionId, $checklistId);
        
        // Generate unique filename
        $filename = $this->generateUniqueFilename($file);
        
        // Store the file
        $storedPath = $file->storeAs($path, $filename, $disk);
        
        // Calculate file hash for deduplication
        $fileHash = hash_file('sha256', $file->getRealPath());
        
        // Extract metadata
        $metadata = $this->extractFileMetadata($file);
        
        // Create file metadata record
        $fileMetadata = FileMetadata::create([
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'path' => $storedPath,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'file_hash' => $fileHash,
            'metadata' => $metadata,
            'property_id' => $propertyId,
            'mission_id' => $missionId,
            'checklist_id' => $checklistId,
            'uploaded_by' => Auth::id(),
            'storage_disk' => $disk,
            'is_public' => $isPublic,
        ]);

        // Auto-generate thumbnails for images
        if ($fileMetadata->isImage()) {
            try {
                $this->imageOptimizationService->generateThumbnails($fileMetadata);
            } catch (\Exception $e) {
                // Log error but don't fail the upload
                logger()->warning('Failed to generate thumbnails for uploaded image', [
                    'file_id' => $fileMetadata->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $fileMetadata;
    }

    /**
     * Generate organized file path based on hierarchy.
     */
    protected function generateOrganizedPath(
        UploadedFile $file,
        ?int $propertyId = null,
        ?int $missionId = null,
        ?int $checklistId = null
    ): string {
        $basePath = 'files';
        
        if ($propertyId) {
            $basePath .= "/properties/{$propertyId}";
            
            if ($missionId) {
                $basePath .= "/missions/{$missionId}";
                
                if ($checklistId) {
                    $basePath .= "/checklists/{$checklistId}";
                }
            } else {
                $basePath .= "/general";
            }
        } else {
            $basePath .= "/system";
        }
        
        // Add date-based organization
        $basePath .= "/" . date('Y/m');
        
        return $basePath;
    }

    /**
     * Generate unique filename while preserving extension.
     */
    protected function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $basename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        
        // Sanitize filename
        $basename = Str::slug($basename);
        
        // Add timestamp and random string for uniqueness
        $unique = time() . '_' . Str::random(8);
        
        return $basename . '_' . $unique . ($extension ? '.' . $extension : '');
    }

    /**
     * Extract file metadata.
     */
    protected function extractFileMetadata(UploadedFile $file): array
    {
        $metadata = [];
        
        // For images, extract dimensions
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $imageInfo = getimagesize($file->getRealPath());
            if ($imageInfo) {
                $metadata['width'] = $imageInfo[0];
                $metadata['height'] = $imageInfo[1];
                $metadata['type'] = $imageInfo[2];
            }
        }
        
        return $metadata;
    }

    /**
     * Move file to a different organization structure.
     */
    public function moveFile(
        FileMetadata $fileMetadata,
        ?int $propertyId = null,
        ?int $missionId = null,
        ?int $checklistId = null
    ): bool {
        $oldPath = $fileMetadata->path;
        
        // Create a temporary uploaded file object for path generation
        $newPath = $this->generateOrganizedPath(
            new class($fileMetadata->original_name) {
                public function __construct(private string $name) {}
                public function getClientOriginalName(): string { return $this->name; }
                public function getClientOriginalExtension(): string { return pathinfo($this->name, PATHINFO_EXTENSION); }
                public function getMimeType(): string { return ''; }
                public function getSize(): int { return 0; }
                public function getRealPath(): string { return ''; }
            },
            $propertyId,
            $missionId,
            $checklistId
        ) . '/' . $fileMetadata->filename;
        
        // Move the file in storage
        if (Storage::disk($fileMetadata->storage_disk)->move($oldPath, $newPath)) {
            // Update the metadata
            $fileMetadata->update([
                'path' => $newPath,
                'property_id' => $propertyId,
                'mission_id' => $missionId,
                'checklist_id' => $checklistId,
            ]);
            
            return true;
        }
        
        return false;
    }

    /**
     * Get files by organization hierarchy.
     */
    public function getFilesByHierarchy(
        ?int $propertyId = null,
        ?int $missionId = null,
        ?int $checklistId = null,
        ?string $mimeType = null
    ) {
        $query = FileMetadata::query()
            ->with(['property', 'mission', 'checklist', 'uploadedBy']);
        
        if ($propertyId) {
            $query->where('property_id', $propertyId);
        }
        
        if ($missionId) {
            $query->where('mission_id', $missionId);
        }
        
        if ($checklistId) {
            $query->where('checklist_id', $checklistId);
        }
        
        if ($mimeType) {
            $query->where('mime_type', 'like', $mimeType . '%');
        }
        
        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Check if user has access to file.
     */
    public function hasFileAccess(FileMetadata $fileMetadata, User $user): bool
    {
        // Public files are accessible to all authenticated users
        if ($fileMetadata->is_public) {
            return true;
        }
        
        // File owner always has access
        if ($fileMetadata->uploaded_by === $user->id) {
            return true;
        }
        
        // Admin has access to all files
        if ($user->role === 'admin') {
            return true;
        }
        
        // Ops users have access to files in their properties/missions
        if ($user->role === 'ops') {
            // Check if user has access to the property/mission
            if ($fileMetadata->property_id) {
                // For now, ops users have access to all properties
                // This can be enhanced with property-specific permissions
                return true;
            }
        }
        
        // Checker users have access to files in their assigned missions
        if ($user->role === 'checker' && $fileMetadata->mission_id) {
            $mission = Mission::find($fileMetadata->mission_id);
            return $mission && $mission->checker_id === $user->id;
        }
        
        return false;
    }

    /**
     * Get storage usage statistics.
     */
    public function getStorageStats(): array
    {
        $stats = [
            'total_files' => FileMetadata::count(),
            'total_size' => FileMetadata::sum('size'),
            'by_type' => FileMetadata::selectRaw('
                CASE 
                    WHEN mime_type LIKE "image/%" THEN "images"
                    WHEN mime_type LIKE "video/%" THEN "videos"
                    WHEN mime_type LIKE "application/pdf" THEN "documents"
                    ELSE "other"
                END as type,
                COUNT(*) as count,
                SUM(size) as size
            ')
            ->groupBy('type')
            ->get()
            ->keyBy('type'),
            'by_property' => FileMetadata::with('property')
                ->selectRaw('property_id, COUNT(*) as count, SUM(size) as size')
                ->whereNotNull('property_id')
                ->groupBy('property_id')
                ->get(),
        ];
        
        return $stats;
    }

    /**
     * Clean up orphaned files (files without metadata records).
     */
    public function cleanupOrphanedFiles(string $disk = 'local'): array
    {
        $cleaned = [];
        $allFiles = Storage::disk($disk)->allFiles('files');
        
        foreach ($allFiles as $filePath) {
            $exists = FileMetadata::where('path', $filePath)
                ->where('storage_disk', $disk)
                ->exists();
            
            if (!$exists) {
                if (Storage::disk($disk)->delete($filePath)) {
                    $cleaned[] = $filePath;
                }
            }
        }
        
        return $cleaned;
    }

    /**
     * Find duplicate files by hash.
     */
    public function findDuplicateFiles(): array
    {
        return FileMetadata::selectRaw('file_hash, COUNT(*) as count')
            ->whereNotNull('file_hash')
            ->groupBy('file_hash')
            ->having('count', '>', 1)
            ->with(['property', 'mission', 'checklist'])
            ->get()
            ->map(function ($item) {
                return [
                    'hash' => $item->file_hash,
                    'count' => $item->count,
                    'files' => FileMetadata::where('file_hash', $item->file_hash)->get(),
                ];
            })
            ->toArray();
    }
}