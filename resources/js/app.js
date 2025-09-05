import './bootstrap';
import '../css/app.css';

// Import Alpine.js and plugins
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import collapse from '@alpinejs/collapse';

// Initialize Alpine.js
Alpine.plugin(focus);
Alpine.plugin(collapse);
window.Alpine = Alpine;

// Start Alpine.js when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    Alpine.start();
});

// Import Flowbite for interactive components
import 'flowbite';

// Global error handling
window.handleError = (error, context = 'global') => {
    console.error(`Error in ${context}:`, error);
    
    // Show user-friendly error message
    const toast = document.createElement('div');
    toast.className = 'fixed top-4 right-4 bg-red-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
    toast.textContent = 'An error occurred. Please try again.';
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 5000);
};

// Global loading state
window.showLoading = () => {
    const loader = document.createElement('div');
    loader.id = 'global-loader';
    loader.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    loader.innerHTML = `
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white"></div>
    `;
    document.body.appendChild(loader);
};

window.hideLoading = () => {
    const loader = document.getElementById('global-loader');
    if (loader) {
        loader.remove();
    }
};

// Add global event listeners for AJAX requests
document.addEventListener('ajaxStart', window.showLoading);
document.addEventListener('ajaxStop', window.hideLoading);
document.addEventListener('ajaxError', (event) => {
    window.handleError(event.detail.error, 'ajax');
});