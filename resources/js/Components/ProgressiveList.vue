<template>
  <div class="progressive-list" :class="containerClass">
    <!-- Search and filters -->
    <div v-if="showSearch || showFilters" class="progressive-list-controls">
      <div v-if="showSearch" class="search-container">
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="searchPlaceholder"
            class="search-input"
            @input="handleSearch"
          />
          <div class="search-icon">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <button
            v-if="searchQuery"
            @click="clearSearch"
            class="search-clear"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
      
      <div v-if="showFilters" class="filters-container">
        <slot name="filters" :filters="filters" :updateFilters="updateFilters" :clearFilters="clearFilters" />
      </div>
    </div>
    
    <!-- Loading state -->
    <div v-if="isLoading && isEmpty" class="loading-container">
      <div class="loading-spinner">
        <svg class="animate-spin h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>
      <p class="loading-text">{{ loadingText }}</p>
    </div>
    
    <!-- Empty state -->
    <div v-else-if="isEmpty && !error" class="empty-container">
      <slot name="empty">
        <div class="empty-content">
          <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
          </svg>
          <h3 class="empty-title">{{ emptyTitle }}</h3>
          <p class="empty-message">{{ emptyMessage }}</p>
        </div>
      </slot>
    </div>
    
    <!-- Error state -->
    <div v-else-if="error" class="error-container">
      <slot name="error" :error="error" :retry="retry">
        <div class="error-content">
          <svg class="w-12 h-12 text-red-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
          <h3 class="error-title">Something went wrong</h3>
          <p class="error-message">{{ error.message || 'Failed to load data' }}</p>
          <button @click="retry" class="retry-button">
            Try Again
          </button>
        </div>
      </slot>
    </div>
    
    <!-- Data list -->
    <div v-else-if="hasData" class="data-container">
      <!-- Virtual scrolling container -->
      <div
        v-if="virtualScroll"
        ref="virtualContainer"
        class="virtual-scroll-container"
        :style="{ height: virtualHeight }"
      >
        <div class="virtual-scroll-spacer" :style="{ height: `${getTotalHeight()}px` }">
          <div
            v-for="(item, index) in visibleItems"
            :key="getItemKey(item, visibleRange.start + index)"
            :style="getItemStyle(index)"
            class="virtual-scroll-item"
          >
            <slot :item="item" :index="visibleRange.start + index" />
          </div>
        </div>
      </div>
      
      <!-- Regular scrolling container -->
      <div v-else class="regular-scroll-container">
        <div
          v-for="(item, index) in data"
          :key="getItemKey(item, index)"
          class="list-item"
        >
          <slot :item="item" :index="index" />
        </div>
        
        <!-- Load more trigger -->
        <div
          v-if="hasMore"
          ref="loadMoreTrigger"
          class="load-more-trigger"
        >
          <div v-if="isLoadingMore" class="loading-more">
            <svg class="animate-spin h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Loading more...</span>
          </div>
          <button
            v-else-if="canLoadMore"
            @click="loadMore"
            class="load-more-button"
          >
            Load More
          </button>
        </div>
      </div>
    </div>
    
    <!-- Stats -->
    <div v-if="showStats && hasData" class="stats-container">
      <p class="stats-text">
        Showing {{ data.length }} of {{ totalItems || 'many' }} items
        <span v-if="searchQuery"> for "{{ searchQuery }}"</span>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { useProgressiveLoading, useInfiniteScroll, useVirtualScroll } from '../Composables/useProgressiveLoading';

const props = defineProps({
  endpoint: {
    type: String,
    required: true
  },
  pageSize: {
    type: Number,
    default: 20
  },
  searchPlaceholder: {
    type: String,
    default: 'Search...'
  },
  loadingText: {
    type: String,
    default: 'Loading...'
  },
  emptyTitle: {
    type: String,
    default: 'No items found'
  },
  emptyMessage: {
    type: String,
    default: 'Try adjusting your search or filters'
  },
  showSearch: {
    type: Boolean,
    default: true
  },
  showFilters: {
    type: Boolean,
    default: false
  },
  showStats: {
    type: Boolean,
    default: true
  },
  autoLoad: {
    type: Boolean,
    default: true
  },
  virtualScroll: {
    type: Boolean,
    default: false
  },
  virtualHeight: {
    type: String,
    default: '400px'
  },
  itemHeight: {
    type: Number,
    default: 60
  },
  containerClass: {
    type: String,
    default: ''
  },
  itemKey: {
    type: [String, Function],
    default: 'id'
  }
});

const emit = defineEmits(['loaded', 'error', 'search', 'filter']);

// Progressive loading
const {
  data,
  isLoading,
  isLoadingMore,
  error,
  hasMore,
  totalItems,
  searchQuery,
  filters,
  isEmpty,
  hasData,
  canLoadMore,
  load,
  loadMore,
  refresh,
  search,
  clearSearch,
  updateFilters,
  clearFilters,
  retry
} = useProgressiveLoading(props.endpoint, {
  pageSize: props.pageSize,
  autoLoad: props.autoLoad
});

// Virtual scrolling
const virtualContainer = ref(null);
const {
  visibleItems,
  visibleRange,
  setItems,
  scrollToIndex,
  getItemStyle,
  getTotalHeight
} = useVirtualScroll(virtualContainer, {
  itemHeight: props.itemHeight
});

// Infinite scroll
const loadMoreTrigger = ref(null);
const { observe, unobserve } = useInfiniteScroll(loadMore);

// Methods
const handleSearch = (event) => {
  const query = event.target.value;
  search(query);
  emit('search', query);
};

const getItemKey = (item, index) => {
  if (typeof props.itemKey === 'function') {
    return props.itemKey(item, index);
  }
  return item[props.itemKey] || index;
};

// Watch for data changes in virtual scroll mode
watch(data, (newData) => {
  if (props.virtualScroll) {
    setItems(newData);
  }
}, { immediate: true });

// Watch for filter changes
watch(filters, (newFilters) => {
  emit('filter', newFilters);
}, { deep: true });

// Setup infinite scroll observer
onMounted(async () => {
  await nextTick();
  
  if (!props.virtualScroll && loadMoreTrigger.value) {
    observe(loadMoreTrigger.value);
  }
});

onUnmounted(() => {
  if (loadMoreTrigger.value) {
    unobserve(loadMoreTrigger.value);
  }
});

// Emit events
watch(data, (newData) => {
  emit('loaded', newData);
});

watch(error, (newError) => {
  if (newError) {
    emit('error', newError);
  }
});

// Expose methods for parent components
defineExpose({
  refresh,
  loadMore,
  search,
  clearSearch,
  updateFilters,
  clearFilters,
  scrollToIndex,
  retry
});
</script>

<style scoped>
.progressive-list {
  @apply w-full;
}

.progressive-list-controls {
  @apply mb-4 space-y-4;
}

.search-container {
  @apply relative;
}

.search-input {
  @apply w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}

.search-icon {
  @apply absolute left-3 top-1/2 transform -translate-y-1/2;
}

.search-clear {
  @apply absolute right-3 top-1/2 transform -translate-y-1/2 p-1 hover:bg-gray-100 rounded;
}

.filters-container {
  @apply flex flex-wrap gap-2;
}

.loading-container,
.empty-container,
.error-container {
  @apply flex flex-col items-center justify-center py-12 text-center;
}

.loading-spinner {
  @apply mb-4;
}

.loading-text {
  @apply text-gray-500;
}

.empty-content,
.error-content {
  @apply max-w-sm;
}

.empty-title,
.error-title {
  @apply text-lg font-medium text-gray-900 mb-2;
}

.empty-message,
.error-message {
  @apply text-gray-500 mb-4;
}

.retry-button {
  @apply px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors;
}

.data-container {
  @apply w-full;
}

.virtual-scroll-container {
  @apply relative overflow-auto;
}

.virtual-scroll-spacer {
  @apply relative;
}

.virtual-scroll-item {
  @apply absolute left-0 right-0;
}

.regular-scroll-container {
  @apply space-y-2;
}

.list-item {
  @apply w-full;
}

.load-more-trigger {
  @apply mt-4 flex justify-center;
}

.loading-more {
  @apply flex items-center space-x-2 text-gray-500;
}

.load-more-button {
  @apply px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors;
}

.stats-container {
  @apply mt-4 pt-4 border-t border-gray-200;
}

.stats-text {
  @apply text-sm text-gray-500 text-center;
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .progressive-list-controls {
    @apply mb-3 space-y-3;
  }
  
  .search-input {
    @apply text-sm;
  }
  
  .loading-container,
  .empty-container,
  .error-container {
    @apply py-8;
  }
  
  .empty-title,
  .error-title {
    @apply text-base;
  }
  
  .empty-message,
  .error-message {
    @apply text-sm;
  }
  
  .stats-text {
    @apply text-xs;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .search-input {
    @apply bg-gray-800 border-gray-600 text-white placeholder-gray-400;
  }
  
  .search-input:focus {
    @apply ring-blue-400 border-transparent;
  }
  
  .search-clear:hover {
    @apply bg-gray-700;
  }
  
  .empty-title,
  .error-title {
    @apply text-white;
  }
  
  .empty-message,
  .error-message,
  .loading-text,
  .stats-text {
    @apply text-gray-400;
  }
  
  .load-more-button {
    @apply bg-gray-700 text-gray-200 hover:bg-gray-600;
  }
  
  .stats-container {
    @apply border-gray-700;
  }
}
</style>