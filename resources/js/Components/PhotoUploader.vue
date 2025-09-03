<template>
    <div class="space-y-2">
        <div class="flex items-center space-x-2">
            <input
                type="file"
                accept="image/jpeg,image/png,image/gif"
                @change="onFileChange"
                class="hidden"
                ref="fileInput"
                :disabled="loading"
                multiple
            >
            <button
                type="button"
                @click="$refs.fileInput.click()"
                class="px-3 py-2 bg-primary text-white rounded hover:bg-accent text-sm"
                :class="{ 'opacity-50 cursor-not-allowed': loading, 'bg-error-border hover:bg-error-text': required && selectedPhotos.length === 0 }"
                :disabled="loading"
            >
                {{ loading ? 'Processing...' : 'Add Photos' }}
            </button>
            <span v-if="required" class="text-sm text-error-text">*Required</span>
        </div>
        
        <div v-if="selectedPhotos.length > 0 || existingPhotos.length > 0" class="grid grid-cols-3 md:grid-cols-4 gap-2">
            <!-- Existing Photos -->
            <div v-for="photo in existingPhotos" :key="photo.id" class="relative group">
                <img
                    :src="getPhotoUrl(photo)"
                    class="w-full h-20 object-cover rounded border"
                    @error="handleImageError"
                >
                <div class="absolute bottom-0 left-0 right-0 bg-gray-500 bg-opacity-75 text-white text-xs p-1 rounded-b">
                    Existing
                </div>
            </div>
            
            <!-- New Selected Photos -->
            <div v-for="(photo, i) in selectedPhotos" :key="`new-${i}`" class="relative group">
                <img
                    :src="photo.preview"
                    class="w-full h-20 object-cover rounded border"
                    @error="handleImageError"
                >
                <button
                    @click.prevent="removePhoto(i)"
                    class="absolute top-1 right-1 bg-error-border text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-error-text"
                >
                    ×
                </button>
                <div class="absolute bottom-0 left-0 right-0 bg-success-border bg-opacity-75 text-white text-xs p-1 rounded-b">
                    New
                </div>
            </div>
        </div>
        
        <div v-if="error" class="text-error-text text-sm">{{ error }}</div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    existingPhotos: {
        type: Array,
        default: () => []
    },
    required: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['photos-selected'])
const fileInput = ref(null)
const loading = ref(false)
const error = ref('')
const selectedPhotos = ref([])

// Emit selected photos whenever they change
watch(selectedPhotos, (newPhotos) => {
    emit('photos-selected', newPhotos.map(p => p.file))
}, { deep: true })

function onFileChange(e) {
    const files = Array.from(e.target.files)
    if (files.length === 0) return

    loading.value = true
    error.value = ''

    const validTypes = ['image/jpeg', 'image/png', 'image/gif']
    const maxSize = 10 * 1024 * 1024 // 10MB

    const validFiles = files.filter(file => {
        if (!validTypes.includes(file.type)) {
            error.value = 'Please upload valid image files (JPEG, PNG, or GIF)'
            return false
        }
        if (file.size > maxSize) {
            error.value = 'File size must be less than 10MB'
            return false
        }
        return true
    })

    if (validFiles.length === 0) {
        loading.value = false
        return
    }

    // Process each file
    const promises = validFiles.map(file => {
        return new Promise((resolve) => {
            const reader = new FileReader()
            reader.onload = (e) => {
                resolve({
                    file,
                    preview: e.target.result
                })
            }
            reader.onerror = () => {
                resolve(null)
            }
            reader.readAsDataURL(file)
        })
    })

    Promise.all(promises).then(results => {
        const validResults = results.filter(r => r !== null)
        selectedPhotos.value.push(...validResults)
        loading.value = false
    })

    e.target.value = null // reset input
}

function removePhoto(index) {
    selectedPhotos.value.splice(index, 1)
}

function getPhotoUrl(photo) {
    if (typeof photo === 'string') {
        return photo
    }
    if (photo.url) {
        return photo.url
    }
    if (photo.photo_path) {
        return `/storage/${photo.photo_path}`
    }
    if (photo.preview) {
        return photo.preview
    }
    return ''
}

function handleImageError(e) {
    // You can add a placeholder image here
    console.warn('Error loading image:', e.target.src)
}
</script>