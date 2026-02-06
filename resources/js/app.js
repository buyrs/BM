import './bootstrap';

import Alpine from 'alpinejs';

// UX Enhancement Modules
import { registerGestureDirectives } from './gestures';
import haptics from './haptics';
import initKeyboardShortcuts from './keyboard-shortcuts';
import initPageTransitions from './page-transitions';

// Make haptics globally available
window.haptics = haptics;

window.Alpine = Alpine;

// Register gesture directives before Alpine starts
registerGestureDirectives();

Alpine.start();

// Initialize keyboard shortcuts for desktop power users
initKeyboardShortcuts();

// Initialize page transitions for smooth navigation
initPageTransitions();

// Add performance optimization for asset loading
// Preload critical resources
if ('requestIdleCallback' in window) {
    requestIdleCallback(() => {
        // Preload critical CSS/JS for faster subsequent page loads
        preloadCriticalAssets();
    });
} else {
    // Fallback for browsers that don't support requestIdleCallback
    setTimeout(() => {
        preloadCriticalAssets();
    }, 1);
}

function preloadCriticalAssets() {
    // Preload critical assets for faster loading
    const criticalAssets = [
        '/css/app.css',
        '/js/app.js',
        // Add other critical assets as needed
    ];

    criticalAssets.forEach(asset => {
        const link = document.createElement('link');
        link.rel = 'preload';
        link.as = 'style';
        link.href = asset;
        document.head.appendChild(link);
    });
}
