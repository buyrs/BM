<template>
    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">
                Planification des Missions
            </h3>
            <div class="flex space-x-2">
                <button
                    @click="viewMode = 'calendar'"
                    :class="viewMode === 'calendar' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                    class="px-3 py-1 rounded-md text-sm font-medium transition-colors"
                >
                    Calendrier
                </button>
                <button
                    @click="viewMode = 'list'"
                    :class="viewMode === 'list' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                    class="px-3 py-1 rounded-md text-sm font-medium transition-colors"
                >
                    Liste
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Checker</label>
                <select
                    v-model="filters.checker_id"
                    @change="applyFilters"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Tous les checkers</option>
                    <option v-for="checker in checkers" :key="checker.id" :value="checker.id">
                        {{ checker.name }}
                    </option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                <select
                    v-model="filters.status"
                    @change="applyFilters"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Tous les statuts</option>
                    <option value="unassigned">Non assignées</option>
                    <option value="assigned">Assignées</option>
                    <option value="in_progress">En cours</option>
                    <option value="completed">Terminées</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select
                    v-model="filters.mission_type"
                    @change="applyFilters"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Tous les types</option>
                    <option value="entry">Entrée</option>
                    <option value="exit">Sortie</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Période</label>
                <select
                    v-model="filters.period"
                    @change="applyFilters"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="today">Aujourd'hui</option>
                    <option value="week">Cette semaine</option>
                    <option value="month">Ce mois</option>
                    <option value="all">Toutes</option>
                </select>
            </div>
        </div>

        <!-- Calendar View -->
        <div v-if="viewMode === 'calendar'" class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-4">
                    <button
                        @click="previousPeriod"
                        class="p-2 hover:bg-gray-100 rounded-md transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    
                    <h4 class="text-lg font-medium text-gray-900">
                        {{ formatPeriodTitle(currentPeriod) }}
                    </h4>
                    
                    <button
                        @click="nextPeriod"
                        class="p-2 hover:bg-gray-100 rounded-md transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
                
                <button
                    @click="goToToday"
                    class="px-3 py-1 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 transition-colors"
                >
                    Aujourd'hui
                </button>
            </div>
            
            <!-- Calendar Grid -->
            <div class="grid grid-cols-7 gap-1 mb-2">
                <div
                    v-for="day in weekDays"
                    :key="day"
                    class="p-2 text-center text-sm font-medium text-gray-700 bg-gray-50"
                >
                    {{ day }}
                </div>
            </div>
            
            <div class="grid grid-cols-7 gap-1">
                <div
                    v-for="date in calendarDates"
                    :key="date.dateString"
                    class="min-h-24 p-1 border border-gray-200 bg-white"
                    :class="{
                        'bg-gray-50': !date.isCurrentMonth,
                        'bg-blue-50 border-blue-300': date.isToday
                    }"
                >
                    <div class="text-sm font-medium text-gray-900 mb-1">
                        {{ date.day }}
                    </div>
                    
                    <div class="space-y-1">
                        <div
                            v-for="mission in getMissionsForDate(date.dateString)"
                            :key="mission.id"
                            class="text-xs p-1 rounded cursor-pointer truncate"
                            :class="getMissionCalendarClass(mission)"
                            @click="$emit('missionSelected', mission)"
                            :title="`${mission.mission_type === 'entry' ? 'Entrée' : 'Sortie'} - ${mission.tenant_name || 'Sans nom'}`"
                        >
                            {{ mission.scheduled_time || '00:00' }} - {{ mission.tenant_name || 'Sans nom' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- List View -->
        <div v-if="viewMode === 'list'">
            <div class="space-y-4">
                <div
                    v-for="mission in filteredMissions"
                    :key="mission.id"
                    class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
                    @click="$emit('missionSelected', mission)"
                >
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center space-x-3">
                            <span :class="getMissionTypeClass(mission.mission_type)" class="px-2 py-1 rounded-full text-xs font-medium">
                                {{ mission.mission_type === 'entry' ? 'Entrée' : 'Sortie' }}
                            </span>
                            <span :class="getMissionStatusClass(mission.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                                {{ formatMissionStatus(mission.status) }}
                            </span>
                            <span v-if="mission.priority && mission.priority <= 2" :class="getPriorityClass(mission.priority)" class="px-2 py-1 rounded-full text-xs font-medium">
                                {{ getPriorityLabel(mission.priority) }}
                            </span>
                        </div>
                        
                        <div class="text-sm text-gray-500">
                            {{ formatDateTime(mission.scheduled_at) }}
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-gray-700">Locataire:</span>
                            <span class="ml-2">{{ mission.tenant_name || 'Non spécifié' }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Adresse:</span>
                            <span class="ml-2">{{ mission.address || 'Non spécifiée' }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Checker:</span>
                            <span class="ml-2">{{ mission.agent?.name || 'Non assigné' }}</span>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="flex justify-end space-x-2 mt-3">
                        <button
                            v-if="mission.status === 'unassigned'"
                            @click.stop="$emit('assignMission', mission)"
                            class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition-colors"
                        >
                            Assigner
                        </button>
                        
                        <button
                            @click.stop="$emit('editMission', mission)"
                            class="px-3 py-1 bg-gray-600 text-white text-xs rounded hover:bg-gray-700 transition-colors"
                        >
                            Modifier
                        </button>
                        
                        <button
                            v-if="mission.status === 'assigned'"
                            @click.stop="$emit('startMission', mission)"
                            class="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700 transition-colors"
                        >
                            Démarrer
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Empty State -->
            <div v-if="filteredMissions.length === 0" class="text-center py-12">
                <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune mission trouvée</h3>
                <p class="text-gray-600">Aucune mission ne correspond aux filtres sélectionnés.</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'

const props = defineProps({
    missions: {
        type: Array,
        default: () => []
    },
    checkers: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits([
    'missionSelected',
    'assignMission',
    'editMission',
    'startMission'
])

const viewMode = ref('calendar')
const currentPeriod = ref(new Date())

const filters = reactive({
    checker_id: '',
    status: '',
    mission_type: '',
    period: 'week'
})

const weekDays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']

const filteredMissions = computed(() => {
    let filtered = [...props.missions]
    
    if (filters.checker_id) {
        filtered = filtered.filter(m => m.agent_id == filters.checker_id)
    }
    
    if (filters.status) {
        filtered = filtered.filter(m => m.status === filters.status)
    }
    
    if (filters.mission_type) {
        filtered = filtered.filter(m => m.mission_type === filters.mission_type)
    }
    
    // Apply period filter
    const now = new Date()
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
    
    if (filters.period === 'today') {
        filtered = filtered.filter(m => {
            const missionDate = new Date(m.scheduled_at)
            return missionDate >= today && missionDate < new Date(today.getTime() + 24 * 60 * 60 * 1000)
        })
    } else if (filters.period === 'week') {
        const weekStart = new Date(today)
        weekStart.setDate(today.getDate() - today.getDay() + 1)
        const weekEnd = new Date(weekStart)
        weekEnd.setDate(weekStart.getDate() + 7)
        
        filtered = filtered.filter(m => {
            const missionDate = new Date(m.scheduled_at)
            return missionDate >= weekStart && missionDate < weekEnd
        })
    } else if (filters.period === 'month') {
        const monthStart = new Date(today.getFullYear(), today.getMonth(), 1)
        const monthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 1)
        
        filtered = filtered.filter(m => {
            const missionDate = new Date(m.scheduled_at)
            return missionDate >= monthStart && missionDate < monthEnd
        })
    }
    
    return filtered.sort((a, b) => new Date(a.scheduled_at) - new Date(b.scheduled_at))
})

const calendarDates = computed(() => {
    const year = currentPeriod.value.getFullYear()
    const month = currentPeriod.value.getMonth()
    
    const firstDay = new Date(year, month, 1)
    const lastDay = new Date(year, month + 1, 0)
    const startDate = new Date(firstDay)
    startDate.setDate(startDate.getDate() - (firstDay.getDay() || 7) + 1)
    
    const dates = []
    const today = new Date()
    
    for (let i = 0; i < 42; i++) {
        const date = new Date(startDate)
        date.setDate(startDate.getDate() + i)
        
        dates.push({
            date,
            day: date.getDate(),
            dateString: date.toISOString().split('T')[0],
            isCurrentMonth: date.getMonth() === month,
            isToday: date.toDateString() === today.toDateString()
        })
    }
    
    return dates
})

const formatPeriodTitle = (date) => {
    return date.toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long'
    })
}

const formatDateTime = (dateString) => {
    if (!dateString) return 'Non programmée'
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const formatMissionStatus = (status) => {
    const statusMap = {
        'unassigned': 'Non assignée',
        'assigned': 'Assignée',
        'in_progress': 'En cours',
        'completed': 'Terminée',
        'cancelled': 'Annulée'
    }
    return statusMap[status] || status
}

const getMissionsForDate = (dateString) => {
    return filteredMissions.value.filter(mission => {
        const missionDate = new Date(mission.scheduled_at).toISOString().split('T')[0]
        return missionDate === dateString
    })
}

const getMissionCalendarClass = (mission) => {
    const classes = {
        'unassigned': 'bg-gray-200 text-gray-800',
        'assigned': 'bg-yellow-200 text-yellow-800',
        'in_progress': 'bg-blue-200 text-blue-800',
        'completed': 'bg-green-200 text-green-800',
        'cancelled': 'bg-red-200 text-red-800'
    }
    return classes[mission.status] || 'bg-gray-200 text-gray-800'
}

const getMissionTypeClass = (type) => {
    return type === 'entry' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'
}

const getMissionStatusClass = (status) => {
    const classes = {
        'unassigned': 'bg-gray-100 text-gray-800',
        'assigned': 'bg-yellow-100 text-yellow-800',
        'in_progress': 'bg-blue-100 text-blue-800',
        'completed': 'bg-green-100 text-green-800',
        'cancelled': 'bg-red-100 text-red-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const getPriorityClass = (priority) => {
    const classes = {
        1: 'bg-red-100 text-red-800',
        2: 'bg-orange-100 text-orange-800'
    }
    return classes[priority] || 'bg-gray-100 text-gray-800'
}

const getPriorityLabel = (priority) => {
    const labels = {
        1: 'Urgent',
        2: 'Élevée'
    }
    return labels[priority] || 'Normale'
}

const applyFilters = () => {
    // Filters are reactive, so this will automatically update filteredMissions
}

const previousPeriod = () => {
    const newDate = new Date(currentPeriod.value)
    newDate.setMonth(newDate.getMonth() - 1)
    currentPeriod.value = newDate
}

const nextPeriod = () => {
    const newDate = new Date(currentPeriod.value)
    newDate.setMonth(newDate.getMonth() + 1)
    currentPeriod.value = newDate
}

const goToToday = () => {
    currentPeriod.value = new Date()
}

onMounted(() => {
    // Initialize with current period
    currentPeriod.value = new Date()
})
</script>
</template>