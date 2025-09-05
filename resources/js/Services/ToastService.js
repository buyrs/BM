/**
 * Toast Service
 * Manages toast notifications throughout the application
 */

// import { reactive } from 'vue'

class ToastService {
  constructor() {
    this.toasts = []
    this.nextId = 1
  }

  /**
   * Add a new toast notification
   */
  add(options) {
    const toast = {
      id: this.nextId++,
      type: options.type || 'info',
      title: options.title || '',
      message: options.message || '',
      duration: options.duration !== undefined ? options.duration : 5000,
      action: options.action || null,
      persistent: options.persistent || false,
      timestamp: Date.now()
    }

    this.toasts.push(toast)

    // Auto-remove if not persistent and has duration
    if (!toast.persistent && toast.duration > 0) {
      setTimeout(() => {
        this.remove(toast.id)
      }, toast.duration)
    }

    return toast.id
  }

  /**
   * Remove a toast by ID
   */
  remove(id) {
    const index = this.toasts.findIndex(toast => toast.id === id)
    if (index > -1) {
      this.toasts.splice(index, 1)
    }
  }

  /**
   * Clear all toasts
   */
  clear() {
    this.toasts.splice(0)
  }

  /**
   * Show success toast
   */
  success(message, options = {}) {
    return this.add({
      type: 'success',
      message,
      ...options
    })
  }

  /**
   * Show error toast
   */
  error(message, options = {}) {
    return this.add({
      type: 'error',
      message,
      duration: options.duration !== undefined ? options.duration : 8000, // Longer for errors
      ...options
    })
  }

  /**
   * Show warning toast
   */
  warning(message, options = {}) {
    return this.add({
      type: 'warning',
      message,
      ...options
    })
  }

  /**
   * Show info toast
   */
  info(message, options = {}) {
    return this.add({
      type: 'info',
      message,
      ...options
    })
  }

  /**
   * Show loading toast
   */
  loading(message, options = {}) {
    return this.add({
      type: 'info',
      message,
      persistent: true,
      ...options
    })
  }

  /**
   * Update an existing toast
   */
  update(id, options) {
    const toast = this.toasts.find(t => t.id === id)
    if (toast) {
      Object.assign(toast, options)
    }
  }

  /**
   * Get all toasts
   */
  getToasts() {
    return this.toasts
  }

  /**
   * Get toast count by type
   */
  getCount(type = null) {
    if (type) {
      return this.toasts.filter(toast => toast.type === type).length
    }
    return this.toasts.length
  }

  /**
   * Check if there are any error toasts
   */
  hasErrors() {
    return this.toasts.some(toast => toast.type === 'error')
  }

  /**
   * Remove all toasts of a specific type
   */
  clearType(type) {
    for (let i = this.toasts.length - 1; i >= 0; i--) {
      if (this.toasts[i].type === type) {
        this.toasts.splice(i, 1)
      }
    }
  }

  /**
   * Show a retry toast for failed operations
   */
  showRetry(operation, retryFn, options = {}) {
    return this.error(`${operation} failed`, {
      title: 'Operation Failed',
      action: {
        label: 'Retry',
        onClick: retryFn
      },
      ...options
    })
  }

  /**
   * Show a network error toast
   */
  showNetworkError(operation, retryFn = null) {
    const action = retryFn ? {
      label: 'Retry',
      onClick: retryFn
    } : null

    return this.error('Network connection error. Please check your internet connection.', {
      title: `${operation} Failed`,
      action,
      persistent: !retryFn
    })
  }

  /**
   * Show a validation error toast
   */
  showValidationError(errors, title = 'Validation Error') {
    let message = 'Please check your input and try again.'
    
    if (typeof errors === 'object' && errors !== null) {
      const errorMessages = Object.values(errors).flat()
      if (errorMessages.length > 0) {
        message = errorMessages.join(', ')
      }
    } else if (typeof errors === 'string') {
      message = errors
    }

    return this.error(message, { title })
  }

  /**
   * Show a permission error toast
   */
  showPermissionError(action = 'perform this action') {
    return this.error(`You do not have permission to ${action}.`, {
      title: 'Access Denied',
      persistent: true
    })
  }

  /**
   * Show a server error toast
   */
  showServerError(operation, retryFn = null) {
    const action = retryFn ? {
      label: 'Retry',
      onClick: retryFn
    } : null

    return this.error('A server error occurred. Please try again in a moment.', {
      title: `${operation} Failed`,
      action
    })
  }
}

// Create singleton instance
const toastService = new ToastService()

// Make it available globally for error service
if (typeof window !== 'undefined') {
  window.toast = toastService
}

export default toastService