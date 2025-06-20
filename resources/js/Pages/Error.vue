<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 flex items-center justify-center">
        <div class="max-w-2xl w-full p-6 bg-white dark:bg-gray-800 rounded-lg shadow-lg">
            <h1 class="text-3xl font-bold text-red-600 dark:text-red-400 mb-4">
                Error {{ status }}
            </h1>
            <p class="text-gray-600 dark:text-gray-300 mb-6">
                {{ message }}
            </p>
            <div class="flex justify-end">
                <Link
                    :href="route('dashboard')"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Return to Dashboard
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    status: {
        type: Number,
        required: true
    },
    message: {
        type: String,
        required: true
    }
});
</script> 