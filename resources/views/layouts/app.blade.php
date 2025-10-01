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
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

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
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 pb-16 sm:pb-0">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-4 sm:py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="pb-safe">
                {{ $slot ?? '' }}
                @yield('content')
            </main>

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
            });

            // Network status handling
            function updateOnlineStatus() {
                const statusIndicator = document.getElementById('network-status');
                if (navigator.onLine) {
                    if (statusIndicator) statusIndicator.classList.add('hidden');
                } else {
                    if (!statusIndicator) {
                        const indicator = document.createElement('div');
                        indicator.id = 'network-status';
                        indicator.className = 'fixed top-0 left-0 right-0 bg-red-600 text-white text-center py-2 text-sm z-50';
                        indicator.textContent = 'You are offline. Some features may be limited.';
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
