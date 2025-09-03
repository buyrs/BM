<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isLoading" class="fixed top-0 left-0 right-0 z-50">
                <div class="bg-primary h-1">
                    <div class="bg-accent h-full animate-pulse" :style="{ width: `${progress}%` }"></div>
                </div>
                
                <!-- Optional loading message -->
                <div v-if="showMessage" class="bg-white shadow-md px-4 py-2 text-center">
                    <p class="text-sm text-text-secondary">{{ message }}</p>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const isLoading = ref(false);
const progress = ref(0);
const message = ref('Loading...');
const showMessage = ref(false);

let progressInterval = null;

const startLoading = (loadingMessage = 'Loading...') => {
    isLoading.value = true;
    progress.value = 0;
    message.value = loadingMessage;
    showMessage.value = !!loadingMessage;
    
    // Simulate progress
    progressInterval = setInterval(() => {
        if (progress.value < 90) {
            progress.value += Math.random() * 10;
        }
    }, 200);
};

const stopLoading = () => {
    if (progressInterval) {
        clearInterval(progressInterval);
        progressInterval = null;
    }
    
    progress.value = 100;
    
    // Hide after a short delay to show completion
    setTimeout(() => {
        isLoading.value = false;
        progress.value = 0;
        showMessage.value = false;
    }, 300);
};

const handleGlobalLoading = (event) => {
    if (event.detail.loading) {
        startLoading(event.detail.message);
    } else {
        stopLoading();
    }
};

onMounted(() => {
    window.addEventListener('global-loading-change', handleGlobalLoading);
    
    // Listen for Inertia page loading
    document.addEventListener('inertia:start', () => startLoading('Loading page...'));
    document.addEventListener('inertia:finish', stopLoading);
});

onUnmounted(() => {
    window.removeEventListener('global-loading-change', handleGlobalLoading);
    document.removeEventListener('inertia:start', startLoading);
    document.removeEventListener('inertia:finish', stopLoading);
    
    if (progressInterval) {
        clearInterval(progressInterval);
    }
});
</script>