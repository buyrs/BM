<template>
    <DashboardOps>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight text-[var(--text-color)]">
                        Incidents
                    </h2>
                    <p class="text-[var(--text-muted-color)] mt-1">
                        Track and resolve property incidents efficiently
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="runIncidentDetection"
                        :disabled="isRunningDetection"
                        class="inline-flex items-center justify-center rounded-lg border border-[var(--border-color)] bg-[var(--card-bg-color)] px-4 py-2 text-sm font-medium text-[var(--text-color)] shadow-sm transition-colors hover:bg-[var(--bg-color)] focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)] focus:ring-offset-2 disabled:opacity-50"
                    >
                        <span class="material-symbols-outlined mr-2 text-base" :class="{ 'animate-spin': isRunningDetection }">
                            {{ isRunningDetection ? 'refresh' : 'search' }}
                        </span>
                        <span v-if="isRunningDetection">Détection en cours...</span>
                        <span v-else>Détecter les incidents</span>
                    </button>
                    <button
                        @click="showBulkActions = !showBulkActions"
                        :class="[
                            'inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2',
                            selectedIncidents.length > 0 
                                ? 'bg-green-500 hover:bg-green-600 text-white focus:ring-green-500' 
                                : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                        ]"
                        :disabled="selectedIncidents.length === 0"
                    >
                        <span class="material-symbols-outlined mr-2 text-base">checklist</span>
                        Actions groupées ({{ selectedIncidents.length }})
                    </button>
                    <button class="inline-flex items-center justify-center rounded-lg bg-[var(--primary-color)] px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-[var(--primary-hover-color)] focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)] focus:ring-offset-2 focus:ring-offset-[var(--bg-color)]">
                        <span class="material-symbols-outlined mr-2 text-base">add</span>
                        New Incident
                    </button>
                </div>
            </div>
        </template>

        <!-- Search and Filters -->
        <div class="rounded-xl border border-[var(--border-color)] bg-[var(--card-bg-color)] shadow-sm overflow-hidden">
            <div class="border-b border-[var(--border-color)] p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="relative flex-1 max-w-md">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[var(--text-muted-color)]">search</span>
                        <input 
                            v-model="searchQuery"
                            @input="debouncedSearch"
                            class="w-full rounded-lg border border-[var(--border-color)] bg-[var(--bg-color)] py-2.5 pl-10 pr-4 text-sm focus:border-[var(--primary-color)] focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)]/20 placeholder:text-[var(--text-muted-color)] text-[var(--text-color)]" 
                            placeholder="Search incidents..." 
                            type="search"
                        />
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <button 
                            @click="showFilters = !showFilters"
                            class="inline-flex items-center gap-2 rounded-lg border border-[var(--border-color)] bg-[var(--card-bg-color)] px-4 py-2.5 text-sm font-medium text-[var(--text-color)] hover:bg-[var(--bg-color)] transition-colors"
                        >
                            <span class="material-symbols-outlined text-base">filter_list</span>
                            Filters
                            <span class="material-symbols-outlined text-base" :class="{ 'rotate-180': showFilters }">expand_more</span>
                        </button>
                        
                        <button class="inline-flex items-center gap-2 rounded-lg border border-[var(--border-color)] bg-[var(--card-bg-color)] px-4 py-2.5 text-sm font-medium text-[var(--text-color)] hover:bg-[var(--bg-color)] transition-colors">
                            <span class="material-symbols-outlined text-base">swap_vert</span>
                            Sort
                        </button>
                    </div>
                </div>
                
                <!-- Filter Dropdown -->
                <div v-show="showFilters" class="mt-4 transition-all duration-300 ease-in-out">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 p-4 bg-[var(--bg-color)] rounded-lg border border-[var(--border-color)]">
                        <div>
                            <label class="block text-sm font-medium text-[var(--text-color)] mb-2">Statut</label>
                            <select v-model="filters.status" @change="applyFilters" class="w-full rounded-lg border border-[var(--border-color)] bg-[var(--card-bg-color)] px-3 py-2 text-sm text-[var(--text-color)] focus:border-[var(--primary-color)] focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)]/20">
                                <option value="all">Tous les statuts</option>
                                <option v-for="(label, value) in statusOptions" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[var(--text-color)] mb-2">Sévérité</label>
                            <select v-model="filters.severity" @change="applyFilters" class="w-full rounded-lg border border-[var(--border-color)] bg-[var(--card-bg-color)] px-3 py-2 text-sm text-[var(--text-color)] focus:border-[var(--primary-color)] focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)]/20">
                                <option value="all">Toutes les sévérités</option>
                                <option v-for="(label, value) in severityLevels" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[var(--text-color)] mb-2">Type</label>
                            <select v-model="filters.type" @change="applyFilters" class="w-full rounded-lg border border-[var(--border-color)] bg-[var(--card-bg-color)] px-3 py-2 text-sm text-[var(--text-color)] focus:border-[var(--primary-color)] focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)]/20">
                                <option value="all">Tous les types</option>
                                <option v-for="(label, value) in incidentTypes" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[var(--text-color)] mb-2">Bail Mobilité</label>
                            <select v-model="filters.bail_mobilite_id" @change="applyFilters" class="w-full rounded-lg border border-[var(--border-color)] bg-[var(--card-bg-color)] px-3 py-2 text-sm text-[var(--text-color)] focus:border-[var(--primary-color)] focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)]/20">
                                <option value="">Tous les BM</option>
                                <option v-for="bm in bailMobilites" :key="bm.id" :value="bm.id">
                                    {{ bm.tenant_name }} - {{ bm.address }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 p-4 bg-[var(--bg-color)] rounded-lg border border-[var(--border-color)]">
                        <div>
                            <label class="block text-sm font-medium text-[var(--text-color)] mb-2">Date de début</label>
                            <input 
                                type="date" 
                                v-model="filters.date_from" 
                                @change="applyFilters"
                                class="w-full rounded-lg border border-[var(--border-color)] bg-[var(--card-bg-color)] px-3 py-2 text-sm text-[var(--text-color)] focus:border-[var(--primary-color)] focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)]/20"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[var(--text-color)] mb-2">Date de fin</label>
                            <input 
                                type="date" 
                                v-model="filters.date_to" 
                                @change="applyFilters"
                                class="w-full rounded-lg border border-[var(--border-color)] bg-[var(--card-bg-color)] px-3 py-2 text-sm text-[var(--text-color)] focus:border-[var(--primary-color)] focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)]/20"
                            >
                        </div>
                    </div>
                </div>
            </div>

        <!-- Bulk Actions -->
        <div v-if="showBulkActions && selectedIncidents.length > 0" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-8 h-8 bg-blue-100 dark:bg-blue-800 rounded-full">
                        <span class="material-symbols-outlined text-blue-600 dark:text-blue-300 text-sm">checklist</span>
                    </div>
                    <span class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        {{ selectedIncidents.length }} incident(s) sélectionné(s)
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="bulkUpdate('mark_in_progress')"
                        class="inline-flex items-center gap-1 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium py-1.5 px-3 rounded-lg transition-colors"
                    >
                        <span class="material-symbols-outlined text-sm">play_arrow</span>
                        En cours
                    </button>
                    <button
                        @click="bulkUpdate('mark_resolved')"
                        class="inline-flex items-center gap-1 bg-green-500 hover:bg-green-600 text-white text-sm font-medium py-1.5 px-3 rounded-lg transition-colors"
                    >
                        <span class="material-symbols-outlined text-sm">check_circle</span>
                        Résolu
                    </button>
                    <button
                        @click="bulkUpdate('mark_closed')"
                        class="inline-flex items-center gap-1 bg-gray-500 hover:bg-gray-600 text-white text-sm font-medium py-1.5 px-3 rounded-lg transition-colors"
                    >
                        <span class="material-symbols-outlined text-sm">close</span>
                        Fermer
                    </button>
                </div>
            </div>
        </div>

            <!-- Incidents Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[var(--border-color)]">
                    <thead class="bg-[var(--bg-color)]">
                        <tr>
                            <th class="py-4 pl-6 pr-3 text-left text-sm font-semibold text-[var(--text-color)]" scope="col">
                                <input 
                                    type="checkbox" 
                                    @change="toggleSelectAll"
                                    :checked="selectedIncidents.length === incidents.data.length && incidents.data.length > 0"
                                    class="rounded border-[var(--border-color)] text-[var(--primary-color)] focus:ring-[var(--primary-color)]"
                                >
                            </th>
                            <th class="py-4 px-3 text-left text-sm font-semibold text-[var(--text-color)]" scope="col">Incident</th>
                            <th class="hidden px-3 py-4 text-left text-sm font-semibold text-[var(--text-color)] lg:table-cell" scope="col">Bail Mobilité</th>
                            <th class="hidden px-3 py-4 text-left text-sm font-semibold text-[var(--text-color)] sm:table-cell" scope="col">Sévérité</th>
                            <th class="px-3 py-4 text-left text-sm font-semibold text-[var(--text-color)]" scope="col">Statut</th>
                            <th class="px-3 py-4 text-left text-sm font-semibold text-[var(--text-color)]" scope="col">Détecté le</th>
                            <th class="relative py-4 pl-3 pr-6" scope="col">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border-color)] bg-[var(--card-bg-color)]">
                        <tr v-for="incident in incidents.data" :key="incident.id" class="hover:bg-[var(--bg-color)] transition-colors cursor-pointer" @click="navigateToIncident(incident.id)">
                            <td class="py-4 pl-6 pr-3" @click.stop>
                                <input 
                                    type="checkbox" 
                                    :value="incident.id"
                                    v-model="selectedIncidents"
                                    class="rounded border-[var(--border-color)] text-[var(--primary-color)] focus:ring-[var(--primary-color)]"
                                >
                            </td>
                            <td class="py-4 px-3">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full flex items-center justify-center" :class="getSeverityIconClass(incident.severity)">
                                            <span class="material-symbols-outlined text-sm" :class="getSeverityIconColor(incident.severity)">
                                                {{ getSeverityIcon(incident.severity) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-[var(--text-color)]">{{ incident.title }}</div>
                                        <div class="text-sm text-[var(--text-muted-color)]">{{ getTypeLabel(incident.type) }}</div>
                                    </div>
                                </div>
                                <dl class="font-normal lg:hidden">
                                    <dt class="sr-only">Bail Mobilité</dt>
                                    <dd class="mt-1 truncate text-[var(--text-muted-color)]">{{ incident.bail_mobilite?.tenant_name }} - {{ incident.bail_mobilite?.address }}</dd>
                                </dl>
                            </td>
                            <td class="hidden px-3 py-4 text-sm text-[var(--text-color)] lg:table-cell">
                                <div class="flex items-center">
                                    <span class="material-symbols-outlined text-[var(--text-muted-color)] mr-2 text-sm">home</span>
                                    <div>
                                        <div class="font-medium">{{ incident.bail_mobilite?.tenant_name }}</div>
                                        <div class="text-[var(--text-muted-color)]">{{ incident.bail_mobilite?.address }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="hidden px-3 py-4 text-sm sm:table-cell">
                                <span :class="getSeverityBadgeClass(incident.severity)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                    {{ getSeverityLabel(incident.severity) }}
                                </span>
                            </td>
                            <td class="px-3 py-4 text-sm">
                                <span :class="getStatusBadgeClass(incident.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                    <span v-if="incident.status === 'open'" class="w-1.5 h-1.5 bg-current rounded-full mr-1.5 animate-pulse"></span>
                                    <span v-else class="w-1.5 h-1.5 bg-current rounded-full mr-1.5"></span>
                                    {{ getStatusLabel(incident.status) }}
                                </span>
                            </td>
                            <td class="px-3 py-4 text-sm text-[var(--text-muted-color)]">
                                <div class="flex items-center">
                                    <span class="material-symbols-outlined mr-1 text-sm">schedule</span>
                                    {{ formatDate(incident.detected_at) }}
                                </div>
                            </td>
                            <td class="relative py-4 pl-3 pr-6 text-right text-sm font-medium" @click.stop>
                                <div class="flex items-center justify-end space-x-2">
                                    <Link 
                                        :href="route('ops.incidents.show', incident.id)"
                                        class="text-[var(--primary-color)] hover:text-[var(--primary-hover-color)] transition-colors p-1 rounded-full hover:bg-[var(--border-color)]"
                                        title="View Details"
                                    >
                                        <span class="material-symbols-outlined text-sm">visibility</span>
                                    </Link>
                                    <button
                                        v-if="incident.status === 'open'"
                                        @click="updateStatus(incident.id, 'in_progress')"
                                        class="text-blue-600 hover:text-blue-700 transition-colors p-1 rounded-full hover:bg-blue-100 dark:hover:bg-blue-900/50"
                                        title="Take Charge"
                                    >
                                        <span class="material-symbols-outlined text-sm">play_arrow</span>
                                    </button>
                                    <button
                                        v-if="['open', 'in_progress'].includes(incident.status)"
                                        @click="updateStatus(incident.id, 'resolved')"
                                        class="text-green-600 hover:text-green-700 transition-colors p-1 rounded-full hover:bg-green-100 dark:hover:bg-green-900/50"
                                        title="Resolve"
                                    >
                                        <span class="material-symbols-outlined text-sm">check_circle</span>
                                    </button>
                                    <button class="text-[var(--text-muted-color)] hover:text-[var(--text-color)] transition-colors p-1 rounded-full hover:bg-[var(--border-color)]" title="More Options">
                                        <span class="material-symbols-outlined text-sm">more_vert</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="incidents.links" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <Link 
                            v-if="incidents.prev_page_url"
                            :href="incidents.prev_page_url"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Précédent
                        </Link>
                        <Link 
                            v-if="incidents.next_page_url"
                            :href="incidents.next_page_url"
                            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Suivant
                        </Link>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Affichage de {{ incidents.from }} à {{ incidents.to }} sur {{ incidents.total }} résultats
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                <template v-for="link in incidents.links" :key="link.label">
                                    <Link 
                                        v-if="link.url"
                                        :href="link.url"
                                        :class="[
                                            'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                            link.active 
                                                ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                                : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                        ]"
                                        v-html="link.label"
                                    />
                                    <span 
                                        v-else
                                        :class="[
                                            'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                            'bg-white border-gray-300 text-gray-300 cursor-default'
                                        ]"
                                        v-html="link.label"
                                    />
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardOps>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import DashboardOps from '@/Layouts/DashboardOps.vue'
import { debounce } from 'lodash'

const props = defineProps({
    incidents: Object,
    bailMobilites: Array,
    users: Array,
    filters: Object,
    incidentTypes: Object,
    severityLevels: Object,
    statusOptions: Object
})

const selectedIncidents = ref([])
const showBulkActions = ref(false)
const isRunningDetection = ref(false)
const showFilters = ref(false)
const searchQuery = ref('')

const filters = reactive({
    status: props.filters.status || 'all',
    severity: props.filters.severity || 'all',
    type: props.filters.type || 'all',
    bail_mobilite_id: props.filters.bail_mobilite_id || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || ''
})

const applyFilters = () => {
    router.get(route('ops.incidents.index'), filters, {
        preserveState: true,
        preserveScroll: true
    })
}

const toggleSelectAll = () => {
    if (selectedIncidents.value.length === props.incidents.data.length) {
        selectedIncidents.value = []
    } else {
        selectedIncidents.value = props.incidents.data.map(incident => incident.id)
    }
}

const updateStatus = (incidentId, status) => {
    router.patch(route('ops.incidents.update-status', incidentId), {
        status: status
    }, {
        preserveState: true,
        onSuccess: () => {
            // Remove from selected if it was selected
            const index = selectedIncidents.value.indexOf(incidentId)
            if (index > -1) {
                selectedIncidents.value.splice(index, 1)
            }
        }
    })
}

const bulkUpdate = (action) => {
    if (selectedIncidents.value.length === 0) return

    router.post(route('ops.api.incidents.bulk-update'), {
        incident_ids: selectedIncidents.value,
        action: action
    }, {
        preserveState: true,
        onSuccess: () => {
            selectedIncidents.value = []
            showBulkActions.value = false
        }
    })
}

const runIncidentDetection = () => {
    isRunningDetection.value = true
    
    router.post(route('ops.api.run-incident-detection'), {}, {
        preserveState: true,
        onFinish: () => {
            isRunningDetection.value = false
        }
    })
}

const navigateToIncident = (incidentId) => {
    router.visit(route('ops.incidents.show', incidentId))
}

const debouncedSearch = debounce(() => {
    applyFilters()
}, 300)

const getSeverityIcon = (severity) => {
    const icons = {
        'low': 'info',
        'medium': 'warning',
        'high': 'error',
        'critical': 'dangerous'
    }
    return icons[severity] || 'warning'
}

const getSeverityIconClass = (severity) => {
    const classes = {
        'low': 'bg-green-100 dark:bg-green-900/50',
        'medium': 'bg-yellow-100 dark:bg-yellow-900/50',
        'high': 'bg-orange-100 dark:bg-orange-900/50',
        'critical': 'bg-red-100 dark:bg-red-900/50'
    }
    return classes[severity] || 'bg-gray-100 dark:bg-gray-900/50'
}

const getSeverityIconColor = (severity) => {
    const colors = {
        'low': 'text-green-600 dark:text-green-400',
        'medium': 'text-yellow-600 dark:text-yellow-400',
        'high': 'text-orange-600 dark:text-orange-400',
        'critical': 'text-red-600 dark:text-red-400'
    }
    return colors[severity] || 'text-gray-600 dark:text-gray-400'
}

const getSeverityBadgeClass = (severity) => {
    const classes = {
        'low': 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
        'medium': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
        'high': 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300',
        'critical': 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300'
    }
    return classes[severity] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/50 dark:text-gray-300'
}

const getStatusBadgeClass = (status) => {
    const classes = {
        'open': 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
        'in_progress': 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
        'resolved': 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
        'closed': 'bg-gray-100 text-gray-800 dark:bg-gray-900/50 dark:text-gray-300'
    }
    return classes[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/50 dark:text-gray-300'
}

const getSeverityLabel = (severity) => {
    return props.severityLevels[severity] || severity
}

const getStatusLabel = (status) => {
    return props.statusOptions[status] || status
}

const getTypeLabel = (type) => {
    return props.incidentTypes[type] || type
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script>