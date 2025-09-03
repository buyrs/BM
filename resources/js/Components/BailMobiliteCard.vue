<template>
    <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-md transition-shadow duration-200">
        <!-- Header -->
        <div class="flex items-center justify-between mb-3">
            <h4 class="font-medium text-text-primary truncate">{{ bailMobilite.tenant_name }}</h4>
            <span :class="getStatusClass(bailMobilite.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                {{ getStatusLabel(bailMobilite.status) }}
            </span>
        </div>

        <!-- Address -->
        <p class="text-sm text-text-secondary mb-2 line-clamp-2">{{ bailMobilite.address }}</p>

        <!-- Dates -->
        <div class="text-xs text-text-secondary mb-3">
            <div class="flex items-center mb-1">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                {{ formatDate(bailMobilite.start_date) }} - {{ formatDate(bailMobilite.end_date) }}
            </div>
            <div class="text-xs text-text-secondary">
                {{ getDurationText(bailMobilite.start_date, bailMobilite.end_date) }}
            </div>
        </div>

        <!-- Mission Status -->
        <div class="space-y-2 mb-4">
            <!-- Entry Mission -->
            <div class="flex items-center justify-between text-xs">
                <span class="text-text-secondary">Entrée:</span>
                <div class="flex items-center space-x-1">
                    <span v-if="bailMobilite.entry_mission?.agent" class="text-success-text">
                        {{ bailMobilite.entry_mission.agent.name }}
                    </span>
                    <span v-else class="text-text-secondary">Non assigné</span>
                    <div :class="getMissionStatusClass(bailMobilite.entry_mission?.status)" class="w-2 h-2 rounded-full"></div>
                </div>
            </div>

            <!-- Exit Mission -->
            <div class="flex items-center justify-between text-xs">
                <span class="text-text-secondary">Sortie:</span>
                <div class="flex items-center space-x-1">
                    <span v-if="bailMobilite.exit_mission?.agent" class="text-success-text">
                        {{ bailMobilite.exit_mission.agent.name }}
                    </span>
                    <span v-else class="text-text-secondary">Non assigné</span>
                    <div :class="getMissionStatusClass(bailMobilite.exit_mission?.status)" class="w-2 h-2 rounded-full"></div>
                </div>
            </div>
        </div>

        <!-- Remaining Days (for in_progress status) -->
        <div v-if="bailMobilite.status === 'in_progress'" class="mb-3">
            <div class="flex items-center text-xs">
                <svg class="w-3 h-3 mr-1 text-warning-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span :class="getRemainingDaysClass(bailMobilite.end_date)">
                    {{ getRemainingDaysText(bailMobilite.end_date) }}
                </span>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-wrap gap-2">
            <!-- View Details -->
            <button
                @click="$emit('viewDetails', bailMobilite)"
                class="flex-1 px-3 py-1.5 text-xs font-medium text-primary bg-secondary rounded hover:bg-accent hover:text-white focus:outline-none focus:ring-2 focus:ring-primary"
            >
                Détails
            </button>

            <!-- Assign Entry (for assigned status without entry assignment) -->
            <button
                v-if="bailMobilite.status === 'assigned' && !bailMobilite.entry_mission?.agent_id"
                @click="$emit('assignEntry', bailMobilite)"
                class="flex-1 px-3 py-1.5 text-xs font-medium text-success-text bg-success-bg rounded hover:bg-success-border hover:text-white focus:outline-none focus:ring-2 focus:ring-success-border"
            >
                Assigner Entrée
            </button>

            <!-- Assign Exit (for in_progress status without exit assignment) -->
            <button
                v-if="bailMobilite.status === 'in_progress' && !bailMobilite.exit_mission?.agent_id"
                @click="$emit('assignExit', bailMobilite)"
                class="flex-1 px-3 py-1.5 text-xs font-medium text-warning-text bg-warning-bg rounded hover:bg-warning-border hover:text-white focus:outline-none focus:ring-2 focus:ring-warning-border"
            >
                Assigner Sortie
            </button>

            <!-- Handle Incident (for incident status) -->
            <button
                v-if="bailMobilite.status === 'incident'"
                @click="$emit('handleIncident', bailMobilite)"
                class="flex-1 px-3 py-1.5 text-xs font-medium text-error-text bg-error-bg rounded hover:bg-error-border hover:text-white focus:outline-none focus:ring-2 focus:ring-error-border"
            >
                Gérer Incident
            </button>
        </div>
    </div>
</template>

<script setup>
defineEmits(['viewDetails', 'assignEntry', 'assignExit', 'handleIncident'])

defineProps({
    bailMobilite: {
        type: Object,
        required: true
    },
    checkers: {
        type: Array,
        default: () => []
    }
})

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: '2-digit'
    })
}

const getDurationText = (startDate, endDate) => {
    const start = new Date(startDate)
    const end = new Date(endDate)
    const diffTime = end - start
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    return `${diffDays} jour${diffDays > 1 ? 's' : ''}`
}

const getRemainingDaysText = (endDate) => {
    const today = new Date()
    const end = new Date(endDate)
    const diffTime = end - today
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    
    if (diffDays < 0) return 'Terminé'
    if (diffDays === 0) return 'Aujourd\'hui'
    if (diffDays === 1) return 'Demain'
    return `${diffDays} jours restants`
}

const getRemainingDaysClass = (endDate) => {
    const today = new Date()
    const end = new Date(endDate)
    const diffTime = end - today
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    
    if (diffDays <= 3) return 'text-error-text font-medium'
    if (diffDays <= 10) return 'text-warning-text'
    return 'text-text-secondary'
}

const getStatusClass = (status) => {
    const classes = {
        assigned: 'bg-warning-bg text-warning-text',
        in_progress: 'bg-info-bg text-info-text',
        completed: 'bg-success-bg text-success-text',
        incident: 'bg-error-bg text-error-text'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusLabel = (status) => {
    const labels = {
        assigned: 'Assigné',
        in_progress: 'En Cours',
        completed: 'Terminé',
        incident: 'Incident'
    }
    return labels[status] || status
}

const getMissionStatusClass = (status) => {
    const classes = {
        unassigned: 'bg-gray-300',
        assigned: 'bg-warning-border',
        in_progress: 'bg-info-border',
        completed: 'bg-success-border',
        cancelled: 'bg-error-border'
    }
    return classes[status] || 'bg-gray-300'
}
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>