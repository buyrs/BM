<template>
    <div class="flex space-x-2 items-center">
        <input
            type="file"
            accept="image/*"
            @change="onFileChange"
            class="hidden"
            ref="fileInput"
        >
        <button
            type="button"
            @click="$refs.fileInput.click()"
            class="px-2 py-1 bg-gray-200 rounded hover:bg-gray-300 text-sm"
        >
            Add Photo
        </button>
        <div class="flex space-x-2">
            <div v-for="(photo, i) in photos" :key="photo.id || i" class="relative group">
                <img
                    :src="getPhotoUrl(photo)"
                    class="w-12 h-12 object-cover rounded border"
                >
                <button
                    v-if="canDelete"
                    @click.prevent="remove(photo)"
                    class="absolute top-0 right-0 p-1 bg-white bg-opacity-80 rounded-bl text-xs text-red-600 hidden group-hover:block"
                >
                    ×
                </button>
            </div>
        </div>
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

function onFileChange(e) {
    const file = e.target.files[0]
    if (file) {
        emit('upload', file)
    }
    e.target.value = null // reset input for next select
}

function remove(photo) {
    emit('delete', photo.id ?? photo)
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
</script>