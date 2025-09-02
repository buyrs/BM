<template>
    <Modal :show="show" @close="$emit('close')" max-width="2xl">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">
                    Bulk Operations
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

            <!-- Selected Missions Summary -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <h4 class="text-sm font-medium text-gray-900 mb-2">
                    Selected Missions ({{ selectedMissions.length }})
                </h4>
                <div class="max-h-32 overflow-y-auto space-y-1">
                    <div
                        v-for="mission in selectedMissions"
                        :key="mission.id"
                        class="flex items-center justify-between text-sm"
                    >
                        <div class="flex items-center space-x-2">
                            <span :class="[
                                'px-2 py-1 rounded-full text-xs font-medium',
                                mission.type === 'entry' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'
                            ]">
                                {{ mission.type === 'entry' ? 'Entry' : 'Exit' }}
                            </span>
                            <span class="text-gray-900">#{{ mission.id }}</span>
                            <span class="text-gray-600">{{ mission.tenant_name }}</span>
                        </div>
                        <span :class="[
                            'px-2 py-1 rounded-full text-xs font-medium',
                            getStatusClasses(mission.status)
                        ]">
                            {{ formatStatus(mission.status) }}
                        </span>
                    </div>
                </div>
            </div>

            <form @submit.prevent="handleSubmit">
                <div class="space-y-4">
                    <!-- Operation Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Select Operation *
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input
                                    v-model="form.action"
                                    type="radio"
                                    value="assign"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                />
                                <span class="ml-2 text-sm text-gray-900">Assign to Checker</span>
                            </label>
                            <label class="flex items-center">
                                <input
                                    v-model="form.action"
                                    type="radio"
                                    value="update_status"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                />
                                <span class="ml-2 text-sm text-gray-900">Update Status</span>
                            </label>
                            <label class="flex items-center">
                                <input
                                    v-model="form.action"
                                    type="radio"
                                    value="delete"
                                    class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300"
                                />
                                <span class="ml-2 text-sm text-red-900">Delete Missions</span>
                            </label>
                        </div>
                        <p v-if="errors.action" class="mt-1 text-sm text-red-600">
                            {{ errors.action }}
                        </p>
                    </div>

                    <!-- Assign to Checker Options -->
                    <div v-if="form.action === 'assign'" class="space-y-4">
                        <div>
                            <label for="agent_id" class="block text-sm font-medium text-gray-700">
                                Select Checker *
                            </label>
                            <select
                                id="agent_id"
                                v-model="form.agent_id"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                :class="{ 'border-red-300': errors.agent_id }"
                            >
                                <option value="">Select a checker...</option>
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

                    <!-- Update Status Options -->
                    <div v-if="form.action === 'update_status'" class="space-y-4">
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">
                                New Status *
                            </label>
                            <select
                                id="status"
                                v-model="form.status"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                :class="{ 'border-red-300': errors.status }"
                            >
                                <option value="">Select status...</option>
                                <option value="unassigned">Unassigned</option>
                                <option value="assigned">Assigned</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <p v-if="errors.status" class="mt-1 text-sm text-red-600">
                                {{ errors.status }}
                            </p>
                        </div>
                    </div>

                    <!-- Delete Confirmation -->
                    <div v-if="form.action === 'delete'" class="bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Warning</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p>You are about to delete {{ selectedMissions.length }} mission(s). This action cannot be undone.</p>
                                    <p class="mt-1">Missions that are in progress or completed cannot be deleted.</p>
                                </div>
                                <div class="mt-3">
                                    <label class="flex items-center">
                                        <input
                                            v-model="deleteConfirmation"
                                            type="checkbox"
                                            class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded"
                                        />
                                        <span class="ml-2 text-sm text-red-900">
                                            I understand that this action cannot be undone
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="form.action !== 'delete'">
                        <label for="notes" class="block text-sm font-medium text-gray-700">
                            Notes
                        </label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="3"
                            placeholder="Add any notes about this bulk operation..."
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            :class="{ 'border-red-300': errors.notes }"
                        ></textarea>
                        <p v-if="errors.notes" class="mt-1 text-sm text-red-600">
                            {{ errors.notes }}
                        </p>
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

                    <!-- Operation Results -->
                    <div v-if="operationResults" class="space-y-3">
                        <div v-if="operationResults.errors && operationResults.errors.length > 0" 
                             class="bg-yellow-50 border border-yellow-200 rounded-md p-3">
                            <div class="flex">
                                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">Some Operations Failed</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <ul class="list-disc pl-5 space-y-1">
                                            <li v-for="error in operationResults.errors" :key="error.mission_id">
                                                Mission #{{ error.mission_id }}: {{ error.error }}
                                            </li>
                                        </ul>
                                    </div>
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
                        :class="[
                            'px-4 py-2 text-sm font-medium border border-transparent rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2',
                            form.action === 'delete' 
                                ? 'text-white bg-red-600 hover:bg-red-700 focus:ring-red-500' 
                                : 'text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500'
                        ]"
                        :disabled="loading || !form.action || (form.action === 'delete' && !deleteConfirmation)"
                    >
                        <span v-if="loading" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        </span>
                        <span v-else>
                            {{ getActionButtonText() }}
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import Modal from '@/Components/Modal.vue'

// Props
const props = defineProps({
    selectedMissions: {
        type: Array,
        default: () => []
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
const emit = defineEmits(['close', 'completed'])

// State
const loading = ref(false)
const errors = ref({})
const errorMessage = ref('')
const deleteConfirmation = ref(false)
const operationResults = ref(null)

// Form data
const form = reactive({
    action: '',
    agent_id: '',
    status: '',
    notes: ''
})

// Watch for show changes to reset state
watch(() => props.show, (show) => {
    if (show) {
        form.action = ''
        form.agent_id = ''
        form.status = ''
        form.notes = ''
        errors.value = {}
        errorMessage.value = ''
        deleteConfirmation.value = false
        operationResults.value = null
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

const getActionButtonText = () => {
    switch (form.action) {
        case 'assign':
            return `Assign ${props.selectedMissions.length} Mission(s)`
        case 'update_status':
            return `Update ${props.selectedMissions.length} Mission(s)`
        case 'delete':
            return `Delete ${props.selectedMissions.length} Mission(s)`
        default:
            return 'Execute Operation'
    }
}

const handleSubmit = async () => {
    if (!form.action || props.selectedMissions.length === 0) return

    loading.value = true
    errors.value = {}
    errorMessage.value = ''
    operationResults.value = null

    try {
        const payload = {
            mission_ids: props.selectedMissions.map(m => m.id),
            action: form.action,
            ...form
        }

        const response = await fetch(route('ops.calendar.missions.bulk-update'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(payload)
        })

        const data = await response.json()

        if (data.success) {
            operationResults.value = data
            emit('completed', data)
            
            // Close modal after a short delay to show results
            setTimeout(() => {
                emit('close')
            }, 2000)
        } else {
            if (data.errors) {
                errors.value = data.errors
            } else {
                errorMessage.value = data.message || 'An error occurred during the bulk operation'
            }
        }
    } catch (error) {
        console.error('Error performing bulk operation:', error)
        errorMessage.value = 'An unexpected error occurred'
    } finally {
        loading.value = false
    }
}
</script>