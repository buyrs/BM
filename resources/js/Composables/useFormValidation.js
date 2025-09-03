/**
 * Form Validation Composable
 * Provides comprehensive form validation with field-specific error display
 */

import { ref, reactive, computed, watch } from 'vue';
import errorMessageService from '@/Services/ErrorMessageService.js';

export function useFormValidation(initialData = {}, validationRules = {}) {
    // Form data
    const form = reactive({ ...initialData });
    
    // Form state
    const errors = ref({});
    const touched = ref({});
    const isSubmitting = ref(false);
    const isValid = ref(true);
    
    // Validation rules
    const rules = reactive(validationRules);
    
    // Built-in validation rules
    const validators = {
        required: (value, message = 'This field is required') => {
            if (value === null || value === undefined || value === '' || 
                (Array.isArray(value) && value.length === 0)) {
                return message;
            }
            return null;
        },
        
        email: (value, message = 'Please enter a valid email address') => {
            if (!value) return null; // Allow empty if not required
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(value) ? null : message;
        },
        
        min: (minLength, message) => (value) => {
            if (!value) return null;
            const length = typeof value === 'string' ? value.length : Number(value);
            return length >= minLength ? null : (message || `Minimum ${minLength} characters required`);
        },
        
        max: (maxLength, message) => (value) => {
            if (!value) return null;
            const length = typeof value === 'string' ? value.length : Number(value);
            return length <= maxLength ? null : (message || `Maximum ${maxLength} characters allowed`);
        },
        
        numeric: (value, message = 'Please enter a valid number') => {
            if (!value) return null;
            return !isNaN(Number(value)) ? null : message;
        },
        
        phone: (value, message = 'Please enter a valid phone number') => {
            if (!value) return null;
            const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
            return phoneRegex.test(value.replace(/\s/g, '')) ? null : message;
        },
        
        url: (value, message = 'Please enter a valid URL') => {
            if (!value) return null;
            try {
                new URL(value);
                return null;
            } catch {
                return message;
            }
        },
        
        date: (value, message = 'Please enter a valid date') => {
            if (!value) return null;
            const date = new Date(value);
            return !isNaN(date.getTime()) ? null : message;
        },
        
        dateAfter: (afterDate, message) => (value) => {
            if (!value) return null;
            const date = new Date(value);
            const after = new Date(afterDate);
            return date > after ? null : (message || `Date must be after ${after.toLocaleDateString()}`);
        },
        
        dateBefore: (beforeDate, message) => (value) => {
            if (!value) return null;
            const date = new Date(value);
            const before = new Date(beforeDate);
            return date < before ? null : (message || `Date must be before ${before.toLocaleDateString()}`);
        },
        
        fileSize: (maxSizeInMB, message) => (file) => {
            if (!file) return null;
            const maxSizeInBytes = maxSizeInMB * 1024 * 1024;
            return file.size <= maxSizeInBytes ? null : (message || `File size must be less than ${maxSizeInMB}MB`);
        },
        
        fileType: (allowedTypes, message) => (file) => {
            if (!file) return null;
            const fileType = file.type || '';
            const isAllowed = allowedTypes.some(type => {
                if (type.includes('*')) {
                    const baseType = type.split('/')[0];
                    return fileType.startsWith(baseType + '/');
                }
                return fileType === type;
            });
            return isAllowed ? null : (message || `File type must be one of: ${allowedTypes.join(', ')}`);
        },
        
        match: (otherField, message) => (value) => {
            const otherValue = form[otherField];
            return value === otherValue ? null : (message || `This field must match ${otherField}`);
        },
        
        custom: (validatorFn, message) => (value) => {
            try {
                const result = validatorFn(value, form);
                return result === true ? null : (result || message || 'Validation failed');
            } catch (error) {
                return message || 'Validation error occurred';
            }
        }
    };
    
    // Validate a single field
    const validateField = (fieldName) => {
        const fieldRules = rules[fieldName];
        if (!fieldRules) return;
        
        const value = form[fieldName];
        const fieldErrors = [];
        
        // Handle array of rules or single rule
        const rulesToCheck = Array.isArray(fieldRules) ? fieldRules : [fieldRules];
        
        for (const rule of rulesToCheck) {
            let validator, message;
            
            if (typeof rule === 'string') {
                // Simple rule name
                validator = validators[rule];
                message = undefined;
            } else if (typeof rule === 'function') {
                // Custom validator function
                validator = rule;
                message = undefined;
            } else if (typeof rule === 'object') {
                // Rule object with parameters
                const { type, params = [], message: customMessage } = rule;
                validator = validators[type];
                message = customMessage;
                
                if (validator && params.length > 0) {
                    validator = validator(...params, message);
                }
            }
            
            if (validator) {
                const error = validator(value, message);
                if (error) {
                    fieldErrors.push(error);
                    break; // Stop at first error
                }
            }
        }
        
        // Update errors
        if (fieldErrors.length > 0) {
            errors.value[fieldName] = fieldErrors[0];
        } else {
            delete errors.value[fieldName];
        }
    };
    
    // Validate all fields
    const validateAll = () => {
        Object.keys(rules).forEach(validateField);
        isValid.value = Object.keys(errors.value).length === 0;
        return isValid.value;
    };
    
    // Clear errors for a field
    const clearFieldError = (fieldName) => {
        delete errors.value[fieldName];
        isValid.value = Object.keys(errors.value).length === 0;
    };
    
    // Clear all errors
    const clearErrors = () => {
        errors.value = {};
        isValid.value = true;
    };
    
    // Set server errors (from API response)
    const setServerErrors = (serverErrors) => {
        if (typeof serverErrors === 'object' && serverErrors !== null) {
            Object.keys(serverErrors).forEach(field => {
                const fieldErrors = serverErrors[field];
                if (Array.isArray(fieldErrors)) {
                    errors.value[field] = fieldErrors[0];
                } else {
                    errors.value[field] = fieldErrors;
                }
            });
        }
        isValid.value = Object.keys(errors.value).length === 0;
    };
    
    // Mark field as touched
    const touchField = (fieldName) => {
        touched.value[fieldName] = true;
    };
    
    // Check if field has been touched
    const isFieldTouched = (fieldName) => {
        return touched.value[fieldName] || false;
    };
    
    // Check if field has error
    const hasFieldError = (fieldName) => {
        return errors.value[fieldName] !== undefined;
    };
    
    // Get field error message
    const getFieldError = (fieldName) => {
        return errors.value[fieldName] || null;
    };
    
    // Reset form to initial state
    const reset = (newData = null) => {
        const dataToUse = newData || initialData;
        Object.keys(form).forEach(key => {
            delete form[key];
        });
        Object.assign(form, dataToUse);
        
        errors.value = {};
        touched.value = {};
        isSubmitting.value = false;
        isValid.value = true;
    };
    
    // Handle form submission with validation
    const handleSubmit = async (submitFn, options = {}) => {
        const {
            validateBeforeSubmit = true,
            showLoadingToast = true,
            showSuccessToast = true,
            showErrorToast = true,
            context = null,
            operation = null
        } = options;
        
        // Mark all fields as touched
        Object.keys(rules).forEach(field => {
            touched.value[field] = true;
        });
        
        // Validate if required
        if (validateBeforeSubmit && !validateAll()) {
            if (showErrorToast) {
                errorMessageService.showError(
                    { response: { status: 422, data: { errors: errors.value } } },
                    context,
                    operation
                );
            }
            return false;
        }
        
        isSubmitting.value = true;
        
        try {
            let loadingToastId = null;
            
            if (showLoadingToast) {
                loadingToastId = errorMessageService.showLoading(null, context, operation);
            }
            
            const result = await submitFn(form);
            
            if (loadingToastId) {
                window.toast?.remove(loadingToastId);
            }
            
            if (showSuccessToast) {
                errorMessageService.showSuccess(null, context, operation);
            }
            
            return result;
        } catch (error) {
            // Handle server validation errors
            if (error.response?.status === 422 && error.response?.data?.errors) {
                setServerErrors(error.response.data.errors);
            }
            
            if (showErrorToast) {
                errorMessageService.showError(error, context, operation);
            }
            
            throw error;
        } finally {
            isSubmitting.value = false;
        }
    };
    
    // Watch form changes for real-time validation
    const enableRealTimeValidation = (debounceMs = 300) => {
        Object.keys(rules).forEach(fieldName => {
            let timeoutId;
            
            watch(
                () => form[fieldName],
                () => {
                    if (touched.value[fieldName]) {
                        clearTimeout(timeoutId);
                        timeoutId = setTimeout(() => {
                            validateField(fieldName);
                        }, debounceMs);
                    }
                }
            );
        });
    };
    
    // Computed properties
    const hasErrors = computed(() => Object.keys(errors.value).length > 0);
    const errorCount = computed(() => Object.keys(errors.value).length);
    const touchedFields = computed(() => Object.keys(touched.value).filter(key => touched.value[key]));
    
    return {
        // Form data
        form,
        
        // Validation state
        errors: errors.value,
        touched: touched.value,
        isSubmitting,
        isValid,
        hasErrors,
        errorCount,
        touchedFields,
        
        // Validation methods
        validateField,
        validateAll,
        clearFieldError,
        clearErrors,
        setServerErrors,
        
        // Field interaction
        touchField,
        isFieldTouched,
        hasFieldError,
        getFieldError,
        
        // Form management
        reset,
        handleSubmit,
        enableRealTimeValidation,
        
        // Validators (for custom rules)
        validators
    };
}

// Utility function to create validation rules
export function createValidationRules(rulesConfig) {
    const rules = {};
    
    Object.keys(rulesConfig).forEach(field => {
        const fieldRules = rulesConfig[field];
        
        if (typeof fieldRules === 'string') {
            // Simple rule string like 'required|email'
            rules[field] = fieldRules.split('|').map(rule => {
                const [ruleName, ...params] = rule.split(':');
                return params.length > 0 ? { type: ruleName, params: params[0].split(',') } : ruleName;
            });
        } else {
            rules[field] = fieldRules;
        }
    });
    
    return rules;
}

// Utility function for common form patterns
export function useFormWithValidation(initialData, validationRules, options = {}) {
    const validation = useFormValidation(initialData, validationRules);
    
    // Enable real-time validation by default
    if (options.realTimeValidation !== false) {
        validation.enableRealTimeValidation(options.debounceMs);
    }
    
    return validation;
}