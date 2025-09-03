/**
 * Composable for managing API state (loading, errors, data)
 */

import { ref, computed } from 'vue';
import apiClient from '@/utils/apiClient';
import errorHandler from '@/utils/errorHandler';

export function useApiState(initialData = null) {
    const loading = ref(false);
    const error = ref(null);
    const data = ref(initialData);

    const hasError = computed(() => error.value !== null);
    const hasData = computed(() => data.value !== null);
    const isEmpty = computed(() => !hasData.value || (Array.isArray(data.value) && data.value.length === 0));

    const clearError = () => {
        error.value = null;
    };

    const setLoading = (isLoading) => {
        loading.value = isLoading;
    };

    const setError = (errorMessage) => {
        error.value = errorMessage;
    };

    const setData = (newData) => {
        data.value = newData;
        error.value = null; // Clear error when data is successfully set
    };

    // Safe API call wrapper
    const safeApiCall = async (apiCall, options = {}) => {
        const {
            showLoading = true,
            clearErrorOnStart = true,
            onSuccess = null,
            onError = null,
            retries = 0
        } = options;

        if (showLoading) setLoading(true);
        if (clearErrorOnStart) clearError();

        let attempt = 0;
        while (attempt <= retries) {
            try {
                const result = await apiCall();
                
                if (result?.data) {
                    setData(result.data);
                }

                if (onSuccess) {
                    onSuccess(result);
                }

                return result;
            } catch (err) {
                attempt++;
                
                if (attempt > retries) {
                    const errorMessage = err.response?.data?.message || err.message || 'An error occurred';
                    setError(errorMessage);
                    
                    if (onError) {
                        onError(err);
                    } else {
                        errorHandler.handleApiError(err);
                    }
                    
                    throw err;
                }
                
                // Wait before retry
                if (attempt <= retries) {
                    await new Promise(resolve => setTimeout(resolve, 1000 * attempt));
                }
            } finally {
                if (attempt > retries && showLoading) {
                    setLoading(false);
                }
            }
        }
    };

    // Convenience methods for common HTTP operations
    const get = (url, config = {}, options = {}) => {
        return safeApiCall(() => apiClient.get(url, config), options);
    };

    const post = (url, data = {}, config = {}, options = {}) => {
        return safeApiCall(() => apiClient.post(url, data, config), options);
    };

    const put = (url, data = {}, config = {}, options = {}) => {
        return safeApiCall(() => apiClient.put(url, data, config), options);
    };

    const patch = (url, data = {}, config = {}, options = {}) => {
        return safeApiCall(() => apiClient.patch(url, data, config), options);
    };

    const del = (url, config = {}, options = {}) => {
        return safeApiCall(() => apiClient.delete(url, config), options);
    };

    // Reset all state
    const reset = () => {
        loading.value = false;
        error.value = null;
        data.value = initialData;
    };

    return {
        // State
        loading,
        error,
        data,
        
        // Computed
        hasError,
        hasData,
        isEmpty,
        
        // Methods
        clearError,
        setLoading,
        setError,
        setData,
        safeApiCall,
        reset,
        
        // HTTP methods
        get,
        post,
        put,
        patch,
        delete: del
    };
}

// Specialized composable for form handling
export function useFormState(initialFormData = {}) {
    const { loading, error, clearError, setLoading, setError } = useApiState();
    const formData = ref({ ...initialFormData });
    const validationErrors = ref({});

    const hasValidationErrors = computed(() => Object.keys(validationErrors.value).length > 0);

    const clearValidationErrors = () => {
        validationErrors.value = {};
    };

    const setValidationErrors = (errors) => {
        validationErrors.value = errors;
    };

    const resetForm = () => {
        formData.value = { ...initialFormData };
        clearError();
        clearValidationErrors();
    };

    const submitForm = async (submitFunction, options = {}) => {
        const { onSuccess, onError, clearErrorsOnStart = true } = options;

        if (clearErrorsOnStart) {
            clearError();
            clearValidationErrors();
        }

        setLoading(true);

        try {
            const result = await submitFunction(formData.value);
            
            if (onSuccess) {
                onSuccess(result);
            }
            
            return result;
        } catch (err) {
            if (err.response?.status === 422) {
                // Validation errors
                setValidationErrors(err.response.data.errors || {});
            } else {
                const errorMessage = err.response?.data?.message || err.message || 'An error occurred';
                setError(errorMessage);
            }
            
            if (onError) {
                onError(err);
            }
            
            throw err;
        } finally {
            setLoading(false);
        }
    };

    return {
        // State
        loading,
        error,
        formData,
        validationErrors,
        
        // Computed
        hasValidationErrors,
        
        // Methods
        clearError,
        clearValidationErrors,
        setValidationErrors,
        resetForm,
        submitForm
    };
}