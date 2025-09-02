<template>
  <div v-if="hasError" class="error-boundary">
    <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
      <div class="flex justify-center mb-4">
        <svg class="w-12 h-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z" />
        </svg>
      </div>
      
      <h3 class="text-lg font-medium text-red-900 mb-2">
        {{ errorTitle }}
      </h3>
      
      <p class="text-sm text-red-700 mb-4">
        {{ errorMessage }}
      </p>
      
      <div class="flex justify-center space-x-3">
        <button
          @click="retry"
          class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
        >
          Try Again
        </button>
        
        <button
          @click="reset"
          class="px-4 py-2 bg-white text-red-600 text-sm font-medium border border-red-300 rounded-md hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
        >
          Reset
        </button>
      </div>
      
      <details v-if="showDetails" class="mt-4 text-left">
        <summary class="cursor-pointer text-sm text-red-600 hover:text-red-800">
          Technical Details
        </summary>
        <pre class="mt-2 text-xs text-red-800 bg-red-100 p-3 rounded overflow-auto">{{ errorDetails }}</pre>
      </details>
    </div>
  </div>
  
  <slot v-else />
</template>

<script setup>
import { ref, onErrorCaptured } from 'vue'

const props = defineProps({
  showDetails: {
    type: Boolean,
    default: false
  },
  onRetry: {
    type: Function,
    default: null
  },
  onReset: {
    type: Function,
    default: null
  }
})

const emit = defineEmits(['error', 'retry', 'reset'])

const hasError = ref(false)
const errorTitle = ref('')
const errorMessage = ref('')
const errorDetails = ref('')

onErrorCaptured((error, instance, info) => {
  console.error('Calendar Error Boundary caught error:', error, info)
  
  hasError.value = true
  errorTitle.value = 'Calendar Error'
  errorMessage.value = getErrorMessage(error)
  errorDetails.value = `${error.message}\n\nStack: ${error.stack}\n\nComponent: ${info}`
  
  emit('error', { error, instance, info })
  
  // Prevent the error from propagating further
  return false
})

const getErrorMessage = (error) => {
  if (error.response?.status === 403) {
    return 'You do not have permission to access this calendar feature.'
  } else if (error.response?.status === 404) {
    return 'The requested calendar data could not be found.'
  } else if (error.response?.status >= 500) {
    return 'A server error occurred while loading the calendar. Please try again.'
  } else if (error.name === 'NetworkError' || error.code === 'NETWORK_ERROR') {
    return 'Network connection error. Please check your internet connection and try again.'
  } else if (error.name === 'TimeoutError') {
    return 'The request timed out. Please try again.'
  } else {
    return 'An unexpected error occurred while loading the calendar.'
  }
}

const retry = () => {
  if (props.onRetry) {
    props.onRetry()
  }
  emit('retry')
  hasError.value = false
}

const reset = () => {
  if (props.onReset) {
    props.onReset()
  }
  emit('reset')
  hasError.value = false
  errorTitle.value = ''
  errorMessage.value = ''
  errorDetails.value = ''
}

// Expose methods for manual error handling
defineExpose({
  setError: (title, message, details = '') => {
    hasError.value = true
    errorTitle.value = title
    errorMessage.value = message
    errorDetails.value = details
  },
  clearError: () => {
    hasError.value = false
    errorTitle.value = ''
    errorMessage.value = ''
    errorDetails.value = ''
  },
  hasError: () => hasError.value
})
</script>

<style scoped>
.error-boundary {
  @apply w-full;
}

.keyboard-selected {
  @apply ring-2 ring-blue-500 ring-offset-2;
}
</style>