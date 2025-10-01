<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Log;

class ImageOptimizationService
{
    /**
     * Optimize an image file
     *
     * @param string $filePath Path to the original image
     * @param string|null $outputPath Path for the optimized image (optional)
     * @param int $quality Quality percentage (1-100)
     * @param int|null $maxWidth Maximum width in pixels
     * @param int|null $maxHeight Maximum height in pixels
     * @return string Path to the optimized image
     */
    public function optimizeImage(string $filePath, ?string $outputPath = null, int $quality = 80, ?int $maxWidth = null, ?int $maxHeight = null): string
    {
        try {
            // If no output path is provided, create one based on the input
            if (!$outputPath) {
                $pathInfo = pathinfo($filePath);
                $outputPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_optimized.' . $pathInfo['extension'];
            }

            // Load the image using Intervention Image
            $image = Image::make($filePath);
            
            // Resize if dimensions are specified
            if ($maxWidth || $maxHeight) {
                $image->resize($maxWidth, $maxHeight, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
            
            // Save with specified quality
            $image->save($outputPath, $quality);
            
            // Clean up memory
            $image->destroy();
            
            return $outputPath;
        } catch (\Exception $e) {
            Log::error('Image optimization failed: ' . $e->getMessage());
            return $filePath; // Return original if optimization fails
        }
    }

    /**
     * Generate multiple image sizes for responsive loading
     *
     * @param string $originalPath
     * @param array $sizes Array of [width, height] pairs
     * @param string $basePath Base path for output files
     * @param int $quality
     * @return array Array of generated file paths
     */
    public function generateResponsiveImages(string $originalPath, array $sizes, string $basePath, int $quality = 80): array
    {
        $generatedPaths = [];
        
        foreach ($sizes as $index => $size) {
            $width = $size[0];
            $height = $size[1] ?? null;
            
            $sizeName = $width . ($height ? 'x' . $height : '');
            $outputPath = $basePath . '/size_' . $sizeName . '_' . basename($originalPath);
            
            $optimizedPath = $this->optimizeImage(
                $originalPath,
                $outputPath,
                $quality,
                $width,
                $height
            );
            
            $generatedPaths[$sizeName] = $optimizedPath;
        }
        
        return $generatedPaths;
    }

    /**
     * Optimize and store an uploaded image
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $directory
     * @param string|null $fileName
     * @param int $quality
     * @param int|null $maxWidth
     * @param int|null $maxHeight
     * @return string Path to the stored optimized image
     */
    public function storeOptimizedImage($file, string $directory, ?string $fileName = null, int $quality = 80, ?int $maxWidth = 1920, ?int $maxHeight = 1080): string
    {
        if (!$fileName) {
            $fileName = time() . '_' . $file->getClientOriginalName();
        }
        
        // Store the original file temporarily
        $tempPath = $file->storeAs('temp', $fileName, 'local');
        $tempFullPath = Storage::disk('local')->path($tempPath);
        
        // Optimize the image
        $optimizedTempPath = $this->optimizeImage($tempFullPath, null, $quality, $maxWidth, $maxHeight);
        
        // Move to the final destination
        $finalPath = $directory . '/' . $fileName;
        $finalFullPath = Storage::disk('public')->putFileAs($directory, new \Illuminate\Http\UploadedFile($optimizedTempPath, $fileName), $fileName);
        
        // Clean up temporary files
        unlink($tempFullPath);
        if ($optimizedTempPath !== $tempFullPath) {
            unlink($optimizedTempPath);
        }
        
        return $finalPath;
    }

    /**
     * Get optimized image URL with query parameters for different sizes
     *
     * @param string $imagePath
     * @param int|null $width
     * @param int|null $height
     * @param int $quality
     * @return string
     */
    public function getOptimizedImageUrl(string $imagePath, ?int $width = null, ?int $height = null, int $quality = 80): string
    {
        $url = Storage::url($imagePath);
        
        $params = [];
        if ($width) $params['w'] = $width;
        if ($height) $params['h'] = $height;
        if ($quality != 80) $params['q'] = $quality; // Only add if not default
        
        if (!empty($params)) {
            $separator = strpos($url, '?') !== false ? '&' : '?';
            $url .= $separator . http_build_query($params);
        }
        
        return $url;
    }

    /**
     * Generate WebP version of an image for better compression
     *
     * @param string $imagePath
     * @param int $quality
     * @return string|null Path to WebP version, or null if not supported
     */
    public function generateWebPVersion(string $imagePath, int $quality = 80): ?string
    {
        try {
            $pathInfo = pathinfo($imagePath);
            $webPPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
            
            // Load and convert to WebP
            $image = Image::make(Storage::disk('public')->path($imagePath));
            $webPPathFull = Storage::disk('public')->path($webPPath);
            $image->encode('webp', $quality)->save($webPPathFull);
            $image->destroy();
            
            return $webPPath;
        } catch (\Exception $e) {
            Log::error('WebP conversion failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create image with multiple formats (original + WebP) for modern browsers
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $directory
     * @param string|null $fileName
     * @param int $quality
     * @param int|null $maxWidth
     * @param int|null $maxHeight
     * @return array Array with paths for different formats
     */
    public function storeMultipleFormats($file, string $directory, ?string $fileName = null, int $quality = 80, ?int $maxWidth = 1920, ?int $maxHeight = 1080): array
    {
        $result = [];
        
        // Store original format
        $originalPath = $this->storeOptimizedImage($file, $directory, $fileName, $quality, $maxWidth, $maxHeight);
        $result['original'] = $originalPath;
        
        // Generate WebP version
        $webPPath = $this->generateWebPVersion($originalPath, $quality);
        if ($webPPath) {
            $result['webp'] = $webPPath;
        }
        
        return $result;
    }
}