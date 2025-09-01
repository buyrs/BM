<template>
    <DashboardOps>
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
                    <p class="text-gray-600">Gérez vos notifications et alertes</p>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5v-12"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">En attente</p>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.total_pending }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Rappels de sortie</p>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.exit_reminders_pending }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Validations</p>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.checklist_validations_pending }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-red-100 rounded-lg">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Incidents</p>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.incident_alerts_pending }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="p-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Filtres</h3>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                                <select 
                                    v-model="selectedStatus" 
                                    @change="applyFilters"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">Tous les statuts</option>
                                    <option value="pending">En attente</option>
                                    <option value="sent">Envoyées</option>
                                    <option value="cancelled">Annulées</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                                <select 
                                    v-model="selectedType" 
                                    @change="applyFilters"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">Tous les types</option>
                                    <option value="exit_reminder">Rappel de sortie</option>
                                    <option value="checklist_validation">Validation checklist</option>
                                    <option value="incident_alert">Alerte incident</option>
                                    <option value="mission_assigned">Mission assignée</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button 
                                    @click="clearFilters"
                                    class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors"
                                >
                                    Effacer les filtres
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notifications List -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Notifications</h3>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <div 
                            v-for="notification in notifications.data" 
                            :key="notification.id"
                            class="p-4 hover:bg-gray-50 transition-colors"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <span 
                                            :class="getNotificationTypeClass(notification.type)"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        >
                                            {{ getNotificationTypeLabel(notification.type) }}
                                        </span>
                                        <span 
                                            :class="getNotificationStatusClass(notification.status)"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        >
                                            {{ getNotificationStatusLabel(notification.status) }}
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            {{ formatDate(notification.created_at) }}
                                        </span>
                                    </div>
                                    <h4 class="text-sm font-medium text-gray-900 mb-1">
                                        {{ notification.message || getDefaultMessage(notification) }}
                                    </h4>
                                    <div v-if="notification.bail_mobilite" class="text-sm text-gray-600">
                                        <p><strong>Locataire:</strong> {{ notification.bail_mobilite.tenant_name }}</p>
                                        <p><strong>Adresse:</strong> {{ notification.bail_mobilite.address }}</p>
                                    </div>
                                    <div v-if="notification.data" class="text-xs text-gray-500 mt-2">
                                        <div v-if="notification.data.incident_reason">
                                            <strong>Raison:</strong> {{ notification.data.incident_reason }}
                                        </div>
                                        <div v-if="notification.data.checker_name">
                                            <strong>Checker:</strong> {{ notification.data.checker_name }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2 ml-4">
                                    <!-- Quick Actions -->
                                    <div v-if="notification.status === 'pending'" class="flex space-x-2">
                                        <button 
                                            v-if="notification.type === 'exit_reminder'"
                                            @click="handleQuickAction(notification, 'assign_exit')"
                                            class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition-colors"
                                        >
                                            Assigner sortie
                                        </button>
                                        <button 
                                            v-if="notification.type === 'checklist_validation'"
                                            @click="handleQuickAction(notification, 'validate_checklist')"
                                            class="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700 transition-colors"
                                        >
                                            Valider
                                        </button>
                                        <button 
                                            v-if="notification.type === 'incident_alert'"
                                            @click="handleQuickAction(notification, 'handle_incident')"
                                            class="px-3 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700 transition-colors"
                                        >
                                            Gérer incident
                                        </button>
                                        <button 
                                            @click="handleQuickAction(notification, 'view_bail_mobilite')"
                                            class="px-3 py-1 bg-gray-600 text-white text-xs rounded hover:bg-gray-700 transition-colors"
                                        >
                                            Voir détails
                                        </button>
                                    </div>
                                    <button 
                                        v-if="notification.status === 'pending'"
                                        @click="markAsHandled(notification)"
                                        class="px-3 py-1 bg-gray-500 text-white text-xs rounded hover:bg-gray-600 transition-colors"
                                    >
                                        Marquer comme traitée
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-if="notifications.data.length === 0" class="p-8 text-center text-gray-500">
                            Aucune notification trouvée
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="notifications.last_page > 1" class="mt-6">
                    <nav class="flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <Link 
                                v-if="notifications.prev_page_url"
                                :href="notifications.prev_page_url"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Précédent
                            </Link>
                            <Link 
                                v-if="notifications.next_page_url"
                                :href="notifications.next_page_url"
                                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Suivant
                            </Link>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Affichage de
                                    <span class="font-medium">{{ notifications.from }}</span>
                                    à
                                    <span class="font-medium">{{ notifications.to }}</span>
                                    sur
                                    <span class="font-medium">{{ notifications.total }}</span>
                                    résultats
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <Link 
                                        v-if="notifications.prev_page_url"
                                        :href="notifications.prev_page_url"
                                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                    >
                                        <span class="sr-only">Précédent</span>
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </Link>
                                    <Link 
                                        v-if="notifications.next_page_url"
                                        :href="notifications.next_page_url"
                                        class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                    >
                                        <span class="sr-only">Suivant</span>
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </Link>
                                </nav>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </DashboardOps>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import DashboardOps from '@/Layouts/DashboardOps.vue'

const props = defineProps({
    notifications: Object,
    filters: Object,
    stats: Object
})

const selectedStatus = ref(props.filters.status || '')
const selectedType = ref(props.filters.type || '')

const applyFilters = () => {
    router.get(route('ops.notifications'), {
        status: selectedStatus.value || undefined,
        type: selectedType.value || undefined
    }, {
        preserveState: true,
        replace: true
    })
}

const clearFilters = () => {
    selectedStatus.value = ''
    selectedType.value = ''
    router.get(route('ops.notifications'), {}, {
        preserveState: true,
        replace: true
    })
}

const getNotificationTypeClass = (type) => {
    const classes = {
        'exit_reminder': 'bg-yellow-100 text-yellow-800',
        'checklist_validation': 'bg-green-100 text-green-800',
        'incident_alert': 'bg-red-100 text-red-800',
        'mission_assigned': 'bg-blue-100 text-blue-800'
    }
    return classes[type] || 'bg-gray-100 text-gray-800'
}

const getNotificationTypeLabel = (type) => {
    const labels = {
        'exit_reminder': 'Rappel de sortie',
        'checklist_validation': 'Validation checklist',
        'incident_alert': 'Alerte incident',
        'mission_assigned': 'Mission assignée'
    }
    return labels[type] || type
}

const getNotificationStatusClass = (status) => {
    const classes = {
        'pending': 'bg-orange-100 text-orange-800',
        'sent': 'bg-green-100 text-green-800',
        'cancelled': 'bg-gray-100 text-gray-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const getNotificationStatusLabel = (status) => {
    const labels = {
        'pending': 'En attente',
        'sent': 'Envoyée',
        'cancelled': 'Annulée'
    }
    return labels[status] || status
}

const getDefaultMessage = (notification) => {
    const messages = {
        'exit_reminder': `Bail Mobilité se termine dans 10 jours - ${notification.bail_mobilite?.tenant_name}`,
        'checklist_validation': `Checklist à valider pour ${notification.bail_mobilite?.tenant_name}`,
        'incident_alert': `Incident détecté pour ${notification.bail_mobilite?.tenant_name}`,
        'mission_assigned': 'Nouvelle mission assignée'
    }
    return messages[notification.type] || 'Notification'
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const markAsHandled = async (notification) => {
    try {
        await router.post(route('ops.notifications.mark-handled', notification.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                // Refresh the page to update the notification status
                router.reload({ only: ['notifications', 'stats'] })
            }
        })
    } catch (error) {
        console.error('Error marking notification as handled:', error)
    }
}

const handleQuickAction = async (notification, action) => {
    try {
        await router.post(route('ops.notifications.action', notification.id), {
            action: action
        })
    } catch (error) {
        console.error('Error handling notification action:', error)
    }
}
</script>