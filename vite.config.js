import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/js/app.js',
                'resources/css/admin.css', // Additional admin-specific styles
                'resources/css/ops.css',   // Additional ops-specific styles
                'resources/css/checker.css' // Additional checker-specific styles
            ],
            refresh: true,
        }),
    ],
    build: {
        // Enable compression for production builds
        rollupOptions: {
            output: {
                // Create separate chunks for better caching
                manualChunks: {
                    vendor: ['alpinejs', 'signature_pad'],
                }
            }
        },
        // Compress assets
        minify: 'terser', // Use terser for more aggressive minification
        cssMinify: true,
        // Enable compression
        brotliSize: true,
    },
    // Optimizations for development
    server: {
        // Enable hot module replacement optimizations
        hmr: true,
    },
});
