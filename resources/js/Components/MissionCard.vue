<template>
    <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition-shadow duration-200 border border-gray-100">
        <div class="flex justify-between items-start mb-4">
            <div class="flex-1">
                <div class="flex items-center space-x-2 mb-1">
                    <h3 class="text-lg font-semibold text-text-primary">
                        {{ mission.mission_type === 'entry' ? 'Entrée' : 'Sortie' }}
                    </h3>
                    <span v-if="mission.priority" :class="getPriorityClass(mission.priority)" class="px-2 py-1 text-xs font-medium rounded-full">
                        {{ getPriorityLabel(mission.priority) }}
                    </span>
                </div>
                <p class="text-sm text-text-secondary">{{ mission.address || 'Adresse non spécifiée' }}</p>
                <p v-if="mission.tenant_name" class="text-sm text-text-secondary">{{ mission.tenant_name }}</p>
            </div>
            <span :class="getStatusClass(mission.status)" class="text-sm whitespace-nowrap">
                {{ formatStatus(mission.status) }}
            </span>
        </div>

        <div class="space-y-3">
            <div class="flex items-center text-sm text-text-secondary">
                <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>{{ formatDate(mission.scheduled_at) }}</span>
                <span v-if="mission.scheduled_time" class="ml-2 text-xs">{{ mission.scheduled_time }}</span>
            </div>
            
            <div v-if="mission.agent" class="flex items-center text-sm text-text-secondary">
                <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span>{{ mission.agent.name }}</span>
            </div>
            
            <div v-if="mission.bail_mobilite" class="flex items-center text-sm text-text-secondary">
                <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Bail Mobilité #{{ mission.bail_mobilite.id }}</span>
            </div>
            
            <div v-if="mission.estimated_duration" class="flex items-center text-sm text-text-secondary">
                <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ mission.estimated_duration }} min estimées</span>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-2">
            <!-- Start Mission (for assigned status) -->
            <button
                v-if="mission.status === 'assigned' && canStartMission"
                @click="startMission"
                class="flex-1 min-w-0 inline-flex items-center justify-center px-3 py-2 bg-success-border text-white text-sm font-medium rounded-lg hover:bg-success-text focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-success-border transition-colors duration-200"
                :class="{ 'opacity-50 cursor-not-allowed': loading }"
                :disabled="loading"
            >
                <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ loading ? 'Démarrage...' : 'Démarrer' }}
            </button>

            <!-- Continue Checklist (for in_progress status) -->
            <Link
                v-if="mission.status === 'in_progress'"
                :href="route('missions.checklist', mission.id)"
                class="flex-1 min-w-0 inline-flex items-center justify-center px-3 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-accent focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors duration-200"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Checklist
            </Link>

            <!-- View Details -->
            <button
                @click="$emit('viewDetails', mission)"
                class="flex-1 min-w-0 inline-flex items-center justify-center px-3 py-2 bg-white text-text-primary text-sm font-medium rounded-lg border border-gray-200 hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors duration-200"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Détails
            </button>

            <!-- Refuse Mission (for assigned status) -->
            <button
                v-if="mission.status === 'assigned'"
                @click="refuseMission"
                class="inline-flex items-center px-3 py-2 bg-error-border text-white text-sm font-medium rounded-lg hover:bg-error-text focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-error-border transition-colors duration-200"
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
                {{ loading ? 'Refus...' : 'Refuser' }}
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
    },
    canStartMission: {
        type: Boolean,
        default: true
    }
})

const emit = defineEmits(['viewDetails', 'missionStarted', 'missionRefused'])

const loading = ref(false)

const formatDate = (date) => {
    if (!date) return 'Non programmée'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    })
}

const formatStatus = (status) => {
    const statusMap = {
        unassigned: 'Non assignée',
        assigned: 'Assignée',
        in_progress: 'En cours',
        completed: 'Terminée',
        cancelled: 'Annulée',
        pending_validation: 'En attente'
    }
    return statusMap[status] || status
}

const getStatusClass = (status) => {
    const classes = {
        unassigned: 'bg-gray-100 text-gray-800',
        assigned: 'bg-warning-bg text-warning-text',
        in_progress: 'bg-info-bg text-info-text',
        completed: 'bg-success-bg text-success-text',
        cancelled: 'bg-error-bg text-error-text',
        pending_validation: 'bg-yellow-100 text-yellow-800'
    }
    return `px-3 py-1 rounded-full ${classes[status] || 'bg-gray-100 text-gray-800'}`
}

const getPriorityClass = (priority) => {
    const classes = {
        1: 'bg-red-100 text-red-800',
        2: 'bg-orange-100 text-orange-800',
        3: 'bg-yellow-100 text-yellow-800',
        4: 'bg-blue-100 text-blue-800',
        5: 'bg-gray-100 text-gray-800'
    }
    return classes[priority] || 'bg-gray-100 text-gray-800'
}

const getPriorityLabel = (priority) => {
    const labels = {
        1: 'Urgent',
        2: 'Élevée',
        3: 'Normale',
        4: 'Faible',
        5: 'Très faible'
    }
    return labels[priority] || 'Normale'
}

const startMission = () => {
    loading.value = true
    router.patch(route('missions.start', props.mission.id), {}, {
        onSuccess: () => {
            emit('missionStarted', props.mission)
        },
        onError: (errors) => {
            console.error('Failed to start mission:', errors)
        },
        onFinish: () => {
            loading.value = false
        }
    })
}

const refuseMission = () => {
    if (!confirm('Êtes-vous sûr de vouloir refuser cette mission ?')) {
        return
    }
    
    loading.value = true
    router.patch(route('missions.refuse', props.mission.id), {}, {
        onSuccess: () => {
            emit('missionRefused', props.mission)
        },
        onError: (errors) => {
            console.error('Failed to refuse mission:', errors)
        },
        onFinish: () => {
            loading.value = false
        }
    })
}
</script> 