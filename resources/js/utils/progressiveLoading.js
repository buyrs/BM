/**
 * Progressive loading utilities for handling large datasets
 */

/**
 * Progressive data loader class
 */
export class ProgressiveLoader {
    constructor(options = {}) {
        this.options = {
            pageSize: 20,
            threshold: 5, // Load more when this many items from the end
            maxConcurrentRequests: 3,
            retryAttempts: 3,
            retryDelay: 1000,
            ...options
        };
        
        this.currentPage = 1;
        this.totalPages = null;
        this.totalItems = null;
        this.isLoading = false;
        this.hasMore = true;
        this.data = [];
        this.error = null;
        this.activeRequests = new Set();
        this.observers = new Set();
    }
    
    /**
     * Load initial data
     */
    async loadInitial(endpoint, params = {}) {
        this.reset();
        return this.loadPage(endpoint, 1, params);
    }
    
    /**
     * Load next page
     */
    async loadNext(endpoint, params = {}) {
        if (!this.hasMore || this.isLoading) {
            return { data: [], hasMore: false };
        }
        
        return this.loadPage(endpoint, this.currentPage + 1, params);
    }
    
    /**
     * Load specific page
     */
    async loadPage(endpoint, page, params = {}) {
        if (this.activeRequests.size >= this.options.maxConcurrentRequests) {
            await this.waitForSlot();
        }
        
        const requestId = `${endpoint}-${page}`;
        
        if (this.activeRequests.has(requestId)) {
            return { data: [], hasMore: this.hasMore };
        }
        
        this.isLoading = true;
        this.error = null;
        this.activeRequests.add(requestId);
        
        try {
            const response = await this.makeRequest(endpoint, {
                ...params,
                page,
                per_page: this.options.pageSize
            });
            
            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.message || `HTTP ${response.status}`);
            }
            
            // Handle different response formats
            const data = this.extractData(result);
            const pagination = this.extractPagination(result);
            
            // Update state
            if (page === 1) {
                this.data = data;
            } else {
                this.data = [...this.data, ...data];
            }
            
            this.currentPage = page;
            this.totalPages = pagination.totalPages;
            this.totalItems = pagination.totalItems;
            this.hasMore = pagination.hasMore;
            
            // Notify observers
            this.notifyObservers('dataLoaded', {
                data: this.data,
                newItems: data,
                page,
                hasMore: this.hasMore,
                totalItems: this.totalItems
            });
            
            return {
                data: this.data,
                newItems: data,
                hasMore: this.hasMore,
                totalItems: this.totalItems
            };
            
        } catch (error) {
            this.error = error;
            this.notifyObservers('error', error);
            
            // Retry logic
            if (this.options.retryAttempts > 0) {
                return this.retryRequest(endpoint, page, params);
            }
            
            throw error;
        } finally {
            this.isLoading = false;
            this.activeRequests.delete(requestId);
        }
    }
    
    /**
     * Make HTTP request with timeout and error handling
     */
    async makeRequest(endpoint, params) {
        const url = new URL(endpoint, window.location.origin);
        Object.keys(params).forEach(key => {
            if (params[key] !== null && params[key] !== undefined) {
                url.searchParams.append(key, params[key]);
            }
        });
        
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000); // 30s timeout
        
        try {
            const response = await fetch(url.toString(), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: controller.signal
            });
            
            clearTimeout(timeoutId);
            return response;
        } catch (error) {
            clearTimeout(timeoutId);
            throw error;
        }
    }
    
    /**
     * Extract data from response
     */
    extractData(response) {
        // Handle different response formats
        if (response.data) {
            return Array.isArray(response.data) ? response.data : response.data.data || [];
        }
        
        if (Array.isArray(response)) {
            return response;
        }
        
        return response.items || [];
    }
    
    /**
     * Extract pagination info from response
     */
    extractPagination(response) {
        const pagination = response.meta || response.pagination || response;
        
        return {
            totalPages: pagination.last_page || pagination.total_pages || null,
            totalItems: pagination.total || pagination.total_items || null,
            hasMore: pagination.has_more_pages || 
                    (pagination.current_page < pagination.last_page) ||
                    (this.currentPage < (pagination.total_pages || Infinity))
        };
    }
    
    /**
     * Retry failed request
     */
    async retryRequest(endpoint, page, params, attempt = 1) {
        if (attempt > this.options.retryAttempts) {
            throw this.error;
        }
        
        await new Promise(resolve => 
            setTimeout(resolve, this.options.retryDelay * attempt)
        );
        
        try {
            return await this.loadPage(endpoint, page, params);
        } catch (error) {
            return this.retryRequest(endpoint, page, params, attempt + 1);
        }
    }
    
    /**
     * Wait for available request slot
     */
    async waitForSlot() {
        return new Promise(resolve => {
            const checkSlot = () => {
                if (this.activeRequests.size < this.options.maxConcurrentRequests) {
                    resolve();
                } else {
                    setTimeout(checkSlot, 100);
                }
            };
            checkSlot();
        });
    }
    
    /**
     * Reset loader state
     */
    reset() {
        this.currentPage = 1;
        this.totalPages = null;
        this.totalItems = null;
        this.isLoading = false;
        this.hasMore = true;
        this.data = [];
        this.error = null;
        this.activeRequests.clear();
    }
    
    /**
     * Add observer for events
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
                console.error('Observer callback error:', error);
            }
        });
    }
    
    /**
     * Get current state
     */
    getState() {
        return {
            data: this.data,
            currentPage: this.currentPage,
            totalPages: this.totalPages,
            totalItems: this.totalItems,
            isLoading: this.isLoading,
            hasMore: this.hasMore,
            error: this.error
        };
    }
}

/**
 * Virtual scrolling implementation for large lists
 */
export class VirtualScroller {
    constructor(container, options = {}) {
        this.container = container;
        this.options = {
            itemHeight: 60,
            bufferSize: 5,
            threshold: 200,
            ...options
        };
        
        this.items = [];
        this.visibleStart = 0;
        this.visibleEnd = 0;
        this.scrollTop = 0;
        this.containerHeight = 0;
        this.totalHeight = 0;
        
        this.init();
    }
    
    init() {
        this.container.style.position = 'relative';
        this.container.style.overflow = 'auto';
        
        // Create viewport
        this.viewport = document.createElement('div');
        this.viewport.style.position = 'absolute';
        this.viewport.style.top = '0';
        this.viewport.style.left = '0';
        this.viewport.style.right = '0';
        this.container.appendChild(this.viewport);
        
        // Create spacer for total height
        this.spacer = document.createElement('div');
        this.spacer.style.position = 'absolute';
        this.spacer.style.top = '0';
        this.spacer.style.left = '0';
        this.spacer.style.right = '0';
        this.spacer.style.zIndex = '-1';
        this.container.appendChild(this.spacer);
        
        // Add scroll listener
        this.container.addEventListener('scroll', this.handleScroll.bind(this));
        
        // Add resize observer
        if (window.ResizeObserver) {
            this.resizeObserver = new ResizeObserver(() => {
                this.updateContainerHeight();
                this.render();
            });
            this.resizeObserver.observe(this.container);
        }
        
        this.updateContainerHeight();
    }
    
    setItems(items) {
        this.items = items;
        this.totalHeight = items.length * this.options.itemHeight;
        this.spacer.style.height = `${this.totalHeight}px`;
        this.render();
    }
    
    addItems(newItems) {
        this.items = [...this.items, ...newItems];
        this.totalHeight = this.items.length * this.options.itemHeight;
        this.spacer.style.height = `${this.totalHeight}px`;
        this.render();
    }
    
    handleScroll() {
        this.scrollTop = this.container.scrollTop;
        this.render();
        
        // Check if we need to load more data
        const scrollBottom = this.scrollTop + this.containerHeight;
        const threshold = this.totalHeight - this.options.threshold;
        
        if (scrollBottom >= threshold && this.options.onLoadMore) {
            this.options.onLoadMore();
        }
    }
    
    updateContainerHeight() {
        this.containerHeight = this.container.clientHeight;
    }
    
    render() {
        const itemHeight = this.options.itemHeight;
        const bufferSize = this.options.bufferSize;
        
        // Calculate visible range
        const visibleStart = Math.max(0, Math.floor(this.scrollTop / itemHeight) - bufferSize);
        const visibleEnd = Math.min(
            this.items.length,
            Math.ceil((this.scrollTop + this.containerHeight) / itemHeight) + bufferSize
        );
        
        // Only re-render if range changed significantly
        if (Math.abs(visibleStart - this.visibleStart) > bufferSize || 
            Math.abs(visibleEnd - this.visibleEnd) > bufferSize) {
            
            this.visibleStart = visibleStart;
            this.visibleEnd = visibleEnd;
            
            // Clear viewport
            this.viewport.innerHTML = '';
            
            // Render visible items
            for (let i = visibleStart; i < visibleEnd; i++) {
                const item = this.items[i];
                if (!item) continue;
                
                const element = this.createItemElement(item, i);
                element.style.position = 'absolute';
                element.style.top = `${i * itemHeight}px`;
                element.style.left = '0';
                element.style.right = '0';
                element.style.height = `${itemHeight}px`;
                
                this.viewport.appendChild(element);
            }
        }
    }
    
    createItemElement(item, index) {
        if (this.options.renderItem) {
            return this.options.renderItem(item, index);
        }
        
        // Default renderer
        const element = document.createElement('div');
        element.className = 'virtual-scroll-item';
        element.textContent = item.toString();
        return element;
    }
    
    scrollToIndex(index) {
        const scrollTop = index * this.options.itemHeight;
        this.container.scrollTop = scrollTop;
    }
    
    destroy() {
        if (this.resizeObserver) {
            this.resizeObserver.disconnect();
        }
        this.container.removeEventListener('scroll', this.handleScroll);
    }
}

/**
 * Intersection Observer for infinite scrolling
 */
export class InfiniteScrollObserver {
    constructor(options = {}) {
        this.options = {
            threshold: 0.1,
            rootMargin: '100px',
            ...options
        };
        
        this.observer = new IntersectionObserver(
            this.handleIntersection.bind(this),
            this.options
        );
        
        this.isLoading = false;
        this.hasMore = true;
    }
    
    observe(element) {
        this.observer.observe(element);
    }
    
    unobserve(element) {
        this.observer.unobserve(element);
    }
    
    handleIntersection(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting && !this.isLoading && this.hasMore) {
                this.loadMore();
            }
        });
    }
    
    async loadMore() {
        if (this.isLoading || !this.hasMore || !this.options.onLoadMore) {
            return;
        }
        
        this.isLoading = true;
        
        try {
            const result = await this.options.onLoadMore();
            this.hasMore = result.hasMore !== false;
        } catch (error) {
            console.error('Infinite scroll load error:', error);
            if (this.options.onError) {
                this.options.onError(error);
            }
        } finally {
            this.isLoading = false;
        }
    }
    
    reset() {
        this.isLoading = false;
        this.hasMore = true;
    }
    
    disconnect() {
        this.observer.disconnect();
    }
}

/**
 * Debounced search for progressive loading
 */
export class DebouncedSearch {
    constructor(callback, delay = 300) {
        this.callback = callback;
        this.delay = delay;
        this.timeoutId = null;
        this.lastQuery = '';
    }
    
    search(query) {
        // Don't search if query hasn't changed
        if (query === this.lastQuery) {
            return;
        }
        
        this.lastQuery = query;
        
        // Clear existing timeout
        if (this.timeoutId) {
            clearTimeout(this.timeoutId);
        }
        
        // Set new timeout
        this.timeoutId = setTimeout(() => {
            this.callback(query);
        }, this.delay);
    }
    
    cancel() {
        if (this.timeoutId) {
            clearTimeout(this.timeoutId);
            this.timeoutId = null;
        }
    }
}

/**
 * Progressive image loading for galleries
 */
export class ProgressiveImageGallery {
    constructor(container, options = {}) {
        this.container = container;
        this.options = {
            batchSize: 6,
            loadDelay: 100,
            placeholder: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjNmNGY2Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIiBmaWxsPSIjOWNhM2FmIj5Mb2FkaW5nLi4uPC90ZXh0Pjwvc3ZnPg==',
            ...options
        };
        
        this.images = [];
        this.loadedCount = 0;
        this.isLoading = false;
    }
    
    setImages(imageUrls) {
        this.images = imageUrls.map(url => ({
            url,
            loaded: false,
            element: null
        }));
        
        this.loadedCount = 0;
        this.container.innerHTML = '';
        this.loadNextBatch();
    }
    
    async loadNextBatch() {
        if (this.isLoading || this.loadedCount >= this.images.length) {
            return;
        }
        
        this.isLoading = true;
        const batchEnd = Math.min(this.loadedCount + this.options.batchSize, this.images.length);
        const batch = this.images.slice(this.loadedCount, batchEnd);
        
        // Create placeholder elements
        batch.forEach((image, index) => {
            const element = this.createImageElement(image, this.loadedCount + index);
            this.container.appendChild(element);
            image.element = element;
        });
        
        // Load images with delay
        for (let i = 0; i < batch.length; i++) {
            setTimeout(() => {
                this.loadImage(batch[i]);
            }, i * this.options.loadDelay);
        }
        
        this.loadedCount = batchEnd;
        this.isLoading = false;
        
        // Continue loading if there are more images
        if (this.loadedCount < this.images.length) {
            setTimeout(() => this.loadNextBatch(), this.options.loadDelay * batch.length);
        }
    }
    
    createImageElement(image, index) {
        const wrapper = document.createElement('div');
        wrapper.className = 'progressive-image-wrapper';
        wrapper.style.position = 'relative';
        wrapper.style.overflow = 'hidden';
        
        const img = document.createElement('img');
        img.src = this.options.placeholder;
        img.alt = `Image ${index + 1}`;
        img.style.width = '100%';
        img.style.height = 'auto';
        img.style.transition = 'opacity 0.3s ease';
        
        wrapper.appendChild(img);
        return wrapper;
    }
    
    loadImage(imageData) {
        const img = imageData.element.querySelector('img');
        const actualImage = new Image();
        
        actualImage.onload = () => {
            img.style.opacity = '0';
            setTimeout(() => {
                img.src = imageData.url;
                img.style.opacity = '1';
                imageData.loaded = true;
                
                if (this.options.onImageLoad) {
                    this.options.onImageLoad(imageData, this.getLoadedCount());
                }
            }, 50);
        };
        
        actualImage.onerror = () => {
            img.alt = 'Failed to load image';
            imageData.element.classList.add('load-error');
            
            if (this.options.onImageError) {
                this.options.onImageError(imageData);
            }
        };
        
        actualImage.src = imageData.url;
    }
    
    getLoadedCount() {
        return this.images.filter(img => img.loaded).length;
    }
    
    loadAll() {
        this.options.batchSize = this.images.length;
        this.loadNextBatch();
    }
}