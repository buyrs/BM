<template>
  <div class="loading-skeleton" :aria-label="ariaLabel">
    <!-- Calendar Navigation Skeleton -->
    <div v-if="type === 'navigation'" class="flex items-center justify-between mb-6">
      <div class="flex items-center space-x-4">
        <div class="skeleton-box w-8 h-8 rounded"></div>
        <div class="skeleton-box w-48 h-8 rounded"></div>
        <div class="skeleton-box w-8 h-8 rounded"></div>
      </div>
      <div class="flex space-x-2">
        <div class="skeleton-box w-16 h-8 rounded"></div>
        <div class="skeleton-box w-16 h-8 rounded"></div>
        <div class="skeleton-box w-16 h-8 rounded"></div>
      </div>
    </div>

    <!-- Calendar Filters Skeleton -->
    <div v-else-if="type === 'filters'" class="flex flex-wrap gap-4 mb-6">
      <div class="skeleton-box w-32 h-10 rounded"></div>
      <div class="skeleton-box w-40 h-10 rounded"></div>
      <div class="skeleton-box w-36 h-10 rounded"></div>
      <div class="skeleton-box w-48 h-10 rounded"></div>
    </div>

    <!-- Calendar Grid Skeleton -->
    <div v-else-if="type === 'grid'" class="calendar-grid-skeleton">
      <!-- Days of Week Header -->
      <div class="grid grid-cols-7 gap-px bg-gray-200 rounded-t-lg overflow-hidden mb-px">
        <div
          v-for="i in 7"
          :key="i"
          class="bg-gray-50 p-3 text-center"
        >
          <div class="skeleton-box w-8 h-4 mx-auto rounded"></div>
        </div>
      </div>

      <!-- Calendar Days -->
      <div class="grid grid-cols-7 gap-px bg-gray-200 rounded-b-lg overflow-hidden">
        <div
          v-for="i in 42"
          :key="i"
          class="bg-white min-h-32 p-2"
        >
          <div class="flex justify-between items-start mb-2">
            <div class="skeleton-box w-6 h-6 rounded-full"></div>
            <div v-if="Math.random() > 0.7" class="skeleton-box w-6 h-4 rounded-full"></div>
          </div>
          
          <!-- Random mission skeletons -->
          <div v-if="Math.random() > 0.6" class="space-y-1">
            <div class="skeleton-box w-full h-6 rounded"></div>
            <div v-if="Math.random() > 0.8" class="skeleton-box w-3/4 h-6 rounded"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Mission List Skeleton -->
    <div v-else-if="type === 'mission-list'" class="space-y-4">
      <div
        v-for="i in count"
        :key="i"
        class="bg-white border border-gray-200 rounded-lg p-4"
      >
        <div class="flex items-center justify-between mb-3">
          <div class="skeleton-box w-24 h-5 rounded"></div>
          <div class="skeleton-box w-16 h-6 rounded-full"></div>
        </div>
        <div class="skeleton-box w-full h-4 rounded mb-2"></div>
        <div class="skeleton-box w-3/4 h-4 rounded mb-3"></div>
        <div class="flex items-center space-x-4">
          <div class="skeleton-box w-20 h-4 rounded"></div>
          <div class="skeleton-box w-24 h-4 rounded"></div>
        </div>
      </div>
    </div>

    <!-- Mission Details Skeleton -->
    <div v-else-if="type === 'mission-details'" class="space-y-6">
      <div class="flex items-center justify-between">
        <div class="skeleton-box w-48 h-8 rounded"></div>
        <div class="skeleton-box w-20 h-6 rounded-full"></div>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-4">
          <div class="skeleton-box w-full h-4 rounded"></div>
          <div class="skeleton-box w-3/4 h-4 rounded"></div>
          <div class="skeleton-box w-1/2 h-4 rounded"></div>
        </div>
        <div class="space-y-4">
          <div class="skeleton-box w-full h-4 rounded"></div>
          <div class="skeleton-box w-2/3 h-4 rounded"></div>
          <div class="skeleton-box w-3/4 h-4 rounded"></div>
        </div>
      </div>
      
      <div class="flex space-x-3">
        <div class="skeleton-box w-24 h-10 rounded"></div>
        <div class="skeleton-box w-20 h-10 rounded"></div>
        <div class="skeleton-box w-28 h-10 rounded"></div>
      </div>
    </div>

    <!-- Generic Content Skeleton -->
    <div v-else class="space-y-4">
      <div
        v-for="i in count"
        :key="i"
        class="skeleton-box w-full rounded"
        :style="{ height: `${Math.random() * 20 + 20}px` }"
      ></div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  type: {
    type: String,
    default: 'content',
    validator: (value) => [
      'navigation',
      'filters', 
      'grid',
      'mission-list',
      'mission-details',
      'content'
    ].includes(value)
  },
  count: {
    type: Number,
    default: 3
  },
  ariaLabel: {
    type: String,
    default: 'Loading calendar content'
  }
})
</script>

<style scoped>
.loading-skeleton {
  @apply animate-pulse;
}

.skeleton-box {
  @apply bg-gray-300;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: loading 1.5s infinite;
}

@keyframes loading {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

.calendar-grid-skeleton {
  @apply w-full;
}

/* Reduce motion for users who prefer it */
@media (prefers-reduced-motion: reduce) {
  .skeleton-box {
    animation: none;
    @apply bg-gray-300;
  }
  
  .loading-skeleton {
    @apply animate-none;
  }
}
</style>