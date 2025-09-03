<template>
    <!-- Error Boundary -->
    <div v-if="hasError" class="error-boundary">
        <div class="bg-red-50 border border-red-200 rounded-lg p-6 m-4">
            <div class="flex items-center mb-4">
                <svg class="w-6 h-6 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <h3 class="text-lg font-semibold text-red-800">Something went wrong</h3>
            </div>
            
            <p class="text-red-700 mb-4">
                {{ errorMessage || 'An unexpected error occurred while rendering this component.' }}
            </p>
            
            <div class="flex flex-wrap gap-3">
                <button
                    @click="retry"
                    class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors duration-200"
                >
                    Try Again
                </button>
                
                <button
                    @click="reload"
                    class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors duration-200"
                >
                    Reload Page
                </button>
                
                <button
                    v-if="showDetails"
                    @click="toggleDetails"
                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors duration-200"
                >
                    {{ showErrorDetails ? 'Hide' : 'Show' }} Details
                </button>
            </div>
            
            <div v-if="showErrorDetails && errorDetails" class="mt-4 p-4 bg-gray-100 rounded-md">
                <h4 class="font-semibold text-gray-800 mb-2">Error Details:</h4>
                <pre class="text-xs text-gray-600 overflow-auto max-h-40">{{ errorDetails }}</pre>
            </div>
        </div>
    </div>
    
    <!-- Content -->
    <slot v-else />
</template>

<script setup>
import { ref, onErrorCaptured } from 'vue';
import errorHandler from '@/utils/errorHandler';

const props = defineProps({
    fallbackMessage: {
        type: String,
        default: ''
    },
    showDetails: {
        type: Boolean,
        default: process.env.NODE_ENV === 'development'
    }
});

const hasError = ref(false);
const errorMessage = ref('');
const errorDetails = ref('');
const showErrorDetails = ref(false);

onErrorCaptured((error, instance, info) => {
    hasError.value = true;
    errorMessage.value = props.fallbackMessage || error.message;
    errorDetails.value = `${error.stack}\n\nComponent Info: ${info}`;
    
    // Log error through global error handler
    errorHandler.handleVueError(error, instance, info);
    
    // Prevent error from propagating
    return false;
});

const retry = () => {
    hasError.value = false;
    errorMessage.value = '';
    errorDetails.value = '';
    showErrorDetails.value = false;
};

const reload = () => {
    window.location.reload();
};

const toggleDetails = () => {
    showErrorDetails.value = !showErrorDetails.value;
};
</script>