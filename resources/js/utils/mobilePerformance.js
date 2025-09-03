/**
 * Mobile Performance Optimization Utilities
 * Provides tools to optimize performance on mobile devices and slower connections
 */

class MobilePerformance {
  constructor() {
    this.isLowEndDevice = this.detectLowEndDevice();
    this.connectionType = this.getConnectionType();
    this.isSlowConnection = this.detectSlowConnection();
    this.performanceObserver = null;
    this.metrics = {
      fps: 60,
      memoryUsage: 0,
      loadTime: 0,
      renderTime: 0
    };
    
    this.init();
  }

  init() {
    this.setupPerformanceMonitoring();
    this.optimizeForDevice();
    this.setupConnectionMonitoring();
  }

  // Device detection
  detectLowEndDevice() {
    // Check device memory (if available)
    if ('deviceMemory' in navigator) {
      return navigator.deviceMemory <= 2; // 2GB or less
    }

    // Check hardware concurrency
    if ('hardwareConcurrency' in navigator) {
      return navigator.hardwareConcurrency <= 2; // 2 cores or less
    }

    // Fallback to user agent detection
    const userAgent = navigator.userAgent.toLowerCase();
    const lowEndPatterns = [
      /android.*4\./,
      /android.*5\./,
      /iphone.*os [5-9]_/,
      /windows phone/,
      /blackberry/,
      /opera mini/
    ];

    return lowEndPatterns.some(pattern => pattern.test(userAgent));
  }

  // Connection detection
  getConnectionType() {
    if ('connection' in navigator) {
      return navigator.connection.effectiveType || 'unknown';
    }
    return 'unknown';
  }

  detectSlowConnection() {
    if ('connection' in navigator) {
      const connection = navigator.connection;
      return (
        connection.effectiveType === 'slow-2g' ||
        connection.effectiveType === '2g' ||
        (connection.effectiveType === '3g' && connection.downlink < 1.5)
      );
    }
    return false;
  }

  // Performance monitoring
  setupPerformanceMonitoring() {
    // Monitor FPS
    this.monitorFPS();
    
    // Monitor memory usage
    this.monitorMemory();
    
    // Monitor load times
    this.monitorLoadTimes();

    // Setup Performance Observer for Core Web Vitals
    if ('PerformanceObserver' in window) {
      this.performanceObserver = new PerformanceObserver((list) => {
        for (const entry of list.getEntries()) {
          this.handlePerformanceEntry(entry);
        }
      });

      try {
        this.performanceObserver.observe({ entryTypes: ['measure', 'navigation', 'paint'] });
      } catch (e) {
        console.warn('Performance Observer not fully supported');
      }
    }
  }

  monitorFPS() {
    let lastTime = performance.now();
    let frameCount = 0;

    const measureFPS = (currentTime) => {
      frameCount++;
      
      if (currentTime - lastTime >= 1000) {
        this.metrics.fps = Math.round((frameCount * 1000) / (currentTime - lastTime));
        frameCount = 0;
        lastTime = currentTime;
        
        // Trigger optimizations if FPS is low
        if (this.metrics.fps < 30) {
          this.handleLowFPS();
        }
      }
      
      requestAnimationFrame(measureFPS);
    };

    requestAnimationFrame(measureFPS);
  }

  monitorMemory() {
    if ('memory' in performance) {
      setInterval(() => {
        this.metrics.memoryUsage = performance.memory.usedJSHeapSize / 1048576; // MB
        
        // Trigger cleanup if memory usage is high
        if (this.metrics.memoryUsage > 50) {
          this.handleHighMemoryUsage();
        }
      }, 5000);
    }
  }

  monitorLoadTimes() {
    window.addEventListener('load', () => {
      setTimeout(() => {
        const navigation = performance.getEntriesByType('navigation')[0];
        if (navigation) {
          this.metrics.loadTime = navigation.loadEventEnd - navigation.fetchStart;
          this.metrics.renderTime = navigation.domContentLoadedEventEnd - navigation.fetchStart;
        }
      }, 0);
    });
  }

  handlePerformanceEntry(entry) {
    switch (entry.entryType) {
      case 'paint':
        if (entry.name === 'first-contentful-paint') {
          console.log('FCP:', entry.startTime);
        }
        break;
      case 'largest-contentful-paint':
        console.log('LCP:', entry.startTime);
        break;
      case 'layout-shift':
        console.log('CLS:', entry.value);
        break;
    }
  }

  // Optimization strategies
  optimizeForDevice() {
    if (this.isLowEndDevice) {
      this.applyLowEndOptimizations();
    }

    if (this.isSlowConnection) {
      this.applySlowConnectionOptimizations();
    }
  }

  applyLowEndOptimizations() {
    // Reduce animation complexity
    document.documentElement.style.setProperty('--animation-duration', '0.1s');
    
    // Disable non-essential animations
    const style = document.createElement('style');
    style.textContent = `
      @media (max-width: 640px) {
        *, *::before, *::after {
          animation-duration: 0.1s !important;
          transition-duration: 0.1s !important;
        }
        
        .animate-pulse,
        .animate-bounce,
        .animate-spin {
          animation: none !important;
        }
      }
    `;
    document.head.appendChild(style);

    // Reduce image quality
    this.optimizeImages();
    
    // Limit concurrent operations
    this.limitConcurrentOperations();
  }

  applySlowConnectionOptimizations() {
    // Preload critical resources
    this.preloadCriticalResources();
    
    // Lazy load non-critical content
    this.setupLazyLoading();
    
    // Compress data transfers
    this.enableDataCompression();
    
    // Reduce polling frequency
    this.reducePollingFrequency();
  }

  handleLowFPS() {
    // Reduce visual complexity
    document.body.classList.add('low-performance-mode');
    
    // Disable expensive effects
    const expensiveElements = document.querySelectorAll('.shadow-lg, .blur, .backdrop-blur');
    expensiveElements.forEach(el => {
      el.classList.add('performance-reduced');
    });
    
    // Throttle scroll events
    this.throttleScrollEvents();
  }

  handleHighMemoryUsage() {
    // Force garbage collection (if available)
    if (window.gc) {
      window.gc();
    }
    
    // Clear caches
    this.clearNonEssentialCaches();
    
    // Reduce image cache size
    this.reduceImageCache();
  }

  // Image optimization
  optimizeImages() {
    const images = document.querySelectorAll('img');
    images.forEach(img => {
      if (!img.dataset.optimized) {
        // Add loading="lazy" if not present
        if (!img.hasAttribute('loading')) {
          img.loading = 'lazy';
        }
        
        // Reduce quality for low-end devices
        if (img.src && !img.src.includes('quality=')) {
          const separator = img.src.includes('?') ? '&' : '?';
          img.src += `${separator}quality=70&format=webp`;
        }
        
        img.dataset.optimized = 'true';
      }
    });
  }

  // Lazy loading setup
  setupLazyLoading() {
    if ('IntersectionObserver' in window) {
      const lazyObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const element = entry.target;
            
            // Load images
            if (element.dataset.src) {
              element.src = element.dataset.src;
              element.removeAttribute('data-src');
            }
            
            // Load components
            if (element.dataset.component) {
              this.loadComponent(element);
            }
            
            lazyObserver.unobserve(element);
          }
        });
      }, {
        rootMargin: '50px'
      });

      // Observe lazy elements
      document.querySelectorAll('[data-src], [data-component]').forEach(el => {
        lazyObserver.observe(el);
      });
    }
  }

  // Resource preloading
  preloadCriticalResources() {
    const criticalResources = [
      '/css/app.css',
      '/js/app.js',
      '/fonts/inter-var.woff2'
    ];

    criticalResources.forEach(resource => {
      const link = document.createElement('link');
      link.rel = 'preload';
      link.href = resource;
      
      if (resource.endsWith('.css')) {
        link.as = 'style';
      } else if (resource.endsWith('.js')) {
        link.as = 'script';
      } else if (resource.includes('font')) {
        link.as = 'font';
        link.type = 'font/woff2';
        link.crossOrigin = 'anonymous';
      }
      
      document.head.appendChild(link);
    });
  }

  // Event throttling
  throttleScrollEvents() {
    let ticking = false;
    
    const throttledScroll = () => {
      if (!ticking) {
        requestAnimationFrame(() => {
          // Handle scroll events here
          ticking = false;
        });
        ticking = true;
      }
    };

    // Replace existing scroll listeners with throttled version
    window.addEventListener('scroll', throttledScroll, { passive: true });
  }

  // Cache management
  clearNonEssentialCaches() {
    // Clear old cached data
    if ('caches' in window) {
      caches.keys().then(cacheNames => {
        cacheNames.forEach(cacheName => {
          if (cacheName.includes('old') || cacheName.includes('temp')) {
            caches.delete(cacheName);
          }
        });
      });
    }

    // Clear localStorage of non-essential items
    const nonEssentialKeys = [];
    for (let i = 0; i < localStorage.length; i++) {
      const key = localStorage.key(i);
      if (key && (key.includes('cache') || key.includes('temp'))) {
        nonEssentialKeys.push(key);
      }
    }
    nonEssentialKeys.forEach(key => localStorage.removeItem(key));
  }

  reduceImageCache() {
    // Reduce the number of cached images
    const images = document.querySelectorAll('img');
    images.forEach((img, index) => {
      if (index > 20) { // Keep only first 20 images in memory
        img.src = '';
        img.dataset.src = img.src;
      }
    });
  }

  // Component loading
  async loadComponent(element) {
    const componentName = element.dataset.component;
    try {
      const component = await import(`@/Components/${componentName}.vue`);
      // Mount component logic here
      element.classList.add('component-loaded');
    } catch (error) {
      console.error(`Failed to load component ${componentName}:`, error);
    }
  }

  // Data compression
  enableDataCompression() {
    // Add compression headers for API requests
    const originalFetch = window.fetch;
    window.fetch = function(url, options = {}) {
      options.headers = {
        ...options.headers,
        'Accept-Encoding': 'gzip, deflate, br'
      };
      return originalFetch(url, options);
    };
  }

  // Polling optimization
  reducePollingFrequency() {
    // Increase polling intervals for slow connections
    const multiplier = this.isSlowConnection ? 3 : 1;
    
    // Override setInterval for polling operations
    const originalSetInterval = window.setInterval;
    window.setInterval = function(callback, delay) {
      return originalSetInterval(callback, delay * multiplier);
    };
  }

  // Limit concurrent operations
  limitConcurrentOperations() {
    let activeOperations = 0;
    const maxConcurrent = this.isLowEndDevice ? 2 : 4;
    
    const operationQueue = [];
    
    window.performOperation = async function(operation) {
      return new Promise((resolve, reject) => {
        const execute = async () => {
          if (activeOperations >= maxConcurrent) {
            operationQueue.push(execute);
            return;
          }
          
          activeOperations++;
          try {
            const result = await operation();
            resolve(result);
          } catch (error) {
            reject(error);
          } finally {
            activeOperations--;
            if (operationQueue.length > 0) {
              const nextOperation = operationQueue.shift();
              nextOperation();
            }
          }
        };
        
        execute();
      });
    };
  }

  // Performance metrics
  getMetrics() {
    return {
      ...this.metrics,
      isLowEndDevice: this.isLowEndDevice,
      connectionType: this.connectionType,
      isSlowConnection: this.isSlowConnection,
      timestamp: Date.now()
    };
  }

  // Performance report
  generateReport() {
    const metrics = this.getMetrics();
    const navigation = performance.getEntriesByType('navigation')[0];
    
    return {
      device: {
        isLowEnd: this.isLowEndDevice,
        memory: navigator.deviceMemory || 'unknown',
        cores: navigator.hardwareConcurrency || 'unknown'
      },
      connection: {
        type: this.connectionType,
        isSlow: this.isSlowConnection,
        downlink: navigator.connection?.downlink || 'unknown'
      },
      performance: {
        fps: metrics.fps,
        memoryUsage: metrics.memoryUsage,
        loadTime: metrics.loadTime,
        renderTime: metrics.renderTime,
        fcp: this.getFCP(),
        lcp: this.getLCP()
      },
      optimizations: {
        lowEndOptimizations: this.isLowEndDevice,
        slowConnectionOptimizations: this.isSlowConnection,
        performanceMode: document.body.classList.contains('low-performance-mode')
      }
    };
  }

  getFCP() {
    const fcpEntry = performance.getEntriesByName('first-contentful-paint')[0];
    return fcpEntry ? fcpEntry.startTime : null;
  }

  getLCP() {
    return new Promise((resolve) => {
      if ('PerformanceObserver' in window) {
        const observer = new PerformanceObserver((list) => {
          const entries = list.getEntries();
          const lastEntry = entries[entries.length - 1];
          resolve(lastEntry.startTime);
          observer.disconnect();
        });
        
        try {
          observer.observe({ entryTypes: ['largest-contentful-paint'] });
        } catch (e) {
          resolve(null);
        }
      } else {
        resolve(null);
      }
    });
  }

  // Cleanup
  destroy() {
    if (this.performanceObserver) {
      this.performanceObserver.disconnect();
    }
  }
}

// Utility functions
export const MobileUtils = {
  // Debounce function optimized for mobile
  debounce(func, wait, immediate = false) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        timeout = null;
        if (!immediate) func(...args);
      };
      const callNow = immediate && !timeout;
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
      if (callNow) func(...args);
    };
  },

  // Throttle function optimized for mobile
  throttle(func, limit) {
    let inThrottle;
    return function(...args) {
      if (!inThrottle) {
        func.apply(this, args);
        inThrottle = true;
        setTimeout(() => inThrottle = false, limit);
      }
    };
  },

  // Check if element is in viewport
  isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
      rect.top >= 0 &&
      rect.left >= 0 &&
      rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
      rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
  },

  // Get optimal image size for device
  getOptimalImageSize(originalWidth, originalHeight) {
    const devicePixelRatio = window.devicePixelRatio || 1;
    const screenWidth = window.innerWidth * devicePixelRatio;
    const screenHeight = window.innerHeight * devicePixelRatio;
    
    const aspectRatio = originalWidth / originalHeight;
    
    let optimalWidth, optimalHeight;
    
    if (aspectRatio > 1) {
      // Landscape
      optimalWidth = Math.min(originalWidth, screenWidth);
      optimalHeight = optimalWidth / aspectRatio;
    } else {
      // Portrait
      optimalHeight = Math.min(originalHeight, screenHeight);
      optimalWidth = optimalHeight * aspectRatio;
    }
    
    return {
      width: Math.round(optimalWidth),
      height: Math.round(optimalHeight)
    };
  },

  // Preload critical images
  preloadImage(src) {
    return new Promise((resolve, reject) => {
      const img = new Image();
      img.onload = () => resolve(img);
      img.onerror = reject;
      img.src = src;
    });
  },

  // Convert touch event to mouse event
  touchToMouse(touchEvent) {
    const touch = touchEvent.touches[0] || touchEvent.changedTouches[0];
    return {
      clientX: touch.clientX,
      clientY: touch.clientY,
      pageX: touch.pageX,
      pageY: touch.pageY,
      screenX: touch.screenX,
      screenY: touch.screenY
    };
  }
};

// Export singleton instance
export default new MobilePerformance();