<template>
    <DashboardOps>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Gestion des Incidents
                </h2>
                <div class="flex space-x-2">
                    <button
                        @click="runIncidentDetection"
                        :disabled="isRunningDetection"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                    >
                        <span v-if="isRunningDetection">Détection en cours...</span>
                        <span v-else>Détecter les incidents</span>
                    </button>
                    <button
                        @click="showBulkActions = !showBulkActions"
                        :class="[
                            'font-bold py-2 px-4 rounded',
                            selectedIncidents.length > 0 
                                ? 'bg-green-500 hover:bg-green-700 text-white' 
                                : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                        ]"
                        :disabled="selectedIncidents.length === 0"
                    >
                        Actions groupées ({{ selectedIncidents.length }})
                    </button>
                </div>
            </div>
        </template>

        <!-- Filters -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                    <select v-model="filters.status" @change="applyFilters" class="w-full border-gray-300 rounded-md">
                        <option value="all">Tous les statuts</option>
                        <option v-for="(label, value) in statusOptions" :key="value" :value="value">
                            {{ label }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sévérité</label>
                    <select v-model="filters.severity" @change="applyFilters" class="w-full border-gray-300 rounded-md">
                        <option value="all">Toutes les sévérités</option>
                        <option v-for="(label, value) in severityLevels" :key="value" :value="value">
                            {{ label }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                    <select v-model="filters.type" @change="applyFilters" class="w-full border-gray-300 rounded-md">
                        <option value="all">Tous les types</option>
                        <option v-for="(label, value) in incidentTypes" :key="value" :value="value">
                            {{ label }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bail Mobilité</label>
                    <select v-model="filters.bail_mobilite_id" @change="applyFilters" class="w-full border-gray-300 rounded-md">
                        <option value="">Tous les BM</option>
                        <option v-for="bm in bailMobilites" :key="bm.id" :value="bm.id">
                            {{ bm.tenant_name }} - {{ bm.address }}
                        </option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date de début</label>
                    <input 
                        type="date" 
                        v-model="filters.date_from" 
                        @change="applyFilters"
                        class="w-full border-gray-300 rounded-md"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date de fin</label>
                    <input 
                        type="date" 
                        v-model="filters.date_to" 
                        @change="applyFilters"
                        class="w-full border-gray-300 rounded-md"
                    >
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div v-if="showBulkActions && selectedIncidents.length > 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-yellow-800">
                    {{ selectedIncidents.length }} incident(s) sélectionné(s)
                </span>
                <div class="flex space-x-2">
                    <button
                        @click="bulkUpdate('mark_in_progress')"
                        class="bg-blue-500 hover:bg-blue-700 text-white text-sm font-bold py-1 px-3 rounded"
                    >
                        Marquer en cours
                    </button>
                    <button
                        @click="bulkUpdate('mark_resolved')"
                        class="bg-green-500 hover:bg-green-700 text-white text-sm font-bold py-1 px-3 rounded"
                    >
                        Marquer résolu
                    </button>
                    <button
                        @click="bulkUpdate('mark_closed')"
                        class="bg-gray-500 hover:bg-gray-700 text-white text-sm font-bold py-1 px-3 rounded"
                    >
                        Fermer
                    </button>
                </div>
            </div>
        </div>

        <!-- Incidents Table -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input 
                                type="checkbox" 
                                @change="toggleSelectAll"
                                :checked="selectedIncidents.length === incidents.data.length && incidents.data.length > 0"
                                class="rounded border-gray-300"
                            >
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Incident
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Bail Mobilité
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Sévérité
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Statut
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Détecté le
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="incident in incidents.data" :key="incident.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input 
                                type="checkbox" 
                                :value="incident.id"
                                v-model="selectedIncidents"
                                class="rounded border-gray-300"
                            >
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ incident.title }}</div>
                            <div class="text-sm text-gray-500">{{ getTypeLabel(incident.type) }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ incident.bail_mobilite?.tenant_name }}</div>
                            <div class="text-sm text-gray-500">{{ incident.bail_mobilite?.address }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="getSeverityBadgeClass(incident.severity)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                {{ getSeverityLabel(incident.severity) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="getStatusBadgeClass(incident.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                {{ getStatusLabel(incident.status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ formatDate(incident.detected_at) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <Link 
                                :href="route('ops.incidents.show', incident.id)"
                                class="text-indigo-600 hover:text-indigo-900 mr-3"
                            >
                                Voir
                            </Link>
                            <button
                                v-if="incident.status === 'open'"
                                @click="updateStatus(incident.id, 'in_progress')"
                                class="text-blue-600 hover:text-blue-900 mr-3"
                            >
                                Prendre en charge
                            </button>
                            <button
                                v-if="['open', 'in_progress'].includes(incident.status)"
                                @click="updateStatus(incident.id, 'resolved')"
                                class="text-green-600 hover:text-green-900"
                            >
                                Résoudre
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

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

const getSeverityBadgeClass = (severity) => {
    const classes = {
        'low': 'bg-green-100 text-green-800',
        'medium': 'bg-yellow-100 text-yellow-800',
        'high': 'bg-orange-100 text-orange-800',
        'critical': 'bg-red-100 text-red-800'
    }
    return classes[severity] || 'bg-gray-100 text-gray-800'
}

const getStatusBadgeClass = (status) => {
    const classes = {
        'open': 'bg-red-100 text-red-800',
        'in_progress': 'bg-yellow-100 text-yellow-800',
        'resolved': 'bg-green-100 text-green-800',
        'closed': 'bg-gray-100 text-gray-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
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