<?php

namespace App\Http\Controllers;

use App\Http\Requests\SecureFileUploadRequest;
use App\Services\FileSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SecureFileController extends Controller
{
    public function __construct(
        private FileSecurityService $fileSecurityService
    ) {}

    /**
     * Upload files securely.
     */
    public function upload(SecureFileUploadRequest $request)
    {
        $user = Auth::user();
        $category = $request->input('category', 'default');
        $propertyId = $request->input('property_id');
        $missionId = $request->input('mission_id');
        $description = $request->input('description');

        $uploadedFiles = [];
        $errors = [];

        foreach ($request->file('files') as $file) {
            // Generate secure filename and path
            $filename = $this->fileSecurityService->generateSecureFilename($file, $category);
            $path = $this->fileSecurityService->generateSecurePath($category, $user->id, $propertyId, $missionId);

            // Store file securely
            $result = $this->fileSecurityService->storeSecurely($file, $path, $filename);

            if ($result['success']) {
                $uploadedFiles[] = [
                    'original_name' => $file->getClientOriginalName(),
                    'filename' => $filename,
                    'path' => $result['path'],
                    'url' => $result['url'],
                    'size' => $result['size'],
                    'mime_type' => $result['mime_type'],
                    'category' => $category,
                    'uploaded_by' => $user->id,
                    'property_id' => $propertyId,
                    'mission_id' => $missionId,
                    'description' => $description,
                    'uploaded_at' => now()->toISOString()
                ];
            } else {
                $errors[] = [
                    'file' => $file->getClientOriginalName(),
                    'error' => $result['error']
                ];
            }
        }

        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'Some files failed to upload.',
                'uploaded_files' => $uploadedFiles,
                'errors' => $errors
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Files uploaded successfully.',
            'uploaded_files' => $uploadedFiles
        ]);
    }

    /**
     * Download a file securely.
     */
    public function download(Request $request, string $path)
    {
        $user = Auth::user();
        
        // Check if user has permission to access this file
        if (!$this->fileSecurityService->hasFileAccess($path, $user->id, $user->role)) {
            return response()->json([
                'error' => [
                    'code' => 'ACCESS_DENIED',
                    'message' => 'You do not have permission to access this file.'
                ]
            ], 403);
        }

        // Check if file exists
        if (!Storage::exists($path)) {
            return response()->json([
                'error' => [
                    'code' => 'FILE_NOT_FOUND',
                    'message' => 'The requested file was not found.'
                ]
            ], 404);
        }

        // Return file download response
        return Storage::download($path);
    }

    /**
     * Get file information.
     */
    public function info(Request $request, string $path)
    {
        $user = Auth::user();
        
        // Check if user has permission to access this file
        if (!$this->fileSecurityService->hasFileAccess($path, $user->id, $user->role)) {
            return response()->json([
                'error' => [
                    'code' => 'ACCESS_DENIED',
                    'message' => 'You do not have permission to access this file.'
                ]
            ], 403);
        }

        // Check if file exists
        if (!Storage::exists($path)) {
            return response()->json([
                'error' => [
                    'code' => 'FILE_NOT_FOUND',
                    'message' => 'The requested file was not found.'
                ]
            ], 404);
        }

        $fileInfo = [
            'path' => $path,
            'size' => Storage::size($path),
            'last_modified' => Storage::lastModified($path),
            'mime_type' => Storage::mimeType($path),
            'url' => Storage::url($path)
        ];

        return response()->json([
            'success' => true,
            'file_info' => $fileInfo
        ]);
    }

    /**
     * Delete a file securely.
     */
    public function delete(Request $request, string $path)
    {
        $user = Auth::user();
        
        // Check if user has permission to delete this file
        if (!$this->fileSecurityService->hasFileAccess($path, $user->id, $user->role)) {
            return response()->json([
                'error' => [
                    'code' => 'ACCESS_DENIED',
                    'message' => 'You do not have permission to delete this file.'
                ]
            ], 403);
        }

        // Check if file exists
        if (!Storage::exists($path)) {
            return response()->json([
                'error' => [
                    'code' => 'FILE_NOT_FOUND',
                    'message' => 'The requested file was not found.'
                ]
            ], 404);
        }

        // Delete the file
        if (Storage::delete($path)) {
            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully.'
            ]);
        }

        return response()->json([
            'error' => [
                'code' => 'DELETE_FAILED',
                'message' => 'Failed to delete the file.'
            ]
        ], 500);
    }

    /**
     * List files for a user/property/mission.
     */
    public function list(Request $request)
    {
        $user = Auth::user();
        $propertyId = $request->input('property_id');
        $missionId = $request->input('mission_id');
        $category = $request->input('category');

        // Build path based on filters
        $basePath = 'secure';
        if ($category) {
            $basePath .= "/{$category}";
        }

        $files = [];
        $directories = Storage::directories($basePath);

        foreach ($directories as $directory) {
            // Check if user has access to this directory
            if ($this->fileSecurityService->hasFileAccess($directory, $user->id, $user->role)) {
                $directoryFiles = Storage::files($directory);
                
                foreach ($directoryFiles as $filePath) {
                    $files[] = [
                        'path' => $filePath,
                        'name' => basename($filePath),
                        'size' => Storage::size($filePath),
                        'last_modified' => Storage::lastModified($filePath),
                        'mime_type' => Storage::mimeType($filePath),
                        'url' => Storage::url($filePath)
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'files' => $files,
            'total' => count($files)
        ]);
    }

    /**
     * Get allowed file types for a category.
     */
    public function allowedTypes(string $category = 'default')
    {
        $allowedTypes = $this->fileSecurityService->getAllowedTypes($category);
        $categories = $this->fileSecurityService->getAllowedCategories();

        return response()->json([
            'success' => true,
            'category' => $category,
            'allowed_types' => $allowedTypes,
            'available_categories' => $categories
        ]);
    }
}