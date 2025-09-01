<template>
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Notifications</h3>
                <div class="flex items-center space-x-2">
                    <span 
                        v-if="pendingCount > 0"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
                    >
                        {{ pendingCount }} en attente
                    </span>
                    <Link 
                        :href="route('ops.notifications')"
                        class="text-sm text-blue-600 hover:text-blue-800"
                    >
                        Voir tout
                    </Link>
                </div>
            </div>
        </div>
        <div class="max-h-96 overflow-y-auto">
            <div v-if="notifications.length === 0" class="p-4 text-center text-gray-500">
                Aucune notification en attente
            </div>
            <div v-else class="divide-y divide-gray-200">
                <div 
                    v-for="notification in notifications" 
                    :key="notification.id"
                    class="p-4 hover:bg-gray-50 transition-colors"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2 mb-1">
                                <span 
                                    :class="getNotificationTypeClass(notification.type)"
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                >
                                    {{ getNotificationTypeLabel(notification.type) }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ formatRelativeTime(notification.created_at) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-900 truncate">
                                {{ notification.message || getDefaultMessage(notification) }}
                            </p>
                            <div v-if="notification.bail_mobilite" class="text-xs text-gray-600 mt-1">
                                {{ notification.bail_mobilite.tenant_name }} - {{ notification.bail_mobilite.address }}
                            </div>
                        </div>
                        <div class="flex items-center space-x-1 ml-2">
                            <!-- Priority indicator -->
                            <div 
                                v-if="notification.type === 'incident_alert'"
                                class="w-2 h-2 bg-red-500 rounded-full"
                                title="Priorité élevée"
                            ></div>
                            <div 
                                v-else-if="notification.type === 'exit_reminder'"
                                class="w-2 h-2 bg-yellow-500 rounded-full"
                                title="Rappel important"
                            ></div>
                            <div 
                                v-else
                                class="w-2 h-2 bg-blue-500 rounded-full"
                                title="Notification standard"
                            ></div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="flex items-center space-x-2 mt-2">
                        <button 
                            v-if="notification.type === 'exit_reminder'"
                            @click="handleQuickAction(notification, 'assign_exit')"
                            class="px-2 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition-colors"
                        >
                            Assigner sortie
                        </button>
                        <button 
                            v-if="notification.type === 'checklist_validation'"
                            @click="handleQuickAction(notification, 'validate_checklist')"
                            class="px-2 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700 transition-colors"
                        >
                            Valider
                        </button>
                        <button 
                            v-if="notification.type === 'incident_alert'"
                            @click="handleQuickAction(notification, 'handle_incident')"
                            class="px-2 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700 transition-colors"
                        >
                            Gérer
                        </button>
                        <button 
                            @click="handleQuickAction(notification, 'view_bail_mobilite')"
                            class="px-2 py-1 bg-gray-500 text-white text-xs rounded hover:bg-gray-600 transition-colors"
                        >
                            Voir
                        </button>
                        <button 
                            @click="markAsHandled(notification)"
                            class="px-2 py-1 border border-gray-300 text-gray-700 text-xs rounded hover:bg-gray-50 transition-colors"
                        >
                            Marquer comme traitée
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    notifications: {
        type: Array,
        default: () => []
    }
})

const pendingCount = computed(() => {
    return props.notifications.filter(n => n.status === 'pending').length
})

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
        'exit_reminder': 'Rappel',
        'checklist_validation': 'Validation',
        'incident_alert': 'Incident',
        'mission_assigned': 'Mission'
    }
    return labels[type] || type
}

const getDefaultMessage = (notification) => {
    const messages = {
        'exit_reminder': `Bail Mobilité se termine dans 10 jours`,
        'checklist_validation': `Checklist à valider`,
        'incident_alert': `Incident détecté`,
        'mission_assigned': 'Nouvelle mission assignée'
    }
    return messages[notification.type] || 'Notification'
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

const markAsHandled = async (notification) => {
    try {
        await router.post(route('ops.notifications.mark-handled', notification.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                // Remove the notification from the list
                const index = props.notifications.findIndex(n => n.id === notification.id)
                if (index > -1) {
                    props.notifications.splice(index, 1)
                }
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