/**
 * Error Message Service
 * Provides contextual, user-friendly error messages and retry mechanisms
 */

import toastService from './ToastService.js';

class ErrorMessageService {
    constructor() {
        this.errorMessages = {
            // Network errors
            'NETWORK_ERROR': 'Network connection failed. Please check your internet connection.',
            'TIMEOUT_ERROR': 'Request timed out. Please try again.',
            'CONNECTION_REFUSED': 'Unable to connect to server. Please try again later.',
            
            // Authentication errors
            'AUTH_FAILED': 'Authentication failed. Please log in again.',
            'SESSION_EXPIRED': 'Your session has expired. Please log in again.',
            'INVALID_CREDENTIALS': 'Invalid username or password.',
            
            // Permission errors
            'PERMISSION_DENIED': 'You don\'t have permission to perform this action.',
            'ROLE_REQUIRED': 'This action requires additional permissions.',
            
            // Validation errors
            'VALIDATION_FAILED': 'Please check your input and try again.',
            'REQUIRED_FIELD': 'This field is required.',
            'INVALID_FORMAT': 'Please enter a valid format.',
            'FILE_TOO_LARGE': 'File size is too large. Please choose a smaller file.',
            'INVALID_FILE_TYPE': 'Invalid file type. Please choose a different file.',
            
            // Business logic errors
            'MISSION_NOT_FOUND': 'Mission not found. It may have been deleted or moved.',
            'CHECKLIST_ALREADY_COMPLETED': 'This checklist has already been completed.',
            'SIGNATURE_REQUIRED': 'A signature is required to complete this action.',
            'BAIL_MOBILITE_EXPIRED': 'This bail mobilité has expired and cannot be modified.',
            
            // Server errors
            'SERVER_ERROR': 'A server error occurred. Our team has been notified.',
            'SERVICE_UNAVAILABLE': 'Service is temporarily unavailable. Please try again later.',
            'DATABASE_ERROR': 'Database error occurred. Please try again.',
            
            // File upload errors
            'UPLOAD_FAILED': 'File upload failed. Please try again.',
            'STORAGE_FULL': 'Storage is full. Please contact support.',
            
            // Generic errors
            'UNKNOWN_ERROR': 'An unexpected error occurred. Please try again.',
            'OPERATION_FAILED': 'Operation failed. Please try again.'
        };

        this.contextualMessages = {
            'missions': {
                'create': 'Failed to create mission',
                'update': 'Failed to update mission',
                'delete': 'Failed to delete mission',
                'assign': 'Failed to assign mission',
                'complete': 'Failed to complete mission'
            },
            'checklists': {
                'create': 'Failed to create checklist',
                'update': 'Failed to update checklist',
                'submit': 'Failed to submit checklist',
                'validate': 'Failed to validate checklist'
            },
            'signatures': {
                'create': 'Failed to create signature',
                'save': 'Failed to save signature',
                'generate_pdf': 'Failed to generate PDF contract'
            },
            'bail_mobilites': {
                'create': 'Failed to create bail mobilité',
                'update': 'Failed to update bail mobilité',
                'delete': 'Failed to delete bail mobilité'
            },
            'users': {
                'create': 'Failed to create user',
                'update': 'Failed to update user profile',
                'delete': 'Failed to delete user'
            }
        };

        this.retryStrategies = {
            'network': { maxRetries: 3, delay: 1000, backoff: true },
            'server': { maxRetries: 2, delay: 2000, backoff: true },
            'timeout': { maxRetries: 2, delay: 1500, backoff: false },
            'validation': { maxRetries: 0, delay: 0, backoff: false },
            'permission': { maxRetries: 0, delay: 0, backoff: false }
        };
    }

    /**
     * Get user-friendly error message
     */
    getMessage(error, context = null, operation = null) {
        let message = this.errorMessages['UNKNOWN_ERROR'];
        
        // Check for specific error codes or types
        if (error.code && this.errorMessages[error.code]) {
            message = this.errorMessages[error.code];
        } else if (error.response?.status) {
            message = this.getHttpErrorMessage(error.response.status, error.response.data);
        } else if (error.message) {
            message = this.parseErrorMessage(error.message);
        }

        // Add contextual information
        if (context && operation && this.contextualMessages[context]?.[operation]) {
            const contextMessage = this.contextualMessages[context][operation];
            message = `${contextMessage}: ${message}`;
        }

        return message;
    }

    /**
     * Get HTTP error message based on status code
     */
    getHttpErrorMessage(status, data = null) {
        switch (status) {
            case 400:
                return data?.message || 'Bad request. Please check your input.';
            case 401:
                return 'Authentication required. Please log in.';
            case 403:
                return 'Access denied. You don\'t have permission for this action.';
            case 404:
                return 'Resource not found. It may have been moved or deleted.';
            case 409:
                return 'Conflict detected. The resource may have been modified by another user.';
            case 422:
                return this.formatValidationErrors(data?.errors) || 'Validation failed. Please check your input.';
            case 429:
                return 'Too many requests. Please wait a moment and try again.';
            case 500:
                return 'Internal server error. Our team has been notified.';
            case 502:
                return 'Service temporarily unavailable. Please try again later.';
            case 503:
                return 'Service maintenance in progress. Please try again later.';
            case 504:
                return 'Request timeout. Please try again.';
            default:
                return data?.message || `Server error (${status}). Please try again.`;
        }
    }

    /**
     * Parse generic error message
     */
    parseErrorMessage(message) {
        // Common error patterns
        if (message.includes('Network Error') || message.includes('ERR_NETWORK')) {
            return this.errorMessages['NETWORK_ERROR'];
        }
        if (message.includes('timeout') || message.includes('TIMEOUT')) {
            return this.errorMessages['TIMEOUT_ERROR'];
        }
        if (message.includes('ECONNREFUSED')) {
            return this.errorMessages['CONNECTION_REFUSED'];
        }
        
        return message;
    }

    /**
     * Format validation errors into readable message
     */
    formatValidationErrors(errors) {
        if (!errors || typeof errors !== 'object') {
            return null;
        }

        const errorMessages = [];
        for (const [field, messages] of Object.entries(errors)) {
            if (Array.isArray(messages)) {
                errorMessages.push(...messages);
            } else {
                errorMessages.push(messages);
            }
        }

        return errorMessages.length > 0 ? errorMessages.join(', ') : null;
    }

    /**
     * Show error with appropriate toast type and retry option
     */
    showError(error, context = null, operation = null, retryFn = null) {
        const message = this.getMessage(error, context, operation);
        const errorType = this.categorizeError(error);
        
        if (retryFn && this.shouldShowRetry(errorType)) {
            const strategy = this.retryStrategies[errorType];
            toastService.showRetry(operation || 'Operation', retryFn, {
                title: 'Error',
                duration: strategy.maxRetries > 0 ? 8000 : 5000
            });
        } else {
            toastService.error(message, {
                title: this.getErrorTitle(errorType),
                duration: this.getErrorDuration(errorType)
            });
        }
    }

    /**
     * Categorize error for appropriate handling
     */
    categorizeError(error) {
        if (error.code === 'NETWORK_ERROR' || error.message?.includes('Network Error')) {
            return 'network';
        }
        if (error.code === 'TIMEOUT_ERROR' || error.message?.includes('timeout')) {
            return 'timeout';
        }
        if (error.response?.status >= 500) {
            return 'server';
        }
        if (error.response?.status === 422) {
            return 'validation';
        }
        if (error.response?.status === 401 || error.response?.status === 403) {
            return 'permission';
        }
        
        return 'unknown';
    }

    /**
     * Determine if retry option should be shown
     */
    shouldShowRetry(errorType) {
        const strategy = this.retryStrategies[errorType];
        return strategy && strategy.maxRetries > 0;
    }

    /**
     * Get appropriate error title
     */
    getErrorTitle(errorType) {
        const titles = {
            'network': 'Connection Error',
            'server': 'Server Error',
            'timeout': 'Timeout Error',
            'validation': 'Validation Error',
            'permission': 'Access Denied',
            'unknown': 'Error'
        };
        
        return titles[errorType] || 'Error';
    }

    /**
     * Get appropriate error duration
     */
    getErrorDuration(errorType) {
        const durations = {
            'network': 8000,
            'server': 8000,
            'timeout': 6000,
            'validation': 10000, // Longer for validation errors
            'permission': 6000,
            'unknown': 5000
        };
        
        return durations[errorType] || 5000;
    }

    /**
     * Show success message
     */
    showSuccess(message, context = null, operation = null) {
        const contextualMessage = this.getSuccessMessage(message, context, operation);
        toastService.success(contextualMessage, {
            duration: 4000
        });
    }

    /**
     * Get contextual success message
     */
    getSuccessMessage(message, context, operation) {
        const successMessages = {
            'missions': {
                'create': 'Mission created successfully',
                'update': 'Mission updated successfully',
                'delete': 'Mission deleted successfully',
                'assign': 'Mission assigned successfully',
                'complete': 'Mission completed successfully'
            },
            'checklists': {
                'create': 'Checklist created successfully',
                'update': 'Checklist updated successfully',
                'submit': 'Checklist submitted successfully',
                'validate': 'Checklist validated successfully'
            },
            'signatures': {
                'create': 'Signature created successfully',
                'save': 'Signature saved successfully',
                'generate_pdf': 'PDF contract generated successfully'
            },
            'bail_mobilites': {
                'create': 'Bail mobilité created successfully',
                'update': 'Bail mobilité updated successfully',
                'delete': 'Bail mobilité deleted successfully'
            },
            'users': {
                'create': 'User created successfully',
                'update': 'Profile updated successfully',
                'delete': 'User deleted successfully'
            }
        };

        if (context && operation && successMessages[context]?.[operation]) {
            return successMessages[context][operation];
        }

        return message || 'Operation completed successfully';
    }

    /**
     * Show loading message
     */
    showLoading(message, context = null, operation = null) {
        const contextualMessage = this.getLoadingMessage(message, context, operation);
        return toastService.loading(contextualMessage);
    }

    /**
     * Get contextual loading message
     */
    getLoadingMessage(message, context, operation) {
        const loadingMessages = {
            'missions': {
                'create': 'Creating mission...',
                'update': 'Updating mission...',
                'delete': 'Deleting mission...',
                'assign': 'Assigning mission...',
                'complete': 'Completing mission...'
            },
            'checklists': {
                'create': 'Creating checklist...',
                'update': 'Updating checklist...',
                'submit': 'Submitting checklist...',
                'validate': 'Validating checklist...'
            },
            'signatures': {
                'create': 'Creating signature...',
                'save': 'Saving signature...',
                'generate_pdf': 'Generating PDF contract...'
            },
            'bail_mobilites': {
                'create': 'Creating bail mobilité...',
                'update': 'Updating bail mobilité...',
                'delete': 'Deleting bail mobilité...'
            },
            'users': {
                'create': 'Creating user...',
                'update': 'Updating profile...',
                'delete': 'Deleting user...'
            }
        };

        if (context && operation && loadingMessages[context]?.[operation]) {
            return loadingMessages[context][operation];
        }

        return message || 'Loading...';
    }

    /**
     * Handle form submission with comprehensive error handling
     */
    async handleFormSubmission(submitFn, context, operation, options = {}) {
        const loadingToastId = this.showLoading(options.loadingMessage, context, operation);
        
        try {
            const result = await submitFn();
            
            // Remove loading toast
            toastService.remove(loadingToastId);
            
            // Show success message
            this.showSuccess(options.successMessage, context, operation);
            
            return result;
        } catch (error) {
            // Remove loading toast
            toastService.remove(loadingToastId);
            
            // Show error with retry option if applicable
            this.showError(error, context, operation, options.retryFn);
            
            throw error;
        }
    }
}

// Create singleton instance
const errorMessageService = new ErrorMessageService();

// Make it available globally
if (typeof window !== 'undefined') {
    window.errorMessages = errorMessageService;
}

export default errorMessageService;