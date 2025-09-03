/**
 * Enhanced API Client with Error Handling and Retry Logic
 */

import axios from 'axios';
import errorHandler from './errorHandler';

class ApiClient {
    constructor() {
        this.client = axios.create({
            timeout: 10000, // 10 second timeout
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        this.setupInterceptors();
        this.retryConfig = {
            retries: 3,
            retryDelay: 1000,
            retryCondition: (error) => {
                return error.code === 'NETWORK_ERROR' || 
                       (error.response && error.response.status >= 500);
            }
        };
    }

    setupInterceptors() {
        // Request interceptor
        this.client.interceptors.request.use(
            (config) => {
                // Add CSRF token if available
                const token = document.querySelector('meta[name="csrf-token"]');
                if (token) {
                    config.headers['X-CSRF-TOKEN'] = token.getAttribute('content');
                }

                // Add loading state
                this.setLoadingState(true);
                
                return config;
            },
            (error) => {
                this.setLoadingState(false);
                return Promise.reject(error);
            }
        );

        // Response interceptor
        this.client.interceptors.response.use(
            (response) => {
                this.setLoadingState(false);
                return response;
            },
            async (error) => {
                this.setLoadingState(false);
                
                const originalRequest = error.config;
                
                // Handle retry logic
                if (this.shouldRetry(error) && !originalRequest._retry) {
                    originalRequest._retry = true;
                    originalRequest._retryCount = (originalRequest._retryCount || 0) + 1;
                    
                    if (originalRequest._retryCount <= this.retryConfig.retries) {
                        await this.delay(this.retryConfig.retryDelay * originalRequest._retryCount);
                        return this.client(originalRequest);
                    }
                }

                // Handle specific error cases
                if (error.response?.status === 401) {
                    this.handleAuthError();
                } else if (error.response?.status === 419) {
                    // CSRF token mismatch - refresh page
                    window.location.reload();
                } else {
                    errorHandler.handleApiError(error, originalRequest.url);
                }

                return Promise.reject(error);
            }
        );
    }

    shouldRetry(error) {
        return this.retryConfig.retryCondition(error);
    }

    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    handleAuthError() {
        errorHandler.handleAuthError();
    }

    setLoadingState(loading) {
        // Dispatch custom event for global loading state
        window.dispatchEvent(new CustomEvent('api-loading', { 
            detail: { loading } 
        }));
    }

    // Wrapper methods with enhanced error handling
    async get(url, config = {}) {
        try {
            const response = await this.client.get(url, config);
            return this.handleSuccess(response);
        } catch (error) {
            throw this.handleError(error, 'GET', url);
        }
    }

    async post(url, data = {}, config = {}) {
        try {
            const response = await this.client.post(url, data, config);
            return this.handleSuccess(response);
        } catch (error) {
            throw this.handleError(error, 'POST', url);
        }
    }

    async put(url, data = {}, config = {}) {
        try {
            const response = await this.client.put(url, data, config);
            return this.handleSuccess(response);
        } catch (error) {
            throw this.handleError(error, 'PUT', url);
        }
    }

    async patch(url, data = {}, config = {}) {
        try {
            const response = await this.client.patch(url, data, config);
            return this.handleSuccess(response);
        } catch (error) {
            throw this.handleError(error, 'PATCH', url);
        }
    }

    async delete(url, config = {}) {
        try {
            const response = await this.client.delete(url, config);
            return this.handleSuccess(response);
        } catch (error) {
            throw this.handleError(error, 'DELETE', url);
        }
    }

    handleSuccess(response) {
        // Log successful requests in development
        if (process.env.NODE_ENV === 'development') {
            console.log('API Success:', response.config.method.toUpperCase(), response.config.url, response.data);
        }
        return response;
    }

    handleError(error, method, url) {
        // Enhanced error information
        const enhancedError = {
            ...error,
            method,
            url,
            timestamp: new Date().toISOString()
        };

        return enhancedError;
    }

    // Utility method for safe API calls with loading states
    async safeCall(apiCall, loadingRef = null, errorRef = null) {
        try {
            if (loadingRef) loadingRef.value = true;
            if (errorRef) errorRef.value = null;
            
            const result = await apiCall();
            return result;
        } catch (error) {
            if (errorRef) {
                errorRef.value = error.response?.data?.message || error.message || 'An error occurred';
            }
            throw error;
        } finally {
            if (loadingRef) loadingRef.value = false;
        }
    }

    // Method to check API health
    async healthCheck() {
        try {
            const response = await this.get('/api/health');
            return response.data;
        } catch (error) {
            return { status: 'error', message: 'API unavailable' };
        }
    }
}

// Create and export singleton instance
const apiClient = new ApiClient();

export default apiClient;