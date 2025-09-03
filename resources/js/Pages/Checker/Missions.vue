<template>
    <Head title="My Missions" />

    <DashboardChecker>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-primary">My Missions</h2>
                    <p class="text-text-secondary mt-1">
                        Manage and track your assigned property inspections.
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="bg-white rounded-lg px-4 py-2 shadow-sm border">
                        <span class="text-sm text-text-secondary">Total: </span>
                        <span class="font-semibold text-primary">{{ filteredMissions.length }}</span>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex-1 min-w-64">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search by address, tenant name..."
                                class="w-full bg-white border-gray-200 rounded-md shadow-sm p-3 focus:ring-2 focus:ring-primary focus:border-transparent"
                            />
                        </div>
                        
                        <select
                            v-model="filterStatus"
                            class="bg-white border-gray-200 rounded-md shadow-sm p-3 focus:ring-2 focus:ring-primary focus:border-transparent"
                        >
                            <option value="all">All Status</option>
                            <option value="assigned">Assigned</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="overdue">Overdue</option>
                        </select>

                        <select
                            v-model="filterType"
                            class="bg-white border-gray-200 rounded-md shadow-sm p-3 focus:ring-2 focus:ring-primary focus:border-transparent"
                        >
                            <option value="all">All Types</option>
                            <option value="checkin">Check-in</option>
                            <option value="checkout">Check-out</option>
                        </select>

                        <select
                            v-model="sortBy"
                            class="bg-white border-gray-200 rounded-md shadow-sm p-3 focus:ring-2 focus:ring-primary focus:border-transparent"
                        >
                            <option value="scheduled_at">Sort by Date</option>
                            <option value="status">Sort by Status</option>
                            <option value="address">Sort by Address</option>
                        </select>
                        
                        <button
                            @click="resetFilters"
                            class="px-4 py-3 text-sm text-text-secondary hover:text-primary transition-colors duration-200"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <!-- Mission Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-warning-border">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-text-secondary">Assigned</p>
                                <p class="text-2xl font-bold text-warning-text">{{ getStatusCount('assigned') }}</p>
                            </div>
                            <div class="p-2 rounded-full bg-warning-bg">
                                <svg class="w-5 h-5 text-warning-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-info-border">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-text-secondary">In Progress</p>
                                <p class="text-2xl font-bold text-info-text">{{ getStatusCount('in_progress') }}</p>
                            </div>
                            <div class="p-2 rounded-full bg-info-bg">
                                <svg class="w-5 h-5 text-info-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-success-border">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-text-secondary">Completed</p>
                                <p class="text-2xl font-bold text-success-text">{{ getStatusCount('completed') }}</p>
                            </div>
                            <div class="p-2 rounded-full bg-success-bg">
                                <svg class="w-5 h-5 text-success-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-error-border">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-text-secondary">Overdue</p>
                                <p class="text-2xl font-bold text-error-text">{{ getOverdueCount() }}</p>
                            </div>
                            <div class="p-2 rounded-full bg-error-bg">
                                <svg class="w-5 h-5 text-error-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Missions Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="mission in filteredMissions"
                        :key="mission.id"
                        class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition-shadow duration-200"
                    >
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <h3 class="font-semibold text-text-primary text-lg mb-1">
                                    {{ mission.address || 'Property Address' }}
                                </h3>
                                <p class="text-sm text-text-secondary">
                                    {{ mission.tenant_name || 'Tenant Name' }}
                                </p>
                            </div>
                            <span :class="[
                                'text-xs px-3 py-1 rounded-full font-medium',
                                getStatusClass(mission.status)
                            ]">
                                {{ formatStatus(mission.status) }}
                            </span>
                        </div>
                        
                        <div class="space-y-3 mb-4">
                            <div class="flex items-center text-sm text-text-secondary">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                {{ formatDateTime(mission.scheduled_at) }}
                            </div>
                            
                            <div class="flex items-center text-sm text-text-secondary">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                {{ mission.type === 'checkin' ? 'Check-in Inspection' : 'Check-out Inspection' }}
                            </div>
                            
                            <div v-if="mission.priority" class="flex items-center text-sm">
                                <svg class="w-4 h-4 mr-2" :class="getPriorityColor(mission.priority)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                                <span :class="getPriorityColor(mission.priority)">
                                    {{ mission.priority.charAt(0).toUpperCase() + mission.priority.slice(1) }} Priority
                                </span>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                            <div class="flex items-center space-x-2">
                                <Link
                                    :href="route('missions.show', mission.id)"
                                    class="text-primary hover:text-accent text-sm font-medium"
                                >
                                    View Details
                                </Link>
                                <span v-if="mission.status === 'assigned'" class="text-gray-300">•</span>
                                <button
                                    v-if="mission.status === 'assigned'"
                                    @click="startMission(mission.id)"
                                    class="text-success-text hover:text-green-700 text-sm font-medium"
                                >
                                    Start Mission
                                </button>
                            </div>
                            
                            <div v-if="isOverdue(mission.scheduled_at)" class="text-error-text text-xs font-medium">
                                Overdue
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredMissions.length === 0" class="text-center py-12">
                    <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="text-xl font-semibold text-text-primary mb-2">No missions found</h3>
                    <p class="text-text-secondary mb-6">No missions match your current filters.</p>
                    <button
                        @click="resetFilters"
                        class="bg-primary text-white px-6 py-3 rounded-md hover:bg-accent transition-colors duration-200"
                    >
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>
    </DashboardChecker>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import DashboardChecker from '@/Layouts/DashboardChecker.vue'

const props = defineProps({
    missions: {
        type: Object,
        required: true,
        default: () => ({ data: [] })
    }
})

const searchQuery = ref('')
const filterStatus = ref('all')
const filterType = ref('all')
const sortBy = ref('scheduled_at')

const filteredMissions = computed(() => {
    let filtered = props.missions.data.filter(mission => {
        const searchMatch = searchQuery.value === '' || 
            mission.address?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            mission.tenant_name?.toLowerCase().includes(searchQuery.value.toLowerCase())
        const statusMatch = filterStatus.value === 'all' || mission.status === filterStatus.value
        const typeMatch = filterType.value === 'all' || mission.type === filterType.value
        
        return searchMatch && statusMatch && typeMatch
    })

    // Sort missions
    filtered.sort((a, b) => {
        switch (sortBy.value) {
            case 'scheduled_at':
                return new Date(a.scheduled_at) - new Date(b.scheduled_at)
            case 'status':
                return a.status.localeCompare(b.status)
            case 'address':
                return (a.address || '').localeCompare(b.address || '')
            default:
                return 0
        }
    })

    return filtered
})

const getStatusCount = (status) => {
    return props.missions.data.filter(mission => mission.status === status).length
}

const getOverdueCount = () => {
    const now = new Date()
    return props.missions.data.filter(mission => 
        new Date(mission.scheduled_at) < now && mission.status !== 'completed'
    ).length
}

const getStatusClass = (status) => {
    const statusClasses = {
        completed: "bg-success-bg text-success-text",
        in_progress: "bg-info-bg text-info-text",
        assigned: "bg-warning-bg text-warning-text",
        pending: "bg-gray-100 text-gray-800",
        overdue: "bg-error-bg text-error-text",
    }
    return statusClasses[status] || "bg-gray-100 text-gray-800"
}

const formatStatus = (status) => {
    const statusLabels = {
        completed: "Completed",
        in_progress: "In Progress",
        assigned: "Assigned",
        pending: "Pending",
        overdue: "Overdue",
    }
    return statusLabels[status] || status
}

const formatDateTime = (dateString) => {
    if (!dateString) return 'N/A'
    return new Date(dateString).toLocaleString("en-US", {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getPriorityColor = (priority) => {
    const priorityColors = {
        high: "text-error-text",
        medium: "text-warning-text",
        low: "text-info-text",
    }
    return priorityColors[priority] || "text-text-secondary"
}

const isOverdue = (scheduledAt) => {
    return new Date(scheduledAt) < new Date()
}

const resetFilters = () => {
    searchQuery.value = ''
    filterStatus.value = 'all'
    filterType.value = 'all'
    sortBy.value = 'scheduled_at'
}

const startMission = (missionId) => {
    router.patch(route('missions.start', missionId), {}, {
        onSuccess: () => {
            // Mission started successfully
        }
    })
}
</script> 