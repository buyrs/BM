<template>
    <Modal :show="show" @close="$emit('close')" max-width="lg">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">
                    Assign Mission to Checker
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

            <div v-if="mission" class="mb-4 p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-2 mb-2">
                    <span :class="[
                        'px-2 py-1 rounded-full text-xs font-medium',
                        mission.type === 'entry' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'
                    ]">
                        {{ mission.type === 'entry' ? 'Entry' : 'Exit' }} Mission
                    </span>
                    <span class="text-sm text-gray-600">#{{ mission.id }}</span>
                </div>
                <p class="text-sm text-gray-900 font-medium">{{ mission.tenant_name }}</p>
                <p class="text-sm text-gray-600">{{ mission.address }}</p>
                <p class="text-sm text-gray-600">
                    {{ formatDate(mission.scheduled_at) }}
                    <span v-if="mission.scheduled_time">at {{ formatTime(mission.scheduled_time) }}</span>
                </p>
            </div>

            <form @submit.prevent="handleSubmit">
                <div class="space-y-4">
                    <div>
                        <label for="agent_id" class="block text-sm font-medium text-gray-700">
                            Select Checker *
                        </label>
                        <select
                            id="agent_id"
                            v-model="form.agent_id"
                            required
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            :class="{ 'border-red-300': errors.agent_id }"
                        >
                            <option value="">Select a checker...</option>
                            <option
                                v-for="checker in availableCheckers"
                                :key="checker.id"
                                :value="checker.id"
                            >
                                {{ checker.name }}
                                <span v-if="checker.agent?.phone" class="text-gray-500">
                                    ({{ checker.agent.phone }})
                                </span>
                            </option>
                        </select>
                        <p v-if="errors.agent_id" class="mt-1 text-sm text-red-600">
                            {{ errors.agent_id }}
                        </p>
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700">
                            Assignment Notes
                        </label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="3"
                            placeholder="Add any special instructions or notes for the checker..."
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            :class="{ 'border-red-300': errors.notes }"
                        ></textarea>
                        <p v-if="errors.notes" class="mt-1 text-sm text-red-600">
                            {{ errors.notes }}
                        </p>
                    </div>

                    <!-- Checker Availability Info -->
                    <div v-if="selectedChecker" class="bg-blue-50 border border-blue-200 rounded-md p-3">
                        <div class="flex">
                            <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800">Checker Information</h3>
                                <div class="mt-2 text-sm text-blue-700">
                                    <p><strong>Name:</strong> {{ selectedChecker.name }}</p>
                                    <p><strong>Email:</strong> {{ selectedChecker.email }}</p>
                                    <p v-if="selectedChecker.agent?.phone">
                                        <strong>Phone:</strong> {{ selectedChecker.agent.phone }}
                                    </p>
                                    <p v-if="checkerStats[selectedChecker.id]">
                                        <strong>Active Missions:</strong> {{ checkerStats[selectedChecker.id].active_missions || 0 }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Conflict Warning -->
                    <div v-if="conflicts.length > 0" class="bg-yellow-50 border border-yellow-200 rounded-md p-3">
                        <div class="flex">
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">Scheduling Conflicts</h3>
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
                    
                    <button
                        type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        :disabled="loading || !form.agent_id"
                    >
                        <span v-if="loading" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Assigning...
                        </span>
                        <span v-else>Assign Mission</span>
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
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
const emit = defineEmits(['close', 'assigned'])

// State
const loading = ref(false)
const errors = ref({})
const errorMessage = ref('')
const conflicts = ref([])
const checkerStats = ref({})

// Form data
const form = reactive({
    agent_id: '',
    notes: ''
})

// Computed
const availableCheckers = computed(() => {
    return props.checkers.filter(checker => checker.id !== props.mission?.agent?.id)
})

const selectedChecker = computed(() => {
    return props.checkers.find(checker => checker.id === parseInt(form.agent_id))
})

// Watch for show changes to reset state
watch(() => props.show, (show) => {
    if (show) {
        form.agent_id = ''
        form.notes = ''
        errors.value = {}
        errorMessage.value = ''
        conflicts.value = []
        loadCheckerStats()
    }
})

// Watch for checker selection to check conflicts
watch(() => form.agent_id, (checkerId) => {
    if (checkerId && props.mission) {
        checkConflicts()
    } else {
        conflicts.value = []
    }
})

// Methods
const formatDate = (dateString) => {
    if (!dateString) return 'Not specified'
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const formatTime = (timeString) => {
    if (!timeString) return null
    
    const [hours, minutes] = timeString.split(':')
    const hour = parseInt(hours)
    const period = hour >= 12 ? 'PM' : 'AM'
    const displayHour = hour === 0 ? 12 : hour > 12 ? hour - 12 : hour
    
    return `${displayHour}:${minutes} ${period}`
}

const loadCheckerStats = async () => {
    try {
        const response = await fetch(route('ops.api.checkers'))
        const data = await response.json()
        
        if (data.checkers) {
            checkerStats.value = data.checkers.reduce((stats, checker) => {
                stats[checker.id] = {
                    active_missions: checker.active_missions_count || 0
                }
                return stats
            }, {})
        }
    } catch (error) {
        console.error('Error loading checker stats:', error)
    }
}

const checkConflicts = async () => {
    if (!props.mission || !form.agent_id) {
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
                date: new Date(props.mission.scheduled_at).toISOString().split('T')[0],
                time: props.mission.scheduled_time || '09:00',
                checker_id: form.agent_id,
                mission_id: props.mission.id
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
        const response = await fetch(route('ops.calendar.missions.assign', props.mission.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(form)
        })

        const data = await response.json()

        if (data.success) {
            emit('assigned', data.mission)
            emit('close')
        } else {
            if (data.errors) {
                errors.value = data.errors
            } else {
                errorMessage.value = data.message || 'An error occurred while assigning the mission'
            }
        }
    } catch (error) {
        console.error('Error assigning mission:', error)
        errorMessage.value = 'An unexpected error occurred'
    } finally {
        loading.value = false
    }
}
</script>