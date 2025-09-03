<template>
  <div class="lazy-image-container" :class="containerClass">
    <img
      ref="imageRef"
      :class="[
        'lazy-image',
        {
          'lazy-loading': isLoading,
          'lazy-loaded': isLoaded,
          'lazy-error': hasError
        },
        imageClass
      ]"
      :alt="alt"
      :style="imageStyle"
      @load="handleLoad"
      @error="handleError"
    />
    
    <!-- Loading placeholder -->
    <div
      v-if="isLoading && showPlaceholder"
      class="lazy-image-placeholder"
      :style="placeholderStyle"
    >
      <div class="lazy-image-spinner">
        <svg class="animate-spin h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>
    </div>
    
    <!-- Error placeholder -->
    <div
      v-if="hasError"
      class="lazy-image-error"
      :style="placeholderStyle"
    >
      <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
      <p class="text-sm text-gray-500 mt-2">Failed to load image</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { generateSrcSet, getOptimalFormat } from '../utils/imageOptimization';

const props = defineProps({
  src: {
    type: String,
    required: true
  },
  alt: {
    type: String,
    default: ''
  },
  width: {
    type: [Number, String],
    default: null
  },
  height: {
    type: [Number, String],
    default: null
  },
  sizes: {
    type: String,
    default: '100vw'
  },
  placeholder: {
    type: String,
    default: null
  },
  showPlaceholder: {
    type: Boolean,
    default: true
  },
  lazy: {
    type: Boolean,
    default: true
  },
  progressive: {
    type: Boolean,
    default: false
  },
  quality: {
    type: Number,
    default: 80
  },
  format: {
    type: String,
    default: null
  },
  containerClass: {
    type: String,
    default: ''
  },
  imageClass: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['load', 'error']);

const imageRef = ref(null);
const isLoading = ref(true);
const isLoaded = ref(false);
const hasError = ref(false);
const observer = ref(null);

const imageStyle = computed(() => {
  const style = {};
  
  if (props.width) style.width = typeof props.width === 'number' ? `${props.width}px` : props.width;
  if (props.height) style.height = typeof props.height === 'number' ? `${props.height}px` : props.height;
  
  if (isLoading.value) {
    style.opacity = '0';
  } else if (isLoaded.value) {
    style.opacity = '1';
    style.transition = 'opacity 0.3s ease-in-out';
  }
  
  return style;
});

const placeholderStyle = computed(() => {
  const style = {
    position: 'absolute',
    top: '0',
    left: '0',
    width: '100%',
    height: '100%',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#f3f4f6',
    borderRadius: '0.375rem'
  };
  
  if (props.width) style.width = typeof props.width === 'number' ? `${props.width}px` : props.width;
  if (props.height) style.height = typeof props.height === 'number' ? `${props.height}px` : props.height;
  
  return style;
});

const optimizedSrc = computed(() => {
  if (!props.src) return '';
  
  const format = props.format || getOptimalFormat();
  const quality = props.quality;
  
  // Add optimization parameters
  const url = new URL(props.src, window.location.origin);
  url.searchParams.set('f', format);
  url.searchParams.set('q', quality.toString());
  
  if (props.width) {
    url.searchParams.set('w', props.width.toString());
  }
  
  return url.toString();
});

const srcSet = computed(() => {
  if (!props.src) return '';
  return generateSrcSet(props.src, [300, 600, 1200]);
});

function handleLoad() {
  isLoading.value = false;
  isLoaded.value = true;
  hasError.value = false;
  emit('load');
}

function handleError() {
  isLoading.value = false;
  isLoaded.value = false;
  hasError.value = true;
  emit('error');
}

function loadImage() {
  if (!imageRef.value || isLoaded.value) return;
  
  const img = imageRef.value;
  
  // Set placeholder first if provided
  if (props.placeholder && !img.src) {
    img.src = props.placeholder;
  }
  
  // Load the actual image
  const actualImage = new Image();
  
  actualImage.onload = () => {
    img.src = optimizedSrc.value;
    img.srcset = srcSet.value;
    img.sizes = props.sizes;
    handleLoad();
  };
  
  actualImage.onerror = handleError;
  actualImage.src = optimizedSrc.value;
}

function setupIntersectionObserver() {
  if (!props.lazy || !imageRef.value) return;
  
  observer.value = new IntersectionObserver(
    (entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          loadImage();
          observer.value?.unobserve(entry.target);
        }
      });
    },
    {
      root: null,
      rootMargin: '50px',
      threshold: 0.1
    }
  );
  
  observer.value.observe(imageRef.value);
}

onMounted(() => {
  if (props.lazy) {
    setupIntersectionObserver();
  } else {
    loadImage();
  }
});

onUnmounted(() => {
  if (observer.value) {
    observer.value.disconnect();
  }
});

watch(() => props.src, () => {
  if (imageRef.value) {
    isLoading.value = true;
    isLoaded.value = false;
    hasError.value = false;
    
    if (props.lazy) {
      setupIntersectionObserver();
    } else {
      loadImage();
    }
  }
});
</script>

<style scoped>
.lazy-image-container {
  position: relative;
  overflow: hidden;
}

.lazy-image {
  width: 100%;
  height: auto;
  display: block;
}

.lazy-image.lazy-loading {
  opacity: 0;
}

.lazy-image.lazy-loaded {
  opacity: 1;
  transition: opacity 0.3s ease-in-out;
}

.lazy-image-placeholder,
.lazy-image-error {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background-color: #f3f4f6;
  border-radius: 0.375rem;
}

.lazy-image-spinner {
  display: flex;
  align-items: center;
  justify-content: center;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.lazy-loading .lazy-image-placeholder {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>