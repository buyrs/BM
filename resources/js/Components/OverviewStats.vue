<template>
    <div class="overview-stats space-y-6">
        <!-- Key Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Assigned -->
            <StatCard
                title="Assignés"
                :value="metrics.basic?.assigned || 0"
                icon="clock"
                color="warning"
                :trend="getTrend('assigned')"
                :subtitle="`vs mois dernier`"
            />

            <!-- In Progress -->
            <StatCard
                title="En Cours"
                :value="metrics.basic?.in_progress || 0"
                icon="lightning"
                color="info"
                :subtitle="`Durée moy: ${metrics.average_duration || 0}j`"
            />

            <!-- Completed -->
            <StatCard
                title="Terminés"
                :value="metrics.basic?.completed || 0"
                icon="check-circle"
                color="success"
                :trend="getTrend('completed')"
                :subtitle="`ce mois`"
            />

            <!-- Incidents -->
            <StatCard
                title="Incidents"
                :value="metrics.basic?.incident || 0"
                icon="exclamation-triangle"
                color="error"
                :subtitle="`Taux: ${metrics.incident_rate || 0}%`"
                :details="incidentDetails"
            />
        </div>

        <!-- Attention Required Section -->
        <AttentionSection
            v-if="hasAttentionItems"
            :overdue-missions="overdueMissions"
            :unassigned-missions="unassignedMissions"
            :critical-incidents="criticalIncidents"
        />

        <!-- Performance Overview -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Checker Performance -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-text-primary mb-4">
                    Performance des Checkers (ce mois)
                </h3>
                <div class="space-y-3">
                    <div
                        v-for="checker in topCheckers"
                        :key="checker.name"
                        class="flex items-center justify-between"
                    >
                        <span class="text-sm font-medium text-text-primary">
                            {{ checker.name }}
                        </span>
                        <div class="flex items-center space-x-3">
                            <span class="text-sm text-text-secondary">
                                {{ checker.missions_completed }} missions
                            </span>
                            <div class="w-24 bg-gray-200 rounded-full h-2">
                                <div
                                    class="bg-primary h-2 rounded-full transition-all duration-500"
                                    :style="{ width: getPerformanceWidth(checker.missions_completed) + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                    <div v-if="topCheckers.length === 0" class="text-center py-4 text-text-secondary">
                        Aucune donnée de performance disponible
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-text-primary mb-4">
                    Activité Récente
                </h3>
                <div class="space-y-3">
                    <div
                        v-for="activity in recentActivities"
                        :key="activity.id"
                        class="flex items-start space-x-3"
                    >
                        <div :class="getActivityIconClass(activity.type)" class="p-2 rounded-full">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    :d="getActivityIconPath(activity.type)"
                                />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-text-primary">{{ activity.description }}</p>
                            <p class="text-xs text-text-secondary">
                                {{ formatRelativeTime(activity.created_at) }}
                            </p>
                        </div>
                    </div>
                    <div v-if="recentActivities.length === 0" class="text-center py-4 text-text-secondary">
                        Aucune activité récente
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-semibold text-text-primary mb-4">Actions Rapides</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Link
                    :href="route('ops.bail-mobilites.create')"
                    class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-primary hover:bg-primary hover:bg-opacity-5 transition-colors"
                >
                    <div class="p-2 bg-primary bg-opacity-10 rounded-lg mr-3">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-medium text-text-primary">Nouveau Bail Mobilité</h4>
                        <p class="text-sm text-text-secondary">Créer un nouveau contrat</p>
                    </div>
                </Link>

                <Link
                    :href="route('ops.calendar')"
                    class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-primary hover:bg-primary hover:bg-opacity-5 transition-colors"
                >
                    <div class="p-2 bg-info-bg rounded-lg mr-3">
                        <svg class="w-6 h-6 text-info-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-medium text-text-primary">Calendrier</h4>
                        <p class="text-sm text-text-secondary">Gérer les missions</p>
                    </div>
                </Link>

                <Link
                    :href="route('ops.incidents.index')"
                    class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-primary hover:bg-primary hover:bg-opacity-5 transition-colors"
                >
                    <div class="p-2 bg-error-bg rounded-lg mr-3">
                        <svg class="w-6 h-6 text-error-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-medium text-text-primary">Incidents</h4>
                        <p class="text-sm text-text-secondary">Gérer les incidents</p>
                    </div>
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import StatCard from './StatCard.vue'
import AttentionSection from './AttentionSection.vue'

const props = defineProps({
    metrics: {
        type: Object,
        default: () => ({})
    },
    recentActivities: {
        type: Array,
        default: () => []
    },
    todayMissions: {
        type: Array,
        default: () => []
    },
    kanbanData: {
        type: Object,
        default: () => ({})
    }
})

// Computed properties
const topCheckers = computed(() => {
    return (props.metrics.checker_performance || []).slice(0, 5)
})

const incidentDetails = computed(() => {
    const incidents = props.metrics.incidents || {}
    return {
        'Ouverts': incidents.total_open || 0,
        'Critiques': incidents.critical_open || 0,
        'Aujourd\'hui': incidents.detected_today || 0,
        'Cette semaine': incidents.detected_this_week || 0
    }
})

const overdueMissions = computed(() => {
    const now = new Date()
    return props.todayMissions.filter(mission => 
        new Date(mission.scheduled_date) < now && mission.status !== 'completed'
    ).length
})

const unassignedMissions = computed(() => {
    return Object.values(props.kanbanData).flat().filter(bm => 
        bm.status === 'assigned' && (!bm.entry_mission?.agent_id || !bm.exit_mission?.agent_id)
    ).length
})

const criticalIncidents = computed(() => {
    return props.kanbanData.incident?.length || 0
})

const hasAttentionItems = computed(() => {
    return overdueMissions.value > 0 || unassignedMissions.value > 0 || criticalIncidents.value > 0
})

// Methods
const getTrend = (type) => {
    const current = props.metrics.current_month?.[type === 'assigned' ? 'created' : type] || 0
    const last = props.metrics.last_month?.[type === 'assigned' ? 'created' : type] || 0
    const change = current - last
    
    return {
        value: change,
        direction: change > 0 ? 'up' : change < 0 ? 'down' : 'neutral',
        percentage: last > 0 ? Math.round((change / last) * 100) : 0
    }
}

const getPerformanceWidth = (missions) => {
    const max = Math.max(...topCheckers.value.map(c => c.missions_completed), 1)
    return (missions / max) * 100
}

const getActivityIconClass = (type) => {
    const classes = {
        'mission_completed': 'bg-success-bg text-success-text',
        'incident_reported': 'bg-error-bg text-error-text',
        'bail_mobilite_created': 'bg-info-bg text-info-text',
        'checker_assigned': 'bg-warning-bg text-warning-text'
    }
    return classes[type] || 'bg-gray-100 text-gray-600'
}

const getActivityIconPath = (type) => {
    const paths = {
        'mission_completed': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'incident_reported': 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z',
        'bail_mobilite_created': 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'checker_assigned': 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'
    }
    return paths[type] || 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
}

const formatRelativeTime = (dateString) => {
    const date = new Date(dateString)
    const now = new Date()
    const diffInMinutes = Math.floor((now - date) / (1000 * 60))
    
    if (diffInMinutes < 1) return 'À l\'instant'
    if (diffInMinutes < 60) return `${diffInMinutes}min`
    
    const diffInHours = Math.floor(diffInMinutes / 60)
    if (diffInHours < 24) return `${diffInHours}h`
    
    const diffInDays = Math.floor(diffInHours / 24)
    if (diffInDays < 7) return `${diffInDays}j`
    
    return date.toLocaleDateString('fr-FR', { 
        month: 'short', 
        day: 'numeric' 
    })
}
</script>