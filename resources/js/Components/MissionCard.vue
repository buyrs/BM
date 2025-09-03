<template>
    <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition-shadow duration-200">
        <div class="flex justify-between items-start">
            <div>
                <h3 class="text-lg font-semibold text-text-primary">
                    {{ mission.type === 'checkin' ? 'Check-in' : 'Check-out' }}
                </h3>
                <p class="mt-1 text-sm text-text-secondary">{{ mission.address }}</p>
            </div>
            <span :class="getStatusClass(mission.status)" class="text-sm">
                {{ formatStatus(mission.status) }}
            </span>
        </div>

        <div class="mt-4">
            <div class="flex items-center text-sm text-text-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                {{ formatDate(mission.scheduled_at) }}
            </div>
            <div class="mt-2 flex items-center text-sm text-text-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                {{ mission.tenant_name }}
            </div>
        </div>

        <div class="mt-6 flex justify-end space-x-3">
            <Link
                v-if="mission.status === 'in_progress'"
                :href="route('checklist.create', mission.id)"
                class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-accent focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors duration-200"
                :class="{ 'opacity-50 cursor-not-allowed': loading }"
                :disabled="loading"
            >
                <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                {{ loading ? 'Loading...' : 'Démarrer checklist' }}
            </Link>
            <Link
                :href="route('missions.show', mission.id)"
                class="inline-flex items-center px-4 py-2 bg-white text-text-primary text-sm font-medium rounded-lg border border-gray-200 hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors duration-200"
                :class="{ 'opacity-50 cursor-not-allowed': loading }"
                :disabled="loading"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                View Details
            </Link>
            <button
                v-if="mission.status === 'assigned'"
                @click="refuseMission"
                class="inline-flex items-center px-4 py-2 bg-error-border text-white text-sm font-medium rounded-lg hover:bg-error-text focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-error-border transition-colors duration-200"
                :class="{ 'opacity-50 cursor-not-allowed': loading }"
                :disabled="loading"
            >
                <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                {{ loading ? 'Refusing...' : 'Refuse' }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    mission: {
        type: Object,
        required: true
    }
})

const loading = ref(false)

const formatDate = (date) => {
    return new Date(date).toLocaleString()
}

const formatStatus = (status) => {
    const statusMap = {
        unassigned: 'Unassigned',
        assigned: 'Assigned',
        in_progress: 'In Progress',
        completed: 'Completed',
        cancelled: 'Cancelled'
    }
    return statusMap[status] || status
}

const getStatusClass = (status) => {
    const classes = {
        unassigned: 'bg-gray-100 text-gray-800',
        assigned: 'bg-warning-bg text-warning-text',
        in_progress: 'bg-info-bg text-info-text',
        completed: 'bg-success-bg text-success-text',
        cancelled: 'bg-error-bg text-error-text'
    }
    return `px-3 py-1 rounded-full ${classes[status]}`
}

const refuseMission = () => {
    loading.value = true
    router.patch(route('missions.refuse', props.mission.id), {}, {
        onFinish: () => {
            loading.value = false
        }
    })
}
</script> 