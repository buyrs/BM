<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="{{ app(\App\Services\MobileResponsivenessService::class)->getViewportConfig() }}">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- PWA Meta Tags -->
        <meta name="theme-color" content="#4f46e5">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
        <meta name="mobile-web-app-capable" content="yes">
        
        <!-- PWA Manifest -->
        <link rel="manifest" href="{{ route('pwa.manifest') }}">
        
        <!-- Apple Touch Icons -->
        <link rel="apple-touch-icon" sizes="152x152" href="/images/icons/icon-152x152.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/images/icons/icon-180x180.png">
        
        <!-- Favicon -->
        <link rel="icon" type="image/png" sizes="32x32" href="/images/icons/icon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/images/icons/icon-16x16.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- Mobile-specific styles -->
        <style>
            /* Prevent text size adjustment on orientation change */
            html {
                -webkit-text-size-adjust: 100%;
                -ms-text-size-adjust: 100%;
            }
            
            /* Smooth scrolling for mobile */
            html {
                scroll-behavior: smooth;
                -webkit-overflow-scrolling: touch;
            }
            
            /* Safe area support */
            body {
                padding-top: env(safe-area-inset-top);
                padding-bottom: env(safe-area-inset-bottom);
                padding-left: env(safe-area-inset-left);
                padding-right: env(safe-area-inset-right);
            }
            
            /* Touch action optimization */
            .touch-action-manipulation {
                touch-action: manipulation;
            }
            
            /* Prevent zoom on input focus (iOS) */
            @media screen and (-webkit-min-device-pixel-ratio: 0) {
                select, textarea, input[type="text"], input[type="password"], 
                input[type="datetime"], input[type="datetime-local"], 
                input[type="date"], input[type="month"], input[type="time"], 
                input[type="week"], input[type="number"], input[type="email"], 
                input[type="url"], input[type="search"], input[type="tel"], 
                input[type="color"] {
                    font-size: 16px !important;
                }
            }
            
            /* Mobile-first responsive utilities */
            .mobile-hidden { display: none; }
            @media (min-width: 640px) {
                .mobile-hidden { display: block; }
                .mobile-only { display: none; }
            }
        </style>
    </head>
    <body class="font-sans antialiased bg-secondary-50">
        @php
            $userRole = 'guest';
            if (Auth::guard('admin')->check()) {
                $userRole = 'admin';
            } elseif (Auth::guard('ops')->check()) {
                $userRole = 'ops';
            } elseif (Auth::guard('checker')->check()) {
                $userRole = 'checker';
            }
        @endphp
        
        @if($userRole !== 'guest')
            <!-- Modern Layout with Sidebar -->
            <div class="flex h-screen bg-secondary-50" x-data="{ sidebarOpen: false }">
                <!-- Sidebar -->
                <div class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-medium transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0" 
                     :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }">
                    <div class="flex flex-col h-full">
                        <!-- Logo -->
                        <div class="flex items-center justify-between h-16 px-6 border-b border-secondary-200">
                            <a href="{{ Auth::guard('admin')->check() ? route('admin.dashboard') : (Auth::guard('ops')->check() ? route('ops.dashboard') : route('checker.dashboard')) }}" 
                               class="flex items-center space-x-3">
                                <x-application-logo class="h-8 w-8 text-primary-600" />
                                <span class="text-xl font-bold text-secondary-900">{{ config('app.name') }}</span>
                            </a>
                            <button @click="sidebarOpen = false" class="lg:hidden p-2 rounded-md text-secondary-400 hover:text-secondary-500 hover:bg-secondary-100">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Navigation -->
                        <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto scrollbar-thin">
                            @include('layouts.navigation')
                        </nav>

                        <!-- User Profile -->
                        <div class="border-t border-secondary-200 p-4">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="h-8 w-8 bg-primary-500 rounded-full flex items-center justify-center">
                                        <span class="text-sm font-medium text-white">
                                            {{ strtoupper(substr(Auth::guard('admin')->check() ? Auth::guard('admin')->user()->name : (Auth::guard('ops')->check() ? Auth::guard('ops')->user()->name : Auth::guard('checker')->user()->name), 0, 1)) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-secondary-900 truncate">
                                        {{ Auth::guard('admin')->check() ? Auth::guard('admin')->user()->name : (Auth::guard('ops')->check() ? Auth::guard('ops')->user()->name : Auth::guard('checker')->user()->name) }}
                                    </p>
                                    <p class="text-xs text-secondary-500 truncate">
                                        {{ ucfirst($userRole) }}
                                    </p>
                                </div>
                                <x-dropdown align="right" width="48">
                                    <x-slot name="trigger">
                                        <button class="flex text-secondary-400 hover:text-secondary-500 focus:outline-none focus:text-secondary-500 transition duration-150 ease-in-out">
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                            </svg>
                                        </button>
                                    </x-slot>
                                    <x-slot name="content">
                                        <form method="POST" action="{{ route(Auth::guard('admin')->check() ? 'admin.logout' : (Auth::guard('ops')->check() ? 'ops.logout' : 'checker.logout')) }}">
                                            @csrf
                                            <x-dropdown-link href="#" onclick="event.preventDefault(); this.closest('form').submit();" class="flex items-center px-4 py-2 text-sm text-secondary-700 hover:bg-secondary-100">
                                                <svg class="mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                                </svg>
                                                {{ __('Log Out') }}
                                            </x-dropdown-link>
                                        </form>
                                    </x-slot>
                                </x-dropdown>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="flex-1 flex flex-col overflow-hidden">
                    <!-- Top Header -->
                    <header class="bg-white border-b border-secondary-200 lg:border-none">
                        <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                            <div class="flex items-center">
                                <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-md text-secondary-400 hover:text-secondary-500 hover:bg-secondary-100">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                </button>
                                <!-- Page Title -->
                                @isset($header)
                                    <div class="ml-4 lg:ml-0">
                                        {{ $header }}
                                    </div>
                                @endisset
                            </div>
                            
                            <div class="flex items-center space-x-4">
                                <!-- Notification Bell -->
                                <button class="p-2 text-secondary-400 hover:text-secondary-500 hover:bg-secondary-100 rounded-lg">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-3.5-3.5L21 9a9 9 0 11-9 9l4.5-4.5z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </header>

                    <!-- Page Content -->
                    <main class="flex-1 overflow-x-hidden overflow-y-auto">
                        <div class="p-4 sm:p-6 lg:p-8">
                            {{ $slot ?? '' }}
                            @yield('content')
                        </div>
                    </main>
                </div>

                <!-- Mobile sidebar backdrop -->
                <div x-show="sidebarOpen" 
                     x-transition:enter="transition-opacity ease-linear duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition-opacity ease-linear duration-300"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     @click="sidebarOpen = false"
                     class="fixed inset-0 z-40 bg-secondary-900 bg-opacity-75 lg:hidden"
                     style="display: none;"></div>
            </div>
        @else
            <!-- Guest Layout -->
            <div class="min-h-screen bg-secondary-50">
                <!-- Page Content for Guests -->
                <main>
                    {{ $slot ?? '' }}
                    @yield('content')
                </main>
            </div>
        @endif

            <!-- Mobile Navigation -->
            @php
                $userRole = 'guest';
                if (Auth::guard('admin')->check()) {
                    $userRole = 'admin';
                } elseif (Auth::guard('ops')->check()) {
                    $userRole = 'ops';
                } elseif (Auth::guard('checker')->check()) {
                    $userRole = 'checker';
                }
            @endphp
            
            @if($userRole !== 'guest')
                <x-mobile-navigation :userRole="$userRole" />
            @endif
        </div>

        <!-- PWA Installation Prompt -->
        <div id="pwa-install-prompt" class="fixed bottom-20 sm:bottom-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-80 bg-white rounded-lg shadow-lg border border-gray-200 p-4 hidden z-40">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-medium text-gray-900">Install App</h3>
                    <p class="text-xs text-gray-500 mt-1">Add to your home screen for quick access</p>
                </div>
                <div class="flex space-x-2">
                    <button id="pwa-install-dismiss" class="text-xs text-gray-400 hover:text-gray-600">
                        Dismiss
                    </button>
                    <button id="pwa-install-button" class="text-xs bg-indigo-600 text-white px-3 py-1 rounded hover:bg-indigo-700">
                        Install
                    </button>
                </div>
            </div>
        </div>

        <!-- Service Worker Registration -->
        <script>
            // Register service worker
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register('/sw.js')
                        .then(function(registration) {
                            console.log('ServiceWorker registration successful');
                            
                            // Listen for messages from service worker
                            if (navigator.serviceWorker.controller) {
                                navigator.serviceWorker.controller.postMessage({type: 'SKIP_WAITING'});
                            }
                        })
                        .catch(function(err) {
                            console.log('ServiceWorker registration failed: ', err);
                        });
                });
            }

            // PWA Installation
            let deferredPrompt;
            const installPrompt = document.getElementById('pwa-install-prompt');
            const installButton = document.getElementById('pwa-install-button');
            const dismissButton = document.getElementById('pwa-install-dismiss');

            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;
                
                // Show install prompt if not dismissed
                if (!localStorage.getItem('pwa-install-dismissed')) {
                    installPrompt.classList.remove('hidden');
                }
            });

            installButton.addEventListener('click', async () => {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    
                    if (outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                    }
                    
                    deferredPrompt = null;
                    installPrompt.classList.add('hidden');
                }
            });

            dismissButton.addEventListener('click', () => {
                installPrompt.classList.add('hidden');
                localStorage.setItem('pwa-install-dismissed', 'true');
            });

            // Handle app installation
            window.addEventListener('appinstalled', (evt) => {
                console.log('App was installed');
                installPrompt.classList.add('hidden');
            });

            // Offline data storage functions
            const OfflineData = {
                // Store data to be synced when online
                storeForSync: function(key, data) {
                    const id = Date.now().toString();
                    const item = {
                        id: id,
                        timestamp: new Date().toISOString(),
                        data: data
                    };
                    
                    const existingData = JSON.parse(localStorage.getItem(key) || '[]');
                    existingData.push(item);
                    localStorage.setItem(key, JSON.stringify(existingData));
                    
                    return id;
                },
                
                // Get stored offline data
                getStoredForSync: function(key) {
                    return JSON.parse(localStorage.getItem(key) || '[]');
                },
                
                // Remove synced data
                removeStoredSync: function(key, id) {
                    const existingData = JSON.parse(localStorage.getItem(key) || '[]');
                    const updatedData = existingData.filter(item => item.id !== id);
                    localStorage.setItem(key, JSON.stringify(updatedData));
                },
                
                // Attempt to sync data when back online
                syncWhenOnline: function() {
                    if (navigator.onLine) {
                        // Try to sync any stored offline data
                        this.processStoredData();
                    }
                },
                
                processStoredData: function() {
                    const checklistSubmissions = this.getStoredForSync('checklist-submissions');
                    
                    if (checklistSubmissions.length > 0) {
                        // Trigger service worker to sync data
                        if (navigator.serviceWorker && navigator.serviceWorker.controller) {
                            navigator.serviceWorker.controller.postMessage({ type: 'SYNC_DATA' });
                        }
                    }
                }
            };

            // Mobile-specific enhancements
            document.addEventListener('DOMContentLoaded', function() {
                // Prevent double-tap zoom on buttons
                document.querySelectorAll('button, .btn, [role="button"]').forEach(element => {
                    element.style.touchAction = 'manipulation';
                });

                // Add touch feedback
                document.addEventListener('touchstart', function(e) {
                    if (e.target.matches('button, .btn, [role="button"], a')) {
                        e.target.style.opacity = '0.7';
                    }
                });

                document.addEventListener('touchend', function(e) {
                    if (e.target.matches('button, .btn, [role="button"], a')) {
                        setTimeout(() => {
                            e.target.style.opacity = '';
                        }, 150);
                    }
                });

                // Handle orientation changes
                window.addEventListener('orientationchange', function() {
                    // Force repaint to fix layout issues
                    setTimeout(() => {
                        window.scrollTo(0, window.scrollY);
                    }, 100);
                });

                // Improve scroll performance on mobile
                if ('scrollBehavior' in document.documentElement.style) {
                    document.documentElement.style.scrollBehavior = 'smooth';
                }
                
                // Process any stored offline data when page loads
                if (navigator.onLine) {
                    OfflineData.processStoredData();
                }
            });

            // Network status handling
            function updateOnlineStatus() {
                const statusIndicator = document.getElementById('network-status');
                if (navigator.onLine) {
                    if (statusIndicator) statusIndicator.classList.add('hidden');
                    
                    // Process offline data when back online
                    OfflineData.syncWhenOnline();
                } else {
                    if (!statusIndicator) {
                        const indicator = document.createElement('div');
                        indicator.id = 'network-status';
                        indicator.className = 'fixed top-0 left-0 right-0 bg-red-600 text-white text-center py-2 text-sm z-50';
                        indicator.textContent = 'You are offline. Data will sync when connection is restored.';
                        document.body.appendChild(indicator);
                    } else {
                        statusIndicator.classList.remove('hidden');
                    }
                }
            }

            window.addEventListener('online', updateOnlineStatus);
            window.addEventListener('offline', updateOnlineStatus);
            updateOnlineStatus();
        </script>
    </body>
</html>
