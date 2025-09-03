<template>
    <div class="p-4 border rounded">
        <h3 class="text-lg font-semibold mb-4">Error Handling Test</h3>
        
        <div class="space-y-4">
            <button 
                @click="triggerVueError"
                class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600"
            >
                Trigger Vue Error
            </button>
            
            <button 
                @click="triggerApiError"
                class="px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600"
            >
                Trigger API Error
            </button>
            
            <button 
                @click="testLoadingState"
                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
            >
                Test Loading State
            </button>
            
            <div v-if="loading" class="text-blue-600">
                Loading...
            </div>
            
            <div v-if="error" class="text-red-600">
                Error: {{ error }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useApiState } from '@/composables/useApiState';
import errorHandler from '@/utils/errorHandler';

const { loading, error, get } = useApiState();

const triggerVueError = () => {
    // This will trigger a Vue error
    throw new Error('Test Vue error for error boundary');
};

const triggerApiError = async () => {
    try {
        // This will trigger an API error
        await get('/api/nonexistent-endpoint');
    } catch (err) {
        // Error is already handled by useApiState
    }
};

const testLoadingState = async () => {
    try {
        // Simulate a loading state
        await get('/api/test-endpoint', {}, {
            showLoading: true,
            onSuccess: () => {
                errorHandler.showToast('Success!', 'success');
            }
        });
    } catch (err) {
        // Error handled by useApiState
    }
};
</script>