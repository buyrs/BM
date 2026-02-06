@props(['userRole'])

@php
    // Define badge counts - these would typically come from a service or controller
    $badgeCounts = [
        'admin' => [
            'missions' => session('pending_missions_count', 0),
            'users' => session('pending_users_count', 0),
        ],
        'ops' => [
            'missions' => session('today_missions_count', 0),
        ],
        'checker' => [
            'checklists' => session('pending_checklists_count', 0),
        ],
    ];
    
    $badges = $badgeCounts[$userRole] ?? [];
@endphp

<!-- Enhanced Mobile Navigation Bar with Haptics & Badges -->
<nav 
    x-data="mobileNavigation()"
    class="lg:hidden fixed inset-x-0 bottom-0 z-50 bg-white/80 dark:bg-secondary-900/80 backdrop-blur-md border-t border-secondary-200 dark:border-secondary-700"
    style="padding-bottom: env(safe-area-inset-bottom, 0px);"
>
    <div class="px-2 py-2">
        <div class="flex justify-around items-center">
            @if($userRole === 'admin')
                <!-- Admin Navigation -->
                <x-mobile-nav-item 
                    href="{{ route('admin.dashboard') }}" 
                    :active="request()->routeIs('admin.dashboard')"
                    icon="dashboard"
                    label="Dashboard"
                />
                
                <x-mobile-nav-item 
                    href="{{ route('admin.properties.index') }}" 
                    :active="request()->routeIs('admin.properties.*')"
                    icon="properties"
                    label="Properties"
                />

                <x-mobile-nav-item 
                    href="{{ route('admin.users.index') }}" 
                    :active="request()->routeIs('admin.users.*')"
                    icon="users"
                    label="Users"
                    :badge="$badges['users'] ?? 0"
                />

                <x-mobile-nav-item 
                    href="{{ route('admin.missions.index') }}" 
                    :active="request()->routeIs('admin.missions.*')"
                    icon="missions"
                    label="Missions"
                    :badge="$badges['missions'] ?? 0"
                />
                
                <!-- More Menu -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open; handleTap()" 
                            class="group flex flex-col items-center justify-center px-3 py-2 rounded-xl transition-all duration-200 text-secondary-600 dark:text-secondary-400 hover:bg-secondary-100 dark:hover:bg-secondary-800 hover:text-secondary-900 dark:hover:text-white active:scale-95">
                        <div class="flex items-center justify-center w-8 h-8 mb-1 rounded-lg transition-colors duration-200 group-hover:bg-secondary-200 dark:group-hover:bg-secondary-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </div>
                        <span class="text-xs font-medium">More</span>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div x-show="open" x-cloak @click.away="open = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                         class="absolute bottom-full mb-2 right-0 w-56 bg-white dark:bg-secondary-800 rounded-xl shadow-strong border border-secondary-200 dark:border-secondary-700 py-2 overflow-hidden">
                        
                        <a href="{{ route('admin.analytics.dashboard') }}" 
                           @click="handleTap()"
                           class="flex items-center px-4 py-3 text-sm text-secondary-700 dark:text-secondary-200 hover:bg-secondary-50 dark:hover:bg-secondary-700 transition-colors">
                            <div class="w-8 h-8 rounded-lg bg-primary-100 dark:bg-primary-900/50 flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div>
                                <span class="font-medium">Analytics</span>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400">View insights & reports</p>
                            </div>
                        </a>
                        
                        @if (Route::has('admin.monitoring.index'))
                        <a href="{{ route('admin.monitoring.index') }}" 
                           @click="handleTap()"
                           class="flex items-center px-4 py-3 text-sm text-secondary-700 dark:text-secondary-200 hover:bg-secondary-50 dark:hover:bg-secondary-700 transition-colors">
                            <div class="w-8 h-8 rounded-lg bg-warning-100 dark:bg-warning-900/50 flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-warning-600 dark:text-warning-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                            </div>
                            <div>
                                <span class="font-medium">Monitoring</span>
                                <p class="text-xs text-secondary-500 dark:text-secondary-400">System health status</p>
                            </div>
                        </a>
                        @endif
                        
                        <div class="border-t border-secondary-200 dark:border-secondary-700 my-2"></div>
                        
                        <a href="{{ route('profile.edit') }}" 
                           @click="handleTap()"
                           class="flex items-center px-4 py-3 text-sm text-secondary-700 dark:text-secondary-200 hover:bg-secondary-50 dark:hover:bg-secondary-700 transition-colors">
                            <div class="w-8 h-8 rounded-lg bg-secondary-100 dark:bg-secondary-700 flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-secondary-600 dark:text-secondary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <span class="font-medium">Settings</span>
                        </a>
                    </div>
                </div>
                
            @elseif($userRole === 'ops')
                <!-- Ops Navigation -->
                <x-mobile-nav-item 
                    href="{{ route('ops.dashboard') }}" 
                    :active="request()->routeIs('ops.dashboard')"
                    icon="dashboard"
                    label="Dashboard"
                />
                
                <x-mobile-nav-item 
                    href="{{ route('ops.missions.index') }}" 
                    :active="request()->routeIs('ops.missions.*')"
                    icon="missions"
                    label="Missions"
                    :badge="$badges['missions'] ?? 0"
                />
                
                <x-mobile-nav-item 
                    href="{{ route('ops.users.index') }}" 
                    :active="request()->routeIs('ops.users.*')"
                    icon="users"
                    label="Checkers"
                />
                
            @elseif($userRole === 'checker')
                <!-- Checker Navigation -->
                <x-mobile-nav-item 
                    href="{{ route('checker.dashboard') }}" 
                    :active="request()->routeIs('checker.dashboard')"
                    icon="dashboard"
                    label="Dashboard"
                />
                
                <x-mobile-nav-item 
                    href="{{ route('checker.dashboard') }}" 
                    :active="request()->routeIs('checklists.*')"
                    icon="checklists"
                    label="Checklists"
                    :badge="$badges['checklists'] ?? 0"
                />
            @endif
        </div>
    </div>
    
    <!-- Home Indicator for iOS -->
    <div class="h-1 bg-secondary-900/10 dark:bg-white/10 rounded-full mx-auto" style="width: 134px; margin-top: 2px;"></div>
</nav>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('mobileNavigation', () => ({
            handleTap() {
                if (window.haptics) {
                    window.haptics.selection();
                }
            }
        }));
    });
</script>
