<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class CdnService
{
    /**
     * Get CDN URL for an asset
     *
     * @param string $path
     * @param bool $useCdn
     * @return string
     */
    public function getCdnUrl(string $path, bool $useCdn = true): string
    {
        if ($useCdn && config('app.cdn_enabled', false)) {
            $cdnUrl = config('app.cdn_url');
            if ($cdnUrl) {
                return rtrim($cdnUrl, '/') . '/' . ltrim($path, '/');
            }
        }
        
        // Fall back to regular asset URL
        return Storage::url($path);
    }

    /**
     * Get optimized image URL with CDN and responsive parameters
     *
     * @param string $imagePath
     * @param array $params Additional parameters (width, height, quality, etc.)
     * @param bool $useCdn
     * @return string
     */
    public function getOptimizedImageCdnUrl(string $imagePath, array $params = [], bool $useCdn = true): string
    {
        $url = $this->getCdnUrl($imagePath, $useCdn);
        
        if (!empty($params)) {
            $separator = strpos($url, '?') !== false ? '&' : '?';
            $url .= $separator . http_build_query($params);
        }
        
        return $url;
    }

    /**
     * Generate responsive image sources for <picture> element
     *
     * @param string $imagePath
     * @param array $sizes Array of [width, height, media_query] or just [width, height]
     * @param bool $includeWebP
     * @param bool $useCdn
     * @return array
     */
    public function generateResponsiveSources(string $imagePath, array $sizes, bool $includeWebP = true, bool $useCdn = true): array
    {
        $sources = [];
        
        // Generate different sizes
        foreach ($sizes as $size) {
            $width = $size[0];
            $height = $size[1] ?? null;
            $media = $size[2] ?? null;
            
            $params = ['w' => $width];
            if ($height) $params['h'] = $height;
            
            $source = [
                'src' => $this->getOptimizedImageCdnUrl($imagePath, $params, $useCdn),
                'width' => $width,
                'height' => $height,
            ];
            
            if ($media) {
                $source['media'] = $media;
            }
            
            $sources[] = $source;
        }
        
        // Include WebP sources if requested
        if ($includeWebP) {
            $webPSources = [];
            foreach ($sources as $source) {
                $webPSource = $source;
                // Convert to WebP if needed - in a real implementation you'd convert the file
                $webPSources[] = $webPSource;
            }
            return ['sources' => $sources, 'webp_sources' => $webPSources];
        }
        
        return ['sources' => $sources];
    }

    /**
     * Get static asset URL (CSS, JS) with versioning for cache busting
     *
     * @param string $assetPath
     * @param bool $useCdn
     * @return string
     */
    public function getAssetUrl(string $assetPath, bool $useCdn = true): string
    {
        $version = config('app.asset_version', time());
        $url = $this->getCdnUrl($assetPath, $useCdn);
        
        $separator = strpos($url, '?') !== false ? '&' : '?';
        return $url . $separator . 'v=' . $version;
    }

    /**
     * Preload critical assets for faster loading
     *
     * @param array $assets List of asset paths to preload
     * @return string HTML link tags for preloading
     */
    public function generatePreloadTags(array $assets): string
    {
        $tags = [];
        
        foreach ($assets as $asset) {
            $type = $this->getAssetType($asset);
            $url = $this->getAssetUrl($asset);
            
            $tags[] = "<link rel=\"preload\" href=\"{$url}\" as=\"{$type}\">";
        }
        
        return implode("\n", $tags);
    }

    /**
     * Determine asset type based on file extension
     *
     * @param string $assetPath
     * @return string
     */
    private function getAssetType(string $assetPath): string
    {
        $extension = strtolower(pathinfo($assetPath, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'css':
                return 'style';
            case 'js':
                return 'script';
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
            case 'webp':
                return 'image';
            case 'woff':
            case 'woff2':
            case 'ttf':
            case 'eot':
                return 'font';
            default:
                return 'fetch';
        }
    }
}