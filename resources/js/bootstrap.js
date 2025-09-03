import axios from 'axios';
import apiClient from './utils/apiClient';
import errorMessageService from './Services/ErrorMessageService.js';
import toastService from './Services/ToastService.js';

// Keep the original axios for backward compatibility
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Make enhanced API client available globally
window.apiClient = apiClient;

// Make services available globally
window.toast = toastService;
window.errorMessages = errorMessageService;

// Global loading state management
let loadingCount = 0;
const updateGlobalLoading = (loading) => {
    if (loading) {
        loadingCount++;
    } else {
        loadingCount = Math.max(0, loadingCount - 1);
    }
    
    // Dispatch global loading event
    window.dispatchEvent(new CustomEvent('global-loading-change', {
        detail: { loading: loadingCount > 0 }
    }));
};

// Listen for API loading events
window.addEventListener('api-loading', (event) => {
    updateGlobalLoading(event.detail.loading);
});
