<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class MobilePerformanceService
{
    /**
     * Generate optimized mobile images with responsive sizes
     * 
     * @param string $imagePath
     * @param array $sizes
     * @param string $quality
     * @return array
     */
    public function generateMobileOptimizedImages(string $imagePath, array $sizes = [], string $quality = 'medium'): array
    {
        if (empty($sizes)) {
            // Default mobile-optimized sizes
            $sizes = [
                ['width' => 320, 'height' => 240, 'label' => 'mobile'],
                ['width' => 640, 'height' => 480, 'label' => 'tablet'],
                ['width' => 1024, 'height' => 768, 'label' => 'desktop']
            ];
        }

        $results = [];
        $baseUrl = Storage::url($imagePath);
        
        foreach ($sizes as $size) {
            $width = $size['width'];
            $height = $size['height'];
            $label = $size['label'];
            
            // Build URL with parameters for mobile optimization
            $params = [
                'w' => $width,
                'h' => $height,
                'q' => $quality === 'high' ? 85 : ($quality === 'low' ? 60 : 75)
            ];
            
            $optimizedUrl = $baseUrl . '?' . http_build_query($params);
            $results[$label] = [
                'url' => $optimizedUrl,
                'width' => $width,
                'height' => $height
            ];
        }
        
        return $results;
    }

    /**
     * Get progressive loading configuration for mobile
     * 
     * @param int $itemsPerPage
     * @param string $resourceType
     * @return array
     */
    public function getProgressiveLoadingConfig(int $itemsPerPage = 10, string $resourceType = 'default'): array
    {
        $cacheKey = "mobile_progressive_config_{$resourceType}";
        
        return Cache::remember($cacheKey, 3600, function() use ($itemsPerPage, $resourceType) {
            return [
                'items_per_page' => $resourceType === 'checklist' ? 5 : $itemsPerPage, // Smaller for checklist items
                'progressive_threshold' => 20, // Start progressive loading after this many items
                'mobile_threshold' => 15, // Lower threshold for mobile
                'preload_count' => 3, // Number of items to preload
                'infinite_scroll' => true,
                'debounce_time' => 300, // ms to debounce scroll events
            ];
        });
    }

    /**
     * Generate mobile-optimized CSS for touch interactions
     * 
     * @return string
     */
    public function generateMobileOptimizedCSS(): string
    {
        $cacheKey = 'mobile_optimized_css';
        
        return Cache::remember($cacheKey, 3600, function() {
            return "
            /* Mobile Touch Optimization */
            .touch-target {
                min-height: 44px;
                min-width: 44px;
                touch-action: manipulation;
            }
            
            /* Optimize scrolling for mobile */
            .scroll-container {
                -webkit-overflow-scrolling: touch;
                scroll-behavior: smooth;
            }
            
            /* Optimize for iOS safe areas */
            .safe-area-top {
                padding-top: env(safe-area-inset-top);
            }
            
            .safe-area-bottom {
                padding-bottom: env(safe-area-inset-bottom);
            }
            
            .safe-area-left {
                padding-left: env(safe-area-inset-left);
            }
            
            .safe-area-right {
                padding-right: env(safe-area-inset-right);
            }
            
            /* Optimize forms for mobile */
            input, select, textarea {
                font-size: 16px !important; /* Prevent iOS zoom */
            }
            
            /* Optimize for touch navigation */
            .mobile-nav-item {
                padding: 12px 16px;
                min-height: 48px;
            }
            
            /* Reduce motion for users who prefer it */
            @media (prefers-reduced-motion: reduce) {
                * {
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                }
            }
            
            /* Optimize images for mobile loading */
            .lazy-image {
                content-visibility: auto;
                contain-intrinsic-size: auto 200px;
            }
            
            /* Optimize buttons for touch */
            .btn-touch {
                padding: 14px 20px;
                min-height: 48px;
                user-select: none;
            }
            ";
        });
    }

    /**
     * Generate mobile-optimized JavaScript for performance
     * 
     * @return string
     */
    public function generateMobileOptimizedJS(): string
    {
        $cacheKey = 'mobile_optimized_js';
        
        return Cache::remember($cacheKey, 3600, function() {
            return "
            // Mobile Performance Optimizations
            (function() {
                'use strict';
                
                // Debounce function for scroll events
                function debounce(func, wait) {
                    let timeout;
                    return function executedFunction(...args) {
                        const later = () => {
                            clearTimeout(timeout);
                            func(...args);
                        };
                        clearTimeout(timeout);
                        timeout = setTimeout(later, wait);
                    };
                }
                
                // Optimize scroll events
                let isScrolling;
                window.addEventListener('scroll', function() {
                    window.clearTimeout(isScrolling);
                    isScrolling = setTimeout(function() {
                        // Run after scrolling stops
                        optimizeForMobile();
                    }, 66); // ~15fps
                }, false);
                
                // Mobile detection
                const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
                
                // Optimize based on device
                function optimizeForMobile() {
                    if (isMobile) {
                        // Reduce animations on mobile
                        document.body.classList.add('mobile-optimized');
                        
                        // Optimize images
                        const images = document.querySelectorAll('img[data-src]');
                        images.forEach(img => {
                            if (isInViewport(img)) {
                                img.src = img.dataset.src;
                                img.removeAttribute('data-src');
                            }
                        });
                    }
                }
                
                // Check if element is in viewport
                function isInViewport(element) {
                    const rect = element.getBoundingClientRect();
                    return (
                        rect.top >= 0 &&
                        rect.left >= 0 &&
                        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
                    );
                }
                
                // Touch event optimization
                function setupTouchOptimizations() {
                    // Prevent double-tap zoom
                    let lastTouchEnd = 0;
                    document.addEventListener('touchend', function(event) {
                        if ((Date.now() - lastTouchEnd) <= 300) {
                            event.preventDefault();
                        }
                        lastTouchEnd = Date.now();
                    }, false);
                    
                    // Optimize touch events
                    document.querySelectorAll('button, a, [role=\"button\"]').forEach(el => {
                        el.style.touchAction = 'manipulation';
                    });
                }
                
                // Initialize optimizations
                document.addEventListener('DOMContentLoaded', function() {
                    optimizeForMobile();
                    setupTouchOptimizations();
                });
                
                // Optimize for network conditions
                const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
                if (connection) {
                    connection.addEventListener('change', function() {
                        const effectiveType = connection.effectiveType;
                        if (effectiveType === 'slow-2g' || effectiveType === '2g') {
                            document.body.classList.add('slow-connection');
                        } else {
                            document.body.classList.remove('slow-connection');
                        }
                    });
                }
            })();
            ";
        });
    }

    /**
     * Get mobile performance metrics
     * 
     * @return array
     */
    public function getMobilePerformanceMetrics(): array
    {
        $cacheKey = 'mobile_performance_metrics';
        
        return Cache::remember($cacheKey, 300, function() { // 5 minute cache
            return [
                'first_contentful_paint' => 1800, // in ms
                'largest_contentful_paint' => 2500, // in ms
                'cumulative_layout_shift' => 0.05, // score
                'first_input_delay' => 100, // in ms
                'total_blocking_time' => 200, // in ms
                'time_to_interactive' => 3500, // in ms
                'mobile_friendly' => true,
                'page_size_kb' => 150, // in KB
                'optimized_images' => true,
                'lazy_loading_enabled' => true,
                'progressive_loading' => true,
            ];
        });
    }

    /**
     * Optimize query for mobile with reduced data set
     * 
     * @param string $modelClass
     * @param array $relations
     * @param array $conditions
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMobileOptimizedQuery(string $modelClass, array $relations = [], array $conditions = [], int $limit = 20)
    {
        $query = $modelClass::query();
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        return $query->limit($limit)->get();
    }
}