/**
 * Performance monitoring and optimization service
 */

class PerformanceService {
    constructor() {
        this.metrics = new Map();
        this.observers = new Set();
        this.isEnabled = true;
        
        this.init();
    }
    
    init() {
        // Initialize performance observers
        this.initPerformanceObserver();
        this.initIntersectionObserver();
        this.initMutationObserver();
        
        // Monitor page load performance
        this.monitorPageLoad();
        
        // Monitor memory usage
        this.monitorMemoryUsage();
        
        // Monitor network conditions
        this.monitorNetworkConditions();
    }
    
    /**
     * Initialize Performance Observer for monitoring various metrics
     */
    initPerformanceObserver() {
        if (!('PerformanceObserver' in window)) return;
        
        try {
            // Monitor navigation timing
            const navObserver = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    this.recordMetric('navigation', {
                        name: entry.name,
                        duration: entry.duration,
                        startTime: entry.startTime,
                        type: entry.entryType
                    });
                }
            });
            navObserver.observe({ entryTypes: ['navigation'] });
            
            // Monitor resource loading
            const resourceObserver = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    this.recordMetric('resource', {
                        name: entry.name,
                        duration: entry.duration,
                        size: entry.transferSize,
                        type: this.getResourceType(entry.name)
                    });
                }
            });
            resourceObserver.observe({ entryTypes: ['resource'] });
            
            // Monitor long tasks
            const longTaskObserver = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    this.recordMetric('long-task', {
                        duration: entry.duration,
                        startTime: entry.startTime,
                        attribution: entry.attribution
                    });
                }
            });
            longTaskObserver.observe({ entryTypes: ['longtask'] });
            
            // Monitor largest contentful paint
            const lcpObserver = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    this.recordMetric('lcp', {
                        value: entry.startTime,
                        element: entry.element?.tagName
                    });
                }
            });
            lcpObserver.observe({ entryTypes: ['largest-contentful-paint'] });
            
        } catch (error) {
            console.warn('Performance Observer initialization failed:', error);
        }
    }
    
    /**
     * Initialize Intersection Observer for monitoring element visibility
     */
    initIntersectionObserver() {
        if (!('IntersectionObserver' in window)) return;
        
        this.visibilityObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                const element = entry.target;
                const isVisible = entry.isIntersecting;
                
                if (isVisible) {
                    this.recordMetric('element-visible', {
                        element: element.tagName,
                        id: element.id,
                        className: element.className,
                        timestamp: Date.now()
                    });
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '50px'
        });
    }
    
    /**
     * Initialize Mutation Observer for monitoring DOM changes
     */
    initMutationObserver() {
        if (!('MutationObserver' in window)) return;
        
        this.mutationObserver = new MutationObserver((mutations) => {
            let addedNodes = 0;
            let removedNodes = 0;
            let attributeChanges = 0;
            
            mutations.forEach(mutation => {
                addedNodes += mutation.addedNodes.length;
                removedNodes += mutation.removedNodes.length;
                if (mutation.type === 'attributes') attributeChanges++;
            });
            
            if (addedNodes > 10 || removedNodes > 10 || attributeChanges > 20) {
                this.recordMetric('dom-thrashing', {
                    addedNodes,
                    removedNodes,
                    attributeChanges,
                    timestamp: Date.now()
                });
            }
        });
        
        this.mutationObserver.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeOldValue: false
        });
    }
    
    /**
     * Monitor page load performance
     */
    monitorPageLoad() {
        window.addEventListener('load', () => {
            setTimeout(() => {
                const navigation = performance.getEntriesByType('navigation')[0];
                
                if (navigation) {
                    this.recordMetric('page-load', {
                        domContentLoaded: navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart,
                        loadComplete: navigation.loadEventEnd - navigation.loadEventStart,
                        totalTime: navigation.loadEventEnd - navigation.navigationStart,
                        dnsLookup: navigation.domainLookupEnd - navigation.domainLookupStart,
                        tcpConnect: navigation.connectEnd - navigation.connectStart,
                        serverResponse: navigation.responseEnd - navigation.requestStart
                    });
                }
                
                // Monitor Core Web Vitals
                this.measureCoreWebVitals();
            }, 0);
        });
    }
    
    /**
     * Measure Core Web Vitals
     */
    measureCoreWebVitals() {
        // First Input Delay (FID)
        if ('PerformanceEventTiming' in window) {
            const observer = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (entry.name === 'first-input') {
                        this.recordMetric('fid', {
                            value: entry.processingStart - entry.startTime
                        });
                    }
                }
            });
            observer.observe({ entryTypes: ['first-input'] });
        }
        
        // Cumulative Layout Shift (CLS)
        let clsValue = 0;
        const clsObserver = new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                if (!entry.hadRecentInput) {
                    clsValue += entry.value;
                }
            }
            
            this.recordMetric('cls', { value: clsValue });
        });
        clsObserver.observe({ entryTypes: ['layout-shift'] });
    }
    
    /**
     * Monitor memory usage
     */
    monitorMemoryUsage() {
        if (!('memory' in performance)) return;
        
        const checkMemory = () => {
            const memory = performance.memory;
            
            this.recordMetric('memory', {
                used: memory.usedJSHeapSize,
                total: memory.totalJSHeapSize,
                limit: memory.jsHeapSizeLimit,
                usage: (memory.usedJSHeapSize / memory.jsHeapSizeLimit) * 100
            });
            
            // Warn if memory usage is high
            const usagePercent = (memory.usedJSHeapSize / memory.jsHeapSizeLimit) * 100;
            if (usagePercent > 80) {
                console.warn('High memory usage detected:', usagePercent.toFixed(2) + '%');
                this.notifyObservers('high-memory-usage', { usage: usagePercent });
            }
        };
        
        // Check memory every 30 seconds
        setInterval(checkMemory, 30000);
        checkMemory(); // Initial check
    }
    
    /**
     * Monitor network conditions
     */
    monitorNetworkConditions() {
        if (!('connection' in navigator)) return;
        
        const connection = navigator.connection;
        
        const recordNetworkInfo = () => {
            this.recordMetric('network', {
                effectiveType: connection.effectiveType,
                downlink: connection.downlink,
                rtt: connection.rtt,
                saveData: connection.saveData
            });
        };
        
        connection.addEventListener('change', recordNetworkInfo);
        recordNetworkInfo(); // Initial recording
    }
    
    /**
     * Record a performance metric
     */
    recordMetric(type, data) {
        if (!this.isEnabled) return;
        
        const timestamp = Date.now();
        const metric = {
            type,
            data,
            timestamp
        };
        
        // Store in memory (with size limit)
        if (!this.metrics.has(type)) {
            this.metrics.set(type, []);
        }
        
        const typeMetrics = this.metrics.get(type);
        typeMetrics.push(metric);
        
        // Keep only last 100 entries per type
        if (typeMetrics.length > 100) {
            typeMetrics.shift();
        }
        
        // Notify observers
        this.notifyObservers('metric-recorded', metric);
    }
    
    /**
     * Get performance metrics
     */
    getMetrics(type = null) {
        if (type) {
            return this.metrics.get(type) || [];
        }
        
        const allMetrics = {};
        for (const [key, value] of this.metrics) {
            allMetrics[key] = value;
        }
        
        return allMetrics;
    }
    
    /**
     * Get performance summary
     */
    getSummary() {
        const summary = {
            pageLoad: this.getPageLoadSummary(),
            resources: this.getResourceSummary(),
            coreWebVitals: this.getCoreWebVitalsSummary(),
            memory: this.getMemorySummary(),
            network: this.getNetworkSummary()
        };
        
        return summary;
    }
    
    getPageLoadSummary() {
        const pageLoadMetrics = this.getMetrics('page-load');
        if (pageLoadMetrics.length === 0) return null;
        
        const latest = pageLoadMetrics[pageLoadMetrics.length - 1];
        return latest.data;
    }
    
    getResourceSummary() {
        const resourceMetrics = this.getMetrics('resource');
        
        const summary = {
            total: resourceMetrics.length,
            totalSize: 0,
            averageDuration: 0,
            byType: {}
        };
        
        resourceMetrics.forEach(metric => {
            const { size = 0, duration = 0, type = 'unknown' } = metric.data;
            
            summary.totalSize += size;
            summary.averageDuration += duration;
            
            if (!summary.byType[type]) {
                summary.byType[type] = { count: 0, size: 0, duration: 0 };
            }
            
            summary.byType[type].count++;
            summary.byType[type].size += size;
            summary.byType[type].duration += duration;
        });
        
        if (resourceMetrics.length > 0) {
            summary.averageDuration /= resourceMetrics.length;
        }
        
        return summary;
    }
    
    getCoreWebVitalsSummary() {
        const lcp = this.getMetrics('lcp');
        const fid = this.getMetrics('fid');
        const cls = this.getMetrics('cls');
        
        return {
            lcp: lcp.length > 0 ? lcp[lcp.length - 1].data.value : null,
            fid: fid.length > 0 ? fid[fid.length - 1].data.value : null,
            cls: cls.length > 0 ? cls[cls.length - 1].data.value : null
        };
    }
    
    getMemorySummary() {
        const memoryMetrics = this.getMetrics('memory');
        if (memoryMetrics.length === 0) return null;
        
        const latest = memoryMetrics[memoryMetrics.length - 1];
        return latest.data;
    }
    
    getNetworkSummary() {
        const networkMetrics = this.getMetrics('network');
        if (networkMetrics.length === 0) return null;
        
        const latest = networkMetrics[networkMetrics.length - 1];
        return latest.data;
    }
    
    /**
     * Measure component render time
     */
    measureComponentRender(componentName, renderFunction) {
        const startTime = performance.now();
        
        const result = renderFunction();
        
        const endTime = performance.now();
        const duration = endTime - startTime;
        
        this.recordMetric('component-render', {
            component: componentName,
            duration
        });
        
        return result;
    }
    
    /**
     * Measure API request performance
     */
    measureApiRequest(url, requestFunction) {
        const startTime = performance.now();
        
        return requestFunction().then(result => {
            const endTime = performance.now();
            const duration = endTime - startTime;
            
            this.recordMetric('api-request', {
                url,
                duration,
                success: true
            });
            
            return result;
        }).catch(error => {
            const endTime = performance.now();
            const duration = endTime - startTime;
            
            this.recordMetric('api-request', {
                url,
                duration,
                success: false,
                error: error.message
            });
            
            throw error;
        });
    }
    
    /**
     * Monitor element visibility
     */
    observeElement(element) {
        if (this.visibilityObserver) {
            this.visibilityObserver.observe(element);
        }
    }
    
    /**
     * Stop monitoring element
     */
    unobserveElement(element) {
        if (this.visibilityObserver) {
            this.visibilityObserver.unobserve(element);
        }
    }
    
    /**
     * Add performance observer
     */
    addObserver(callback) {
        this.observers.add(callback);
        return () => this.observers.delete(callback);
    }
    
    /**
     * Notify observers
     */
    notifyObservers(event, data) {
        this.observers.forEach(callback => {
            try {
                callback(event, data);
            } catch (error) {
                console.error('Performance observer callback error:', error);
            }
        });
    }
    
    /**
     * Get resource type from URL
     */
    getResourceType(url) {
        const extension = url.split('.').pop()?.toLowerCase();
        
        if (['js', 'mjs'].includes(extension)) return 'script';
        if (['css'].includes(extension)) return 'stylesheet';
        if (['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'].includes(extension)) return 'image';
        if (['woff', 'woff2', 'ttf', 'otf'].includes(extension)) return 'font';
        if (['json', 'xml'].includes(extension)) return 'xhr';
        
        return 'other';
    }
    
    /**
     * Clear all metrics
     */
    clearMetrics() {
        this.metrics.clear();
    }
    
    /**
     * Enable/disable performance monitoring
     */
    setEnabled(enabled) {
        this.isEnabled = enabled;
    }
    
    /**
     * Destroy the service
     */
    destroy() {
        if (this.visibilityObserver) {
            this.visibilityObserver.disconnect();
        }
        
        if (this.mutationObserver) {
            this.mutationObserver.disconnect();
        }
        
        this.observers.clear();
        this.metrics.clear();
    }
}

// Create singleton instance
export const performanceService = new PerformanceService();
export default performanceService;