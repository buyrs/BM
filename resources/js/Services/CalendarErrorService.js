/**
 * Calendar Error Service
 * Handles error management, retry logic, and user notifications for calendar operations
 */

class CalendarErrorService {
  constructor() {
    this.retryAttempts = new Map()
    this.maxRetries = 3
    this.retryDelay = 1000 // Base delay in milliseconds
    this.errorCallbacks = new Map()
  }

  /**
   * Handle API errors with automatic retry logic
   */
  async handleApiError(error, operation, retryFn, options = {}) {
    const {
      maxRetries = this.maxRetries,
      retryDelay = this.retryDelay,
      showNotification = true,
      context = 'calendar'
    } = options

    const operationKey = `${context}_${operation}`
    const currentAttempts = this.retryAttempts.get(operationKey) || 0

    console.error(`Calendar API Error [${operation}]:`, error)

    // Check if this is a retryable error
    if (this.isRetryableError(error) && currentAttempts < maxRetries) {
      this.retryAttempts.set(operationKey, currentAttempts + 1)
      
      if (showNotification) {
        this.showRetryNotification(operation, currentAttempts + 1, maxRetries)
      }

      // Exponential backoff
      const delay = retryDelay * Math.pow(2, currentAttempts)
      
      return new Promise((resolve, reject) => {
        setTimeout(async () => {
          try {
            const result = await retryFn()
            this.retryAttempts.delete(operationKey)
            resolve(result)
          } catch (retryError) {
            reject(await this.handleApiError(retryError, operation, retryFn, options))
          }
        }, delay)
      })
    }

    // Max retries reached or non-retryable error
    this.retryAttempts.delete(operationKey)
    
    const errorInfo = this.categorizeError(error)
    
    if (showNotification) {
      this.showErrorNotification(errorInfo, operation)
    }

    return {
      error: true,
      type: errorInfo.type,
      message: errorInfo.message,
      userMessage: errorInfo.userMessage,
      canRetry: errorInfo.canRetry,
      originalError: error
    }
  }

  /**
   * Determine if an error is retryable
   */
  isRetryableError(error) {
    // Network errors
    if (error.name === 'NetworkError' || error.code === 'NETWORK_ERROR') {
      return true
    }

    // Timeout errors
    if (error.name === 'TimeoutError' || error.code === 'TIMEOUT') {
      return true
    }

    // HTTP status codes that are retryable
    if (error.response?.status) {
      const status = error.response.status
      return status >= 500 || status === 408 || status === 429
    }

    // Inertia/Axios specific errors
    if (error.code === 'ERR_NETWORK' || error.code === 'ERR_INTERNET_DISCONNECTED') {
      return true
    }

    return false
  }

  /**
   * Categorize errors for appropriate handling
   */
  categorizeError(error) {
    const status = error.response?.status
    const message = error.response?.data?.message || error.message

    if (status === 401) {
      return {
        type: 'authentication',
        message: 'Authentication required',
        userMessage: 'Your session has expired. Please log in again.',
        canRetry: false
      }
    }

    if (status === 403) {
      return {
        type: 'authorization',
        message: 'Access denied',
        userMessage: 'You do not have permission to perform this action.',
        canRetry: false
      }
    }

    if (status === 404) {
      return {
        type: 'not_found',
        message: 'Resource not found',
        userMessage: 'The requested information could not be found.',
        canRetry: false
      }
    }

    if (status === 422) {
      return {
        type: 'validation',
        message: 'Validation error',
        userMessage: message || 'Please check your input and try again.',
        canRetry: false,
        validationErrors: error.response?.data?.errors
      }
    }

    if (status === 429) {
      return {
        type: 'rate_limit',
        message: 'Rate limit exceeded',
        userMessage: 'Too many requests. Please wait a moment and try again.',
        canRetry: true
      }
    }

    if (status >= 500) {
      return {
        type: 'server_error',
        message: 'Server error',
        userMessage: 'A server error occurred. Please try again in a moment.',
        canRetry: true
      }
    }

    if (error.name === 'NetworkError' || error.code === 'ERR_NETWORK') {
      return {
        type: 'network_error',
        message: 'Network error',
        userMessage: 'Network connection error. Please check your internet connection.',
        canRetry: true
      }
    }

    if (error.name === 'TimeoutError' || error.code === 'TIMEOUT') {
      return {
        type: 'timeout',
        message: 'Request timeout',
        userMessage: 'The request timed out. Please try again.',
        canRetry: true
      }
    }

    // Generic error
    return {
      type: 'unknown',
      message: message || 'Unknown error',
      userMessage: 'An unexpected error occurred. Please try again.',
      canRetry: true
    }
  }

  /**
   * Show retry notification to user
   */
  showRetryNotification(operation, attempt, maxAttempts) {
    const message = `Retrying ${operation}... (${attempt}/${maxAttempts})`
    
    // Use toast notification if available
    if (window.toast) {
      window.toast.info(message, { duration: 2000 })
    } else {
      console.info(message)
    }
  }

  /**
   * Show error notification to user
   */
  showErrorNotification(errorInfo, operation) {
    const title = `${operation} failed`
    
    // Use toast notification if available
    if (window.toast) {
      window.toast.error(errorInfo.userMessage, { 
        title,
        duration: 5000,
        action: errorInfo.canRetry ? {
          label: 'Retry',
          onClick: () => this.triggerRetry(operation)
        } : null
      })
    } else {
      console.error(`${title}: ${errorInfo.userMessage}`)
    }
  }

  /**
   * Register error callback for specific operations
   */
  onError(operation, callback) {
    if (!this.errorCallbacks.has(operation)) {
      this.errorCallbacks.set(operation, [])
    }
    this.errorCallbacks.get(operation).push(callback)
  }

  /**
   * Trigger retry for an operation
   */
  triggerRetry(operation) {
    const callbacks = this.errorCallbacks.get(operation) || []
    callbacks.forEach(callback => {
      try {
        callback()
      } catch (error) {
        console.error('Error in retry callback:', error)
      }
    })
  }

  /**
   * Clear retry attempts for an operation
   */
  clearRetryAttempts(operation, context = 'calendar') {
    const operationKey = `${context}_${operation}`
    this.retryAttempts.delete(operationKey)
  }

  /**
   * Get retry count for an operation
   */
  getRetryCount(operation, context = 'calendar') {
    const operationKey = `${context}_${operation}`
    return this.retryAttempts.get(operationKey) || 0
  }

  /**
   * Create a wrapped API function with error handling
   */
  wrapApiCall(operation, apiFn, options = {}) {
    return async (...args) => {
      try {
        const result = await apiFn(...args)
        this.clearRetryAttempts(operation, options.context)
        return result
      } catch (error) {
        return await this.handleApiError(
          error,
          operation,
          () => apiFn(...args),
          options
        )
      }
    }
  }

  /**
   * Validate network connectivity
   */
  async checkConnectivity() {
    try {
      const response = await fetch('/api/health', {
        method: 'HEAD',
        cache: 'no-cache'
      })
      return response.ok
    } catch (error) {
      return false
    }
  }

  /**
   * Handle offline/online events
   */
  setupConnectivityHandling() {
    window.addEventListener('online', () => {
      if (window.toast) {
        window.toast.success('Connection restored')
      }
      // Trigger retry for failed operations
      this.retryAttempts.forEach((attempts, operationKey) => {
        const operation = operationKey.split('_').slice(1).join('_')
        this.triggerRetry(operation)
      })
    })

    window.addEventListener('offline', () => {
      if (window.toast) {
        window.toast.warning('Connection lost. Some features may not work.')
      }
    })
  }
}

// Create singleton instance
const calendarErrorService = new CalendarErrorService()

// Setup connectivity handling
if (typeof window !== 'undefined') {
  calendarErrorService.setupConnectivityHandling()
}

export default calendarErrorService