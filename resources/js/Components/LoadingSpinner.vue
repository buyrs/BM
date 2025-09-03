<template>
    <!-- Loading State -->
    <div class="loading-container flex items-center justify-center" :class="containerClass">
        <div class="flex flex-col items-center">
            <!-- Spinner -->
            <div 
                class="animate-spin rounded-full border-b-2 mb-4"
                :class="[
                    size === 'sm' ? 'h-6 w-6' : size === 'lg' ? 'h-16 w-16' : 'h-12 w-12',
                    `border-${color}`
                ]"
                role="status"
                :aria-label="message || 'Loading'"
            ></div>
            
            <!-- Loading Message -->
            <p v-if="message" class="text-text-secondary text-center" :class="textSize">
                {{ message }}
            </p>
            
            <!-- Progress Bar (optional) -->
            <div v-if="showProgress && progress !== null" class="w-full max-w-xs mt-4">
                <div class="bg-gray-200 rounded-full h-2">
                    <div 
                        class="bg-primary h-2 rounded-full transition-all duration-300"
                        :style="{ width: `${Math.min(100, Math.max(0, progress))}%` }"
                    ></div>
                </div>
                <p class="text-xs text-text-secondary text-center mt-1">
                    {{ Math.round(progress) }}%
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    message: {
        type: String,
        default: 'Loading...'
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value)
    },
    color: {
        type: String,
        default: 'primary'
    },
    fullHeight: {
        type: Boolean,
        default: false
    },
    showProgress: {
        type: Boolean,
        default: false
    },
    progress: {
        type: Number,
        default: null
    }
});

const containerClass = computed(() => ({
    'min-h-screen': props.fullHeight,
    'p-8': props.fullHeight,
    'p-4': !props.fullHeight
}));

const textSize = computed(() => ({
    'text-sm': props.size === 'sm',
    'text-base': props.size === 'md',
    'text-lg': props.size === 'lg'
}));
</script>