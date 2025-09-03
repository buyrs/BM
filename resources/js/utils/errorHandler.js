/**
 * Global Error Handler for Vue.js Application
 * Provides centralized error handling, logging, and user feedback
 */

class ErrorHandler {
    constructor() {
        this.errorQueue = [];
        this.maxErrors = 50;
        this.isOnline = navigator.onLine;
        
        // Listen for online/offline events
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.processOfflineErrors();
        });
        
        window.addEventListener('offline', () => {
            this.isOnline = false;
        });
    }

    /**
     * Handle Vue component errors
     */
    handleVueError(error, instance, info) {
        const errorData = {
            type: 'vue_error',
            message: error.message,
            stack: error.stack,
            component: instance?.$options.name || 'Unknown',
            info,
            timestamp: new Date().toISOString(),
            url: window.location.href,
            userAgent: navigator.userAgent
        };

        this.logError(errorData);
        this.showUserFriendlyMessage('Something went wrong. Please refresh the page and try again.');
    }

    /**
     * Handle API/Network errors
     */
    handleApiError(error, context = '') {
        const errorData = {
            type: 'api_error',
            message: error.message,
            status: error.response?.status,
            statusText: error.response?.statusText,
            data: error.response?.data,
            context,
            timestamp: new Date().toISOString(),
            url: window.location.href
        };

        this.logError(errorData);

        // Handle specific error types
        if (error.response?.status === 401) {
            this.handleAuthError();
        } else if (error.response?.status === 403) {
            this.showUserFriendlyMessage('You don\'t have permission to perform this action.');
        } else if (error.response?.status === 404) {
            this.showUserFriendlyMessage('The requested resource was not found.');
        } else if (error.response?.status >= 500) {
            this.showUserFriendlyMessage('Server error occurred. Please try again later.');
        } else if (!this.isOnline) {
            this.showUserFriendlyMessage('You appear to be offline. Please check your connection.');
        } else {
            const message = error.response?.data?.message || 'An unexpected error occurred.';
            this.showUserFriendlyMessage(message);
        }

        return errorData;
    }

    /**
     * Handle authentication errors
     */
    handleAuthError() {
        this.showUserFriendlyMessage('Your session has expired. Redirecting to login...');
        setTimeout(() => {
            window.location.href = '/login';
        }, 2000);
    }

    /**
     * Log error to console and queue for potential remote logging
     */
    logError(errorData) {
        console.error('Application Error:', errorData);
        
        // Add to error queue for potential remote logging
        this.errorQueue.push(errorData);
        
        // Keep queue size manageable
        if (this.errorQueue.length > this.maxErrors) {
            this.errorQueue.shift();
        }

        // In production, you might want to send errors to a logging service
        if (process.env.NODE_ENV === 'production') {
            this.sendToLoggingService(errorData);
        }
    }

    /**
     * Send error to remote logging service (placeholder)
     */
    async sendToLoggingService(errorData) {
        try {
            // Only send critical errors to avoid spam
            if (this.isCriticalError(errorData)) {
                // Implement your logging service here
                // await fetch('/api/log-error', {
                //     method: 'POST',
                //     headers: { 'Content-Type': 'application/json' },
                //     body: JSON.stringify(errorData)
                // });
            }
        } catch (e) {
            // Silently fail - don't create error loops
        }
    }

    /**
     * Determine if error is critical
     */
    isCriticalError(errorData) {
        return errorData.type === 'vue_error' || 
               (errorData.type === 'api_error' && errorData.status >= 500);
    }

    /**
     * Show user-friendly error message
     */
    showUserFriendlyMessage(message, type = 'error') {
        // Create toast notification
        this.showToast(message, type);
    }

    /**
     * Show toast notification
     */
    showToast(message, type = 'error', duration = 5000) {
        // Remove existing toasts of the same type
        const existingToasts = document.querySelectorAll(`.toast-${type}`);
        existingToasts.forEach(toast => toast.remove());

        const toast = document.createElement('div');
        toast.className = `toast toast-${type} fixed top-4 right-4 z-50 max-w-sm p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full`;
        
        const bgColor = type === 'error' ? 'bg-red-500' : 
                       type === 'warning' ? 'bg-yellow-500' : 
                       type === 'success' ? 'bg-green-500' : 'bg-blue-500';
        
        toast.classList.add(bgColor, 'text-white');
        
        toast.innerHTML = `
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        ${type === 'error' ? 
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>' :
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'
                        }
                    </svg>
                    <span class="text-sm font-medium">${message}</span>
                </div>
                <button class="ml-4 text-white hover:text-gray-200" onclick="this.parentElement.parentElement.remove()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        `;

        document.body.appendChild(toast);

        // Animate in
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
        }, 100);

        // Auto remove
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 300);
        }, duration);
    }

    /**
     * Process errors that occurred while offline
     */
    processOfflineErrors() {
        // In a real implementation, you might retry failed API calls
        this.showToast('Connection restored', 'success');
    }

    /**
     * Get error statistics
     */
    getErrorStats() {
        const stats = {
            total: this.errorQueue.length,
            byType: {},
            recent: this.errorQueue.slice(-10)
        };

        this.errorQueue.forEach(error => {
            stats.byType[error.type] = (stats.byType[error.type] || 0) + 1;
        });

        return stats;
    }
}

// Create global instance
const errorHandler = new ErrorHandler();

export default errorHandler;