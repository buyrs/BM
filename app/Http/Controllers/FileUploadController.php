<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileUploadRequest;
use App\Services\FileOrganizationService;
use App\Models\FileMetadata;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class FileUploadController extends Controller
{
    public function __construct(
        private FileOrganizationService $fileOrganizationService
    ) {}

    /**
     * Upload a file with organization.
     */
    public function upload(FileUploadRequest $request): JsonResponse
    {
        try {
            $fileMetadata = $this->fileOrganizationService->uploadFile(
                file: $request->file('file'),
                propertyId: $request->input('property_id'),
                missionId: $request->input('mission_id'),
                checklistId: $request->input('checklist_id'),
                isPublic: $request->boolean('is_public', false),
                disk: $request->input('disk', 'local')
            );

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => [
                    'id' => $fileMetadata->id,
                    'filename' => $fileMetadata->filename,
                    'original_name' => $fileMetadata->original_name,
                    'size' => $fileMetadata->human_size,
                    'mime_type' => $fileMetadata->mime_type,
                    'url' => $fileMetadata->url,
                    'is_image' => $fileMetadata->isImage(),
                    'metadata' => $fileMetadata->metadata,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'File upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get files by hierarchy.
     */
    public function index(Request $request): JsonResponse
    {
        $files = $this->fileOrganizationService->getFilesByHierarchy(
            propertyId: $request->input('property_id'),
            missionId: $request->input('mission_id'),
            checklistId: $request->input('checklist_id'),
            mimeType: $request->input('mime_type')
        );

        return response()->json([
            'success' => true,
            'data' => $files->map(function ($file) {
                return [
                    'id' => $file->id,
                    'filename' => $file->filename,
                    'original_name' => $file->original_name,
                    'size' => $file->human_size,
                    'mime_type' => $file->mime_type,
                    'url' => $file->url,
                    'is_image' => $file->isImage(),
                    'metadata' => $file->metadata,
                    'property' => $file->property?->only(['id', 'name']),
                    'mission' => $file->mission?->only(['id', 'title']),
                    'checklist' => $file->checklist?->only(['id', 'title']),
                    'uploaded_by' => $file->uploadedBy?->only(['id', 'name']),
                    'created_at' => $file->created_at,
                ];
            })
        ]);
    }

    /**
     * Download a file.
     */
    public function download(FileMetadata $fileMetadata)
    {
        // Check access permissions
        if (!$this->fileOrganizationService->hasFileAccess($fileMetadata, Auth::user())) {
            abort(403, 'Access denied');
        }

        // Mark as accessed
        $fileMetadata->markAsAccessed();

        // Return file download
        return response()->download(
            storage_path('app/' . $fileMetadata->path),
            $fileMetadata->original_name
        );
    }

    /**
     * Delete a file.
     */
    public function destroy(FileMetadata $fileMetadata): JsonResponse
    {
        // Check access permissions
        if (!$this->fileOrganizationService->hasFileAccess($fileMetadata, Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        try {
            // Delete file from storage
            $fileMetadata->deleteFile();
            
            // Delete metadata record
            $fileMetadata->delete();

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'File deletion failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Move a file to different organization.
     */
    public function move(FileMetadata $fileMetadata, Request $request): JsonResponse
    {
        // Check access permissions
        if (!$this->fileOrganizationService->hasFileAccess($fileMetadata, Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $request->validate([
            'property_id' => 'nullable|exists:properties,id',
            'mission_id' => 'nullable|exists:missions,id',
            'checklist_id' => 'nullable|exists:checklists,id',
        ]);

        try {
            $success = $this->fileOrganizationService->moveFile(
                $fileMetadata,
                $request->input('property_id'),
                $request->input('mission_id'),
                $request->input('checklist_id')
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'File moved successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'File move failed'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'File move failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get storage statistics.
     */
    public function stats(): JsonResponse
    {
        $stats = $this->fileOrganizationService->getStorageStats();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
