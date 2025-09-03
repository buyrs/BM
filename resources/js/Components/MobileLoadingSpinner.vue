<template>
  <div class="mobile-loading" :class="loadingClasses">
    <div class="loading-content">
      <!-- Spinner -->
      <div v-if="type === 'spinner'" class="spinner" :class="spinnerSize">
        <div class="spinner-ring"></div>
        <div class="spinner-ring"></div>
        <div class="spinner-ring"></div>
        <div class="spinner-ring"></div>
      </div>
      
      <!-- Dots -->
      <div v-else-if="type === 'dots'" class="dots-loader">
        <div class="dot"></div>
        <div class="dot"></div>
        <div class="dot"></div>
      </div>
      
      <!-- Pulse -->
      <div v-else-if="type === 'pulse'" class="pulse-loader" :class="pulseSize">
        <div class="pulse-circle"></div>
      </div>
      
      <!-- Progress bar -->
      <div v-else-if="type === 'progress'" class="progress-loader">
        <div class="progress-bar">
          <div 
            class="progress-fill" 
            :style="{ width: `${progress}%` }"
          ></div>
        </div>
        <div v-if="showPercentage" class="progress-text">
          {{ Math.round(progress) }}%
        </div>
      </div>
      
      <!-- Skeleton -->
      <div v-else-if="type === 'skeleton'" class="skeleton-loader">
        <div class="skeleton-line skeleton-title"></div>
        <div class="skeleton-line skeleton-text"></div>
        <div class="skeleton-line skeleton-text short"></div>
      </div>
      
      <!-- Custom content -->
      <div v-else-if="type === 'custom'" class="custom-loader">
        <slot name="loader">
          <div class="default-spinner"></div>
        </slot>
      </div>
      
      <!-- Loading text -->
      <div v-if="message" class="loading-message" :class="messageSize">
        {{ message }}
      </div>
      
      <!-- Loading tips for slow connections -->
      <div v-if="showTips && isSlowConnection" class="loading-tips">
        <p class="tip-text">{{ currentTip }}</p>
      </div>
    </div>
    
    <!-- Overlay for fullscreen loading -->
    <div v-if="overlay" class="loading-overlay" @click="handleOverlayClick"></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  type: {
    type: String,
    default: 'spinner',
    validator: (value) => ['spinner', 'dots', 'pulse', 'progress', 'skeleton', 'custom'].includes(value)
  },
  size: {
    type: String,
    default: 'medium',
    validator: (value) => ['small', 'medium', 'large'].includes(value)
  },
  message: {
    type: String,
    default: ''
  },
  progress: {
    type: Number,
    default: 0,
    validator: (value) => value >= 0 && value <= 100
  },
  showPercentage: {
    type: Boolean,
    default: false
  },
  overlay: {
    type: Boolean,
    default: false
  },
  fullscreen: {
    type: Boolean,
    default: false
  },
  color: {
    type: String,
    default: 'primary'
  },
  showTips: {
    type: Boolean,
    default: true
  },
  dismissible: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['dismiss'])

// Loading tips for slow connections
const loadingTips = [
  'Optimizing for your connection...',
  'Loading essential content first...',
  'Preparing offline capabilities...',
  'Syncing your data...',
  'Almost ready...'
]

const currentTip = ref('')
const tipInterval = ref(null)
const isSlowConnection = ref(false)

// Computed properties
const loadingClasses = computed(() => ({
  'loading-fullscreen': props.fullscreen,
  'loading-overlay-active': props.overlay,
  [`loading-${props.color}`]: true,
  'loading-dismissible': props.dismissible
}))

const spinnerSize = computed(() => ({
  'spinner-small': props.size === 'small',
  'spinner-medium': props.size === 'medium',
  'spinner-large': props.size === 'large'
}))

const pulseSize = computed(() => ({
  'pulse-small': props.size === 'small',
  'pulse-medium': props.size === 'medium',
  'pulse-large': props.size === 'large'
}))

const messageSize = computed(() => ({
  'text-sm': props.size === 'small',
  'text-base': props.size === 'medium',
  'text-lg': props.size === 'large'
}))

// Methods
const detectSlowConnection = () => {
  if ('connection' in navigator) {
    const connection = navigator.connection
    return (
      connection.effectiveType === 'slow-2g' ||
      connection.effectiveType === '2g' ||
      (connection.effectiveType === '3g' && connection.downlink < 1.5)
    )
  }
  return false
}

const startTipRotation = () => {
  if (!props.showTips || !isSlowConnection.value) return
  
  let tipIndex = 0
  currentTip.value = loadingTips[tipIndex]
  
  tipInterval.value = setInterval(() => {
    tipIndex = (tipIndex + 1) % loadingTips.length
    currentTip.value = loadingTips[tipIndex]
  }, 3000)
}

const stopTipRotation = () => {
  if (tipInterval.value) {
    clearInterval(tipInterval.value)
    tipInterval.value = null
  }
}

const handleOverlayClick = () => {
  if (props.dismissible) {
    emit('dismiss')
  }
}

// Lifecycle
onMounted(() => {
  isSlowConnection.value = detectSlowConnection()
  
  if (isSlowConnection.value) {
    // Delay showing tips to avoid flashing
    setTimeout(() => {
      startTipRotation()
    }, 2000)
  }
  
  // Prevent body scroll when fullscreen
  if (props.fullscreen) {
    document.body.style.overflow = 'hidden'
  }
})

onUnmounted(() => {
  stopTipRotation()
  
  if (props.fullscreen) {
    document.body.style.overflow = ''
  }
})
</script>

<style scoped>
.mobile-loading {
  @apply relative flex items-center justify-center;
}

.loading-fullscreen {
  @apply fixed inset-0 z-50 bg-white bg-opacity-95;
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
}

.loading-overlay {
  @apply absolute inset-0 bg-black bg-opacity-20;
}

.loading-content {
  @apply relative z-10 flex flex-col items-center justify-center p-6 text-center;
}

/* Spinner Styles */
.spinner {
  @apply relative inline-block;
}

.spinner-small {
  @apply w-8 h-8;
}

.spinner-medium {
  @apply w-12 h-12;
}

.spinner-large {
  @apply w-16 h-16;
}

.spinner-ring {
  @apply absolute border-2 border-solid rounded-full;
  border-color: currentColor transparent transparent transparent;
  animation: spinner-rotate 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
}

.spinner-small .spinner-ring {
  @apply w-8 h-8 border-2;
}

.spinner-medium .spinner-ring {
  @apply w-12 h-12 border-2;
}

.spinner-large .spinner-ring {
  @apply w-16 h-16 border-4;
}

.spinner-ring:nth-child(1) {
  animation-delay: -0.45s;
}

.spinner-ring:nth-child(2) {
  animation-delay: -0.3s;
}

.spinner-ring:nth-child(3) {
  animation-delay: -0.15s;
}

@keyframes spinner-rotate {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

/* Dots Loader */
.dots-loader {
  @apply flex space-x-2;
}

.dot {
  @apply w-3 h-3 bg-current rounded-full;
  animation: dots-bounce 1.4s ease-in-out infinite both;
}

.dot:nth-child(1) {
  animation-delay: -0.32s;
}

.dot:nth-child(2) {
  animation-delay: -0.16s;
}

@keyframes dots-bounce {
  0%, 80%, 100% {
    transform: scale(0);
  }
  40% {
    transform: scale(1);
  }
}

/* Pulse Loader */
.pulse-loader {
  @apply relative;
}

.pulse-small {
  @apply w-8 h-8;
}

.pulse-medium {
  @apply w-12 h-12;
}

.pulse-large {
  @apply w-16 h-16;
}

.pulse-circle {
  @apply w-full h-full bg-current rounded-full;
  animation: pulse-scale 1s ease-in-out infinite;
}

@keyframes pulse-scale {
  0% {
    transform: scale(0);
    opacity: 1;
  }
  100% {
    transform: scale(1);
    opacity: 0;
  }
}

/* Progress Loader */
.progress-loader {
  @apply w-full max-w-xs;
}

.progress-bar {
  @apply w-full h-2 bg-gray-200 rounded-full overflow-hidden;
}

.progress-fill {
  @apply h-full bg-current rounded-full transition-all duration-300 ease-out;
}

.progress-text {
  @apply mt-2 text-sm font-medium;
}

/* Skeleton Loader */
.skeleton-loader {
  @apply w-full max-w-sm space-y-3;
}

.skeleton-line {
  @apply h-4 bg-gray-200 rounded animate-pulse;
}

.skeleton-title {
  @apply h-6 w-3/4;
}

.skeleton-text {
  @apply w-full;
}

.skeleton-text.short {
  @apply w-2/3;
}

/* Loading Message */
.loading-message {
  @apply mt-4 font-medium text-gray-700;
}

/* Loading Tips */
.loading-tips {
  @apply mt-6 max-w-xs;
}

.tip-text {
  @apply text-sm text-gray-500 italic;
  animation: tip-fade 3s ease-in-out infinite;
}

@keyframes tip-fade {
  0%, 100% {
    opacity: 0.7;
  }
  50% {
    opacity: 1;
  }
}

/* Color Variants */
.loading-primary {
  @apply text-primary;
}

.loading-secondary {
  @apply text-gray-600;
}

.loading-success {
  @apply text-success-text;
}

.loading-warning {
  @apply text-warning-text;
}

.loading-error {
  @apply text-error-text;
}

/* Dismissible indicator */
.loading-dismissible .loading-content::after {
  content: 'Tap to dismiss';
  @apply absolute -bottom-8 left-1/2 transform -translate-x-1/2 text-xs text-gray-400;
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .loading-content {
    @apply p-4;
  }
  
  .loading-message {
    @apply text-sm;
  }
  
  .loading-tips {
    @apply mt-4;
  }
  
  .tip-text {
    @apply text-xs;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .spinner-ring,
  .dot,
  .pulse-circle,
  .progress-fill,
  .skeleton-line,
  .tip-text {
    animation: none;
  }
  
  .pulse-circle {
    @apply opacity-50;
  }
  
  .skeleton-line {
    @apply bg-gray-300;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .loading-fullscreen {
    @apply bg-white;
  }
  
  .loading-message {
    @apply text-black font-bold;
  }
  
  .spinner-ring,
  .dot,
  .pulse-circle {
    @apply border-black;
    color: black;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .loading-fullscreen {
    @apply bg-gray-900 bg-opacity-95;
  }
  
  .loading-message {
    @apply text-gray-200;
  }
  
  .tip-text {
    @apply text-gray-400;
  }
  
  .progress-bar {
    @apply bg-gray-700;
  }
  
  .skeleton-line {
    @apply bg-gray-700;
  }
}
</style>