<?php

namespace App\Http\Middleware;

use App\Services\FileSecurityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecureFileUpload
{
    public function __construct(
        private FileSecurityService $fileSecurityService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $category = 'default'): Response
    {
        // Only process requests with file uploads
        if (!$request->hasFile('file') && !$request->hasFile('files')) {
            return $next($request);
        }

        $files = [];
        
        // Handle single file upload
        if ($request->hasFile('file')) {
            $files[] = $request->file('file');
        }
        
        // Handle multiple file uploads
        if ($request->hasFile('files')) {
            $uploadedFiles = $request->file('files');
            if (is_array($uploadedFiles)) {
                $files = array_merge($files, $uploadedFiles);
            } else {
                $files[] = $uploadedFiles;
            }
        }

        $errors = [];
        $validFiles = [];

        foreach ($files as $index => $file) {
            if (!$file) {
                continue;
            }

            $validation = $this->fileSecurityService->validateFile($file, $category);
            
            if (!$validation['valid']) {
                $errors["file_{$index}"] = $validation['errors'];
            } else {
                $validFiles[] = $file;
            }
        }

        // If there are validation errors, return them
        if (!empty($errors)) {
            return response()->json([
                'error' => [
                    'code' => 'FILE_VALIDATION_FAILED',
                    'message' => 'One or more files failed security validation.',
                    'details' => $errors
                ]
            ], 422);
        }

        // Add validated files info to request for use in controllers
        $request->attributes->set('validated_files', $validFiles);
        $request->attributes->set('file_category', $category);

        return $next($request);
    }
}