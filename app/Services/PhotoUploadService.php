<?php

namespace App\Services;

use App\Models\ChecklistPhoto;
use App\Models\ChecklistItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;
use Exception;

class PhotoUploadService
{
    /**
     * Upload and process a photo for a checklist item
     */
    public function uploadChecklistPhoto(
        ChecklistItem $item,
        UploadedFile $file,
        ?int $uploadedBy = null,
        array $options = []
    ): ChecklistPhoto {
        $uploadedBy = $uploadedBy ?? auth()->id();
        
        // Validate file
        $this->validatePhotoFile($file);
        
        // Generate unique filename
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $filename = 'checklist_' . $item->checklist_id . '_' . $item->id . '_' . time() . '_' . uniqid() . '.' . $extension;
        
        // Create directory structure
        $basePath = "checklists/{$item->checklist_id}/photos";
        $fullPath = "{$basePath}/{$filename}";
        
        // Store original file
        $file->storeAs($basePath, $filename, 'public');
        
        // Generate thumbnails
        $thumbnails = $this->generateThumbnails($file, $basePath, $filename);
        
        // Create photo record
        $photo = ChecklistPhoto::create([
            'checklist_item_id' => $item->id,
            'photo_path' => $fullPath,
            'filename' => $filename,
            'original_name' => $originalName,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'uploaded_by' => $uploadedBy,
            'metadata' => [
                'thumbnails' => $thumbnails,
                'dimensions' => $this->getImageDimensions($file),
                'upload_options' => $options
            ]
        ]);
        
        // Log photo upload
        Log::info('Checklist photo uploaded', [
            'photo_id' => $photo->id,
            'checklist_item_id' => $item->id,
            'filename' => $filename,
            'file_size' => $file->getSize(),
            'uploaded_by' => $uploadedBy
        ]);
        
        return $photo;
    }
    
    /**
     * Upload multiple photos for a checklist item
     */
    public function uploadMultiplePhotos(
        ChecklistItem $item,
        array $files,
        ?int $uploadedBy = null,
        array $options = []
    ): array {
        $uploadedPhotos = [];
        
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $uploadedPhotos[] = $this->uploadChecklistPhoto($item, $file, $uploadedBy, $options);
            }
        }
        
        return $uploadedPhotos;
    }
    
    /**
     * Process base64 image data
     */
    public function processBase64Image(
        ChecklistItem $item,
        string $base64Data,
        ?int $uploadedBy = null,
        array $options = []
    ): ChecklistPhoto {
        $uploadedBy = $uploadedBy ?? auth()->id();
        
        // Decode base64 data
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Data));
        
        if (!$imageData) {
            throw new Exception('Invalid base64 image data');
        }
        
        // Generate filename
        $filename = 'checklist_' . $item->checklist_id . '_' . $item->id . '_' . time() . '_' . uniqid() . '.jpg';
        $basePath = "checklists/{$item->checklist_id}/photos";
        $fullPath = "{$basePath}/{$filename}";
        
        // Store image
        Storage::disk('public')->put($fullPath, $imageData);
        
        // Generate thumbnails
        $thumbnails = $this->generateThumbnailsFromData($imageData, $basePath, $filename);
        
        // Create photo record
        $photo = ChecklistPhoto::create([
            'checklist_item_id' => $item->id,
            'photo_path' => $fullPath,
            'filename' => $filename,
            'original_name' => 'signature_' . time() . '.jpg',
            'file_size' => strlen($imageData),
            'mime_type' => 'image/jpeg',
            'uploaded_by' => $uploadedBy,
            'metadata' => [
                'thumbnails' => $thumbnails,
                'source' => 'base64',
                'upload_options' => $options
            ]
        ]);
        
        return $photo;
    }
    
    /**
     * Generate thumbnails for an image
     */
    protected function generateThumbnails(UploadedFile $file, string $basePath, string $filename): array
    {
        $thumbnails = [];
        
        try {
            $image = Image::make($file);
            
            // Small thumbnail (150x150)
            $smallThumb = $this->createThumbnail($image, 150, 150, $basePath, $filename, 'small');
            if ($smallThumb) {
                $thumbnails['small'] = $smallThumb;
            }
            
            // Medium thumbnail (300x300)
            $mediumThumb = $this->createThumbnail($image, 300, 300, $basePath, $filename, 'medium');
            if ($mediumThumb) {
                $thumbnails['medium'] = $mediumThumb;
            }
            
            // Large thumbnail (600x600)
            $largeThumb = $this->createThumbnail($image, 600, 600, $basePath, $filename, 'large');
            if ($largeThumb) {
                $thumbnails['large'] = $largeThumb;
            }
            
        } catch (Exception $e) {
            Log::warning('Failed to generate thumbnails', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
        }
        
        return $thumbnails;
    }
    
    /**
     * Generate thumbnails from base64 data
     */
    protected function generateThumbnailsFromData(string $imageData, string $basePath, string $filename): array
    {
        $thumbnails = [];
        
        try {
            $image = Image::make($imageData);
            
            // Small thumbnail (150x150)
            $smallThumb = $this->createThumbnail($image, 150, 150, $basePath, $filename, 'small');
            if ($smallThumb) {
                $thumbnails['small'] = $smallThumb;
            }
            
            // Medium thumbnail (300x300)
            $mediumThumb = $this->createThumbnail($image, 300, 300, $basePath, $filename, 'medium');
            if ($mediumThumb) {
                $thumbnails['medium'] = $mediumThumb;
            }
            
        } catch (Exception $e) {
            Log::warning('Failed to generate thumbnails from base64', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
        }
        
        return $thumbnails;
    }
    
    /**
     * Create a thumbnail with specified dimensions
     */
    protected function createThumbnail($image, int $width, int $height, string $basePath, string $filename, string $size): ?string
    {
        try {
            $thumbnailFilename = $this->getThumbnailFilename($filename, $size);
            $thumbnailPath = "{$basePath}/thumbnails/{$thumbnailFilename}";
            
            $thumbnail = $image->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            
            Storage::disk('public')->put($thumbnailPath, $thumbnail->encode('jpg', 85));
            
            return $thumbnailPath;
            
        } catch (Exception $e) {
            Log::warning("Failed to create {$size} thumbnail", [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Get thumbnail filename
     */
    protected function getThumbnailFilename(string $filename, string $size): string
    {
        $pathInfo = pathinfo($filename);
        return $pathInfo['filename'] . "_{$size}." . $pathInfo['extension'];
    }
    
    /**
     * Get image dimensions
     */
    protected function getImageDimensions(UploadedFile $file): array
    {
        try {
            $image = Image::make($file);
            return [
                'width' => $image->width(),
                'height' => $image->height()
            ];
        } catch (Exception $e) {
            return ['width' => 0, 'height' => 0];
        }
    }
    
    /**
     * Validate photo file
     */
    protected function validatePhotoFile(UploadedFile $file): void
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new Exception('Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.');
        }
        
        if ($file->getSize() > $maxSize) {
            throw new Exception('File size too large. Maximum size is 10MB.');
        }
    }
    
    /**
     * Delete a photo and its thumbnails
     */
    public function deletePhoto(ChecklistPhoto $photo): bool
    {
        try {
            // Delete original file
            if (Storage::disk('public')->exists($photo->photo_path)) {
                Storage::disk('public')->delete($photo->photo_path);
            }
            
            // Delete thumbnails
            if (isset($photo->metadata['thumbnails'])) {
                foreach ($photo->metadata['thumbnails'] as $thumbnail) {
                    if (Storage::disk('public')->exists($thumbnail)) {
                        Storage::disk('public')->delete($thumbnail);
                    }
                }
            }
            
            // Delete photo record
            $photo->delete();
            
            Log::info('Checklist photo deleted', [
                'photo_id' => $photo->id,
                'filename' => $photo->filename
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to delete checklist photo', [
                'photo_id' => $photo->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get photo URL with fallback to thumbnail
     */
    public function getPhotoUrl(ChecklistPhoto $photo, string $size = 'original'): string
    {
        if ($size === 'original') {
            return Storage::disk('public')->url($photo->photo_path);
        }
        
        if (isset($photo->metadata['thumbnails'][$size])) {
            return Storage::disk('public')->url($photo->metadata['thumbnails'][$size]);
        }
        
        // Fallback to original
        return Storage::disk('public')->url($photo->photo_path);
    }
    
    /**
     * Optimize image for web display
     */
    public function optimizeImage(string $path, int $maxWidth = 1200, int $quality = 85): string
    {
        try {
            $image = Image::make(Storage::disk('public')->path($path));
            
            if ($image->width() > $maxWidth) {
                $image->resize($maxWidth, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                
                $optimizedPath = str_replace('.', '_optimized.', $path);
                Storage::disk('public')->put($optimizedPath, $image->encode('jpg', $quality));
                
                return $optimizedPath;
            }
            
            return $path;
            
        } catch (Exception $e) {
            Log::warning('Failed to optimize image', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return $path;
        }
    }
}
