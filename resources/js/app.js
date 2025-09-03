import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { Ziggy } from './ziggy';
import errorHandler from './utils/errorHandler';
import GlobalLoadingIndicator from './Components/GlobalLoadingIndicator.vue';
import GlobalToastContainer from './Components/GlobalToastContainer.vue';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ 
            render: () => h('div', [
                h(App, props),
                h(GlobalLoadingIndicator),
                h(GlobalToastContainer)
            ])
        })
            .use(plugin)
            .use(ZiggyVue, Ziggy);

        // Global error handling
        app.config.errorHandler = (error, instance, info) => {
            errorHandler.handleVueError(error, instance, info);
        };

        // Global properties for error handling
        app.config.globalProperties.$errorHandler = errorHandler;

        // Global loading state
        app.provide('globalLoading', { value: false });

        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// Register service worker for PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js')
            .then(registration => {
                console.log('ServiceWorker registration successful');
            })
            .catch(err => {
                console.log('ServiceWorker registration failed: ', err);
            });
    });
}
