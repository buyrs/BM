import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { ProgressiveLoader, InfiniteScrollObserver, DebouncedSearch } from '../utils/progressiveLoading';

/**
 * Composable for progressive data loading
 */
export function useProgressiveLoading(endpoint, options = {}) {
    const defaultOptions = {
        pageSize: 20,
        autoLoad: true,
        searchDelay: 300,
        ...options
    };
    
    // State
    const data = ref([]);
    const isLoading = ref(false);
    const isLoadingMore = ref(false);
    const error = ref(null);
    const hasMore = ref(true);
    const totalItems = ref(0);
    const currentPage = ref(1);
    const searchQuery = ref('');
    const filters = reactive({});
    
    // Progressive loader instance
    let loader = null;
    let searchDebouncer = null;
    
    // Computed
    const isEmpty = computed(() => data.value.length === 0 && !isLoading.value);
    const hasData = computed(() => data.value.length > 0);
    const canLoadMore = computed(() => hasMore.value && !isLoading.value && !isLoadingMore.value);
    
    // Initialize loader
    const initLoader = () => {
        loader = new ProgressiveLoader({
            pageSize: defaultOptions.pageSize,
            ...defaultOptions
        });
        
        loader.addObserver((event, eventData) => {
            switch (event) {
                case 'dataLoaded':
                    data.value = eventData.data;
                    hasMore.value = eventData.hasMore;
                    totalItems.value = eventData.totalItems || 0;
                    currentPage.value = eventData.page;
                    isLoading.value = false;
                    isLoadingMore.value = false;
                    error.value = null;
                    break;
                    
                case 'error':
                    error.value = eventData;
                    isLoading.value = false;
                    isLoadingMore.value = false;
                    break;
            }
        });
        
        // Initialize search debouncer
        searchDebouncer = new DebouncedSearch((query) => {
            refresh({ search: query });
        }, defaultOptions.searchDelay);
    };
    
    // Load initial data
    const load = async (params = {}) => {
        if (!loader) return;
        
        isLoading.value = true;
        error.value = null;
        
        try {
            const requestParams = {
                ...filters,
                search: searchQuery.value,
                ...params
            };
            
            await loader.loadInitial(endpoint, requestParams);
        } catch (err) {
            error.value = err;
            isLoading.value = false;
        }
    };
    
    // Load more data
    const loadMore = async () => {
        if (!canLoadMore.value || !loader) return;
        
        isLoadingMore.value = true;
        
        try {
            const requestParams = {
                ...filters,
                search: searchQuery.value
            };
            
            await loader.loadNext(endpoint, requestParams);
        } catch (err) {
            error.value = err;
            isLoadingMore.value = false;
        }
    };
    
    // Refresh data
    const refresh = async (newParams = {}) => {
        if (!loader) return;
        
        // Update filters
        Object.assign(filters, newParams);
        
        // Reset loader state
        loader.reset();
        
        // Load fresh data
        await load();
    };
    
    // Search
    const search = (query) => {
        searchQuery.value = query;
        if (searchDebouncer) {
            searchDebouncer.search(query);
        }
    };
    
    // Clear search
    const clearSearch = () => {
        searchQuery.value = '';
        refresh();
    };
    
    // Update filters
    const updateFilters = (newFilters) => {
        Object.assign(filters, newFilters);
        refresh();
    };
    
    // Clear filters
    const clearFilters = () => {
        Object.keys(filters).forEach(key => delete filters[key]);
        refresh();
    };
    
    // Retry on error
    const retry = () => {
        if (data.value.length === 0) {
            load();
        } else {
            loadMore();
        }
    };
    
    // Reset everything
    const reset = () => {
        if (loader) {
            loader.reset();
        }
        
        data.value = [];
        isLoading.value = false;
        isLoadingMore.value = false;
        error.value = null;
        hasMore.value = true;
        totalItems.value = 0;
        currentPage.value = 1;
        searchQuery.value = '';
        Object.keys(filters).forEach(key => delete filters[key]);
    };
    
    // Lifecycle
    onMounted(() => {
        initLoader();
        
        if (defaultOptions.autoLoad) {
            load();
        }
    });
    
    onUnmounted(() => {
        if (searchDebouncer) {
            searchDebouncer.cancel();
        }
    });
    
    return {
        // State
        data,
        isLoading,
        isLoadingMore,
        error,
        hasMore,
        totalItems,
        currentPage,
        searchQuery,
        filters,
        
        // Computed
        isEmpty,
        hasData,
        canLoadMore,
        
        // Methods
        load,
        loadMore,
        refresh,
        search,
        clearSearch,
        updateFilters,
        clearFilters,
        retry,
        reset
    };
}

/**
 * Composable for infinite scrolling
 */
export function useInfiniteScroll(loadMoreCallback, options = {}) {
    const defaultOptions = {
        threshold: 0.1,
        rootMargin: '100px',
        ...options
    };
    
    const isLoading = ref(false);
    const hasMore = ref(true);
    const error = ref(null);
    let observer = null;
    
    const observe = (element) => {
        if (!element || observer) return;
        
        observer = new InfiniteScrollObserver({
            ...defaultOptions,
            onLoadMore: async () => {
                if (isLoading.value || !hasMore.value) return;
                
                isLoading.value = true;
                error.value = null;
                
                try {
                    const result = await loadMoreCallback();
                    hasMore.value = result.hasMore !== false;
                    return result;
                } catch (err) {
                    error.value = err;
                    throw err;
                } finally {
                    isLoading.value = false;
                }
            },
            onError: (err) => {
                error.value = err;
            }
        });
        
        observer.observe(element);
    };
    
    const unobserve = (element) => {
        if (observer && element) {
            observer.unobserve(element);
        }
    };
    
    const reset = () => {
        if (observer) {
            observer.reset();
        }
        isLoading.value = false;
        hasMore.value = true;
        error.value = null;
    };
    
    onUnmounted(() => {
        if (observer) {
            observer.disconnect();
        }
    });
    
    return {
        isLoading,
        hasMore,
        error,
        observe,
        unobserve,
        reset
    };
}

/**
 * Composable for virtual scrolling
 */
export function useVirtualScroll(containerRef, options = {}) {
    const defaultOptions = {
        itemHeight: 60,
        bufferSize: 5,
        ...options
    };
    
    const items = ref([]);
    const visibleItems = ref([]);
    const visibleRange = reactive({ start: 0, end: 0 });
    
    let virtualScroller = null;
    
    const initVirtualScroller = () => {
        if (!containerRef.value || virtualScroller) return;
        
        virtualScroller = new (class {
            constructor(container, opts) {
                this.container = container;
                this.options = opts;
                this.scrollTop = 0;
                this.containerHeight = 0;
                this.itemHeight = opts.itemHeight;
                this.bufferSize = opts.bufferSize;
                
                this.init();
            }
            
            init() {
                this.updateContainerHeight();
                this.container.addEventListener('scroll', this.handleScroll.bind(this));
                
                if (window.ResizeObserver) {
                    this.resizeObserver = new ResizeObserver(() => {
                        this.updateContainerHeight();
                        this.updateVisibleItems();
                    });
                    this.resizeObserver.observe(this.container);
                }
            }
            
            updateContainerHeight() {
                this.containerHeight = this.container.clientHeight;
            }
            
            handleScroll() {
                this.scrollTop = this.container.scrollTop;
                this.updateVisibleItems();
            }
            
            updateVisibleItems() {
                const start = Math.max(0, Math.floor(this.scrollTop / this.itemHeight) - this.bufferSize);
                const end = Math.min(
                    items.value.length,
                    Math.ceil((this.scrollTop + this.containerHeight) / this.itemHeight) + this.bufferSize
                );
                
                visibleRange.start = start;
                visibleRange.end = end;
                visibleItems.value = items.value.slice(start, end);
            }
            
            scrollToIndex(index) {
                const scrollTop = index * this.itemHeight;
                this.container.scrollTop = scrollTop;
            }
            
            destroy() {
                if (this.resizeObserver) {
                    this.resizeObserver.disconnect();
                }
                this.container.removeEventListener('scroll', this.handleScroll);
            }
        })(containerRef.value, defaultOptions);
    };
    
    const setItems = (newItems) => {
        items.value = newItems;
        if (virtualScroller) {
            virtualScroller.updateVisibleItems();
        }
    };
    
    const scrollToIndex = (index) => {
        if (virtualScroller) {
            virtualScroller.scrollToIndex(index);
        }
    };
    
    const getItemStyle = (index) => {
        const actualIndex = visibleRange.start + index;
        return {
            position: 'absolute',
            top: `${actualIndex * defaultOptions.itemHeight}px`,
            left: '0',
            right: '0',
            height: `${defaultOptions.itemHeight}px`
        };
    };
    
    const getTotalHeight = () => {
        return items.value.length * defaultOptions.itemHeight;
    };
    
    onMounted(() => {
        initVirtualScroller();
    });
    
    onUnmounted(() => {
        if (virtualScroller) {
            virtualScroller.destroy();
        }
    });
    
    return {
        items,
        visibleItems,
        visibleRange,
        setItems,
        scrollToIndex,
        getItemStyle,
        getTotalHeight
    };
}