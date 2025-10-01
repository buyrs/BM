@props(['userRole'])

<!-- Modern Mobile Navigation Bar -->
<nav class="lg:hidden fixed inset-x-0 bottom-0 z-50 bg-white/80 backdrop-blur-md border-t border-secondary-200 safe-area-inset-bottom">
    <div class="px-2 py-2">
        <div class="flex justify-around items-center">
            @if($userRole === 'admin')
                <!-- Admin Navigation -->
                <a href="{{ route('admin.dashboard') }}" 
                   class="group flex flex-col items-center justify-center px-3 py-2 rounded-xl transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-primary-100 text-primary-700' : 'text-secondary-600 hover:bg-secondary-100 hover:text-secondary-900 active:scale-95' }}">
                    <div class="flex items-center justify-center w-8 h-8 mb-1 rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-primary-200' : 'group-hover:bg-secondary-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                        </svg>
                    </div>
                    <span class="text-xs font-medium">Dashboard</span>
                </a>
                
                <a href="{{ route('admin.properties.index') }}" 
                   class="group flex flex-col items-center justify-center px-3 py-2 rounded-xl transition-all duration-200 {{ request()->routeIs('admin.properties.*') ? 'bg-primary-100 text-primary-700' : 'text-secondary-600 hover:bg-secondary-100 hover:text-secondary-900 active:scale-95' }}">
                    <div class="flex items-center justify-center w-8 h-8 mb-1 rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.properties.*') ? 'bg-primary-200' : 'group-hover:bg-secondary-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <span class="text-xs font-medium">Properties</span>
                </a>

                <a href="{{ route('admin.users.index') }}" 
                   class="group flex flex-col items-center justify-center px-3 py-2 rounded-xl transition-all duration-200 {{ request()->routeIs('admin.users.*') ? 'bg-primary-100 text-primary-700' : 'text-secondary-600 hover:bg-secondary-100 hover:text-secondary-900 active:scale-95' }}">
                    <div class="flex items-center justify-center w-8 h-8 mb-1 rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.users.*') ? 'bg-primary-200' : 'group-hover:bg-secondary-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <span class="text-xs font-medium">Users</span>
                </a>

                <a href="{{ route('admin.missions.index') }}" 
                   class="group flex flex-col items-center justify-center px-3 py-2 rounded-xl transition-all duration-200 {{ request()->routeIs('admin.missions.*') ? 'bg-primary-100 text-primary-700' : 'text-secondary-600 hover:bg-secondary-100 hover:text-secondary-900 active:scale-95' }}">
                    <div class="flex items-center justify-center w-8 h-8 mb-1 rounded-lg transition-colors duration-200 {{ request()->routeIs('admin.missions.*') ? 'bg-primary-200' : 'group-hover:bg-secondary-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <span class="text-xs font-medium">Missions</span>
                </a>
                
                <!-- More Menu -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" 
                            class="group flex flex-col items-center justify-center px-3 py-2 rounded-xl transition-all duration-200 text-secondary-600 hover:bg-secondary-100 hover:text-secondary-900 active:scale-95">
                        <div class="flex items-center justify-center w-8 h-8 mb-1 rounded-lg transition-colors duration-200 group-hover:bg-secondary-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </div>
                        <span class="text-xs font-medium">More</span>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div x-show="open" x-cloak @click.away="open = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-2"
                         class="absolute bottom-full mb-2 left-1/2 transform -translate-x-1/2 w-48 bg-white rounded-xl shadow-medium border border-secondary-200 py-2">
                        <a href="{{ route('admin.analytics.dashboard') }}" class="flex items-center px-4 py-2 text-sm text-secondary-700 hover:bg-secondary-50">
                            <svg class="w-4 h-4 mr-3 text-secondary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            Analytics
                        </a>
                        @if (Route::has('admin.monitoring.index'))
                        <a href="{{ route('admin.monitoring.index') }}" class="flex items-center px-4 py-2 text-sm text-secondary-700 hover:bg-secondary-50">
                            <svg class="w-4 h-4 mr-3 text-secondary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                            Monitoring
                        </a>
                        @else
                        <div class="flex items-center px-4 py-2 text-sm text-secondary-400 cursor-not-allowed opacity-50" title="Monitoring route not available">
                            <svg class="w-4 h-4 mr-3 text-secondary-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                            Monitoring
                        </div>
                        @endif
                    </div>
                </div>
                
            @elseif($userRole === 'ops')
                <!-- Ops Navigation -->
                <a href="{{ route('ops.dashboard') }}" 
                   class="group flex flex-col items-center justify-center px-3 py-2 rounded-xl transition-all duration-200 {{ request()->routeIs('ops.dashboard') ? 'bg-primary-100 text-primary-700' : 'text-secondary-600 hover:bg-secondary-100 hover:text-secondary-900 active:scale-95' }}">
                    <div class="flex items-center justify-center w-8 h-8 mb-1 rounded-lg transition-colors duration-200 {{ request()->routeIs('ops.dashboard') ? 'bg-primary-200' : 'group-hover:bg-secondary-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                        </svg>
                    </div>
                    <span class="text-xs font-medium">Dashboard</span>
                </a>
                
                <a href="{{ route('ops.missions.index') }}" 
                   class="group flex flex-col items-center justify-center px-3 py-2 rounded-xl transition-all duration-200 {{ request()->routeIs('ops.missions.*') ? 'bg-primary-100 text-primary-700' : 'text-secondary-600 hover:bg-secondary-100 hover:text-secondary-900 active:scale-95' }}">
                    <div class="flex items-center justify-center w-8 h-8 mb-1 rounded-lg transition-colors duration-200 {{ request()->routeIs('ops.missions.*') ? 'bg-primary-200' : 'group-hover:bg-secondary-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <span class="text-xs font-medium">Missions</span>
                </a>
                
                <a href="{{ route('ops.users.index') }}" 
                   class="group flex flex-col items-center justify-center px-3 py-2 rounded-xl transition-all duration-200 {{ request()->routeIs('ops.users.*') ? 'bg-primary-100 text-primary-700' : 'text-secondary-600 hover:bg-secondary-100 hover:text-secondary-900 active:scale-95' }}">
                    <div class="flex items-center justify-center w-8 h-8 mb-1 rounded-lg transition-colors duration-200 {{ request()->routeIs('ops.users.*') ? 'bg-primary-200' : 'group-hover:bg-secondary-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <span class="text-xs font-medium">Checkers</span>
                </a>
                
            @elseif($userRole === 'checker')
                <!-- Checker Navigation -->
                <a href="{{ route('checker.dashboard') }}" 
                   class="group flex flex-col items-center justify-center px-3 py-2 rounded-xl transition-all duration-200 {{ request()->routeIs('checker.dashboard') ? 'bg-primary-100 text-primary-700' : 'text-secondary-600 hover:bg-secondary-100 hover:text-secondary-900 active:scale-95' }}">
                    <div class="flex items-center justify-center w-8 h-8 mb-1 rounded-lg transition-colors duration-200 {{ request()->routeIs('checker.dashboard') ? 'bg-primary-200' : 'group-hover:bg-secondary-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                        </svg>
                    </div>
                    <span class="text-xs font-medium">Dashboard</span>
                </a>
                
                <a href="{{ route('checker.dashboard') }}" 
                   class="group flex flex-col items-center justify-center px-3 py-2 rounded-xl transition-all duration-200 {{ request()->routeIs('checklists.*') ? 'bg-primary-100 text-primary-700' : 'text-secondary-600 hover:bg-secondary-100 hover:text-secondary-900 active:scale-95' }}">
                    <div class="flex items-center justify-center w-8 h-8 mb-1 rounded-lg transition-colors duration-200 {{ request()->routeIs('checklists.*') ? 'bg-primary-200' : 'group-hover:bg-secondary-200' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <span class="text-xs font-medium">Checklists</span>
                </a>
            @endif
        </div>
    </div>
    
    <!-- Home Indicator for iOS -->
    <div class="h-1 bg-secondary-900/10 rounded-full mx-auto" style="width: 134px; margin-top: 2px; margin-bottom: env(safe-area-inset-bottom, 8px);"></div>
</nav>
