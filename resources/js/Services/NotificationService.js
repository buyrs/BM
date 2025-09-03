/**
 * Real-time Notification Service for frontend
 * Handles real-time notification updates, broadcasting, and UI interactions
 */

class NotificationService {
    constructor() {
        this.notifications = []
        this.listeners = []
        this.pollingInterval = null
        this.pollingFrequency = 30000 // 30 seconds
        this.isPolling = false
        this.lastFetchTime = null
    }

    /**
     * Initialize the notification service
     */
    init() {
        this.startPolling()
        this.setupVisibilityChangeHandler()
        return this
    }

    /**
     * Start polling for new notifications
     */
    startPolling() {
        if (this.isPolling) return

        this.isPolling = true
        this.fetchNotifications()
        
        this.pollingInterval = setInterval(() => {
            this.fetchNotifications()
        }, this.pollingFrequency)
    }

    /**
     * Stop polling for notifications
     */
    stopPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval)
            this.pollingInterval = null
        }
        this.isPolling = false
    }

    /**
     * Fetch notifications from the server
     */
    async fetchNotifications() {
        try {
            const response = await axios.get('/ops/api/notifications/pending', {
                params: {
                    since: this.lastFetchTime
                }
            })

            const { notifications, count } = response.data
            
            // Update notifications and notify listeners
            this.updateNotifications(notifications)
            this.lastFetchTime = new Date().toISOString()
            
            // Emit update event
            this.emit('notifications:updated', {
                notifications: this.notifications,
                count: count,
                newNotifications: notifications
            })

        } catch (error) {
            console.error('Failed to fetch notifications:', error)
            this.emit('notifications:error', error)
            
            // Show toast notification for connection errors
            if (window.toast) {
                window.toast.error('Erreur de connexion aux notifications')
            }
        }
    }

    /**
     * Update the notifications list
     */
    updateNotifications(newNotifications) {
        // Merge new notifications with existing ones
        const existingIds = this.notifications.map(n => n.id)
        const freshNotifications = newNotifications.filter(n => !existingIds.includes(n.id))
        
        // Add new notifications to the beginning
        this.notifications = [...freshNotifications, ...this.notifications]
        
        // Show browser notifications for new items
        if (freshNotifications.length > 0) {
            this.showBrowserNotifications(freshNotifications)
        }
        
        // Sort by priority and date
        this.notifications.sort((a, b) => {
            const priorityOrder = {
                'incident_alert': 1,
                'exit_reminder': 2,
                'checklist_validation': 3,
                'mission_assigned': 4,
                'calendar_update': 5
            }
            
            const aPriority = priorityOrder[a.type] || 10
            const bPriority = priorityOrder[b.type] || 10
            
            if (aPriority !== bPriority) {
                return aPriority - bPriority
            }
            
            return new Date(b.created_at) - new Date(a.created_at)
        })
    }

    /**
     * Show browser notifications for new items
     */
    async showBrowserNotifications(notifications) {
        if (!('Notification' in window)) return
        
        // Request permission if not granted
        if (Notification.permission === 'default') {
            await Notification.requestPermission()
        }
        
        if (Notification.permission === 'granted') {
            notifications.forEach(notification => {
                const title = this.getNotificationTitle(notification)
                const body = this.getNotificationMessage(notification)
                const icon = this.getNotificationIcon(notification.type)
                
                const browserNotification = new Notification(title, {
                    body,
                    icon,
                    tag: `notification-${notification.id}`,
                    requireInteraction: notification.type === 'incident_alert'
                })
                
                // Auto-close after 5 seconds (except for incidents)
                if (notification.type !== 'incident_alert') {
                    setTimeout(() => {
                        browserNotification.close()
                    }, 5000)
                }
                
                // Handle click
                browserNotification.onclick = () => {
                    window.focus()
                    this.handleNotificationClick(notification)
                    browserNotification.close()
                }
            })
        }
    }

    /**
     * Get notification title for browser notification
     */
    getNotificationTitle(notification) {
        const titles = {
            'exit_reminder': 'Rappel de sortie',
            'checklist_validation': 'Validation requise',
            'incident_alert': '🚨 Incident détecté',
            'mission_assigned': 'Nouvelle mission',
            'calendar_update': 'Mise à jour calendrier'
        }
        return titles[notification.type] || 'Notification'
    }

    /**
     * Get notification message
     */
    getNotificationMessage(notification) {
        if (notification.message) return notification.message
        
        const bailMobilite = notification.bail_mobilite
        const tenantName = bailMobilite?.tenant_name || 'Locataire inconnu'
        
        const messages = {
            'exit_reminder': `Bail Mobilité se termine dans 10 jours - ${tenantName}`,
            'checklist_validation': `Checklist à valider pour ${tenantName}`,
            'incident_alert': `Incident détecté pour ${tenantName}`,
            'mission_assigned': 'Nouvelle mission assignée',
            'calendar_update': `Calendrier mis à jour - ${tenantName}`
        }
        
        return messages[notification.type] || 'Nouvelle notification'
    }

    /**
     * Get notification icon
     */
    getNotificationIcon(type) {
        const icons = {
            'exit_reminder': '/images/icons/reminder.png',
            'checklist_validation': '/images/icons/checklist.png',
            'incident_alert': '/images/icons/alert.png',
            'mission_assigned': '/images/icons/mission.png',
            'calendar_update': '/images/icons/calendar.png'
        }
        return icons[type] || '/images/icons/notification.png'
    }

    /**
     * Handle notification click
     */
    handleNotificationClick(notification) {
        // Emit click event for components to handle
        this.emit('notification:clicked', notification)
        
        // Default navigation based on type
        const routes = {
            'exit_reminder': () => `/ops/bail-mobilites/${notification.bail_mobilite_id}`,
            'checklist_validation': () => `/ops/missions/${notification.data?.mission_id}/validate`,
            'incident_alert': () => `/ops/bail-mobilites/${notification.bail_mobilite_id}`,
            'mission_assigned': () => `/ops/missions/${notification.data?.mission_id}`,
            'calendar_update': () => '/ops/calendar'
        }
        
        const getRoute = routes[notification.type]
        if (getRoute && window.router) {
            window.router.visit(getRoute())
        }
    }

    /**
     * Mark notification as handled
     */
    async markAsHandled(notificationId) {
        try {
            await axios.post(`/ops/notifications/${notificationId}/mark-handled`)
            
            // Remove from local list
            this.notifications = this.notifications.filter(n => n.id !== notificationId)
            
            this.emit('notification:handled', notificationId)
            this.emit('notifications:updated', {
                notifications: this.notifications,
                count: this.notifications.length
            })
            
            return true
        } catch (error) {
            console.error('Failed to mark notification as handled:', error)
            this.emit('notifications:error', error)
            
            if (window.toast) {
                window.toast.error('Erreur lors du marquage de la notification')
            }
            return false
        }
    }

    /**
     * Handle quick action on notification
     */
    async handleQuickAction(notificationId, action) {
        try {
            const response = await axios.post(`/ops/notifications/${notificationId}/action`, {
                action
            })
            
            this.emit('notification:action', { notificationId, action, response: response.data })
            
            // Optionally remove from list after action
            if (response.data.remove_from_list) {
                this.notifications = this.notifications.filter(n => n.id !== notificationId)
                this.emit('notifications:updated', {
                    notifications: this.notifications,
                    count: this.notifications.length
                })
            }
            
            return response.data
        } catch (error) {
            console.error('Failed to handle notification action:', error)
            this.emit('notifications:error', error)
            
            if (window.toast) {
                window.toast.error('Erreur lors de l\'action sur la notification')
            }
            throw error
        }
    }

    /**
     * Get notifications by type
     */
    getNotificationsByType(type) {
        return this.notifications.filter(n => n.type === type)
    }

    /**
     * Get urgent notifications (incidents and exit reminders)
     */
    getUrgentNotifications() {
        return this.notifications.filter(n => 
            ['incident_alert', 'exit_reminder'].includes(n.type)
        )
    }

    /**
     * Get notification count by type
     */
    getCountByType(type) {
        return this.getNotificationsByType(type).length
    }

    /**
     * Get total notification count
     */
    getTotalCount() {
        return this.notifications.length
    }

    /**
     * Setup visibility change handler to pause/resume polling
     */
    setupVisibilityChangeHandler() {
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                // Reduce polling frequency when tab is not visible
                this.stopPolling()
                this.pollingFrequency = 60000 // 1 minute
                this.startPolling()
            } else {
                // Resume normal polling when tab becomes visible
                this.stopPolling()
                this.pollingFrequency = 30000 // 30 seconds
                this.startPolling()
                // Fetch immediately when tab becomes visible
                this.fetchNotifications()
            }
        })
    }

    /**
     * Add event listener
     */
    on(event, callback) {
        if (!this.listeners[event]) {
            this.listeners[event] = []
        }
        this.listeners[event].push(callback)
    }

    /**
     * Remove event listener
     */
    off(event, callback) {
        if (!this.listeners[event]) return
        
        const index = this.listeners[event].indexOf(callback)
        if (index > -1) {
            this.listeners[event].splice(index, 1)
        }
    }

    /**
     * Emit event to listeners
     */
    emit(event, data) {
        if (!this.listeners[event]) return
        
        this.listeners[event].forEach(callback => {
            try {
                callback(data)
            } catch (error) {
                console.error(`Error in notification listener for ${event}:`, error)
            }
        })
    }

    /**
     * Destroy the service
     */
    destroy() {
        this.stopPolling()
        this.listeners = []
        this.notifications = []
    }
}

// Create singleton instance
const notificationService = new NotificationService()

export default notificationService