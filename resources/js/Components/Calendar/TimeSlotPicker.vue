<template>
    <div class="time-slot-picker">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Select Available Time Slot
            </label>
            <div class="text-xs text-gray-600 mb-3">
                {{ formatDate(date) }}
            </div>
        </div>

        <div v-if="loading" class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
            <p class="text-sm text-gray-600 mt-2">Loading available time slots...</p>
        </div>

        <div v-else-if="error" class="text-center py-8">
            <svg class="w-12 h-12 text-red-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm text-red-600">{{ error }}</p>
            <button
                @click="loadTimeSlots"
                class="mt-2 text-sm text-blue-600 hover:text-blue-800 underline"
            >
                Try Again
            </button>
        </div>

        <div v-else class="space-y-3">
            <!-- Business Hours Slots -->
            <div v-if="businessHoursSlots.length > 0">
                <h4 class="text-sm font-medium text-gray-900 mb-2">Business Hours (9 AM - 7 PM)</h4>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    <label
                        v-for="slot in businessHoursSlots"
                        :key="slot.time"
                        :class="[
                            'flex items-center justify-center p-3 rounded-md border cursor-pointer transition-colors',
                            slot.available
                                ? 'border-green-300 bg-green-50 hover:bg-green-100 text-green-800'
                                : 'border-gray-300 bg-gray-50 text-gray-500 cursor-not-allowed',
                            selectedSlot?.time === slot.time
                                ? 'ring-2 ring-blue-500 border-blue-500'
                                : ''
                        ]"
                    >
                        <input
                            v-model="selectedSlot"
                            type="radio"
                            :value="slot"
                            :disabled="!slot.available"
                            class="sr-only"
                        />
                        <div class="text-center">
                            <div class="text-sm font-medium">
                                {{ formatTime(slot.time) }}
                            </div>
                            <div v-if="!slot.available && slot.existing_mission" class="text-xs mt-1">
                                {{ slot.existing_mission.type === 'entry' ? 'Entry' : 'Exit' }} mission
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Extended Hours Slots -->
            <div v-if="extendedHoursSlots.length > 0">
                <h4 class="text-sm font-medium text-gray-900 mb-2">Extended Hours</h4>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    <label
                        v-for="slot in extendedHoursSlots"
                        :key="slot.time"
                        :class="[
                            'flex items-center justify-center p-3 rounded-md border cursor-pointer transition-colors',
                            'border-yellow-300 bg-yellow-50 hover:bg-yellow-100 text-yellow-800',
                            selectedSlot?.time === slot.time
                                ? 'ring-2 ring-blue-500 border-blue-500'
                                : ''
                        ]"
                    >
                        <input
                            v-model="selectedSlot"
                            type="radio"
                            :value="slot"
                            class="sr-only"
                        />
                        <div class="text-center">
                            <div class="text-sm font-medium">
                                {{ formatTime(slot.time) }}
                            </div>
                            <div class="text-xs mt-1">
                                Outside business hours
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Weekend Warning -->
            <div v-if="isWeekend" class="bg-orange-50 border border-orange-200 rounded-md p-3">
                <div class="flex items-start space-x-2">
                    <svg class="w-5 h-5 text-orange-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <h4 class="text-sm font-medium text-orange-800">Weekend Scheduling</h4>
                        <p class="text-xs text-orange-700 mt-1">
                            This is a weekend day. Consider rescheduling to a weekday if possible.
                        </p>
                    </div>
                </div>
            </div>

            <!-- No Available Slots -->
            <div v-if="availableSlots.length === 0" class="text-center py-6">
                <svg class="w-12 h-12 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="text-sm font-medium text-gray-900">No Available Time Slots</h3>
                <p class="text-xs text-gray-600 mt-1">
                    All time slots for this date are either booked or outside business hours.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'

// Props
const props = defineProps({
    date: {
        type: [Date, String],
        required: true
    },
    checkerId: {
        type: Number,
        default: null
    },
    excludeMissionId: {
        type: Number,
        default: null
    }
})

// Emits
const emit = defineEmits(['slot-selected'])

// State
const loading = ref(false)
const error = ref(null)
const timeSlots = ref([])
const selectedSlot = ref(null)

// Computed properties
const isWeekend = computed(() => {
    const date = new Date(props.date)
    return date.getDay() === 0 || date.getDay() === 6
})

const availableSlots = computed(() => {
    return timeSlots.value.filter(slot => slot.available)
})

const businessHoursSlots = computed(() => {
    return timeSlots.value.filter(slot => slot.is_business_hours)
})

const extendedHoursSlots = computed(() => {
    return timeSlots.value.filter(slot => !slot.is_business_hours && slot.available)
})

// Methods
const formatDate = (date) => {
    const d = new Date(date)
    return d.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const formatTime = (timeString) => {
    const [hours, minutes] = timeString.split(':')
    const hour = parseInt(hours)
    const period = hour >= 12 ? 'PM' : 'AM'
    const displayHour = hour === 0 ? 12 : hour > 12 ? hour - 12 : hour
    
    return `${displayHour}:${minutes} ${period}`
}

const loadTimeSlots = async () => {
    loading.value = true
    error.value = null

    try {
        const params = new URLSearchParams({
            date: new Date(props.date).toISOString().split('T')[0]
        })

        if (props.checkerId) {
            params.append('checker_id', props.checkerId)
        }

        const response = await fetch(`${route('ops.calendar.time-slots')}?${params}`)
        const data = await response.json()

        if (response.ok) {
            timeSlots.value = data.available_slots || []
        } else {
            throw new Error(data.message || 'Failed to load time slots')
        }
    } catch (err) {
        console.error('Error loading time slots:', err)
        error.value = err.message || 'Failed to load time slots'
    } finally {
        loading.value = false
    }
}

// Watch for slot selection
watch(selectedSlot, (newSlot) => {
    if (newSlot) {
        emit('slot-selected', newSlot)
    }
})

// Watch for prop changes
watch([() => props.date, () => props.checkerId], () => {
    selectedSlot.value = null
    loadTimeSlots()
})

// Load initial data
onMounted(() => {
    loadTimeSlots()
})
</script>

<style scoped>
.time-slot-picker {
    @apply w-full;
}

/* Custom radio button styling */
input[type="radio"]:checked + div {
    @apply font-semibold;
}

/* Disabled slot styling */
label:has(input:disabled) {
    @apply opacity-60;
}

/* Available slot hover effects */
label:not(:has(input:disabled)):hover {
    @apply transform scale-105;
    transition: transform 0.1s ease;
}
</style>