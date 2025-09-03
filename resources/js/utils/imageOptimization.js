/**
 * Image optimization and lazy loading utilities
 */

/**
 * Create optimized image URLs with different sizes
 * @param {string} originalUrl - Original image URL
 * @param {Object} options - Optimization options
 * @returns {Object} Object with different sized URLs
 */
export function createOptimizedImageUrls(originalUrl, options = {}) {
    const { 
        sizes = [150, 300, 600, 1200],
        format = 'webp',
        quality = 80
    } = options;
    
    const urls = {};
    
    sizes.forEach(size => {
        urls[`${size}w`] = `${originalUrl}?w=${size}&f=${format}&q=${quality}`;
    });
    
    urls.original = originalUrl;
    
    return urls;
}

/**
 * Generate responsive image srcset
 * @param {string} originalUrl - Original image URL
 * @param {Array} sizes - Array of sizes
 * @returns {string} Srcset string
 */
export function generateSrcSet(originalUrl, sizes = [300, 600, 1200]) {
    return sizes
        .map(size => `${originalUrl}?w=${size}&f=webp&q=80 ${size}w`)
        .join(', ');
}

/**
 * Compress image file before upload
 * @param {File} file - Image file
 * @param {Object} options - Compression options
 * @returns {Promise<File>} Compressed file
 */
export function compressImage(file, options = {}) {
    const {
        maxWidth = 1920,
        maxHeight = 1080,
        quality = 0.8,
        format = 'image/jpeg'
    } = options;
    
    return new Promise((resolve, reject) => {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const img = new Image();
        
        img.onload = () => {
            // Calculate new dimensions
            let { width, height } = img;
            
            if (width > maxWidth) {
                height = (height * maxWidth) / width;
                width = maxWidth;
            }
            
            if (height > maxHeight) {
                width = (width * maxHeight) / height;
                height = maxHeight;
            }
            
            canvas.width = width;
            canvas.height = height;
            
            // Draw and compress
            ctx.drawImage(img, 0, 0, width, height);
            
            canvas.toBlob(
                (blob) => {
                    if (blob) {
                        const compressedFile = new File([blob], file.name, {
                            type: format,
                            lastModified: Date.now()
                        });
                        resolve(compressedFile);
                    } else {
                        reject(new Error('Failed to compress image'));
                    }
                },
                format,
                quality
            );
        };
        
        img.onerror = () => reject(new Error('Failed to load image'));
        img.src = URL.createObjectURL(file);
    });
}

/**
 * Lazy image loading with intersection observer
 */
export class LazyImageLoader {
    constructor(options = {}) {
        this.options = {
            root: null,
            rootMargin: '50px',
            threshold: 0.1,
            ...options
        };
        
        this.observer = new IntersectionObserver(
            this.handleIntersection.bind(this),
            this.options
        );
        
        this.loadedImages = new Set();
    }
    
    observe(img) {
        if (this.loadedImages.has(img)) return;
        this.observer.observe(img);
    }
    
    unobserve(img) {
        this.observer.unobserve(img);
    }
    
    handleIntersection(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                this.loadImage(entry.target);
            }
        });
    }
    
    loadImage(img) {
        const src = img.dataset.src;
        const srcset = img.dataset.srcset;
        
        if (!src) return;
        
        // Create a new image to preload
        const imageLoader = new Image();
        
        imageLoader.onload = () => {
            // Add fade-in animation
            img.style.opacity = '0';
            img.style.transition = 'opacity 0.3s ease-in-out';
            
            if (srcset) img.srcset = srcset;
            img.src = src;
            
            // Remove loading placeholder
            img.classList.remove('lazy-loading');
            img.classList.add('lazy-loaded');
            
            // Fade in
            requestAnimationFrame(() => {
                img.style.opacity = '1';
            });
            
            this.loadedImages.add(img);
            this.unobserve(img);
        };
        
        imageLoader.onerror = () => {
            img.classList.add('lazy-error');
            img.alt = 'Failed to load image';
            this.unobserve(img);
        };
        
        if (srcset) imageLoader.srcset = srcset;
        imageLoader.src = src;
    }
    
    disconnect() {
        this.observer.disconnect();
        this.loadedImages.clear();
    }
}

// Global lazy image loader instance
export const globalLazyLoader = new LazyImageLoader();

/**
 * Vue directive for lazy loading images
 */
export const vLazyImage = {
    mounted(el, binding) {
        // Add loading class
        el.classList.add('lazy-loading');
        
        // Set placeholder if provided
        if (binding.value?.placeholder) {
            el.src = binding.value.placeholder;
        }
        
        // Store original src in data attribute
        if (binding.value?.src) {
            el.dataset.src = binding.value.src;
        }
        
        if (binding.value?.srcset) {
            el.dataset.srcset = binding.value.srcset;
        }
        
        // Start observing
        globalLazyLoader.observe(el);
    },
    
    updated(el, binding) {
        if (binding.value?.src !== binding.oldValue?.src) {
            el.dataset.src = binding.value.src;
            if (!globalLazyLoader.loadedImages.has(el)) {
                globalLazyLoader.observe(el);
            }
        }
    },
    
    unmounted(el) {
        globalLazyLoader.unobserve(el);
    }
};

/**
 * Progressive image loading with blur effect
 */
export function createProgressiveImage(lowQualityUrl, highQualityUrl) {
    return {
        lowQuality: lowQualityUrl,
        highQuality: highQualityUrl,
        loaded: false
    };
}

/**
 * Preload images for better UX
 */
export function preloadImages(urls) {
    return Promise.all(
        urls.map(url => {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.onload = () => resolve(url);
                img.onerror = () => reject(new Error(`Failed to preload ${url}`));
                img.src = url;
            });
        })
    );
}

/**
 * Check if WebP is supported
 */
export function supportsWebP() {
    const canvas = document.createElement('canvas');
    canvas.width = 1;
    canvas.height = 1;
    return canvas.toDataURL('image/webp').indexOf('data:image/webp') === 0;
}

/**
 * Get optimal image format based on browser support
 */
export function getOptimalFormat() {
    if (supportsWebP()) return 'webp';
    return 'jpeg';
}