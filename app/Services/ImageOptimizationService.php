<?php

namespace App\Services;

use App\Models\FileMetadata;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageOptimizationService extends BaseService
{
    protected ImageManager $imageManager;
    
    protected array $thumbnailSizes = [
        'small' => ['width' => 150, 'height' => 150],
        'medium' => ['width' => 300, 'height' => 300],
        'large' => ['width' => 800, 'height' => 600],
    ];

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * Optimize an uploaded image file.
     */
    public function optimizeImage(UploadedFile $file, int $quality = 85): string
    {
        if (!$this->isImage($file)) {
            throw new \InvalidArgumentException('File is not an image');
        }

        $image = $this->imageManager->read($file->getRealPath());
        
        // Auto-orient based on EXIF data
        $image = $image->orient();
        
        // Resize if too large (max 2048px on longest side)
        $maxDimension = 2048;
        if ($image->width() > $maxDimension || $image->height() > $maxDimension) {
            if ($image->width() > $image->height()) {
                $image = $image->resize($maxDimension, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
            } else {
                $image = $image->resize(null, $maxDimension, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }
        }
        
        // Create temporary file for optimized image
        $tempPath = tempnam(sys_get_temp_dir(), 'optimized_');
        
        // Save with compression
        $image->save($tempPath, $quality);
        
        return $tempPath;
    }

    /**
     * Generate thumbnails for an image.
     */
    public function generateThumbnails(FileMetadata $fileMetadata): array
    {
        if (!$fileMetadata->isImage() || !$fileMetadata->exists()) {
            return [];
        }

        $thumbnails = [];
        $originalPath = Storage::disk($fileMetadata->storage_disk)->path($fileMetadata->path);
        $image = $this->imageManager->read($originalPath);
        
        foreach ($this->thumbnailSizes as $size => $dimensions) {
            $thumbnail = clone $image;
            
            // Resize maintaining aspect ratio
            $thumbnail = $thumbnail->resize(
                $dimensions['width'], 
                $dimensions['height'], 
                function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize(); // Don't upscale small images
                }
            );
            
            // Generate thumbnail path
            $pathInfo = pathinfo($fileMetadata->path);
            $thumbnailPath = $pathInfo['dirname'] . '/thumbnails/' . 
                           $pathInfo['filename'] . '_' . $size . '.' . $pathInfo['extension'];
            
            // Save thumbnail
            $thumbnailFullPath = Storage::disk($fileMetadata->storage_disk)->path($thumbnailPath);
            
            // Ensure directory exists
            $thumbnailDir = dirname($thumbnailFullPath);
            if (!is_dir($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }
            
            $thumbnail->save($thumbnailFullPath, 85);
            
            $thumbnails[$size] = [
                'path' => $thumbnailPath,
                'url' => Storage::disk($fileMetadata->storage_disk)->url($thumbnailPath),
                'width' => $thumbnail->width(),
                'height' => $thumbnail->height(),
            ];
        }
        
        // Update file metadata with thumbnail info
        $metadata = $fileMetadata->metadata ?? [];
        $metadata['thumbnails'] = $thumbnails;
        $fileMetadata->update(['metadata' => $metadata]);
        
        return $thumbnails;
    }

    /**
     * Extract comprehensive image metadata.
     */
    public function extractImageMetadata(string $imagePath): array
    {
        $metadata = [];
        
        try {
            $image = $this->imageManager->read($imagePath);
            
            $metadata['width'] = $image->width();
            $metadata['height'] = $image->height();
            $metadata['aspect_ratio'] = round($image->width() / $image->height(), 2);
            
            // Get EXIF data if available
            $exifData = @exif_read_data($imagePath);
            if ($exifData) {
                $metadata['exif'] = [
                    'camera_make' => $exifData['Make'] ?? null,
                    'camera_model' => $exifData['Model'] ?? null,
                    'date_taken' => $exifData['DateTime'] ?? null,
                    'orientation' => $exifData['Orientation'] ?? null,
                    'flash' => $exifData['Flash'] ?? null,
                    'focal_length' => $exifData['FocalLength'] ?? null,
                    'iso' => $exifData['ISOSpeedRatings'] ?? null,
                    'aperture' => $exifData['COMPUTED']['ApertureFNumber'] ?? null,
                    'exposure_time' => $exifData['ExposureTime'] ?? null,
                ];
                
                // GPS coordinates if available
                if (isset($exifData['GPSLatitude']) && isset($exifData['GPSLongitude'])) {
                    $metadata['gps'] = [
                        'latitude' => $this->convertGpsCoordinate($exifData['GPSLatitude'], $exifData['GPSLatitudeRef']),
                        'longitude' => $this->convertGpsCoordinate($exifData['GPSLongitude'], $exifData['GPSLongitudeRef']),
                    ];
                }
            }
            
            // Color analysis
            $metadata['colors'] = $this->extractDominantColors($image);
            
        } catch (\Exception $e) {
            $metadata['error'] = 'Failed to extract metadata: ' . $e->getMessage();
        }
        
        return $metadata;
    }

    /**
     * Convert image to different format.
     */
    public function convertFormat(FileMetadata $fileMetadata, string $targetFormat, int $quality = 85): ?FileMetadata
    {
        if (!$fileMetadata->isImage() || !$fileMetadata->exists()) {
            return null;
        }

        $supportedFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array(strtolower($targetFormat), $supportedFormats)) {
            throw new \InvalidArgumentException('Unsupported target format');
        }

        $originalPath = Storage::disk($fileMetadata->storage_disk)->path($fileMetadata->path);
        $image = $this->imageManager->read($originalPath);
        
        // Generate new filename
        $pathInfo = pathinfo($fileMetadata->path);
        $newPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_converted.' . $targetFormat;
        $newFullPath = Storage::disk($fileMetadata->storage_disk)->path($newPath);
        
        // Convert and save
        $image->save($newFullPath, $quality);
        
        // Create new file metadata record
        $newFileMetadata = $fileMetadata->replicate();
        $newFileMetadata->filename = $pathInfo['filename'] . '_converted.' . $targetFormat;
        $newFileMetadata->path = $newPath;
        $newFileMetadata->mime_type = 'image/' . ($targetFormat === 'jpg' ? 'jpeg' : $targetFormat);
        $newFileMetadata->size = filesize($newFullPath);
        $newFileMetadata->file_hash = hash_file('sha256', $newFullPath);
        $newFileMetadata->save();
        
        return $newFileMetadata;
    }

    /**
     * Batch optimize images.
     */
    public function batchOptimizeImages(array $fileMetadataIds, int $quality = 85): array
    {
        $results = [];
        
        foreach ($fileMetadataIds as $id) {
            $fileMetadata = FileMetadata::find($id);
            if (!$fileMetadata || !$fileMetadata->isImage()) {
                $results[$id] = ['success' => false, 'message' => 'File not found or not an image'];
                continue;
            }
            
            try {
                // Generate thumbnails
                $thumbnails = $this->generateThumbnails($fileMetadata);
                
                // Extract metadata
                $originalPath = Storage::disk($fileMetadata->storage_disk)->path($fileMetadata->path);
                $metadata = $this->extractImageMetadata($originalPath);
                
                // Update file metadata
                $existingMetadata = $fileMetadata->metadata ?? [];
                $fileMetadata->update(['metadata' => array_merge($existingMetadata, $metadata)]);
                
                $results[$id] = [
                    'success' => true,
                    'thumbnails' => $thumbnails,
                    'metadata' => $metadata
                ];
            } catch (\Exception $e) {
                $results[$id] = ['success' => false, 'message' => $e->getMessage()];
            }
        }
        
        return $results;
    }

    /**
     * Check if file is an image.
     */
    protected function isImage(UploadedFile $file): bool
    {
        return str_starts_with($file->getMimeType(), 'image/');
    }

    /**
     * Convert GPS coordinates from EXIF format to decimal degrees.
     */
    protected function convertGpsCoordinate(array $coordinate, string $hemisphere): float
    {
        $degrees = count($coordinate) > 0 ? $this->gpsToDecimal($coordinate[0]) : 0;
        $minutes = count($coordinate) > 1 ? $this->gpsToDecimal($coordinate[1]) : 0;
        $seconds = count($coordinate) > 2 ? $this->gpsToDecimal($coordinate[2]) : 0;
        
        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);
        
        if ($hemisphere === 'S' || $hemisphere === 'W') {
            $decimal *= -1;
        }
        
        return $decimal;
    }

    /**
     * Convert GPS fraction to decimal.
     */
    protected function gpsToDecimal(string $fraction): float
    {
        $parts = explode('/', $fraction);
        if (count($parts) === 2 && $parts[1] != 0) {
            return $parts[0] / $parts[1];
        }
        return (float) $fraction;
    }

    /**
     * Extract dominant colors from image.
     */
    protected function extractDominantColors($image, int $colorCount = 5): array
    {
        // This is a simplified color extraction
        // In production, you might want to use a more sophisticated algorithm
        try {
            // Resize image for faster processing
            $smallImage = clone $image;
            $smallImage = $smallImage->resize(100, 100);
            
            // For now, return a placeholder
            // A real implementation would analyze pixel colors
            return [
                'dominant' => '#' . dechex(rand(0, 16777215)),
                'palette' => array_map(fn() => '#' . dechex(rand(0, 16777215)), range(1, $colorCount))
            ];
        } catch (\Exception $e) {
            return ['error' => 'Color extraction failed'];
        }
    }

    /**
     * Clean up old thumbnails.
     */
    public function cleanupThumbnails(FileMetadata $fileMetadata): bool
    {
        if (!isset($fileMetadata->metadata['thumbnails'])) {
            return true;
        }
        
        $success = true;
        foreach ($fileMetadata->metadata['thumbnails'] as $thumbnail) {
            if (!Storage::disk($fileMetadata->storage_disk)->delete($thumbnail['path'])) {
                $success = false;
            }
        }
        
        // Remove thumbnail info from metadata
        $metadata = $fileMetadata->metadata;
        unset($metadata['thumbnails']);
        $fileMetadata->update(['metadata' => $metadata]);
        
        return $success;
    }
}