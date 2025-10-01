<?php

namespace App\Http\Controllers\Api;

use App\Models\FileMetadata;
use App\Services\AuditLogger;
use App\Services\FileOrganizationService;
use App\Services\FileSecurityService;
use App\Services\ImageOptimizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class FileController extends BaseApiController
{
    public function __construct(
        private AuditLogger $auditLogger,
        private FileOrganizationService $fileOrganizationService,
        private FileSecurityService $fileSecurityService,
        private ImageOptimizationService $imageOptimizationService
    ) {}

    /**
     * Get files with pagination and filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            
            // Get pagination and sorting parameters
            $paginationParams = $this->getPaginationParams($request);
            $sortingParams = $this->getSortingParams($request, ['filename', 'size', 'created_at']);
            $filters = $this->getFilterParams($request, ['property_id', 'mission_id', 'checklist_id', 'mime_type', 'search']);

            // Build query
            $query = FileMetadata::with(['property', 'mission', 'checklist']);

            // Role-based filtering
            if ($user->role === 'checker') {
                $query->whereHas('mission', function ($q) use ($user) {
                    $q->where('checker_id', $user->id);
                });
            } elseif ($user->role === 'ops') {
                $query->whereHas('mission', function ($q) use ($user) {
                    $q->where('ops_id', $user->id);
                });
            }

            // Apply filters
            if (!empty($filters['property_id'])) {
                $query->where('property_id', $filters['property_id']);
            }

            if (!empty($filters['mission_id'])) {
                $query->where('mission_id', $filters['mission_id']);
            }

            if (!empty($filters['checklist_id'])) {
                $query->where('checklist_id', $filters['checklist_id']);
            }

            if (!empty($filters['mime_type'])) {
                $query->where('mime_type', 'like', $filters['mime_type'] . '%');
            }

            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('filename', 'like', "%{$search}%")
                      ->orWhere('original_name', 'like', "%{$search}%");
                });
            }

            // Apply sorting
            $query->orderBy($sortingParams['sort_by'], $sortingParams['sort_order']);

            // Get paginated results
            $files = $query->paginate($paginationParams['per_page']);

            // Transform data
            $transformedFiles = $files->getCollection()->map(function ($file) {
                return $this->transformFile($file);
            });

            $files->setCollection($transformedFiles);

            return $this->paginated($files, 'Files retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve files');
        }
    }

    /**
     * Get a specific file by ID
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            
            $file = FileMetadata::with(['property', 'mission', 'checklist'])->findOrFail($id);

            // Check permissions
            if (!$this->canAccessFile($user, $file)) {
                return $this->forbidden('You do not have permission to access this file');
            }

            return $this->success([
                'file' => $this->transformFile($file, ['detailed'])
            ], 'File retrieved successfully');

        } catch (\Exception $e) {
            return $this->notFound('File not found');
        }
    }

    /**
     * Upload a new file
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            // Validate request
            $validated = $request->validate([
                'file' => ['required', 'file', 'max:10240'], // 10MB max
                'property_id' => ['nullable', 'exists:properties,id'],
                'mission_id' => ['nullable', 'exists:missions,id'],
                'checklist_id' => ['nullable', 'exists:checklists,id'],
                'description' => ['nullable', 'string', 'max:500'],
            ]);

            $uploadedFile = $request->file('file');

            // Security validation
            if (!$this->fileSecurityService->validateFile($uploadedFile)) {
                return $this->error('File failed security validation', 422);
            }

            // Check file permissions based on mission/checklist
            if (!empty($validated['mission_id'])) {
                $mission = \App\Models\Mission::findOrFail($validated['mission_id']);
                
                if ($user->role === 'checker' && $mission->checker_id !== $user->id) {
                    return $this->forbidden('You can only upload files to your own missions');
                }
                
                if ($user->role === 'ops' && $mission->ops_id !== $user->id && !$this->checkRole($request, ['admin'])) {
                    return $this->forbidden('You can only upload files to missions assigned to you');
                }
            }

            // Organize and store file
            $fileData = $this->fileOrganizationService->organizeFile(
                $uploadedFile,
                $validated['property_id'] ?? null,
                $validated['mission_id'] ?? null,
                $validated['checklist_id'] ?? null
            );

            // Optimize image if applicable
            if (str_starts_with($uploadedFile->getMimeType(), 'image/')) {
                $this->imageOptimizationService->optimizeImage($fileData['path']);
            }

            // Create file metadata record
            $fileMetadata = FileMetadata::create([
                'filename' => $fileData['filename'],
                'original_name' => $uploadedFile->getClientOriginalName(),
                'path' => $fileData['path'],
                'size' => $uploadedFile->getSize(),
                'mime_type' => $uploadedFile->getMimeType(),
                'property_id' => $validated['property_id'] ?? null,
                'mission_id' => $validated['mission_id'] ?? null,
                'checklist_id' => $validated['checklist_id'] ?? null,
                'uploaded_by' => $user->id,
                'description' => $validated['description'] ?? null,
            ]);

            // Log the action
            $this->auditLogger->log('file_uploaded', $user, [
                'file_id' => $fileMetadata->id,
                'filename' => $fileMetadata->filename,
                'size' => $fileMetadata->size,
                'mime_type' => $fileMetadata->mime_type,
                'property_id' => $fileMetadata->property_id,
                'mission_id' => $fileMetadata->mission_id,
            ]);

            return $this->success([
                'file' => $this->transformFile($fileMetadata)
            ], 'File uploaded successfully', 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to upload file');
        }
    }

    /**
     * Update file metadata
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            $file = FileMetadata::findOrFail($id);

            // Check permissions
            if (!$this->canAccessFile($user, $file)) {
                return $this->forbidden('You do not have permission to update this file');
            }

            // Validate request
            $validated = $request->validate([
                'description' => ['nullable', 'string', 'max:500'],
            ]);

            // Store original data for audit
            $originalData = $file->toArray();

            // Update file metadata
            $file->update($validated);

            // Log the action
            $this->auditLogger->log('file_updated', $user, [
                'file_id' => $file->id,
                'filename' => $file->filename,
                'changes' => array_diff_assoc($validated, $originalData),
            ]);

            return $this->success([
                'file' => $this->transformFile($file)
            ], 'File updated successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->notFound('File not found');
        }
    }

    /**
     * Delete a file
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            $file = FileMetadata::findOrFail($id);

            // Check permissions
            if (!$this->canAccessFile($user, $file) || !$this->checkRole($request, ['admin', 'ops'])) {
                return $this->forbidden('You do not have permission to delete this file');
            }

            // Store file data for audit
            $fileData = $file->toArray();

            // Delete physical file
            if (Storage::exists($file->path)) {
                Storage::delete($file->path);
            }

            // Delete file metadata
            $file->delete();

            // Log the action
            $this->auditLogger->log('file_deleted', $user, [
                'file_id' => $id,
                'filename' => $fileData['filename'],
                'path' => $fileData['path'],
            ]);

            return $this->success(null, 'File deleted successfully');

        } catch (\Exception $e) {
            return $this->notFound('File not found');
        }
    }

    /**
     * Download a file
     */
    public function download(Request $request, int $id): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);
            $file = FileMetadata::findOrFail($id);

            // Check permissions
            if (!$this->canAccessFile($user, $file)) {
                return $this->forbidden('You do not have permission to download this file');
            }

            // Check if file exists
            if (!Storage::exists($file->path)) {
                return $this->notFound('File not found on storage');
            }

            // Log the action
            $this->auditLogger->log('file_downloaded', $user, [
                'file_id' => $file->id,
                'filename' => $file->filename,
            ]);

            // Return file URL for download
            $url = Storage::url($file->path);

            return $this->success([
                'download_url' => $url,
                'filename' => $file->original_name,
                'size' => $file->size,
                'mime_type' => $file->mime_type,
            ], 'File download URL generated successfully');

        } catch (\Exception $e) {
            return $this->notFound('File not found');
        }
    }

    /**
     * Get file statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            $query = FileMetadata::query();

            // Filter by user role
            if ($user->role === 'checker') {
                $query->whereHas('mission', function ($q) use ($user) {
                    $q->where('checker_id', $user->id);
                });
            } elseif ($user->role === 'ops') {
                $query->whereHas('mission', function ($q) use ($user) {
                    $q->where('ops_id', $user->id);
                });
            }

            $stats = [
                'total_files' => $query->count(),
                'total_size' => $query->sum('size'),
                'files_by_type' => (clone $query)->selectRaw('mime_type, COUNT(*) as count')
                    ->groupBy('mime_type')
                    ->pluck('count', 'mime_type'),
                'recent_files' => (clone $query)->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(fn($file) => $this->transformFile($file)),
            ];

            return $this->success($stats, 'File statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve file statistics');
        }
    }

    /**
     * Check if user can access a file
     */
    private function canAccessFile($user, FileMetadata $file): bool
    {
        // Admin can access all files
        if ($user->role === 'admin') {
            return true;
        }

        // If file is associated with a mission, check mission permissions
        if ($file->mission_id) {
            $mission = $file->mission;
            
            if ($user->role === 'checker' && $mission->checker_id === $user->id) {
                return true;
            }
            
            if ($user->role === 'ops' && $mission->ops_id === $user->id) {
                return true;
            }
        }

        // If file was uploaded by the user
        if ($file->uploaded_by === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Transform file for API response
     */
    private function transformFile(FileMetadata $file, array $options = []): array
    {
        $data = [
            'id' => $file->id,
            'filename' => $file->filename,
            'original_name' => $file->original_name,
            'size' => $file->size,
            'mime_type' => $file->mime_type,
            'description' => $file->description,
            'created_at' => $file->created_at,
            'updated_at' => $file->updated_at,
            'property' => $file->property ? [
                'id' => $file->property->id,
                'name' => $file->property->name,
            ] : null,
            'mission' => $file->mission ? [
                'id' => $file->mission->id,
                'title' => $file->mission->title,
            ] : null,
            'checklist' => $file->checklist ? [
                'id' => $file->checklist->id,
            ] : null,
        ];

        // Include detailed information if requested
        if (in_array('detailed', $options)) {
            $data['path'] = $file->path;
            $data['uploaded_by'] = $file->uploaded_by;
            $data['property_id'] = $file->property_id;
            $data['mission_id'] = $file->mission_id;
            $data['checklist_id'] = $file->checklist_id;
        }

        return $data;
    }
}