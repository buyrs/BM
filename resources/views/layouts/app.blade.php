<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'BM Platform') }} @isset($title) - {{ $title }} @endisset</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Meta Tags -->
    <meta name="description" content="@yield('meta_description', 'Bail Mobilité Management Platform')">
    <meta name="keywords" content="@yield('meta_keywords', 'bail mobilité, property management, inspection')">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#4f46e5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
    
    <!-- Scripts -->
    @vite(['resources/css/blade.css', 'resources/js/blade.js'])
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50" x-data="{ sidebarOpen: false }" x-cloak>
    <div class="min-h-screen">
        <!-- Role-based Navigation -->
        @auth
            <x-role-based-navigation :user="auth()->user()" />
        @else
            @include('layouts.navigation')
        @endauth
        
        <!-- Sidebar -->
        @include('layouts.sidebar')
        
        <!-- Page Content -->
        <div class="lg:pl-64">
            <!-- Top Bar -->
            <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
                <!-- Mobile sidebar button -->
                <button type="button" class="-m-2.5 p-2.5 text-gray-700 lg:hidden" 
                        x-on:click="sidebarOpen = true">
                    <span class="sr-only">Open sidebar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
                
                <!-- Breadcrumbs -->
                @if(isset($breadcrumbs) && count($breadcrumbs) > 0)
                    <nav class="flex" aria-label="Breadcrumb">
                        <ol class="flex items-center space-x-4">
                            @foreach($breadcrumbs as $breadcrumb)
                                <li>
                                    @if(!$loop->last)
                                        <a href="{{ $breadcrumb['url'] }}" class="text-gray-500 hover:text-gray-700">
                                            {{ $breadcrumb['title'] }}
                                        </a>
                                        <svg class="ml-4 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                        </svg>
                                    @else
                                        <span class="text-gray-900 font-medium">{{ $breadcrumb['title'] }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                @endif
                
                <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                    <div class="flex flex-1"></div>
                    
                    <!-- Notifications -->
                    @auth
                        <div class="flex items-center gap-x-4 lg:gap-x-6">
                            <x-notifications-dropdown />
                            <x-user-dropdown />
                        </div>
                    @endauth
                </div>
            </div>
            
            <!-- Main Content -->
            <main class="py-6">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <!-- Flash Messages -->
                    @if(session('success'))
                        <x-alert type="success" :message="session('success')" class="mb-6" />
                    @endif
                    
                    @if(session('error'))
                        <x-alert type="error" :message="session('error')" class="mb-6" />
                    @endif
                    
                    @if(session('warning'))
                        <x-alert type="warning" :message="session('warning')" class="mb-6" />
                    @endif
                    
                    <!-- Page Content -->
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
    
    <!-- Global Loading Spinner -->
    <div x-show="$store.app.loading" x-cloak 
         class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto"></div>
                <h3 class="text-lg font-medium text-gray-900 mt-4">Loading...</h3>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    @stack('scripts')
    
    @if(config('app.env') === 'local')
        <!-- Development helpers -->
        <script>
            window.userId = {{ auth()->id() ?? 'null' }};
            window.userRole = '{{ auth()->user()?->getRoleNames()?->first() ?? 'guest' }}';
        </script>
    @endif
</body>
</html>
