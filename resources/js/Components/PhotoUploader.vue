<template>
    <div class="flex space-x-2 items-center">
        <input
            type="file"
            accept="image/jpeg,image/png,image/gif"
            @change="onFileChange"
            class="hidden"
            ref="fileInput"
            :disabled="loading"
        >
        <button
            type="button"
            @click="$refs.fileInput.click()"
            class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300 text-sm"
            :class="{ 'opacity-50 cursor-not-allowed': loading }"
            :disabled="loading"
        >
            {{ loading ? 'Uploading...' : 'Add Photo' }}
        </button>
        <div class="flex space-x-2">
            <div v-for="(photo, i) in photos" :key="photo.id || i" class="relative group">
                <img
                    :src="getPhotoUrl(photo)"
                    class="w-12 h-12 object-cover rounded border"
                    @error="handleImageError"
                >
                <button
                    v-if="canDelete && !loading"
                    @click.prevent="remove(photo)"
                    class="absolute top-0 right-0 p-1 bg-white bg-opacity-80 rounded-bl text-xs text-red-600 hidden group-hover:block"
                >
                    ×
                </button>
                <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-75">
                    <svg class="animate-spin h-5 w-5 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div v-if="error" class="text-red-500 text-sm">{{ error }}</div>
    </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
    photos: {
        type: Array,
        default: () => []
    },
    canDelete: {
        type: Boolean,
        default: true
    }
})

const emit = defineEmits(['upload', 'delete'])
const fileInput = ref(null)
const loading = ref(false)
const error = ref('')

function onFileChange(e) {
    const file = e.target.files[0]
    if (file) {
        // Validate file type
        const validTypes = ['image/jpeg', 'image/png', 'image/gif']
        if (!validTypes.includes(file.type)) {
            error.value = 'Please upload a valid image file (JPEG, PNG, or GIF)'
            return
        }

        // Validate file size (5MB max)
        if (file.size > 5 * 1024 * 1024) {
            error.value = 'File size must be less than 5MB'
            return
        }

        loading.value = true
        error.value = ''

        // Convert to base64
        const reader = new FileReader()
        reader.onload = (e) => {
            emit('upload', {
                file,
                data: e.target.result
            })
            loading.value = false
        }
        reader.onerror = () => {
            error.value = 'Error reading file'
            loading.value = false
        }
        reader.readAsDataURL(file)
    }
    e.target.value = null // reset input for next select
}

function remove(photo) {
    if (loading.value) return
    loading.value = true
    try {
        emit('delete', photo.id ?? photo)
    } catch (e) {
        error.value = 'Error removing photo'
    } finally {
        loading.value = false
    }
}

function getPhotoUrl(photo) {
    if (typeof photo === 'string') {
        return photo
    }
    if (photo.url) {
        return photo.url
    }
    if (photo.photo_path) {
        // Adapt this to your public upload path if needed
        return `/storage/${photo.photo_path}`
    }
    if (photo.data) {
        // base64
        return `data:image/jpeg;base64,${photo.data}`
    }
    return ''
}

function handleImageError(e) {
    e.target.src = '/images/placeholder.png' // Add a placeholder image
    error.value = 'Error loading image'
}
</script>