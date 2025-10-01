// Checker-specific JavaScript
import Alpine from 'alpinejs';

// Initialize Alpine
window.Alpine = Alpine;
Alpine.start();

// Checker-specific functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log('Checker scripts loaded');
    
    // Add any checker-specific scripts here
    // For example, checklist interactions, photo uploads, etc.
    
    // Example: Enhanced checklist item interactions
    const checklistItems = document.querySelectorAll('.checklist-item');
    checklistItems.forEach(item => {
        item.addEventListener('click', function() {
            // Add visual feedback for checklist items
            this.classList.add('ring-2', 'ring-blue-500');
        });
    });
});