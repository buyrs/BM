<template>
    <form @submit.prevent="submitChecklist" class="space-y-6">
        <h4 class="text-lg font-semibold">{{ initialData ? 'Edit' : 'Create' }} Checklist</h4>

        <!-- General Information -->
        <div class="space-y-4">
            <h5 class="font-medium text-gray-900">General Information</h5>
            
            <!-- Heating -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Heating Type</label>
                    <select
                        v-model="form.general_info.heating.type"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                    >
                        <option value="">Select type</option>
                        <option value="electric">Electric</option>
                        <option value="gas">Gas</option>
                        <option value="central">Central</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Heating Condition</label>
                    <select
                        v-model="form.general_info.heating.condition"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                    >
                        <option value="">Select condition</option>
                        <option value="excellent">Excellent</option>
                        <option value="good">Good</option>
                        <option value="fair">Fair</option>
                        <option value="poor">Poor</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Comments</label>
                    <input
                        v-model="form.general_info.heating.comment"
                        type="text"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                        placeholder="Optional comments"
                    />
                </div>
            </div>

            <!-- Hot Water -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Hot Water Type</label>
                    <select
                        v-model="form.general_info.hot_water.type"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                    >
                        <option value="">Select type</option>
                        <option value="electric">Electric</option>
                        <option value="gas">Gas</option>
                        <option value="solar">Solar</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Hot Water Condition</label>
                    <select
                        v-model="form.general_info.hot_water.condition"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                    >
                        <option value="">Select condition</option>
                        <option value="excellent">Excellent</option>
                        <option value="good">Good</option>
                        <option value="fair">Fair</option>
                        <option value="poor">Poor</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Comments</label>
                    <input
                        v-model="form.general_info.hot_water.comment"
                        type="text"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                        placeholder="Optional comments"
                    />
                </div>
            </div>

            <!-- Keys -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Number of Keys</label>
                    <input
                        v-model.number="form.general_info.keys.count"
                        type="number"
                        min="0"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Keys Condition</label>
                    <select
                        v-model="form.general_info.keys.condition"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                    >
                        <option value="">Select condition</option>
                        <option value="excellent">Excellent</option>
                        <option value="good">Good</option>
                        <option value="fair">Fair</option>
                        <option value="poor">Poor</option>
                    </select>
                </div>
                <div v-if="mission.mission_type === 'exit'">
                    <label class="flex items-center">
                        <input
                            v-model="form.general_info.keys.returned"
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm"
                        />
                        <span class="ml-2 text-sm text-gray-700">Keys Returned</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Rooms -->
        <div class="space-y-4">
            <h5 class="font-medium text-gray-900">Rooms</h5>
            
            <div
                v-for="(room, roomKey) in form.rooms"
                :key="roomKey"
                class="border rounded-lg p-4 space-y-4"
            >
                <h6 class="font-medium capitalize">{{ roomKey.replace('_', ' ') }}</h6>
                
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div v-for="(value, itemKey) in room" :key="itemKey">
                        <label class="block text-sm font-medium text-gray-700 capitalize">
                            {{ itemKey.replace('_', ' ') }}
                        </label>
                        <select
                            v-model="form.rooms[roomKey][itemKey]"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                        >
                            <option value="">Select condition</option>
                            <option value="excellent">Excellent</option>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                            <option value="damaged">Damaged</option>
                        </select>
                    </div>
                </div>

                <!-- Photo Upload for Room -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Photos for {{ roomKey.replace('_', ' ') }}</label>
                    <PhotoUploader
                        :key="`${roomKey}-photos`"
                        @photos-selected="(photos) => handlePhotosSelected(roomKey, photos)"
                        :existing-photos="getExistingPhotos(roomKey)"
                        :required="isPhotoRequired(roomKey)"
                    />
                </div>
            </div>
        </div>

        <!-- Utilities -->
        <div class="space-y-4">
            <h5 class="font-medium text-gray-900">Utilities</h5>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div v-for="(meter, meterKey) in form.utilities" :key="meterKey" class="space-y-2">
                    <h6 class="font-medium capitalize">{{ meterKey.replace('_', ' ') }}</h6>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Number</label>
                        <input
                            v-model="form.utilities[meterKey].number"
                            type="text"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Reading</label>
                        <input
                            v-model="form.utilities[meterKey].reading"
                            type="text"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                        />
                    </div>
                </div>
            </div>

            <!-- Photo Upload for Utilities -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Utility Meter Photos</label>
                <PhotoUploader
                    @photos-selected="(photos) => handlePhotosSelected('utilities', photos)"
                    :existing-photos="getExistingPhotos('utilities')"
                    :required="true"
                />
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-4">
            <button
                type="button"
                @click="$emit('cancelled')"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
            >
                Cancel
            </button>
            <button
                type="submit"
                :disabled="!isFormValid || submitting"
                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 disabled:opacity-50"
            >
                {{ submitting ? 'Submitting...' : 'Submit Checklist' }}
            </button>
        </div>
    </form>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import PhotoUploader from './PhotoUploader.vue'

const props = defineProps({
    mission: Object,
    initialData: Object
})

const emit = defineEmits(['submitted', 'cancelled'])

const submitting = ref(false)
const photos = ref({})

const form = ref({
    general_info: {
        heating: { type: '', condition: '', comment: '' },
        hot_water: { type: '', condition: '', comment: '' },
        keys: { count: 0, condition: '', comment: '', returned: false }
    },
    rooms: {
        entrance: { walls: '', floor: '', ceiling: '', door: '', windows: '', electrical: '' },
        living_room: { walls: '', floor: '', ceiling: '', windows: '', electrical: '', heating: '' },
        kitchen: { walls: '', floor: '', ceiling: '', windows: '', electrical: '', plumbing: '', appliances: '' }
    },
    utilities: {
        electricity_meter: { number: '', reading: '' },
        gas_meter: { number: '', reading: '' },
        water_meter: { number: '', reading: '' }
    }
})

const isFormValid = computed(() => {
    // Basic validation - ensure required fields are filled
    const hasRequiredPhotos = requiredPhotoSections.value.every(section => 
        photos.value[section] && photos.value[section].length > 0
    )
    
    const hasBasicInfo = form.value.general_info.keys.count > 0
    
    return hasRequiredPhotos && hasBasicInfo
})

const requiredPhotoSections = computed(() => {
    // Define which sections require photos
    return ['entrance', 'living_room', 'kitchen', 'utilities']
})

onMounted(() => {
    if (props.initialData) {
        // Populate form with existing data
        if (props.initialData.general_info) {
            form.value.general_info = { ...form.value.general_info, ...props.initialData.general_info }
        }
        if (props.initialData.rooms) {
            form.value.rooms = { ...form.value.rooms, ...props.initialData.rooms }
        }
        if (props.initialData.utilities) {
            form.value.utilities = { ...form.value.utilities, ...props.initialData.utilities }
        }
    }
})

const handlePhotosSelected = (section, selectedPhotos) => {
    photos.value[section] = selectedPhotos
}

const getExistingPhotos = (section) => {
    // Return existing photos for this section if editing
    if (!props.initialData?.items) return []
    
    const items = props.initialData.items.filter(item => 
        item.item_name === section || item.category === section
    )
    
    return items.flatMap(item => item.photos || [])
}

const isPhotoRequired = (section) => {
    return requiredPhotoSections.value.includes(section)
}

const submitChecklist = () => {
    if (!isFormValid.value) return
    
    submitting.value = true
    
    const formData = new FormData()
    formData.append('checklist_data', JSON.stringify(form.value))
    formData.append('required_photos', JSON.stringify(requiredPhotoSections.value))
    
    // Add photos to form data
    Object.entries(photos.value).forEach(([section, sectionPhotos]) => {
        sectionPhotos.forEach((photo, index) => {
            formData.append(`photos[${section}][${index}]`, photo)
        })
    })
    
    router.post(route('missions.submit-bail-mobilite-checklist', props.mission.id), formData, {
        onSuccess: () => {
            emit('submitted')
        },
        onError: (errors) => {
            console.error('Checklist submission errors:', errors)
        },
        onFinish: () => {
            submitting.value = false
        }
    })
}
</script>