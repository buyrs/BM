import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { VitePWA } from 'vite-plugin-pwa';
import { splitVendorChunkPlugin } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/js/app.js',
                'resources/css/blade.css',  // New CSS for Blade templates
                'resources/js/blade.js'     // New JS for Blade + Alpine.js
            ],
            refresh: true,
        }),
        VitePWA({
            registerType: 'autoUpdate',
            includeAssets: ['favicon.ico', 'robots.txt', 'icons/*.png', 'assets/*.css', 'assets/*.js'],
            manifest: {
                name: 'BM App',
                short_name: 'BM',
                description: 'Business Management Application',
                theme_color: '#4f46e5',
                icons: [
                    {
                        src: '/images/icons/icon-192x192.png',
                        sizes: '192x192',
                        type: 'image/png',
                    },
                    {
                        src: '/images/icons/icon-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                    },
                ],
            },
            workbox: {
                navigateFallback: '/',
                globPatterns: ['**/*.{js,css,html,ico,png,svg,webmanifest}'],
                runtimeCaching: [
                    {
                        urlPattern: ({ url }) => url.origin === self.location.origin && url.pathname.startsWith('/assets/'),
                        handler: 'StaleWhileRevalidate',
                        options: {
                            cacheName: 'assets-cache',
                            expiration: {
                                maxEntries: 50,
                                maxAgeSeconds: 60 * 60 * 24 * 7,
                            },
                        },
                    },
                    {
                        urlPattern: ({ url }) => url.origin === self.location.origin && url.pathname.startsWith('/images/icons/'),
                        handler: 'StaleWhileRevalidate',
                        options: {
                            cacheName: 'icons-cache',
                            expiration: {
                                maxEntries: 20,
                                maxAgeSeconds: 60 * 60 * 24 * 30,
                            },
                        },
                    },
                ],
            },
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    // Vendor chunks
                    'alpine-vendor': ['alpinejs', '@alpinejs/focus', '@alpinejs/collapse'],
                    'flowbite-vendor': ['flowbite']
                }
            }
        },
        chunkSizeWarningLimit: 1000,
        sourcemap: false,
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true
            }
        }
    },
    optimizeDeps: {
        include: [
            'alpinejs',
            '@alpinejs/focus', 
            '@alpinejs/collapse',
            'flowbite'
        ]
    },
    server: {
        hmr: {
            overlay: false
        }
    }
});
