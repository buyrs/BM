<template>
    <div class="bg-white rounded-lg shadow-md">
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <h3 class="text-lg font-medium text-text-primary">Notifications</h3>
                    <div 
                        v-if="isConnected"
                        class="w-2 h-2 bg-green-500 rounded-full"
                        title="Connecté - Mises à jour en temps réel"
                    ></div>
                    <div 
                        v-else
                        class="w-2 h-2 bg-red-500 rounded-full"
                        title="Déconnecté - Pas de mises à jour en temps réel"
                    ></div>
                </div>
                <div class="flex items-center space-x-2">
                    <span 
                        v-if="pendingCount > 0"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        :class="getCountBadgeClass()"
                    >
                        {{ pendingCount }} en attente
                    </span>
                    <button
                        @click="refreshNotifications"
                        :disabled="isRefreshing"
                        class="text-sm text-primary hover:underline disabled:opacity-50"
                        title="Actualiser les notifications"
                    >
                        <span v-if="isRefreshing">⟳</span>
                        <span v-else>↻</span>
                    </button>
                    <Link 
                        :href="route('ops.notifications')"
                        class="text-sm text-primary hover:underline"
                    >
                        Voir tout
                    </Link>
                </div>
            </div>
        </div>
        
        <!-- Loading state -->
        <div v-if="isLoading" class="p-4 text-center">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary mx-auto"></div>
            <p class="text-sm text-text-secondary mt-2">Chargement des notifications...</p>
        </div>
        
        <!-- Notifications list -->
        <div v-else class="max-h-96 overflow-y-auto">
            <div v-if="localNotifications.length === 0" class="p-4 text-center text-text-secondary">
                <div class="text-4xl mb-2">🔔</div>
                <p>Aucune notification en attente</p>
                <p class="text-xs mt-1">Les nouvelles notifications apparaîtront ici</p>
            </div>
            <div v-else class="divide-y divide-gray-200">
                <div 
                    v-for="notification in localNotifications" 
                    :key="notification.id"
                    class="p-4 transition-all duration-200"
                    :class="getNotificationRowClass(notification)"
                    @click="handleNotificationClick(notification)"
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
                                <span class="text-xs text-text-secondary">
                                    {{ formatRelativeTime(notification.created_at) }}
                                </span>
                                <span 
                                    v-if="notification.isNew"
                                    class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                >
                                    Nouveau
                                </span>
                            </div>
                            <p class="text-sm text-text-primary" :class="{ 'truncate': !notification.expanded }">
                                {{ notification.message || getDefaultMessage(notification) }}
                            </p>
                            <div v-if="notification.bail_mobilite" class="text-xs text-text-secondary mt-1">
                                📍 {{ notification.bail_mobilite.tenant_name }} - {{ notification.bail_mobilite.address }}
                            </div>
                            
                            <!-- Additional details for expanded notifications -->
                            <div v-if="notification.expanded && notification.data" class="mt-2 text-xs text-text-secondary">
                                <div v-if="notification.data.checker_name" class="mb-1">
                                    👤 Checker: {{ notification.data.checker_name }}
                                </div>
                                <div v-if="notification.data.mission_type" class="mb-1">
                                    🎯 Type: {{ notification.data.mission_type === 'entry' ? 'Entrée' : 'Sortie' }}
                                </div>
                                <div v-if="notification.data.completed_at" class="mb-1">
                                    ⏰ Terminé: {{ formatDateTime(notification.data.completed_at) }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-1 ml-2">
                            <!-- Priority indicator -->
                            <div 
                                :class="getPriorityIndicatorClass(notification.type)"
                                :title="getPriorityTitle(notification.type)"
                            ></div>
                            
                            <!-- Expand/collapse button -->
                            <button
                                @click.stop="toggleExpanded(notification)"
                                class="text-text-secondary hover:text-text-primary text-xs"
                            >
                                {{ notification.expanded ? '▲' : '▼' }}
                            </button>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="flex items-center space-x-2 mt-3 flex-wrap gap-1">
                        <button 
                            v-if="notification.type === 'exit_reminder'"
                            @click.stop="handleQuickAction(notification, 'assign_exit')"
                            :disabled="notification.isProcessing"
                            class="px-3 py-1 bg-primary text-white text-xs rounded hover:bg-accent transition-colors disabled:opacity-50"
                        >
                            📅 Assigner sortie
                        </button>
                        <button 
                            v-if="notification.type === 'checklist_validation' || notification.type === 'mission_completed'"
                            @click.stop="handleQuickAction(notification, 'validate_checklist')"
                            :disabled="notification.isProcessing"
                            class="px-3 py-1 bg-success-border text-white text-xs rounded hover:bg-success-text transition-colors disabled:opacity-50"
                        >
                            ✅ Valider
                        </button>
                        <button 
                            v-if="notification.type === 'incident_alert'"
                            @click.stop="handleQuickAction(notification, 'handle_incident')"
                            :disabled="notification.isProcessing"
                            class="px-3 py-1 bg-error-border text-white text-xs rounded hover:bg-error-text transition-colors disabled:opacity-50"
                        >
                            🚨 Gérer incident
                        </button>
                        <button 
                            v-if="notification.type === 'mission_assigned'"
                            @click.stop="handleQuickAction(notification, 'view_mission')"
                            :disabled="notification.isProcessing"
                            class="px-3 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600 transition-colors disabled:opacity-50"
                        >
                            👁️ Voir mission
                        </button>
                        <button 
                            @click.stop="handleQuickAction(notification, 'view_bail_mobilite')"
                            :disabled="notification.isProcessing"
                            class="px-3 py-1 bg-gray-500 text-white text-xs rounded hover:bg-gray-600 transition-colors disabled:opacity-50"
                        >
                            📋 Voir détails
                        </button>
                        <button 
                            @click.stop="markAsHandled(notification)"
                            :disabled="notification.isProcessing"
                            class="px-3 py-1 border border-gray-300 text-text-secondary text-xs rounded hover:bg-gray-50 transition-colors disabled:opacity-50"
                        >
                            {{ notification.isProcessing ? '⏳' : '✓' }} Traité
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Error state -->
        <div v-if="error" class="p-4 bg-red-50 border-t border-red-200">
            <div class="flex items-center space-x-2 text-red-700">
                <span>⚠️</span>
                <span class="text-sm">{{ error }}</span>
                <button 
                    @click="clearError"
                    class="text-red-500 hover:text-red-700 text-xs underline"
                >
                    Fermer
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import notificationService from '@/Services/NotificationService'
import toastService from '@/Services/ToastService'

const props = defineProps({
    notifications: {
        type: Array,
        default: () => []
    },
    realTime: {
        type: Boolean,
        default: true
    }
})

const emit = defineEmits(['notification-updated', 'notification-handled'])

// Reactive state
const localNotifications = ref([...props.notifications])
const isLoading = ref(false)
const isRefreshing = ref(false)
const isConnected = ref(false)
const error = ref(null)

// Computed properties
const pendingCount = computed(() => {
    return localNotifications.value.filter(n => n.status === 'pending').length
})

// Initialize real-time notifications
onMounted(() => {
    if (props.realTime) {
        initializeRealTimeNotifications()
    }
    
    // Mark notifications as expanded: false by default
    localNotifications.value = localNotifications.value.map(n => ({
        ...n,
        expanded: false,
        isNew: false,
        isProcessing: false
    }))
})

onUnmounted(() => {
    if (props.realTime) {
        cleanupRealTimeNotifications()
    }
})

// Watch for prop changes
watch(() => props.notifications, (newNotifications) => {
    localNotifications.value = newNotifications.map(n => ({
        ...n,
        expanded: false,
        isNew: false,
        isProcessing: false
    }))
}, { deep: true })

// Real-time notification methods
const initializeRealTimeNotifications = () => {
    notificationService.init()
    isConnected.value = true
    
    // Listen for notification updates
    notificationService.on('notifications:updated', handleNotificationsUpdated)
    notificationService.on('notifications:error', handleNotificationsError)
    notificationService.on('notification:handled', handleNotificationHandled)
}

const cleanupRealTimeNotifications = () => {
    notificationService.off('notifications:updated', handleNotificationsUpdated)
    notificationService.off('notifications:error', handleNotificationsError)
    notificationService.off('notification:handled', handleNotificationHandled)
    notificationService.destroy()
    isConnected.value = false
}

const handleNotificationsUpdated = (data) => {
    const { notifications, newNotifications } = data
    
    // Mark new notifications
    const existingIds = localNotifications.value.map(n => n.id)
    const freshNotifications = newNotifications.filter(n => !existingIds.includes(n.id))
    
    // Update local notifications
    localNotifications.value = notifications.map(n => ({
        ...n,
        expanded: false,
        isNew: freshNotifications.some(fn => fn.id === n.id),
        isProcessing: false
    }))
    
    // Emit update event
    emit('notification-updated', { notifications: localNotifications.value, newCount: freshNotifications.length })
    
    // Clear "new" flag after 5 seconds
    if (freshNotifications.length > 0) {
        setTimeout(() => {
            localNotifications.value = localNotifications.value.map(n => ({
                ...n,
                isNew: false
            }))
        }, 5000)
    }
}

const handleNotificationsError = (errorData) => {
    error.value = 'Erreur lors de la récupération des notifications'
    isConnected.value = false
    console.error('Notification service error:', errorData)
    toastService.error('Erreur lors de la récupération des notifications')
}

const handleNotificationHandled = (notificationId) => {
    localNotifications.value = localNotifications.value.filter(n => n.id !== notificationId)
    emit('notification-handled', notificationId)
}

// UI interaction methods
const refreshNotifications = async () => {
    if (isRefreshing.value) return
    
    isRefreshing.value = true
    error.value = null
    
    try {
        if (props.realTime) {
            await notificationService.fetchNotifications()
        } else {
            // Fallback to page refresh for non-real-time mode
            router.reload({ only: ['notifications'] })
        }
    } catch (err) {
        error.value = 'Erreur lors de l\'actualisation'
        console.error('Error refreshing notifications:', err)
        toastService.error('Erreur lors de l\'actualisation des notifications')
    } finally {
        isRefreshing.value = false
    }
}

const toggleExpanded = (notification) => {
    const index = localNotifications.value.findIndex(n => n.id === notification.id)
    if (index > -1) {
        localNotifications.value[index].expanded = !localNotifications.value[index].expanded
    }
}

const handleNotificationClick = (notification) => {
    toggleExpanded(notification)
}

const markAsHandled = async (notification) => {
    if (notification.isProcessing) return
    
    // Set processing state
    const index = localNotifications.value.findIndex(n => n.id === notification.id)
    if (index > -1) {
        localNotifications.value[index].isProcessing = true
    }
    
    try {
        if (props.realTime) {
            await notificationService.markAsHandled(notification.id)
        } else {
            await router.post(route('ops.notifications.mark-handled', notification.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    localNotifications.value = localNotifications.value.filter(n => n.id !== notification.id)
                    emit('notification-handled', notification.id)
                }
            })
        }
    } catch (err) {
        console.error('Error marking notification as handled:', err)
        error.value = 'Erreur lors du marquage de la notification'
        toastService.error('Erreur lors du marquage de la notification')
        
        // Reset processing state on error
        if (index > -1) {
            localNotifications.value[index].isProcessing = false
        }
    }
}

const handleQuickAction = async (notification, action) => {
    if (notification.isProcessing) return
    
    // Set processing state
    const index = localNotifications.value.findIndex(n => n.id === notification.id)
    if (index > -1) {
        localNotifications.value[index].isProcessing = true
    }
    
    try {
        if (props.realTime) {
            const result = await notificationService.handleQuickAction(notification.id, action)
            
            // Handle navigation based on action
            if (result.redirect_url) {
                router.visit(result.redirect_url)
            }
        } else {
            await router.post(route('ops.notifications.action', notification.id), {
                action: action
            })
        }
    } catch (err) {
        console.error('Error handling notification action:', err)
        error.value = 'Erreur lors de l\'action sur la notification'
        toastService.error('Erreur lors de l\'action sur la notification')
        
        // Reset processing state on error
        if (index > -1) {
            localNotifications.value[index].isProcessing = false
        }
    }
}

const clearError = () => {
    error.value = null
}

// Styling methods
const getNotificationTypeClass = (type) => {
    const classes = {
        'exit_reminder': 'bg-warning-bg text-warning-text',
        'checklist_validation': 'bg-success-bg text-success-text',
        'mission_completed': 'bg-blue-100 text-blue-800',
        'incident_alert': 'bg-error-bg text-error-text',
        'mission_assigned': 'bg-info-bg text-info-text',
        'calendar_update': 'bg-purple-100 text-purple-800'
    }
    return classes[type] || 'bg-gray-100 text-gray-800'
}

const getNotificationTypeLabel = (type) => {
    const labels = {
        'exit_reminder': 'Rappel',
        'checklist_validation': 'Validation',
        'mission_completed': 'Terminée',
        'incident_alert': 'Incident',
        'mission_assigned': 'Mission',
        'calendar_update': 'Calendrier'
    }
    return labels[type] || type
}

const getNotificationRowClass = (notification) => {
    const baseClass = 'hover:bg-gray-50 cursor-pointer'
    
    if (notification.isNew) {
        return `${baseClass} bg-blue-50 border-l-4 border-blue-400`
    }
    
    if (notification.type === 'incident_alert') {
        return `${baseClass} bg-red-50 border-l-4 border-red-400`
    }
    
    return baseClass
}

const getPriorityIndicatorClass = (type) => {
    const classes = {
        'incident_alert': 'w-3 h-3 bg-error-border rounded-full animate-pulse',
        'exit_reminder': 'w-2 h-2 bg-warning-border rounded-full',
        'checklist_validation': 'w-2 h-2 bg-success-border rounded-full',
        'mission_completed': 'w-2 h-2 bg-blue-500 rounded-full',
        'mission_assigned': 'w-2 h-2 bg-info-border rounded-full',
        'calendar_update': 'w-2 h-2 bg-purple-500 rounded-full'
    }
    return classes[type] || 'w-2 h-2 bg-gray-400 rounded-full'
}

const getPriorityTitle = (type) => {
    const titles = {
        'incident_alert': 'Priorité critique',
        'exit_reminder': 'Rappel important',
        'checklist_validation': 'Validation requise',
        'mission_completed': 'Mission terminée',
        'mission_assigned': 'Nouvelle mission',
        'calendar_update': 'Mise à jour calendrier'
    }
    return titles[type] || 'Notification standard'
}

const getCountBadgeClass = () => {
    const urgentCount = localNotifications.value.filter(n => 
        ['incident_alert', 'exit_reminder'].includes(n.type)
    ).length
    
    if (urgentCount > 0) {
        return 'bg-error-bg text-error-text animate-pulse'
    }
    
    return 'bg-warning-bg text-warning-text'
}

const getDefaultMessage = (notification) => {
    const messages = {
        'exit_reminder': `Bail Mobilité se termine dans 10 jours`,
        'checklist_validation': `Checklist à valider`,
        'mission_completed': `Mission terminée - validation requise`,
        'incident_alert': `Incident détecté`,
        'mission_assigned': 'Nouvelle mission assignée',
        'calendar_update': 'Calendrier mis à jour'
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

const formatDateTime = (dateString) => {
    const date = new Date(dateString)
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script>