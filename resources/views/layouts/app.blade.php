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
    <body class="font-sans antialiased bg-secondary-50 dark:bg-secondary-900 text-secondary-900 dark:text-secondary-100 transition-colors duration-200">
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
            <div 
                class="flex h-screen bg-secondary-50 dark:bg-secondary-900 transition-colors duration-200" 
                x-data="{ 
                    sidebarOpen: false,
                    sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
                    toggleCollapse() {
                        this.sidebarCollapsed = !this.sidebarCollapsed;
                        localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed);
                    }
                }"
            >
                <!-- Sidebar -->
                <div 
                    class="fixed inset-y-0 left-0 z-50 bg-white dark:bg-secondary-800 shadow-medium dark:shadow-none dark:border-r dark:border-secondary-700 transform transition-all duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0" 
                    :class="{ 
                        '-translate-x-full': !sidebarOpen, 
                        'translate-x-0': sidebarOpen,
                        'w-64': !sidebarCollapsed,
                        'lg:w-20': sidebarCollapsed,
                        'w-64': !sidebarCollapsed || !$store?.lg
                    }"
                    :style="sidebarCollapsed ? 'width: 5rem' : 'width: 16rem'"
                    @mouseenter="if(sidebarCollapsed) $el.style.width = '16rem'"
                    @mouseleave="if(sidebarCollapsed) $el.style.width = '5rem'"
                >
                    <div class="flex flex-col h-full">
                        <!-- Logo -->
                        <div class="flex items-center justify-between h-16 px-4 border-b border-secondary-200 dark:border-secondary-700">
                            <a href="{{ Auth::guard('admin')->check() ? route('admin.dashboard') : (Auth::guard('ops')->check() ? route('ops.dashboard') : route('checker.dashboard')) }}" 
                               class="flex items-center space-x-3 overflow-hidden">
                                <x-application-logo class="h-8 w-8 flex-shrink-0 text-primary-600" />
                                <span 
                                    class="text-xl font-bold text-secondary-900 dark:text-white whitespace-nowrap transition-opacity duration-200"
                                    :class="{ 'lg:opacity-0 lg:w-0': sidebarCollapsed }"
                                >{{ config('app.name') }}</span>
                            </a>
                            <button @click="sidebarOpen = false" class="lg:hidden p-2 rounded-md text-secondary-400 hover:text-secondary-500 dark:hover:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Navigation -->
                        <nav class="flex-1 px-3 py-6 space-y-1 overflow-y-auto scrollbar-thin" :class="{ 'lg:px-2': sidebarCollapsed }">
                            @include('layouts.navigation')
                        </nav>

                        <!-- Collapse Toggle (Desktop only) -->
                        <div class="hidden lg:block border-t border-secondary-200 dark:border-secondary-700 p-3">
                            <button 
                                @click="toggleCollapse()"
                                class="w-full flex items-center justify-center p-2 rounded-lg text-secondary-500 dark:text-secondary-400 hover:bg-secondary-100 dark:hover:bg-secondary-700 hover:text-secondary-700 dark:hover:text-secondary-200 transition-colors"
                                :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                            >
                                <svg 
                                    class="w-5 h-5 transition-transform duration-200" 
                                    :class="{ 'rotate-180': sidebarCollapsed }"
                                    fill="none" 
                                    viewBox="0 0 24 24" 
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                                </svg>
                            </button>
                        </div>

                        <!-- User Profile -->
                        <div class="border-t border-secondary-200 dark:border-secondary-700 p-4">
                            <div class="flex items-center" :class="{ 'lg:justify-center': sidebarCollapsed }">
                                <div class="flex-shrink-0">
                                    <div class="h-8 w-8 bg-primary-500 rounded-full flex items-center justify-center">
                                        <span class="text-sm font-medium text-white">
                                            {{ strtoupper(substr(Auth::guard('admin')->check() ? Auth::guard('admin')->user()->name : (Auth::guard('ops')->check() ? Auth::guard('ops')->user()->name : Auth::guard('checker')->user()->name), 0, 1)) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0 ml-3 transition-opacity duration-200" :class="{ 'lg:hidden': sidebarCollapsed }">
                                    <p class="text-sm font-medium text-secondary-900 dark:text-white truncate">
                                        {{ Auth::guard('admin')->check() ? Auth::guard('admin')->user()->name : (Auth::guard('ops')->check() ? Auth::guard('ops')->user()->name : Auth::guard('checker')->user()->name) }}
                                    </p>
                                    <p class="text-xs text-secondary-500 dark:text-secondary-400 truncate">
                                        {{ ucfirst($userRole) }}
                                    </p>
                                </div>
                                <x-dropdown align="right" width="48">
                                    <x-slot name="trigger">
                                        <button class="flex text-secondary-400 hover:text-secondary-500 dark:hover:text-secondary-300 focus:outline-none focus:text-secondary-500 transition duration-150 ease-in-out" :class="{ 'lg:hidden': sidebarCollapsed }">
                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                            </svg>
                                        </button>
                                    </x-slot>
                                    <x-slot name="content">
                                        <form method="POST" action="{{ route(Auth::guard('admin')->check() ? 'admin.logout' : (Auth::guard('ops')->check() ? 'ops.logout' : 'checker.logout')) }}">
                                            @csrf
                                            <x-dropdown-link href="#" onclick="event.preventDefault(); this.closest('form').submit();" class="flex items-center px-4 py-2 text-sm text-secondary-700 dark:text-secondary-200 hover:bg-secondary-100 dark:hover:bg-secondary-700">
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
                    <header class="bg-white dark:bg-secondary-800 border-b border-secondary-200 dark:border-secondary-700 lg:border-none lg:shadow-sm dark:lg:shadow-none lg:dark:border-b">
                        <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                            <div class="flex items-center">
                                <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-md text-secondary-400 hover:text-secondary-500 dark:hover:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700">
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
                            
                            <div class="flex items-center space-x-2 sm:space-x-4">
                                <!-- Theme Toggle -->
                                <x-theme-toggle size="sm" />
                                
                                <!-- Notification Bell -->
                                <button class="p-2 text-secondary-400 hover:text-secondary-500 dark:hover:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-700 rounded-lg transition-colors">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </header>

                    <!-- Page Content -->
                    <main class="flex-1 overflow-x-hidden overflow-y-auto bg-secondary-50 dark:bg-secondary-900">
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
                     class="fixed inset-0 z-40 bg-secondary-900/75 dark:bg-black/80 lg:hidden"
                     style="display: none;"></div>
            </div>
        @else
            <!-- Guest Layout -->
            <div class="min-h-screen bg-secondary-50 dark:bg-secondary-900">
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
        <div id="pwa-install-prompt" class="fixed bottom-20 sm:bottom-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-80 bg-white dark:bg-secondary-800 rounded-lg shadow-lg dark:shadow-none border border-secondary-200 dark:border-secondary-700 p-4 hidden z-40">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-medium text-secondary-900 dark:text-white">Install App</h3>
                    <p class="text-xs text-secondary-500 dark:text-secondary-400 mt-1">Add to your home screen for quick access</p>
                </div>
                <div class="flex space-x-2">
                    <button id="pwa-install-dismiss" class="text-xs text-secondary-400 hover:text-secondary-600 dark:hover:text-secondary-200">
                        Dismiss
                    </button>
                    <button id="pwa-install-button" class="text-xs bg-primary-600 text-white px-3 py-1 rounded hover:bg-primary-700">
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
        
        {{-- Global UX Components --}}
        <x-toast-notification position="bottom-right" />
        
        {{-- Desktop-only components --}}
        <div class="hidden lg:block">
            <x-command-palette />
            <x-keyboard-shortcuts-help />
        </div>
    </body>
</html>
