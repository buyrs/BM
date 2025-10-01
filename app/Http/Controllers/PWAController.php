<?php

namespace App\Http\Controllers;

use App\Services\MobileResponsivenessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class PWAController extends Controller
{
    protected MobileResponsivenessService $mobileService;

    public function __construct(MobileResponsivenessService $mobileService)
    {
        $this->mobileService = $mobileService;
    }

    /**
     * Generate PWA manifest
     */
    public function manifest(): JsonResponse
    {
        $manifest = $this->mobileService->generatePWAManifest();
        
        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json');
    }

    /**
     * Service worker
     */
    public function serviceWorker(): Response
    {
        $serviceWorkerContent = $this->generateServiceWorker();
        
        return response($serviceWorkerContent)
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Offline page
     */
    public function offline()
    {
        return view('pwa.offline');
    }

    /**
     * Generate service worker content
     */
    protected function generateServiceWorker(): string
    {
        return <<<JS
const CACHE_NAME = 'property-management-v1';
const urlsToCache = [
    '/',
    '/css/app.css',
    '/js/app.js',
    '/offline',
    '/images/icons/icon-192x192.png',
    '/images/icons/icon-512x512.png'
];

// Install event
self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                return cache.addAll(urlsToCache);
            })
    );
});

// Fetch event
self.addEventListener('fetch', function(event) {
    event.respondWith(
        caches.match(event.request)
            .then(function(response) {
                // Return cached version or fetch from network
                if (response) {
                    return response;
                }
                
                return fetch(event.request).catch(function() {
                    // If both cache and network fail, show offline page
                    if (event.request.destination === 'document') {
                        return caches.match('/offline');
                    }
                });
            })
    );
});

// Activate event
self.addEventListener('activate', function(event) {
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.map(function(cacheName) {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Background sync for offline actions
self.addEventListener('sync', function(event) {
    if (event.tag === 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }
});

function doBackgroundSync() {
    // Handle offline actions when connection is restored
    return new Promise(function(resolve) {
        // Implementation for syncing offline data
        resolve();
    });
}

// Push notifications
self.addEventListener('push', function(event) {
    const options = {
        body: event.data ? event.data.text() : 'New notification',
        icon: '/images/icons/icon-192x192.png',
        badge: '/images/icons/icon-72x72.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'explore',
                title: 'View',
                icon: '/images/icons/checkmark.png'
            },
            {
                action: 'close',
                title: 'Close',
                icon: '/images/icons/xmark.png'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification('Property Management', options)
    );
});

// Notification click
self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('/')
        );
    }
});
JS;
    }
}