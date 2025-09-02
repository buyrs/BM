<template>
    <Modal :show="show" @close="$emit('close')" max-width="2xl">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">
                    Mission Details
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

            <div v-if="mission" class="space-y-4">
                <!-- Mission Type and Status -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span :class="[
                            'px-2 py-1 rounded-full text-xs font-medium',
                            mission.type === 'entry' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'
                        ]">
                            {{ mission.type === 'entry' ? 'Entry' : 'Exit' }} Mission
                        </span>
                        <span :class="[
                            'px-2 py-1 rounded-full text-xs font-medium',
                            getStatusClasses(mission.status)
                        ]">
                            {{ formatStatus(mission.status) }}
                        </span>
                    </div>
                </div>

                <!-- Mission Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Mission ID</label>
                        <p class="mt-1 text-sm text-gray-900 font-mono">#{{ mission.id }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Created</label>
                        <p class="mt-1 text-sm text-gray-900">{{ formatDate(mission.created_at) }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tenant Name</label>
                        <p class="mt-1 text-sm text-gray-900">{{ mission.tenant_name || 'Not specified' }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Address</label>
                        <p class="mt-1 text-sm text-gray-900">{{ mission.address || 'Not specified' }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Scheduled Date</label>
                        <p class="mt-1 text-sm text-gray-900">{{ formatDate(mission.scheduled_at) }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Scheduled Time</label>
                        <p class="mt-1 text-sm text-gray-900">{{ formatTime(mission.scheduled_time) || 'Not specified' }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Assigned Checker</label>
                        <div class="mt-1 flex items-center space-x-2">
                            <p class="text-sm text-gray-900">{{ mission.agent?.name || 'Unassigned' }}</p>
                            <span v-if="!mission.agent" class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">
                                Needs Assignment
                            </span>
                        </div>
                    </div>
                    
                    <div v-if="mission.notes">
                        <label class="block text-sm font-medium text-gray-700">Notes</label>
                        <p class="mt-1 text-sm text-gray-900">{{ mission.notes }}</p>
                    </div>
                </div>

                <!-- Bail Mobilité Information -->
                <div v-if="mission.bail_mobilite" class="border-t pt-4">
                    <h4 class="text-md font-medium text-gray-900 mb-2">Bail Mobilité Details</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Start Date</label>
                            <p class="mt-1 text-sm text-gray-900">{{ formatDate(mission.bail_mobilite.start_date) }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">End Date</label>
                            <p class="mt-1 text-sm text-gray-900">{{ formatDate(mission.bail_mobilite.end_date) }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Duration</label>
                            <p class="mt-1 text-sm text-gray-900">{{ mission.bail_mobilite.duration_days }} days</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">BM Status</label>
                            <p class="mt-1 text-sm text-gray-900">{{ mission.bail_mobilite.status }}</p>
                        </div>
                    </div>
                </div>

                <!-- Conflicts Warning -->
                <div v-if="mission.conflicts && mission.conflicts.length > 0" class="border-t pt-4">
                    <div class="bg-red-50 border border-red-200 rounded-md p-3">
                        <div class="flex">
                            <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Scheduling Conflicts</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul class="list-disc pl-5 space-y-1">
                                        <li v-for="conflict in mission.conflicts" :key="conflict">
                                            {{ conflict }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div v-if="mission.can_edit || mission.can_assign" class="border-t pt-4">
                    <h4 class="text-md font-medium text-gray-900 mb-3">Quick Actions</h4>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-if="mission.status === 'unassigned' && mission.can_assign"
                            @click="handleAssign"
                            class="px-3 py-2 text-sm font-medium text-blue-700 bg-blue-100 border border-blue-300 rounded-md hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Assign Checker
                        </button>
                        
                        <button
                            v-if="mission.status === 'assigned' && mission.can_edit"
                            @click="handleStart"
                            class="px-3 py-2 text-sm font-medium text-green-700 bg-green-100 border border-green-300 rounded-md hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                        >
                            Start Mission
                        </button>
                        
                        <button
                            v-if="mission.status === 'in_progress' && mission.can_edit"
                            @click="handleComplete"
                            class="px-3 py-2 text-sm font-medium text-green-700 bg-green-100 border border-green-300 rounded-md hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                        >
                            Complete Mission
                        </button>
                        
                        <button
                            v-if="mission.status !== 'cancelled' && mission.can_edit"
                            @click="handleCancel"
                            class="px-3 py-2 text-sm font-medium text-red-700 bg-red-100 border border-red-300 rounded-md hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                        >
                            Cancel Mission
                        </button>
                        
                        <button
                            v-if="mission.bail_mobilite"
                            @click="handleViewBailMobilite"
                            class="px-3 py-2 text-sm font-medium text-purple-700 bg-purple-100 border border-purple-300 rounded-md hover:bg-purple-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
                        >
                            View Bail Mobilité
                        </button>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between pt-4 border-t">
                    <div class="flex space-x-3">
                        <button
                            v-if="mission.can_edit"
                            @click="handleEdit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Edit Mission
                        </button>
                        
                        <button
                            v-if="mission.can_edit"
                            @click="handleDuplicate"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Duplicate
                        </button>
                        
                        <button
                            v-if="mission.can_edit && mission.status !== 'in_progress' && mission.status !== 'completed'"
                            @click="handleDelete"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                        >
                            Delete
                        </button>
                    </div>
                    
                    <button
                        @click="$emit('close')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </Modal>
</template>

<script setup>
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
    }
})

// Emits
const emit = defineEmits(['close', 'update', 'assign', 'status-change', 'duplicate', 'view-bail-mobilite', 'delete'])

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

const handleEdit = () => {
    // Emit update event to parent
    emit('update', props.mission)
}

const handleAssign = () => {
    emit('assign', props.mission)
}

const handleStart = () => {
    emit('status-change', { mission: props.mission, status: 'in_progress' })
}

const handleComplete = () => {
    emit('status-change', { mission: props.mission, status: 'completed' })
}

const handleCancel = () => {
    if (confirm('Are you sure you want to cancel this mission?')) {
        emit('status-change', { mission: props.mission, status: 'cancelled' })
    }
}

const handleDuplicate = () => {
    emit('duplicate', props.mission)
}

const handleViewBailMobilite = () => {
    emit('view-bail-mobilite', props.mission.bail_mobilite)
}

const handleDelete = () => {
    if (confirm('Are you sure you want to delete this mission? This action cannot be undone.')) {
        emit('delete', props.mission)
    }
}
</script>