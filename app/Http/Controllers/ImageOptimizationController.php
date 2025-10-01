<?php

namespace App\Http\Controllers;

use App\Models\FileMetadata;
use App\Services\ImageOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ImageOptimizationController extends Controller
{
    public function __construct(
        private ImageOptimizationService $imageOptimizationService
    ) {}

    /**
     * Generate thumbnails for an image.
     */
    public function generateThumbnails(FileMetadata $fileMetadata): JsonResponse
    {
        if (!$fileMetadata->isImage()) {
            return response()->json([
                'success' => false,
                'message' => 'File is not an image'
            ], 400);
        }

        try {
            $thumbnails = $this->imageOptimizationService->generateThumbnails($fileMetadata);

            return response()->json([
                'success' => true,
                'message' => 'Thumbnails generated successfully',
                'data' => $thumbnails
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Thumbnail generation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extract image metadata.
     */
    public function extractMetadata(FileMetadata $fileMetadata): JsonResponse
    {
        if (!$fileMetadata->isImage()) {
            return response()->json([
                'success' => false,
                'message' => 'File is not an image'
            ], 400);
        }

        try {
            $imagePath = storage_path('app/' . $fileMetadata->path);
            $metadata = $this->imageOptimizationService->extractImageMetadata($imagePath);

            // Update file metadata
            $existingMetadata = $fileMetadata->metadata ?? [];
            $fileMetadata->update(['metadata' => array_merge($existingMetadata, $metadata)]);

            return response()->json([
                'success' => true,
                'message' => 'Metadata extracted successfully',
                'data' => $metadata
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Metadata extraction failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convert image to different format.
     */
    public function convertFormat(FileMetadata $fileMetadata, Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|string|in:jpg,jpeg,png,gif,webp',
            'quality' => 'integer|min:1|max:100'
        ]);

        if (!$fileMetadata->isImage()) {
            return response()->json([
                'success' => false,
                'message' => 'File is not an image'
            ], 400);
        }

        try {
            $newFileMetadata = $this->imageOptimizationService->convertFormat(
                $fileMetadata,
                $request->input('format'),
                $request->input('quality', 85)
            );

            if (!$newFileMetadata) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format conversion failed'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Image converted successfully',
                'data' => [
                    'id' => $newFileMetadata->id,
                    'filename' => $newFileMetadata->filename,
                    'mime_type' => $newFileMetadata->mime_type,
                    'size' => $newFileMetadata->human_size,
                    'url' => $newFileMetadata->url,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Format conversion failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch optimize multiple images.
     */
    public function batchOptimize(Request $request): JsonResponse
    {
        $request->validate([
            'file_ids' => 'required|array',
            'file_ids.*' => 'integer|exists:file_metadata,id',
            'quality' => 'integer|min:1|max:100'
        ]);

        try {
            $results = $this->imageOptimizationService->batchOptimizeImages(
                $request->input('file_ids'),
                $request->input('quality', 85)
            );

            $successCount = count(array_filter($results, fn($r) => $r['success']));
            $totalCount = count($results);

            return response()->json([
                'success' => true,
                'message' => "Optimized {$successCount} of {$totalCount} images",
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Batch optimization failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get image thumbnail.
     */
    public function getThumbnail(FileMetadata $fileMetadata, string $size = 'medium')
    {
        if (!$fileMetadata->isImage()) {
            abort(404, 'File is not an image');
        }

        $thumbnails = $fileMetadata->metadata['thumbnails'] ?? [];
        
        if (!isset($thumbnails[$size])) {
            // Generate thumbnails if they don't exist
            $this->imageOptimizationService->generateThumbnails($fileMetadata);
            $fileMetadata->refresh();
            $thumbnails = $fileMetadata->metadata['thumbnails'] ?? [];
        }

        if (!isset($thumbnails[$size])) {
            abort(404, 'Thumbnail not found');
        }

        $thumbnailPath = storage_path('app/' . $thumbnails[$size]['path']);
        
        if (!file_exists($thumbnailPath)) {
            abort(404, 'Thumbnail file not found');
        }

        return response()->file($thumbnailPath);
    }

    /**
     * Clean up thumbnails for an image.
     */
    public function cleanupThumbnails(FileMetadata $fileMetadata): JsonResponse
    {
        try {
            $success = $this->imageOptimizationService->cleanupThumbnails($fileMetadata);

            return response()->json([
                'success' => $success,
                'message' => $success ? 'Thumbnails cleaned up successfully' : 'Some thumbnails could not be deleted'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Thumbnail cleanup failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
