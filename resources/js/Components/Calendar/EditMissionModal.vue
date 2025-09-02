<template>
    <Modal :show="show" @close="$emit('close')" max-width="2xl">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">
                    Edit Mission
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

            <form @submit.prevent="handleSubmit" v-if="mission">
                <div class="space-y-4">
                    <!-- Mission Type and Status (Read-only) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Mission Type</label>
                            <div class="mt-1">
                                <span :class="[
                                    'px-2 py-1 rounded-full text-xs font-medium',
                                    mission.type === 'entry' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'
                                ]">
                                    {{ mission.type === 'entry' ? 'Entry' : 'Exit' }} Mission
                                </span>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Current Status</label>
                            <div class="mt-1">
                                <span :class="[
                                    'px-2 py-1 rounded-full text-xs font-medium',
                                    getStatusClasses(mission.status)
                                ]">
                                    {{ formatStatus(mission.status) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Editable Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="scheduled_at" class="block text-sm font-medium text-gray-700">
                                Scheduled Date *
                            </label>
                            <input
                                id="scheduled_at"
                                v-model="form.scheduled_at"
                                type="date"
                                required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                :class="{ 'border-red-300': errors.scheduled_at }"
                            />
                            <p v-if="errors.scheduled_at" class="mt-1 text-sm text-red-600">
                                {{ errors.scheduled_at }}
                            </p>
                        </div>
                        
                        <div>
                            <label for="scheduled_time" class="block text-sm font-medium text-gray-700">
                                Scheduled Time
                            </label>
                            <input
                                id="scheduled_time"
                                v-model="form.scheduled_time"
                                type="time"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                :class="{ 'border-red-300': errors.scheduled_time }"
                            />
                            <p v-if="errors.scheduled_time" class="mt-1 text-sm text-red-600">
                                {{ errors.scheduled_time }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="tenant_name" class="block text-sm font-medium text-gray-700">
                                Tenant Name
                            </label>
                            <input
                                id="tenant_name"
                                v-model="form.tenant_name"
                                type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                :class="{ 'border-red-300': errors.tenant_name }"
                            />
                            <p v-if="errors.tenant_name" class="mt-1 text-sm text-red-600">
                                {{ errors.tenant_name }}
                            </p>
                        </div>
                        
                        <div>
                            <label for="agent_id" class="block text-sm font-medium text-gray-700">
                                Assigned Checker
                            </label>
                            <select
                                id="agent_id"
                                v-model="form.agent_id"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                :class="{ 'border-red-300': errors.agent_id }"
                            >
                                <option value="">Unassigned</option>
                                <option
                                    v-for="checker in checkers"
                                    :key="checker.id"
                                    :value="checker.id"
                                >
                                    {{ checker.name }}
                                </option>
                            </select>
                            <p v-if="errors.agent_id" class="mt-1 text-sm text-red-600">
                                {{ errors.agent_id }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700">
                            Address
                        </label>
                        <input
                            id="address"
                            v-model="form.address"
                            type="text"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            :class="{ 'border-red-300': errors.address }"
                        />
                        <p v-if="errors.address" class="mt-1 text-sm text-red-600">
                            {{ errors.address }}
                        </p>
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700">
                            Notes
                        </label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="3"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            :class="{ 'border-red-300': errors.notes }"
                        ></textarea>
                        <p v-if="errors.notes" class="mt-1 text-sm text-red-600">
                            {{ errors.notes }}
                        </p>
                    </div>

                    <!-- Conflict Warning -->
                    <div v-if="conflicts.length > 0" class="bg-yellow-50 border border-yellow-200 rounded-md p-3">
                        <div class="flex">
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">Scheduling Conflicts Detected</h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <ul class="list-disc pl-5 space-y-1">
                                        <li v-for="conflict in conflicts" :key="conflict">
                                            {{ conflict }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Error Message -->
                    <div v-if="errorMessage" class="bg-red-50 border border-red-200 rounded-md p-3">
                        <div class="flex">
                            <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Error</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    {{ errorMessage }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between pt-6 border-t mt-6">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        :disabled="loading"
                    >
                        Cancel
                    </button>
                    
                    <div class="flex space-x-3">
                        <button
                            type="button"
                            @click="checkConflicts"
                            class="px-4 py-2 text-sm font-medium text-blue-700 bg-blue-100 border border-blue-300 rounded-md hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            :disabled="loading || !form.scheduled_at || !form.scheduled_time"
                        >
                            Check Conflicts
                        </button>
                        
                        <button
                            type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            :disabled="loading"
                        >
                            <span v-if="loading" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Saving...
                            </span>
                            <span v-else>Save Changes</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </Modal>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'

// Props
const props = defineProps({
    mission: {
        type: Object,
        default: null
    },
    show: {
        type: Boolean,
        default: false
    },
    checkers: {
        type: Array,
        default: () => []
    }
})

// Emits
const emit = defineEmits(['close', 'updated'])

// State
const loading = ref(false)
const errors = ref({})
const errorMessage = ref('')
const conflicts = ref([])

// Form data
const form = reactive({
    scheduled_at: '',
    scheduled_time: '',
    tenant_name: '',
    address: '',
    agent_id: '',
    notes: ''
})

// Watch for mission changes to populate form
watch(() => props.mission, (newMission) => {
    if (newMission) {
        form.scheduled_at = newMission.scheduled_at ? new Date(newMission.scheduled_at).toISOString().split('T')[0] : ''
        form.scheduled_time = newMission.scheduled_time ? newMission.scheduled_time.substring(0, 5) : ''
        form.tenant_name = newMission.tenant_name || ''
        form.address = newMission.address || ''
        form.agent_id = newMission.agent?.id || ''
        form.notes = newMission.notes || ''
    }
}, { immediate: true })

// Watch for show changes to reset state
watch(() => props.show, (show) => {
    if (!show) {
        errors.value = {}
        errorMessage.value = ''
        conflicts.value = []
    }
})

// Methods
const formatStatus = (status) => {
    const statusMap = {
        'unassigned': 'Unassigned',
        'assigned': 'Assigned',
        'in_progress': 'In Progress',
        'completed': 'Completed',
        'cancelled': 'Cancelled'
    }
    
    return statusMap[status] || status
}

const getStatusClasses = (status) => {
    const statusClasses = {
        'unassigned': 'bg-gray-100 text-gray-800',
        'assigned': 'bg-blue-100 text-blue-800',
        'in_progress': 'bg-green-100 text-green-800',
        'completed': 'bg-green-100 text-green-800',
        'cancelled': 'bg-red-100 text-red-800'
    }
    
    return statusClasses[status] || statusClasses.unassigned
}

const checkConflicts = async () => {
    if (!form.scheduled_at || !form.scheduled_time) {
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
                date: form.scheduled_at,
                time: form.scheduled_time,
                checker_id: form.agent_id || null,
                mission_id: props.mission?.id
            })
        })

        const data = await response.json()
        
        if (data.has_conflicts) {
            conflicts.value = data.conflicts
        } else {
            conflicts.value = []
        }
    } catch (error) {
        console.error('Error checking conflicts:', error)
    }
}

const handleSubmit = async () => {
    if (!props.mission) return

    loading.value = true
    errors.value = {}
    errorMessage.value = ''

    try {
        const response = await fetch(route('ops.calendar.missions.update', props.mission.id), {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(form)
        })

        const data = await response.json()

        if (data.success) {
            emit('updated', data.mission)
            emit('close')
        } else {
            if (data.errors) {
                errors.value = data.errors
            } else {
                errorMessage.value = data.message || 'An error occurred while updating the mission'
            }
        }
    } catch (error) {
        console.error('Error updating mission:', error)
        errorMessage.value = 'An unexpected error occurred'
    } finally {
        loading.value = false
    }
}

// Auto-check conflicts when date/time/checker changes
watch([() => form.scheduled_at, () => form.scheduled_time, () => form.agent_id], () => {
    if (form.scheduled_at && form.scheduled_time) {
        checkConflicts()
    }
}, { debounce: 500 })
</script>