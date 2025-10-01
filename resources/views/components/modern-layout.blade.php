<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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

    <div class="min-h-screen flex" x-data="{ sidebarOpen: false }">
        <!-- Sidebar -->
        <div class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-medium transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0" 
             :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }">
            
            <!-- Logo and Brand -->
            <div class="flex items-center justify-between h-16 px-6 border-b border-secondary-200">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-sm">{{ substr(config('app.name', 'APP'), 0, 2) }}</span>
                    </div>
                    <span class="text-xl font-bold text-secondary-900">{{ config('app.name') }}</span>
                </div>
                
                <!-- Close button for mobile -->
                <button @click="sidebarOpen = false" class="lg:hidden p-2 rounded-md text-secondary-400 hover:text-secondary-500 hover:bg-secondary-100">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                @if($userRole === 'admin')
                    <!-- Admin Navigation -->
                    <a href="{{ route('admin.dashboard') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-primary-50 text-primary-700 border-r-2 border-primary-500' : 'text-secondary-600 hover:bg-secondary-50 hover:text-secondary-900' }}">
                        <svg class="mr-3 h-5 w-5 {{ request()->routeIs('admin.dashboard') ? 'text-primary-500' : 'text-secondary-400 group-hover:text-secondary-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                        </svg>
                        Dashboard
                    </a>

                    <!-- Property Management -->
                    <div class="mt-6">
                        <div class="px-3 mb-2">
                            <h3 class="text-xs font-semibold text-secondary-500 uppercase tracking-wider">Property Management</h3>
                        </div>
                        <div class="space-y-1">
                            <a href="{{ route('admin.properties.index') }}" 
                               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.properties.*') ? 'bg-primary-50 text-primary-700' : 'text-secondary-600 hover:bg-secondary-50 hover:text-secondary-900' }}">
                                <svg class="mr-3 h-5 w-5 {{ request()->routeIs('admin.properties.*') ? 'text-primary-500' : 'text-secondary-400 group-hover:text-secondary-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                Properties
                            </a>
                            <a href="{{ route('admin.amenities.index') }}" 
                               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.amenities.*') ? 'bg-primary-50 text-primary-700' : 'text-secondary-600 hover:bg-secondary-50 hover:text-secondary-900' }}">
                                <svg class="mr-3 h-5 w-5 {{ request()->routeIs('admin.amenities.*') ? 'text-primary-500' : 'text-secondary-400 group-hover:text-secondary-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                </svg>
                                Amenities
                            </a>
                        </div>
                    </div>

                    <!-- Management -->
                    <div class="mt-6">
                        <div class="px-3 mb-2">
                            <h3 class="text-xs font-semibold text-secondary-500 uppercase tracking-wider">Management</h3>
                        </div>
                        <div class="space-y-1">
                            <a href="{{ route('admin.users.index') }}" 
                               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.users.*') ? 'bg-primary-50 text-primary-700' : 'text-secondary-600 hover:bg-secondary-50 hover:text-secondary-900' }}">
                                <svg class="mr-3 h-5 w-5 {{ request()->routeIs('admin.users.*') ? 'text-primary-500' : 'text-secondary-400 group-hover:text-secondary-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197" />
                                </svg>
                                Users
                            </a>
                            <a href="{{ route('admin.missions.index') }}" 
                               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.missions.*') ? 'bg-primary-50 text-primary-700' : 'text-secondary-600 hover:bg-secondary-50 hover:text-secondary-900' }}">
                                <svg class="mr-3 h-5 w-5 {{ request()->routeIs('admin.missions.*') ? 'text-primary-500' : 'text-secondary-400 group-hover:text-secondary-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                Missions
                            </a>
                        </div>
                    </div>

                @elseif($userRole === 'ops')
                    <!-- Ops Navigation -->
                    <a href="{{ route('ops.dashboard') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('ops.dashboard') ? 'bg-primary-50 text-primary-700 border-r-2 border-primary-500' : 'text-secondary-600 hover:bg-secondary-50 hover:text-secondary-900' }}">
                        <svg class="mr-3 h-5 w-5 {{ request()->routeIs('ops.dashboard') ? 'text-primary-500' : 'text-secondary-400 group-hover:text-secondary-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                        </svg>
                        Dashboard
                    </a>
                    
                    <div class="mt-6">
                        <div class="px-3 mb-2">
                            <h3 class="text-xs font-semibold text-secondary-500 uppercase tracking-wider">Operations</h3>
                        </div>
                        <div class="space-y-1">
                            <a href="{{ route('ops.users.index') }}" 
                               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('ops.users.*') ? 'bg-primary-50 text-primary-700' : 'text-secondary-600 hover:bg-secondary-50 hover:text-secondary-900' }}">
                                <svg class="mr-3 h-5 w-5 {{ request()->routeIs('ops.users.*') ? 'text-primary-500' : 'text-secondary-400 group-hover:text-secondary-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197" />
                                </svg>
                                Manage Checkers
                            </a>
                            <a href="{{ route('ops.missions.index') }}" 
                               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('ops.missions.*') ? 'bg-primary-50 text-primary-700' : 'text-secondary-600 hover:bg-secondary-50 hover:text-secondary-900' }}">
                                <svg class="mr-3 h-5 w-5 {{ request()->routeIs('ops.missions.*') ? 'text-primary-500' : 'text-secondary-400 group-hover:text-secondary-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                Missions
                            </a>
                            <a href="{{ route('ops.properties.index') }}" 
                               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('ops.properties.*') ? 'bg-primary-50 text-primary-700' : 'text-secondary-600 hover:bg-secondary-50 hover:text-secondary-900' }}">
                                <svg class="mr-3 h-5 w-5 {{ request()->routeIs('ops.properties.*') ? 'text-primary-500' : 'text-secondary-400 group-hover:text-secondary-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                Properties
                            </a>
                        </div>
                    </div>

                @elseif($userRole === 'checker')
                    <!-- Checker Navigation -->
                    <a href="{{ route('checker.dashboard') }}" 
                       class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('checker.dashboard') ? 'bg-primary-50 text-primary-700 border-r-2 border-primary-500' : 'text-secondary-600 hover:bg-secondary-50 hover:text-secondary-900' }}">
                        <svg class="mr-3 h-5 w-5 {{ request()->routeIs('checker.dashboard') ? 'text-primary-500' : 'text-secondary-400 group-hover:text-secondary-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                        </svg>
                        Dashboard
                    </a>
                    
                    <div class="mt-6">
                        <div class="px-3 mb-2">
                            <h3 class="text-xs font-semibold text-secondary-500 uppercase tracking-wider">Tasks</h3>
                        </div>
                        <div class="space-y-1">
                            <a href="#" 
                               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('checklists.*') ? 'bg-primary-50 text-primary-700' : 'text-secondary-600 hover:bg-secondary-50 hover:text-secondary-900' }}">
                                <svg class="mr-3 h-5 w-5 {{ request()->routeIs('checklists.*') ? 'text-primary-500' : 'text-secondary-400 group-hover:text-secondary-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Checklists
                            </a>
                        </div>
                    </div>
                @endif
            </nav>

            <!-- User Profile Section -->
            <div class="border-t border-secondary-200 p-4">
                <div x-data="{ userMenuOpen: false }" class="relative">
                    <button @click="userMenuOpen = !userMenuOpen" 
                            class="w-full flex items-center space-x-3 text-left rounded-lg p-2 hover:bg-secondary-50 transition-colors duration-200">
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
                            <p class="text-xs text-secondary-500 truncate">{{ ucfirst($userRole) }}</p>
                        </div>
                        <svg class="h-4 w-4 text-secondary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                        </svg>
                    </button>
                    
                    <!-- User Menu Dropdown -->
                    <div x-show="userMenuOpen" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-1"
                         @click.away="userMenuOpen = false"
                         class="absolute bottom-full mb-2 w-full bg-white rounded-xl shadow-medium border border-secondary-200 py-2">
                        
                        <form method="POST" action="{{ route(Auth::guard('admin')->check() ? 'admin.logout' : (Auth::guard('ops')->check() ? 'ops.logout' : 'checker.logout')) }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center px-4 py-2 text-sm text-secondary-700 hover:bg-secondary-50 transition-colors duration-200">
                                <svg class="mr-3 h-4 w-4 text-secondary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Sign out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white border-b border-secondary-200 lg:border-none">
                <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center">
                        <!-- Mobile menu button -->
                        <button @click="sidebarOpen = true" 
                                class="lg:hidden p-2 rounded-md text-secondary-400 hover:text-secondary-500 hover:bg-secondary-100">
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
                        <!-- Notifications -->
                        <button class="p-2 text-secondary-400 hover:text-secondary-500 hover:bg-secondary-100 rounded-lg transition-colors duration-200">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-3.5-3.5A4.98 4.98 0 0018 9a9 9 0 11-18 0c0 1.375.28 2.685.784 3.872L0 17h5m10 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-secondary-50">
                <div class="p-4 sm:p-6 lg:p-8">
                    {{ $slot }}
                </div>
            </main>
        </div>

        <!-- Mobile sidebar backdrop -->
        <div x-show="sidebarOpen" x-cloak
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 z-40 bg-secondary-900 bg-opacity-75 lg:hidden"></div>
    </div>

    <!-- Mobile Navigation -->
    @if($userRole !== 'guest')
        <x-mobile-navigation :userRole="$userRole" />
    @endif
</body>
</html>