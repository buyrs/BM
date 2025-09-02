<template>
  <div class="empty-state text-center py-12">
    <div class="flex justify-center mb-4">
      <component 
        :is="iconComponent" 
        class="w-16 h-16 text-gray-400"
        :class="iconClass"
      />
    </div>
    
    <h3 class="text-lg font-medium text-gray-900 mb-2">
      {{ title }}
    </h3>
    
    <p class="text-sm text-gray-500 mb-6 max-w-md mx-auto">
      {{ description }}
    </p>
    
    <div v-if="showActions" class="flex justify-center space-x-3">
      <button
        v-if="primaryAction"
        @click="$emit('primary-action')"
        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
      >
        {{ primaryAction }}
      </button>
      
      <button
        v-if="secondaryAction"
        @click="$emit('secondary-action')"
        class="px-4 py-2 bg-white text-gray-700 text-sm font-medium border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
      >
        {{ secondaryAction }}
      </button>
    </div>
    
    <!-- Additional content slot -->
    <div v-if="$slots.default" class="mt-6">
      <slot />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  type: {
    type: String,
    default: 'no-missions',
    validator: (value) => [
      'no-missions',
      'no-results',
      'no-checkers',
      'network-error',
      'permission-denied',
      'loading-failed'
    ].includes(value)
  },
  title: {
    type: String,
    default: ''
  },
  description: {
    type: String,
    default: ''
  },
  primaryAction: {
    type: String,
    default: ''
  },
  secondaryAction: {
    type: String,
    default: ''
  },
  showActions: {
    type: Boolean,
    default: true
  },
  iconClass: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['primary-action', 'secondary-action'])

const iconComponent = computed(() => {
  switch (props.type) {
    case 'no-missions':
      return 'CalendarIcon'
    case 'no-results':
      return 'SearchIcon'
    case 'no-checkers':
      return 'UsersIcon'
    case 'network-error':
      return 'ExclamationTriangleIcon'
    case 'permission-denied':
      return 'LockClosedIcon'
    case 'loading-failed':
      return 'ExclamationCircleIcon'
    default:
      return 'CalendarIcon'
  }
})

const computedTitle = computed(() => {
  if (props.title) return props.title
  
  switch (props.type) {
    case 'no-missions':
      return 'No missions found'
    case 'no-results':
      return 'No results found'
    case 'no-checkers':
      return 'No checkers available'
    case 'network-error':
      return 'Connection error'
    case 'permission-denied':
      return 'Access denied'
    case 'loading-failed':
      return 'Loading failed'
    default:
      return 'No data available'
  }
})

const computedDescription = computed(() => {
  if (props.description) return props.description
  
  switch (props.type) {
    case 'no-missions':
      return 'There are no missions scheduled for this time period. Create a new mission to get started.'
    case 'no-results':
      return 'No missions match your current filters. Try adjusting your search criteria or clearing filters.'
    case 'no-checkers':
      return 'No checkers are available for assignment. Please contact your administrator.'
    case 'network-error':
      return 'Unable to connect to the server. Please check your internet connection and try again.'
    case 'permission-denied':
      return 'You do not have permission to view this calendar. Please contact your administrator.'
    case 'loading-failed':
      return 'Failed to load calendar data. Please try refreshing the page.'
    default:
      return 'No information is available at this time.'
  }
})

const computedPrimaryAction = computed(() => {
  if (props.primaryAction) return props.primaryAction
  
  switch (props.type) {
    case 'no-missions':
      return 'Create Mission'
    case 'no-results':
      return 'Clear Filters'
    case 'network-error':
    case 'loading-failed':
      return 'Try Again'
    default:
      return ''
  }
})

const computedSecondaryAction = computed(() => {
  if (props.secondaryAction) return props.secondaryAction
  
  switch (props.type) {
    case 'no-results':
      return 'Reset Search'
    case 'network-error':
    case 'loading-failed':
      return 'Refresh Page'
    default:
      return ''
  }
})
</script>

<script>
// Icon components (using Heroicons)
const CalendarIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
    </svg>
  `
}

const SearchIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
    </svg>
  `
}

const UsersIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
    </svg>
  `
}

const ExclamationTriangleIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z" />
    </svg>
  `
}

const LockClosedIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
    </svg>
  `
}

const ExclamationCircleIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
  `
}

export default {
  components: {
    CalendarIcon,
    SearchIcon,
    UsersIcon,
    ExclamationTriangleIcon,
    LockClosedIcon,
    ExclamationCircleIcon
  }
}
</script>

<style scoped>
.empty-state {
  @apply min-h-64 flex flex-col justify-center;
}
</style>