<template>
    <Modal :show="show" @close="$emit('close')" :max-width="isMobile ? 'full' : '2xl'" :mobile="isMobile">
        <div :class="isMobile ? 'modal-mobile' : 'p-6'">
            <div v-if="isMobile" class="modal-content">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">
                    Create New Bail Mobilité Mission
                </h3>
                <button
                    @click="$emit('close')"
                    class="text-gray-400 hover:text-gray-600"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="handleSubmit" :class="['space-y-4', isMobile ? 'mobile-form' : '']">
                <!-- Date Information -->
                <div class="bg-blue-50 p-3 rounded-md">
                    <p class="text-sm text-blue-800">
                        <strong>Selected Date:</strong> {{ formattedSelectedDate }}
                    </p>
                    <p class="text-xs text-blue-600 mt-1">
                        This will create both entry and exit missions for the Bail Mobilité period.
                    </p>
                </div>

                <!-- Tenant Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="tenant_name" class="block text-sm font-medium text-gray-700">
                            Tenant Name *
                        </label>
                        <input
                            id="tenant_name"
                            v-model="form.tenant_name"
                            type="text"
                            required
                            :class="[
                                'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500',
                                isMobile ? 'text-base py-3' : 'sm:text-sm',
                                { 'border-red-300': errors.tenant_name }
                            ]"
                        />
                        <p v-if="errors.tenant_name" class="mt-1 text-sm text-red-600">
                            {{ errors.tenant_name }}
                        </p>
                    </div>

                    <div>
                        <label for="tenant_phone" class="block text-sm font-medium text-gray-700">
                            Tenant Phone
                        </label>
                        <input
                            id="tenant_phone"
                            v-model="form.tenant_phone"
                            type="tel"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            :class="{ 'border-red-300': errors.tenant_phone }"
                        />
                        <p v-if="errors.tenant_phone" class="mt-1 text-sm text-red-600">
                            {{ errors.tenant_phone }}
                        </p>
                    </div>
                </div>

                <div>
                    <label for="tenant_email" class="block text-sm font-medium text-gray-700">
                        Tenant Email
                    </label>
                    <input
                        id="tenant_email"
                        v-model="form.tenant_email"
                        type="email"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        :class="{ 'border-red-300': errors.tenant_email }"
                    />
                    <p v-if="errors.tenant_email" class="mt-1 text-sm text-red-600">
                        {{ errors.tenant_email }}
                    </p>
                </div>

                <!-- Property Information -->
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700">
                        Property Address *
                    </label>
                    <textarea
                        id="address"
                        v-model="form.address"
                        rows="2"
                        required
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        :class="{ 'border-red-300': errors.address }"
                    ></textarea>
                    <p v-if="errors.address" class="mt-1 text-sm text-red-600">
                        {{ errors.address }}
                    </p>
                </div>

                <!-- Mission Dates -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700">
                            Start Date *
                        </label>
                        <input
                            id="start_date"
                            v-model="form.start_date"
                            type="date"
                            required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            :class="{ 'border-red-300': errors.start_date }"
                        />
                        <p v-if="errors.start_date" class="mt-1 text-sm text-red-600">
                            {{ errors.start_date }}
                        </p>
                    </div>

                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700">
                            End Date *
                        </label>
                        <input
                            id="end_date"
                            v-model="form.end_date"
                            type="date"
                            required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            :class="{ 'border-red-300': errors.end_date }"
                        />
                        <p v-if="errors.end_date" class="mt-1 text-sm text-red-600">
                            {{ errors.end_date }}
                        </p>
                    </div>
                </div>

                <!-- Date Validation Error -->
                <div v-if="dateValidationError" class="bg-red-50 border border-red-200 rounded-md p-3">
                    <p class="text-sm text-red-600">
                        {{ dateValidationError }}
                    </p>
                </div>

                <!-- Mission Scheduling -->
                <div class="border-t pt-4">
                    <h4 class="text-md font-medium text-gray-900 mb-3">Mission Scheduling</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Entry Mission -->
                        <div class="bg-blue-50 p-3 rounded-md">
                            <h5 class="text-sm font-medium text-blue-900 mb-2">Entry Mission</h5>
                            
                            <div class="space-y-2">
                                <div>
                                    <label for="entry_time" class="block text-xs font-medium text-blue-700">
                                        Preferred Time
                                    </label>
                                    <input
                                        id="entry_time"
                                        v-model="form.entry_scheduled_time"
                                        type="time"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                                        @change="checkEntryTimeConflicts"
                                    />
                                    <div v-if="entryTimeConflicts.length > 0" class="mt-1">
                                        <div class="text-xs text-yellow-700 bg-yellow-50 border border-yellow-200 rounded p-1">
                                            <div v-for="conflict in entryTimeConflicts" :key="conflict" class="flex items-center space-x-1">
                                                <svg class="w-3 h-3 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                                <span>{{ conflict }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="entry_checker" class="block text-xs font-medium text-blue-700">
                                        Assign Checker
                                    </label>
                                    <select
                                        id="entry_checker"
                                        v-model="form.entry_checker_id"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm"
                                        @change="checkEntryTimeConflicts"
                                    >
                                        <option value="">Select Checker</option>
                                        <option
                                            v-for="checker in checkers"
                                            :key="checker.id"
                                            :value="checker.id"
                                        >
                                            {{ checker.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Exit Mission -->
                        <div class="bg-orange-50 p-3 rounded-md">
                            <h5 class="text-sm font-medium text-orange-900 mb-2">Exit Mission</h5>
                            
                            <div class="space-y-2">
                                <div>
                                    <label for="exit_time" class="block text-xs font-medium text-orange-700">
                                        Preferred Time
                                    </label>
                                    <input
                                        id="exit_time"
                                        v-model="form.exit_scheduled_time"
                                        type="time"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500 text-sm"
                                        @change="checkExitTimeConflicts"
                                    />
                                    <div v-if="exitTimeConflicts.length > 0" class="mt-1">
                                        <div class="text-xs text-yellow-700 bg-yellow-50 border border-yellow-200 rounded p-1">
                                            <div v-for="conflict in exitTimeConflicts" :key="conflict" class="flex items-center space-x-1">
                                                <svg class="w-3 h-3 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                                <span>{{ conflict }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="exit_checker" class="block text-xs font-medium text-orange-700">
                                        Assign Checker
                                    </label>
                                    <select
                                        id="exit_checker"
                                        v-model="form.exit_checker_id"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500 text-sm"
                                        @change="checkExitTimeConflicts"
                                    >
                                        <option value="">Select Checker</option>
                                        <option
                                            v-for="checker in checkers"
                                            :key="checker.id"
                                            :value="checker.id"
                                        >
                                            {{ checker.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">
                        Additional Notes
                    </label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="3"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Any additional information or special instructions..."
                    ></textarea>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        :disabled="processing"
                    >
                        Cancel
                    </button>
                    
                    <button
                        type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="processing || !isFormValid || dateValidationError"
                    >
                        <span v-if="processing">Creating...</span>
                        <span v-else>Create Mission</span>
                    </button>
                </div>
            </form>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'

// Props
const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    selectedDate: {
        type: Date,
        default: null
    },
    checkers: {
        type: Array,
        default: () => []
    }
})

// Emits
const emit = defineEmits(['close', 'create'])

// Reactive state
const processing = ref(false)
const errors = ref({})
const isMobile = ref(false)
const entryTimeConflicts = ref([])
const exitTimeConflicts = ref([])

const form = reactive({
    tenant_name: '',
    tenant_phone: '',
    tenant_email: '',
    address: '',
    start_date: '',
    end_date: '',
    entry_scheduled_time: '09:00',
    exit_scheduled_time: '17:00',
    entry_checker_id: '',
    exit_checker_id: '',
    notes: ''
})

// Computed properties
const formattedSelectedDate = computed(() => {
    if (!props.selectedDate) return 'No date selected'
    
    return props.selectedDate.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
})

const isFormValid = computed(() => {
    return form.tenant_name.trim() !== '' &&
           form.address.trim() !== '' &&
           form.start_date !== '' &&
           form.end_date !== '' &&
           new Date(form.end_date) > new Date(form.start_date)
})

const dateValidationError = computed(() => {
    if (!form.start_date || !form.end_date) return null
    
    const startDate = new Date(form.start_date)
    const endDate = new Date(form.end_date)
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    
    if (startDate < today) {
        return 'Start date cannot be in the past'
    }
    
    if (endDate <= startDate) {
        return 'End date must be after start date'
    }
    
    const diffTime = Math.abs(endDate - startDate)
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    
    if (diffDays > 365) {
        return 'Bail Mobilité period cannot exceed 365 days'
    }
    
    return null
})

// Methods
const handleSubmit = () => {
    processing.value = true
    errors.value = {}

    router.post(route('ops.calendar.missions.create'), form, {
        onSuccess: (page) => {
            processing.value = false
            // The response data should be in page.props or the response itself
            const responseData = page.props?.flash || page
            emit('create', responseData)
            resetForm()
            emit('close')
        },
        onError: (responseErrors) => {
            processing.value = false
            errors.value = responseErrors
        },
        onFinish: () => {
            processing.value = false
        }
    })
}

const resetForm = () => {
    Object.keys(form).forEach(key => {
        if (key.includes('time')) {
            form[key] = key.includes('entry') ? '09:00' : '17:00'
        } else if (key.includes('checker_id')) {
            form[key] = ''
        } else {
            form[key] = ''
        }
    })
    errors.value = {}
    entryTimeConflicts.value = []
    exitTimeConflicts.value = []
}

const checkEntryTimeConflicts = async () => {
    if (!form.start_date || !form.entry_scheduled_time || !form.entry_checker_id) {
        entryTimeConflicts.value = []
        return
    }

    try {
        const response = await fetch(route('ops.calendar.conflicts'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                date: form.start_date,
                time: form.entry_scheduled_time,
                checker_id: form.entry_checker_id
            })
        })

        const data = await response.json()
        entryTimeConflicts.value = data.has_conflicts ? data.conflicts : []
    } catch (error) {
        console.error('Error checking entry time conflicts:', error)
        entryTimeConflicts.value = []
    }
}

const checkExitTimeConflicts = async () => {
    if (!form.end_date || !form.exit_scheduled_time || !form.exit_checker_id) {
        exitTimeConflicts.value = []
        return
    }

    try {
        const response = await fetch(route('ops.calendar.conflicts'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                date: form.end_date,
                time: form.exit_scheduled_time,
                checker_id: form.exit_checker_id
            })
        })

        const data = await response.json()
        exitTimeConflicts.value = data.has_conflicts ? data.conflicts : []
    } catch (error) {
        console.error('Error checking exit time conflicts:', error)
        exitTimeConflicts.value = []
    }
}

// Watch for selectedDate changes to update form dates
watch(() => props.selectedDate, (newDate) => {
    if (newDate) {
        const dateString = newDate.toISOString().split('T')[0]
        form.start_date = dateString
        
        // Set end date to 30 days later by default
        const endDate = new Date(newDate)
        endDate.setDate(endDate.getDate() + 30)
        form.end_date = endDate.toISOString().split('T')[0]
    }
})

// Mobile detection
const checkMobileDevice = () => {
    isMobile.value = window.innerWidth < 768
}

// Watch for modal close to reset form
watch(() => props.show, (isShown) => {
    if (!isShown) {
        resetForm()
    } else {
        checkMobileDevice()
    }
})

// Set up mobile detection
onMounted(() => {
    checkMobileDevice()
    window.addEventListener('resize', checkMobileDevice)
})

onUnmounted(() => {
    window.removeEventListener('resize', checkMobileDevice)
})
</script>