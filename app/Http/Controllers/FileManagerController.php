<?php

namespace App\Http\Controllers;

use App\Models\FileMetadata;
use App\Models\Property;
use App\Models\Mission;
use App\Services\FileOrganizationService;
use App\Services\ImageOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class FileManagerController extends Controller
{
    public function __construct(
        private FileOrganizationService $fileOrganizationService,
        private ImageOptimizationService $imageOptimizationService
    ) {}

    /**
     * Display the file manager interface.
     */
    public function index(Request $request)
    {
        $properties = Property::select('id', 'property_address as name')->get();
        $missions = Mission::select('id', 'title', 'property_address')->get();

        return view('admin.file-manager.index', compact('properties', 'missions'));
    }

    /**
     * Get files with filtering and search.
     */
    public function getFiles(Request $request): JsonResponse
    {
        $query = FileMetadata::query()
            ->with(['property:id,name', 'mission:id,title', 'checklist:id,title', 'uploadedBy:id,name']);

        // Apply filters
        if ($request->filled('property_id')) {
            $query->where('property_id', $request->input('property_id'));
        }

        if ($request->filled('mission_id')) {
            $query->where('mission_id', $request->input('mission_id'));
        }

        if ($request->filled('checklist_id')) {
            $query->where('checklist_id', $request->input('checklist_id'));
        }

        if ($request->filled('mime_type')) {
            $query->where('mime_type', 'like', $request->input('mime_type') . '%');
        }

        if ($request->filled('uploaded_by')) {
            $query->where('uploaded_by', $request->input('uploaded_by'));
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('original_name', 'like', "%{$search}%")
                  ->orWhere('filename', 'like', "%{$search}%");
            });
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 20);
        $files = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $files->items(),
            'pagination' => [
                'current_page' => $files->currentPage(),
                'last_page' => $files->lastPage(),
                'per_page' => $files->perPage(),
                'total' => $files->total(),
            ]
        ]);
    }

    /**
     * Get file details.
     */
    public function show(FileMetadata $fileMetadata): JsonResponse
    {
        $fileMetadata->load(['property', 'mission', 'checklist', 'uploadedBy']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $fileMetadata->id,
                'filename' => $fileMetadata->filename,
                'original_name' => $fileMetadata->original_name,
                'size' => $fileMetadata->human_size,
                'mime_type' => $fileMetadata->mime_type,
                'url' => $fileMetadata->url,
                'is_image' => $fileMetadata->isImage(),
                'metadata' => $fileMetadata->metadata,
                'property' => $fileMetadata->property,
                'mission' => $fileMetadata->mission,
                'checklist' => $fileMetadata->checklist,
                'uploaded_by' => $fileMetadata->uploadedBy,
                'created_at' => $fileMetadata->created_at,
                'last_accessed_at' => $fileMetadata->last_accessed_at,
            ]
        ]);
    }

    /**
     * Bulk delete files.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'file_ids' => 'required|array',
            'file_ids.*' => 'integer|exists:file_metadata,id'
        ]);

        $fileIds = $request->input('file_ids');
        $deletedCount = 0;
        $errors = [];

        foreach ($fileIds as $fileId) {
            $fileMetadata = FileMetadata::find($fileId);
            
            if (!$fileMetadata) {
                $errors[] = "File {$fileId} not found";
                continue;
            }

            // Check permissions
            if (!$this->fileOrganizationService->hasFileAccess($fileMetadata, Auth::user())) {
                $errors[] = "Access denied for file {$fileId}";
                continue;
            }

            try {
                // Clean up thumbnails if it's an image
                if ($fileMetadata->isImage()) {
                    $this->imageOptimizationService->cleanupThumbnails($fileMetadata);
                }

                // Delete file from storage
                $fileMetadata->deleteFile();
                
                // Delete metadata record
                $fileMetadata->delete();
                
                $deletedCount++;
            } catch (\Exception $e) {
                $errors[] = "Failed to delete file {$fileId}: " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => count($errors) === 0,
            'message' => "Deleted {$deletedCount} files" . (count($errors) > 0 ? " with " . count($errors) . " errors" : ""),
            'deleted_count' => $deletedCount,
            'errors' => $errors
        ]);
    }

    /**
     * Bulk move files.
     */
    public function bulkMove(Request $request): JsonResponse
    {
        $request->validate([
            'file_ids' => 'required|array',
            'file_ids.*' => 'integer|exists:file_metadata,id',
            'property_id' => 'nullable|exists:properties,id',
            'mission_id' => 'nullable|exists:missions,id',
            'checklist_id' => 'nullable|exists:checklists,id',
        ]);

        $fileIds = $request->input('file_ids');
        $movedCount = 0;
        $errors = [];

        foreach ($fileIds as $fileId) {
            $fileMetadata = FileMetadata::find($fileId);
            
            if (!$fileMetadata) {
                $errors[] = "File {$fileId} not found";
                continue;
            }

            // Check permissions
            if (!$this->fileOrganizationService->hasFileAccess($fileMetadata, Auth::user())) {
                $errors[] = "Access denied for file {$fileId}";
                continue;
            }

            try {
                $success = $this->fileOrganizationService->moveFile(
                    $fileMetadata,
                    $request->input('property_id'),
                    $request->input('mission_id'),
                    $request->input('checklist_id')
                );

                if ($success) {
                    $movedCount++;
                } else {
                    $errors[] = "Failed to move file {$fileId}";
                }
            } catch (\Exception $e) {
                $errors[] = "Failed to move file {$fileId}: " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => count($errors) === 0,
            'message' => "Moved {$movedCount} files" . (count($errors) > 0 ? " with " . count($errors) . " errors" : ""),
            'moved_count' => $movedCount,
            'errors' => $errors
        ]);
    }

    /**
     * Search files.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'filters' => 'array'
        ]);

        $searchQuery = $request->input('query');
        $filters = $request->input('filters', []);

        $query = FileMetadata::query()
            ->with(['property:id,name', 'mission:id,title', 'uploadedBy:id,name'])
            ->where(function ($q) use ($searchQuery) {
                $q->where('original_name', 'like', "%{$searchQuery}%")
                  ->orWhere('filename', 'like', "%{$searchQuery}%")
                  ->orWhereJsonContains('metadata', $searchQuery);
            });

        // Apply additional filters
        foreach ($filters as $key => $value) {
            if ($value !== null && $value !== '') {
                switch ($key) {
                    case 'property_id':
                    case 'mission_id':
                    case 'checklist_id':
                    case 'uploaded_by':
                        $query->where($key, $value);
                        break;
                    case 'mime_type':
                        $query->where('mime_type', 'like', $value . '%');
                        break;
                    case 'size_min':
                        $query->where('size', '>=', $value);
                        break;
                    case 'size_max':
                        $query->where('size', '<=', $value);
                        break;
                }
            }
        }

        $results = $query->orderBy('created_at', 'desc')->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => $results->map(function ($file) {
                return [
                    'id' => $file->id,
                    'filename' => $file->filename,
                    'original_name' => $file->original_name,
                    'size' => $file->human_size,
                    'mime_type' => $file->mime_type,
                    'url' => $file->url,
                    'is_image' => $file->isImage(),
                    'property' => $file->property?->only(['id', 'name']),
                    'mission' => $file->mission?->only(['id', 'title']),
                    'uploaded_by' => $file->uploadedBy?->only(['id', 'name']),
                    'created_at' => $file->created_at,
                ];
            }),
            'count' => $results->count()
        ]);
    }

    /**
     * Get file access permissions for current user.
     */
    public function getPermissions(FileMetadata $fileMetadata): JsonResponse
    {
        $user = Auth::user();
        $hasAccess = $this->fileOrganizationService->hasFileAccess($fileMetadata, $user);

        $permissions = [
            'can_view' => $hasAccess,
            'can_download' => $hasAccess,
            'can_delete' => $hasAccess && ($user->role === 'admin' || $fileMetadata->uploaded_by === $user->id),
            'can_move' => $hasAccess && ($user->role === 'admin' || $user->role === 'ops'),
            'can_share' => $hasAccess && ($user->role === 'admin' || $user->role === 'ops'),
        ];

        return response()->json([
            'success' => true,
            'data' => $permissions
        ]);
    }

    /**
     * Get storage statistics.
     */
    public function getStats(): JsonResponse
    {
        $stats = $this->fileOrganizationService->getStorageStats();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Export file list.
     */
    public function export(Request $request)
    {
        $request->validate([
            'format' => 'required|in:csv,xlsx',
            'filters' => 'array'
        ]);

        $query = FileMetadata::query()
            ->with(['property:id,name', 'mission:id,title', 'uploadedBy:id,name']);

        // Apply filters (similar to getFiles method)
        $filters = $request->input('filters', []);
        foreach ($filters as $key => $value) {
            if ($value !== null && $value !== '') {
                switch ($key) {
                    case 'property_id':
                    case 'mission_id':
                    case 'checklist_id':
                    case 'uploaded_by':
                        $query->where($key, $value);
                        break;
                    case 'mime_type':
                        $query->where('mime_type', 'like', $value . '%');
                        break;
                }
            }
        }

        $files = $query->orderBy('created_at', 'desc')->get();

        $data = $files->map(function ($file) {
            return [
                'ID' => $file->id,
                'Original Name' => $file->original_name,
                'File Name' => $file->filename,
                'Size' => $file->human_size,
                'MIME Type' => $file->mime_type,
                'Property' => $file->property?->name ?? 'N/A',
                'Mission' => $file->mission?->title ?? 'N/A',
                'Uploaded By' => $file->uploadedBy?->name ?? 'N/A',
                'Upload Date' => $file->created_at->format('Y-m-d H:i:s'),
                'Last Accessed' => $file->last_accessed_at?->format('Y-m-d H:i:s') ?? 'Never',
            ];
        })->toArray();

        $filename = 'file_list_' . date('Y-m-d_H-i-s') . '.' . $request->input('format');

        if ($request->input('format') === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($data) {
                $file = fopen('php://output', 'w');
                
                // Add headers
                if (!empty($data)) {
                    fputcsv($file, array_keys($data[0]));
                }
                
                // Add data
                foreach ($data as $row) {
                    fputcsv($file, $row);
                }
                
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        // For XLSX format, you would need to implement Excel export
        // For now, return CSV format
        return $this->export($request->merge(['format' => 'csv']));
    }
}
