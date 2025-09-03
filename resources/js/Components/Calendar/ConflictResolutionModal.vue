<template>
    <Modal :show="show" @close="$emit('close')" max-width="lg">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-2">
                    <svg class="w-6 h-6 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900">
                        Scheduling Conflicts Detected
                    </h3>
                </div>
                <button
                    @click="$emit('close')"
                    class="text-gray-400 hover:text-gray-600"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div v-if="conflictData" class="space-y-4">
                <!-- Mission Information -->
                <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                    <h4 class="text-sm font-medium text-blue-900 mb-2">Mission Details</h4>
                    <div class="text-sm text-blue-800">
                        <p><strong>Type:</strong> {{ conflictData.mission.type === 'entry' ? 'Entry' : 'Exit' }} Mission</p>
                        <p><strong>Tenant:</strong> {{ conflictData.mission.tenant_name }}</p>
                        <p><strong>Address:</strong> {{ conflictData.mission.address }}</p>
                        <p v-if="conflictData.mission.agent">
                            <strong>Assigned to:</strong> {{ conflictData.mission.agent.name }}
                        </p>
                    </div>
                </div>

                <!-- Proposed Change -->
                <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Proposed Schedule Change</h4>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-600">From:</p>
                            <p class="font-medium">
                                {{ formatDate(conflictData.mission.scheduled_at) }}
                                <span v-if="conflictData.mission.scheduled_time">
                                    at {{ formatTime(conflictData.mission.scheduled_time) }}
                                </span>
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-600">To:</p>
                            <p class="font-medium">
                                {{ formatDate(conflictData.newDate) }}
                                <span v-if="conflictData.newTime">
                                    at {{ formatTime(conflictData.newTime) }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Conflicts List -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                    <h4 class="text-sm font-medium text-yellow-900 mb-2">
                        {{ conflictData.conflicts.length }} Conflict{{ conflictData.conflicts.length !== 1 ? 's' : '' }} Found
                    </h4>
                    <ul class="text-sm text-yellow-800 space-y-1">
                        <li v-for="(conflict, index) in conflictData.conflicts" :key="index" class="flex items-start space-x-2">
                            <svg class="w-4 h-4 text-yellow-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ conflict }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Resolution Options -->
                <div class="space-y-3">
                    <h4 class="text-sm font-medium text-gray-900">Resolution Options</h4>
                    
                    <div class="space-y-2">
                        <label class="flex items-start space-x-3 cursor-pointer">
                            <input
                                v-model="selectedResolution"
                                type="radio"
                                value="proceed"
                                class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                            />
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    Proceed with scheduling despite conflicts
                                </div>
                                <div class="text-xs text-gray-600">
                                    The mission will be rescheduled but conflicts will remain. You may need to resolve them manually.
                                </div>
                            </div>
                        </label>

                        <label class="flex items-start space-x-3 cursor-pointer">
                            <input
                                v-model="selectedResolution"
                                type="radio"
                                value="suggest"
                                class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                            />
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    Find alternative time slots
                                </div>
                                <div class="text-xs text-gray-600">
                                    Show available time slots that don't have conflicts.
                                </div>
                            </div>
                        </label>

                        <label class="flex items-start space-x-3 cursor-pointer">
                            <input
                                v-model="selectedResolution"
                                type="radio"
                                value="cancel"
                                class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                            />
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    Cancel the reschedule
                                </div>
                                <div class="text-xs text-gray-600">
                                    Keep the mission at its current time and date.
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Alternative Time Slots (shown when "suggest" is selected) -->
                <div v-if="selectedResolution === 'suggest'" class="bg-green-50 border border-green-200 rounded-md p-4">
                    <h4 class="text-sm font-medium text-green-900 mb-3">Available Alternative Time Slots</h4>
                    
                    <TimeSlotPicker
                        :date="conflictData.newDate"
                        :checker-id="conflictData.mission.agent?.id"
                        :exclude-mission-id="conflictData.mission.id"
                        @slot-selected="handleSlotSelected"
                    />
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Cancel
                    </button>
                    
                    <button
                        type="button"
                        @click="handleResolve"
                        :disabled="!selectedResolution || (selectedResolution === 'suggest' && !selectedAlternativeSlot)"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ getResolveButtonText() }}
                    </button>
                </div>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import Modal from '@/Components/Modal.vue'
import TimeSlotPicker from './TimeSlotPicker.vue'

// Props
const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    conflictData: {
        type: Object,
        default: null
    }
})

// Emits
const emit = defineEmits(['close', 'resolve'])

// State
const selectedResolution = ref('cancel')
const selectedAlternativeSlot = ref(null)

// Methods
const formatDate = (dateString) => {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const formatTime = (timeString) => {
    if (!timeString) return ''
    
    const [hours, minutes] = timeString.split(':')
    const hour = parseInt(hours)
    const period = hour >= 12 ? 'PM' : 'AM'
    const displayHour = hour === 0 ? 12 : hour > 12 ? hour - 12 : hour
    
    return `${displayHour}:${minutes} ${period}`
}

const getResolveButtonText = () => {
    switch (selectedResolution.value) {
        case 'proceed':
            return 'Proceed with Conflicts'
        case 'suggest':
            return selectedAlternativeSlot.value ? 'Use Selected Time' : 'Select Time Slot'
        case 'cancel':
            return 'Cancel Reschedule'
        default:
            return 'Resolve'
    }
}

const handleResolve = () => {
    let resolution = {
        action: selectedResolution.value,
        conflictData: props.conflictData
    }

    if (selectedResolution.value === 'suggest' && selectedAlternativeSlot.value) {
        resolution.newTime = selectedAlternativeSlot.value.time
        resolution.newDate = props.conflictData.newDate
    }

    emit('resolve', resolution)
}

const handleSlotSelected = (slot) => {
    selectedAlternativeSlot.value = slot
}

// Watch for resolution changes
watch(selectedResolution, (newValue) => {
    selectedAlternativeSlot.value = null
})

// Reset state when modal opens/closes
watch(() => props.show, (show) => {
    if (show) {
        selectedResolution.value = 'cancel'
        selectedAlternativeSlot.value = null
    }
})
</script>