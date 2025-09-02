<template>
  <Teleport to="body">
    <div
      v-if="toasts.length > 0"
      class="toast-container fixed top-4 right-4 z-50 space-y-2"
      role="region"
      aria-label="Notifications"
    >
      <TransitionGroup
        name="toast"
        tag="div"
        class="space-y-2"
      >
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="[
            'max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden',
            getToastClasses(toast.type)
          ]"
          role="alert"
          :aria-live="toast.type === 'error' ? 'assertive' : 'polite'"
        >
          <div class="p-4">
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <component 
                  :is="getIconComponent(toast.type)" 
                  :class="getIconClasses(toast.type)" 
                />
              </div>
              
              <div class="ml-3 w-0 flex-1 pt-0.5">
                <p v-if="toast.title" class="text-sm font-medium text-gray-900">
                  {{ toast.title }}
                </p>
                <p class="text-sm text-gray-500" :class="{ 'mt-1': toast.title }">
                  {{ toast.message }}
                </p>
                
                <!-- Action button -->
                <div v-if="toast.action" class="mt-3 flex space-x-7">
                  <button
                    @click="handleAction(toast)"
                    class="bg-white rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2"
                    :class="getActionClasses(toast.type)"
                  >
                    {{ toast.action.label }}
                  </button>
                </div>
              </div>
              
              <div class="ml-4 flex-shrink-0 flex">
                <button
                  @click="removeToast(toast.id)"
                  class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                  :aria-label="`Close ${toast.title || 'notification'}`"
                >
                  <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
          
          <!-- Progress bar for auto-dismiss -->
          <div
            v-if="!toast.persistent && toast.duration > 0"
            class="h-1 bg-gray-200"
          >
            <div
              class="h-full transition-all ease-linear"
              :class="getProgressClasses(toast.type)"
              :style="getProgressStyle(toast)"
            ></div>
          </div>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import toastService from '@/Services/ToastService.js'

const toasts = computed(() => toastService.getToasts())

const getToastClasses = (type) => {
  switch (type) {
    case 'success':
      return 'border-l-4 border-green-400'
    case 'error':
      return 'border-l-4 border-red-400'
    case 'warning':
      return 'border-l-4 border-yellow-400'
    default:
      return 'border-l-4 border-blue-400'
  }
}

const getIconComponent = (type) => {
  switch (type) {
    case 'success':
      return 'CheckCircleIcon'
    case 'error':
      return 'XCircleIcon'
    case 'warning':
      return 'ExclamationTriangleIcon'
    default:
      return 'InformationCircleIcon'
  }
}

const getIconClasses = (type) => {
  const baseClasses = 'h-5 w-5'
  switch (type) {
    case 'success':
      return `${baseClasses} text-green-400`
    case 'error':
      return `${baseClasses} text-red-400`
    case 'warning':
      return `${baseClasses} text-yellow-400`
    default:
      return `${baseClasses} text-blue-400`
  }
}

const getActionClasses = (type) => {
  switch (type) {
    case 'success':
      return 'text-green-600 hover:text-green-500 focus:ring-green-500'
    case 'error':
      return 'text-red-600 hover:text-red-500 focus:ring-red-500'
    case 'warning':
      return 'text-yellow-600 hover:text-yellow-500 focus:ring-yellow-500'
    default:
      return 'text-blue-600 hover:text-blue-500 focus:ring-blue-500'
  }
}

const getProgressClasses = (type) => {
  switch (type) {
    case 'success':
      return 'bg-green-400'
    case 'error':
      return 'bg-red-400'
    case 'warning':
      return 'bg-yellow-400'
    default:
      return 'bg-blue-400'
  }
}

const getProgressStyle = (toast) => {
  if (toast.persistent || toast.duration <= 0) {
    return { width: '100%' }
  }
  
  const elapsed = Date.now() - toast.timestamp
  const progress = Math.max(0, 100 - (elapsed / toast.duration) * 100)
  
  return { 
    width: `${progress}%`,
    transitionDuration: '100ms'
  }
}

const removeToast = (id) => {
  toastService.remove(id)
}

const handleAction = (toast) => {
  if (toast.action?.onClick) {
    toast.action.onClick()
  }
  removeToast(toast.id)
}
</script>

<script>
// Icon components
const CheckCircleIcon = {
  template: `
    <svg viewBox="0 0 20 20" fill="currentColor">
      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
    </svg>
  `
}

const XCircleIcon = {
  template: `
    <svg viewBox="0 0 20 20" fill="currentColor">
      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
    </svg>
  `
}

const ExclamationTriangleIcon = {
  template: `
    <svg viewBox="0 0 20 20" fill="currentColor">
      <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
    </svg>
  `
}

const InformationCircleIcon = {
  template: `
    <svg viewBox="0 0 20 20" fill="currentColor">
      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
    </svg>
  `
}

export default {
  components: {
    CheckCircleIcon,
    XCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon
  }
}
</script>

<style scoped>
.toast-container {
  max-width: calc(100vw - 2rem);
}

@media (max-width: 640px) {
  .toast-container {
    left: 1rem;
    right: 1rem;
    top: 1rem;
  }
}

/* Toast transitions */
.toast-enter-active {
  transition: all 0.3s ease-out;
}

.toast-leave-active {
  transition: all 0.2s ease-in;
}

.toast-enter-from {
  transform: translateX(100%);
  opacity: 0;
}

.toast-leave-to {
  transform: translateX(100%);
  opacity: 0;
}

.toast-move {
  transition: transform 0.3s ease;
}
</style>