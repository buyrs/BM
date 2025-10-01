@php
    $dashboardRoute = Auth::guard('admin')->check() ? 'admin.dashboard' : (Auth::guard('ops')->check() ? 'ops.dashboard' : (Auth::guard('checker')->check() ? 'checker.dashboard' : null));
    $currentRoute = Auth::guard('admin')->check() ? request()->routeIs('admin.dashboard') : (Auth::guard('ops')->check() ? request()->routeIs('ops.dashboard') : (Auth::guard('checker')->check() ? request()->routeIs('checker.dashboard') : false));
@endphp

<!-- Main Navigation Links -->
@if($dashboardRoute)
<a href="{{ route($dashboardRoute) }}" 
   class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ $currentRoute ? 'bg-primary-50 text-primary-700 border-r-2 border-primary-500' : 'text-secondary-600 hover:bg-secondary-50 hover:text-secondary-900' }}">
    <svg class="mr-3 h-5 w-5 {{ $currentRoute ? 'text-primary-500' : 'text-secondary-400 group-hover:text-secondary-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4" />
    </svg>
    {{ __('Dashboard') }}
</a>
@endif

<!-- Admin-specific Navigation -->
@if(Auth::guard('admin')->check())
    <!-- Properties Section -->
    <div class="mt-6">
        <div class="px-3 mb-2">
            <h3 class="text-xs font-semibold text-secondary-500 uppercase tracking-wider">Property Management</h3>
        </div>
        <div class="space-y-1">
            <a href="{{ route('admin.properties.index') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.properties.*') ? 'bg-primary-50 text-primary-700 border-r-2 border-primary-500' : 'text-secondary-600 hover:bg-secondary-50 hover:text-secondary-900' }}">
                <svg class="mr-3 h-5 w-5 {{ request()->routeIs('admin.properties.*') ? 'text-primary-500' : 'text-secondary-400 group-hover:text-secondary-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                {{ __('Properties') }}
            </a>
            
            <a href="{{ route('admin.amenity_types.index') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.amenity_types.*') ? 'bg-primary-50 text-primary-700 border-r-2 border-primary-500' : 'text-secondary-600 hover:bg-secondary-50 hover:text-secondary-900' }}">
                <svg class="mr-3 h-5 w-5 {{ request()->routeIs('admin.amenity_types.*') ? 'text-primary-500' : 'text-secondary-400 group-hover:text-secondary-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                {{ __('Amenity Types') }}
            </a>
            
            <a href="{{ route('admin.amenities.index') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.amenities.*') ? 'bg-primary-50 text-primary-700 border-r-2 border-primary-500' : 'text-secondary-600 hover:bg-secondary-50 hover:text-secondary-900' }}">
                <svg class="mr-3 h-5 w-5 {{ request()->routeIs('admin.amenities.*') ? 'text-primary-500' : 'text-secondary-400 group-hover:text-secondary-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                </svg>
                {{ __('Amenities') }}
            </a>
        </div>
    </div>

    <!-- Management Section -->
    <div class="mt-6">
        <div class="px-3 mb-2">
            <h3 class="text-xs font-semibold text-secondary-500 uppercase tracking-wider">Management</h3>
        </div>
        <div class="space-y-1">
            <a href="{{ route('admin.users.index') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.users.*') ? 'bg-primary-50 text-primary-700 border-r-2 border-primary-500' : 'text-secondary-600 hover:bg-secondary-50 hover:text-secondary-900' }}">
                <svg class="mr-3 h-5 w-5 {{ request()->routeIs('admin.users.*') ? 'text-primary-500' : 'text-secondary-400 group-hover:text-secondary-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                </svg>
                {{ __('Users') }}
            </a>
            
            <a href="{{ route('admin.missions.index') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.missions.*') ? 'bg-primary-50 text-primary-700 border-r-2 border-primary-500' : 'text-secondary-600 hover:bg-secondary-50 hover:text-secondary-900' }}">
                <svg class="mr-3 h-5 w-5 {{ request()->routeIs('admin.missions.*') ? 'text-primary-500' : 'text-secondary-400 group-hover:text-secondary-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                {{ __('Missions') }}
            </a>
            
            <a href="{{ route('admin.file-manager.index') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.file-manager.*') ? 'bg-primary-50 text-primary-700 border-r-2 border-primary-500' : 'text-secondary-600 hover:bg-secondary-50 hover:text-secondary-900' }}">
                <svg class="mr-3 h-5 w-5 {{ request()->routeIs('admin.file-manager.*') ? 'text-primary-500' : 'text-secondary-400 group-hover:text-secondary-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4" />
                </svg>
                {{ __('File Manager') }}
            </a>
        </div>
    </div>
    
    <!-- System Section -->
    <div class="mt-6">
        <div class="px-3 mb-2">
            <h3 class="text-xs font-semibold text-secondary-500 uppercase tracking-wider">System</h3>
        </div>
        <div class="space-y-1">
            <a href="{{ route('admin.analytics.dashboard') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.analytics.*') ? 'bg-primary-50 text-primary-700 border-r-2 border-primary-500' : 'text-secondary-600 hover:bg-secondary-50 hover:text-secondary-900' }}">
                <svg class="mr-3 h-5 w-5 {{ request()->routeIs('admin.analytics.*') ? 'text-primary-500' : 'text-secondary-400 group-hover:text-secondary-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                {{ __('Analytics') }}
            </a>
            
            @if (Route::has('admin.monitoring.index'))
            <a href="{{ route('admin.monitoring.index') }}" 
               class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.monitoring.*') ? 'bg-primary-50 text-primary-700 border-r-2 border-primary-500' : 'text-secondary-600 hover:bg-secondary-50 hover:text-secondary-900' }}">
                <svg class="mr-3 h-5 w-5 {{ request()->routeIs('admin.monitoring.*') ? 'text-primary-500' : 'text-secondary-400 group-hover:text-secondary-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                {{ __('Monitoring') }}
            </a>
            @else
            <div class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg opacity-50 cursor-not-allowed" title="Monitoring route not available">
                <svg class="mr-3 h-5 w-5 text-secondary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                <span class="text-secondary-400">{{ __('Monitoring') }}</span>
            </div>
            @endif
        </div>
    </div>
@endif

<!-- Ops-specific Navigation -->
@if(Auth::guard('ops')->check())
    <div class="mt-6">
        <div class="px-3 mb-2">
            <h3 class="text-xs font-semibold text-secondary-500 uppercase tracking-wider">Operations</h3>
        </div>
        <div class="space-y-1">
            <!-- Add ops-specific navigation items here -->
        </div>
    </div>
@endif

<!-- Checker-specific Navigation -->
@if(Auth::guard('checker')->check())
    <div class="mt-6">
        <div class="px-3 mb-2">
            <h3 class="text-xs font-semibold text-secondary-500 uppercase tracking-wider">Checking</h3>
        </div>
        <div class="space-y-1">
            <!-- Add checker-specific navigation items here -->
        </div>
    </div>
@endif
