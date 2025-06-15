<template>
    <div class="bg-white rounded-xl shadow-md overflow-hidden transition-all duration-200 hover:shadow-lg">
        <div class="p-6">
            <!-- Header -->
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ mission.type === 'checkin' ? 'Check-in' : 'Check-out' }}
                    </h3>
                    <p class="text-sm text-gray-500">
                        {{ formatDate(mission.scheduled_at) }}
                    </p>
                </div>
                <span :class="getStatusBadgeClass(mission.status)" class="px-3 py-1 rounded-full text-sm font-medium">
                    {{ formatStatus(mission.status) }}
                </span>
            </div>

            <!-- Content -->
            <div class="space-y-3">
                <div class="flex items-center text-gray-600">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>{{ mission.address }}</span>
                </div>
                <div class="flex items-center text-gray-600">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span>{{ mission.tenant_name }}</span>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-6 flex justify-end space-x-3">
                <Link
                    v-if="mission.status === 'in_progress'"
                    :href="route('checklist.create', mission.id)"
                    class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Démarrer checklist
                </Link>
                <Link
                    :href="route('missions.show', mission.id)"
                    class="inline-flex items-center px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    View Details
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    mission: {
        type: Object,
        required: true
    }
})

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

const getStatusBadgeClass = (status) => {
    const classes = {
        unassigned: 'bg-gray-100 text-gray-800',
        assigned: 'bg-warning-100 text-warning-800',
        in_progress: 'bg-primary-100 text-primary-800',
        completed: 'bg-success-100 text-success-800',
        cancelled: 'bg-error-100 text-error-800'
    }
    return classes[status] || classes.unassigned
}
</script> 