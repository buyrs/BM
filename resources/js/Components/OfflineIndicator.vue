<template>
  <Transition name="offline-slide">
    <div
      v-if="!isOnline"
      class="offline-indicator safe-area-top"
      :class="indicatorClasses"
    >
      <div class="offline-content">
        <div class="offline-icon">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path 
              stroke-linecap="round" 
              stroke-linejoin="round" 
              stroke-width="2" 
              d="M18.364 5.636l-12.728 12.728m0 0L5.636 18.364m12.728-12.728L5.636 5.636m12.728 12.728L18.364 18.364" 
            />
          </svg>
        </div>
        
        <div class="offline-text">
          <span class="offline-message">{{ currentMessage }}</span>
          <span v-if="pendingSyncCount > 0" class="sync-count">
            {{ pendingSyncCount }} items to sync
          </span>
        </div>
        
        <div class="offline-actions">
          <button
            v-if="showRetryButton"
            @click="handleRetry"
            class="retry-btn touch-target touch-manipulation"
            :disabled="isRetrying"
          >
            <svg 
              v-if="isRetrying" 
              class="w-3 h-3 animate-spin" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <svg 
              v-else 
              class="w-3 h-3" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span class="sr-only">Retry connection</span>
          </button>
          
          <button
            v-if="dismissible"
            @click="handleDismiss"
            class="dismiss-btn touch-target touch-manipulation"
          >
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <span class="sr-only">Dismiss</span>
          </button>
        </div>
      </div>
      
      <!-- Progress bar for sync operations -->
      <div v-if="showSyncProgress && syncProgress > 0" class="sync-progress">
        <div 
          class="sync-progress-bar" 
          :style="{ width: `${syncProgress}%` }"
        ></div>
      </div>
    </div>
  </Transition>
  
  <!-- Reconnection toast -->
  <Transition name="toast-slide">
    <div
      v-if="showReconnectedToast"
      class="reconnected-toast safe-area-top"
    >
      <div class="toast-content">
        <div class="toast-icon">
          <svg class="w-4 h-4 text-success-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <span class="toast-message">Back online! Syncing data...</span>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'

const props = defineProps({
  pendingSyncCount: {
    type: Number,
    default: 0
  },
  syncProgress: {
    type: Number,
    default: 0,
    validator: (value) => value >= 0 && value <= 100
  },
  showSyncProgress: {
    type: Boolean,
    default: false
  },
  showRetryButton: {
    type: Boolean,
    default: true
  },
  dismissible: {
    type: Boolean,
    default: false
  },
  position: {
    type: String,
    default: 'top',
    validator: (value) => ['top', 'bottom'].includes(value)
  },
  variant: {
    type: String,
    default: 'warning',
    validator: (value) => ['warning', 'error', 'info'].includes(value)
  },
  persistent: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['retry', 'dismiss', 'online', 'offline'])

// State
const isOnline = ref(navigator.onLine)
const isRetrying = ref(false)
const isDismissed = ref(false)
const showReconnectedToast = ref(false)
const messageIndex = ref(0)

// Messages that rotate when offline
const offlineMessages = [
  'You\'re offline',
  'Working offline',
  'No internet connection',
  'Offline mode active'
]

const currentMessage = computed(() => {
  if (props.pendingSyncCount > 0) {
    return 'Offline - Changes saved locally'
  }
  return offlineMessages[messageIndex.value]
})

const indicatorClasses = computed(() => ({
  'offline-bottom': props.position === 'bottom',
  'offline-dismissed': isDismissed.value,
  [`offline-${props.variant}`]: true,
  'offline-persistent': props.persistent
}))

// Methods
const handleOnline = () => {
  const wasOffline = !isOnline.value
  isOnline.value = true
  isDismissed.value = false
  
  if (wasOffline) {
    showReconnectedToast.value = true
    emit('online')
    
    // Hide reconnected toast after 3 seconds
    setTimeout(() => {
      showReconnectedToast.value = false
    }, 3000)
    
    // Vibrate if supported
    if ('vibrate' in navigator) {
      navigator.vibrate([100, 50, 100])
    }
  }
}

const handleOffline = () => {
  isOnline.value = false
  isDismissed.value = false
  showReconnectedToast.value = false
  emit('offline')
  
  // Start message rotation
  startMessageRotation()
  
  // Vibrate if supported
  if ('vibrate' in navigator) {
    navigator.vibrate([200, 100, 200])
  }
}

const handleRetry = async () => {
  if (isRetrying.value) return
  
  isRetrying.value = true
  emit('retry')
  
  // Simulate retry attempt
  try {
    // Try to fetch a small resource to test connectivity
    const response = await fetch('/api/ping', {
      method: 'HEAD',
      cache: 'no-cache',
      timeout: 5000
    })
    
    if (response.ok) {
      handleOnline()
    }
  } catch (error) {
    console.log('Retry failed:', error)
  } finally {
    setTimeout(() => {
      isRetrying.value = false
    }, 1000)
  }
}

const handleDismiss = () => {
  if (!props.persistent) {
    isDismissed.value = true
    emit('dismiss')
  }
}

const startMessageRotation = () => {
  if (isOnline.value) return
  
  const interval = setInterval(() => {
    if (isOnline.value) {
      clearInterval(interval)
      return
    }
    
    messageIndex.value = (messageIndex.value + 1) % offlineMessages.length
  }, 4000)
}

// Watch for sync count changes
watch(() => props.pendingSyncCount, (newCount, oldCount) => {
  if (newCount > oldCount && newCount > 0) {
    // New items added to sync queue
    if ('vibrate' in navigator) {
      navigator.vibrate(50)
    }
  }
})

// Lifecycle
onMounted(() => {
  window.addEventListener('online', handleOnline)
  window.addEventListener('offline', handleOffline)
  
  // Initial check
  if (!navigator.onLine) {
    handleOffline()
  }
})

onUnmounted(() => {
  window.removeEventListener('online', handleOnline)
  window.removeEventListener('offline', handleOffline)
})
</script>

<style scoped>
.offline-indicator {
  @apply fixed left-0 right-0 z-50 shadow-lg;
  top: 0;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}

.offline-bottom {
  top: auto;
  bottom: 0;
}

.offline-dismissed {
  transform: translateY(-100%);
}

.offline-bottom.offline-dismissed {
  transform: translateY(100%);
}

.offline-content {
  @apply flex items-center justify-between px-4 py-3;
}

.offline-icon {
  @apply flex-shrink-0 mr-3;
}

.offline-text {
  @apply flex-1 min-w-0;
}

.offline-message {
  @apply block text-sm font-medium;
}

.sync-count {
  @apply block text-xs opacity-90 mt-0.5;
}

.offline-actions {
  @apply flex items-center space-x-2 ml-3;
}

.retry-btn,
.dismiss-btn {
  @apply p-1.5 rounded-full hover:bg-black hover:bg-opacity-10 transition-colors;
}

.retry-btn:disabled {
  @apply opacity-50 cursor-not-allowed;
}

.sync-progress {
  @apply absolute bottom-0 left-0 right-0 h-1 bg-black bg-opacity-20;
}

.sync-progress-bar {
  @apply h-full bg-white bg-opacity-80 transition-all duration-300;
}

/* Variant styles */
.offline-warning {
  @apply bg-warning-bg text-warning-text border-b border-warning-border;
}

.offline-error {
  @apply bg-error-bg text-error-text border-b border-error-border;
}

.offline-info {
  @apply bg-info-bg text-info-text border-b border-info-border;
}

/* Reconnected toast */
.reconnected-toast {
  @apply fixed left-4 right-4 z-50 bg-success-bg text-success-text border border-success-border rounded-lg shadow-lg;
  top: 4rem;
}

.toast-content {
  @apply flex items-center px-4 py-3;
}

.toast-icon {
  @apply flex-shrink-0 mr-3;
}

.toast-message {
  @apply text-sm font-medium;
}

/* Transitions */
.offline-slide-enter-active,
.offline-slide-leave-active {
  @apply transition-transform duration-300 ease-in-out;
}

.offline-slide-enter-from {
  transform: translateY(-100%);
}

.offline-slide-leave-to {
  transform: translateY(-100%);
}

.offline-bottom.offline-slide-enter-from,
.offline-bottom.offline-slide-leave-to {
  transform: translateY(100%);
}

.toast-slide-enter-active,
.toast-slide-leave-active {
  @apply transition-all duration-300 ease-in-out;
}

.toast-slide-enter-from,
.toast-slide-leave-to {
  @apply opacity-0 transform scale-95 translate-y-2;
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .offline-content {
    @apply px-3 py-2;
  }
  
  .offline-message {
    @apply text-xs;
  }
  
  .sync-count {
    @apply text-xs;
  }
  
  .reconnected-toast {
    @apply left-2 right-2;
    top: 3rem;
  }
  
  .toast-content {
    @apply px-3 py-2;
  }
  
  .toast-message {
    @apply text-xs;
  }
}

/* Persistent indicator styling */
.offline-persistent {
  @apply border-l-4;
}

.offline-persistent.offline-warning {
  @apply border-l-warning-border;
}

.offline-persistent.offline-error {
  @apply border-l-error-border;
}

.offline-persistent.offline-info {
  @apply border-l-info-border;
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .offline-indicator {
    @apply border-2 border-current;
  }
  
  .retry-btn,
  .dismiss-btn {
    @apply border border-current;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .offline-slide-enter-active,
  .offline-slide-leave-active,
  .toast-slide-enter-active,
  .toast-slide-leave-active {
    @apply transition-none;
  }
  
  .sync-progress-bar {
    @apply transition-none;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .offline-indicator {
    backdrop-filter: blur(10px) brightness(0.8);
    -webkit-backdrop-filter: blur(10px) brightness(0.8);
  }
  
  .reconnected-toast {
    @apply bg-gray-800 text-green-400 border-green-600;
  }
  
  .retry-btn:hover,
  .dismiss-btn:hover {
    @apply bg-white bg-opacity-20;
  }
}
</style>