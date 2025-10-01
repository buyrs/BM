<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Admin Dashboard</h1>
                <p class="text-secondary-600 mt-1">Welcome back! Here's what's happening today.</p>
            </div>
            <div class="flex items-center space-x-3">
                <x-badge variant="success" dot>{{ now()->format('M d, Y') }}</x-badge>
            </div>
        </div>
    </x-slot>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Properties -->
        <x-card variant="elevated" padding="default">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-secondary-600">Total Properties</p>
                    <p class="text-3xl font-bold text-secondary-900 mt-2">24</p>
                    <p class="text-sm text-success-600 mt-1">+2 this month</p>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-primary-100 rounded-xl">
                    <svg class="w-6 h-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
            </div>
        </x-card>

        <!-- Active Users -->
        <x-card variant="elevated" padding="default">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-secondary-600">Active Users</p>
                    <p class="text-3xl font-bold text-secondary-900 mt-2">156</p>
                    <p class="text-sm text-success-600 mt-1">+12% growth</p>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-success-100 rounded-xl">
                    <svg class="w-6 h-6 text-success-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
            </div>
        </x-card>

        <!-- Active Missions -->
        <x-card variant="elevated" padding="default">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-secondary-600">Active Missions</p>
                    <p class="text-3xl font-bold text-secondary-900 mt-2">43</p>
                    <p class="text-sm text-warning-600 mt-1">8 pending review</p>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-warning-100 rounded-xl">
                    <svg class="w-6 h-6 text-warning-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
            </div>
        </x-card>

        <!-- System Health -->
        <x-card variant="elevated" padding="default">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-secondary-600">System Health</p>
                    <div class="flex items-center mt-2">
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-success-500 rounded-full"></div>
                            <p class="text-sm font-medium text-success-600">All systems operational</p>
                        </div>
                    </div>
                    <p class="text-sm text-secondary-500 mt-1">99.9% uptime</p>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-success-100 rounded-xl">
                    <svg class="w-6 h-6 text-success-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Quick Actions -->
        <x-card variant="elevated" padding="default">
            <x-slot name="header">
                <h3 class="text-lg font-semibold text-secondary-900">Quick Actions</h3>
                <p class="text-sm text-secondary-600 mt-1">Frequently used management tools</p>
            </x-slot>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <a href="{{ route('admin.properties.create') }}" 
                   class="group flex items-center p-4 bg-secondary-50 hover:bg-primary-50 rounded-lg transition-colors duration-200">
                    <div class="flex items-center justify-center w-10 h-10 bg-primary-100 group-hover:bg-primary-200 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-secondary-900">Add Property</p>
                        <p class="text-xs text-secondary-600">Create new property</p>
                    </div>
                </a>

                <a href="{{ route('admin.users.create') }}" 
                   class="group flex items-center p-4 bg-secondary-50 hover:bg-success-50 rounded-lg transition-colors duration-200">
                    <div class="flex items-center justify-center w-10 h-10 bg-success-100 group-hover:bg-success-200 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-success-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-secondary-900">Add User</p>
                        <p class="text-xs text-secondary-600">Invite new user</p>
                    </div>
                </a>

                <a href="{{ route('admin.missions.create') }}" 
                   class="group flex items-center p-4 bg-secondary-50 hover:bg-warning-50 rounded-lg transition-colors duration-200">
                    <div class="flex items-center justify-center w-10 h-10 bg-warning-100 group-hover:bg-warning-200 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-warning-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m0 0V9a2 2 0 01-2 2H9a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-secondary-900">Create Mission</p>
                        <p class="text-xs text-secondary-600">New mission plan</p>
                    </div>
                </a>

                <a href="{{ route('admin.analytics.dashboard') }}" 
                   class="group flex items-center p-4 bg-secondary-50 hover:bg-accent-50 rounded-lg transition-colors duration-200">
                    <div class="flex items-center justify-center w-10 h-10 bg-accent-100 group-hover:bg-accent-200 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-accent-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-secondary-900">View Analytics</p>
                        <p class="text-xs text-secondary-600">Performance metrics</p>
                    </div>
                </a>
            </div>
        </x-card>

        <!-- Recent Activity -->
        <x-card variant="elevated" padding="default">
            <x-slot name="header">
                <h3 class="text-lg font-semibold text-secondary-900">Recent Activity</h3>
                <p class="text-sm text-secondary-600 mt-1">Latest system activities</p>
            </x-slot>

            <div class="space-y-4">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-2 h-2 bg-success-500 rounded-full mt-2"></div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-secondary-900">New property "Sunset Villa" added</p>
                        <p class="text-xs text-secondary-600">2 hours ago by John Doe</p>
                    </div>
                </div>

                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-2 h-2 bg-primary-500 rounded-full mt-2"></div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-secondary-900">Mission "Beach Cleanup" completed</p>
                        <p class="text-xs text-secondary-600">4 hours ago by Sarah Smith</p>
                    </div>
                </div>

                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-2 h-2 bg-warning-500 rounded-full mt-2"></div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-secondary-900">System backup completed successfully</p>
                        <p class="text-xs text-secondary-600">6 hours ago by System</p>
                    </div>
                </div>

                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-2 h-2 bg-accent-500 rounded-full mt-2"></div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-secondary-900">5 new users registered</p>
                        <p class="text-xs text-secondary-600">8 hours ago</p>
                    </div>
                </div>
            </div>

            <x-slot name="footer">
                <a href="#" class="text-sm font-medium text-primary-600 hover:text-primary-700">View all activity →</a>
            </x-slot>
        </x-card>
    </div>

    <!-- Management Links -->
    <x-card variant="elevated" padding="default">
        <x-slot name="header">
            <h3 class="text-lg font-semibold text-secondary-900">System Management</h3>
            <p class="text-sm text-secondary-600 mt-1">Access all administrative tools</p>
        </x-slot>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
            <a href="{{ route('admin.users.index') }}" 
               class="group p-6 bg-gradient-to-br from-primary-50 to-primary-100 hover:from-primary-100 hover:to-primary-200 rounded-xl transition-all duration-200 hover:shadow-md">
                <div class="flex flex-col items-center text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-primary-500 text-white rounded-xl mb-4 group-hover:scale-110 transition-transform duration-200">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <h4 class="font-semibold text-secondary-900">Manage Users</h4>
                    <p class="text-sm text-secondary-600 mt-1">User accounts & permissions</p>
                </div>
            </a>

            <a href="{{ route('admin.properties.index') }}" 
               class="group p-6 bg-gradient-to-br from-success-50 to-success-100 hover:from-success-100 hover:to-success-200 rounded-xl transition-all duration-200 hover:shadow-md">
                <div class="flex flex-col items-center text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-success-500 text-white rounded-xl mb-4 group-hover:scale-110 transition-transform duration-200">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <h4 class="font-semibold text-secondary-900">Properties</h4>
                    <p class="text-sm text-secondary-600 mt-1">Property management</p>
                </div>
            </a>

            <a href="{{ route('admin.missions.index') }}" 
               class="group p-6 bg-gradient-to-br from-warning-50 to-warning-100 hover:from-warning-100 hover:to-warning-200 rounded-xl transition-all duration-200 hover:shadow-md">
                <div class="flex flex-col items-center text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-warning-500 text-white rounded-xl mb-4 group-hover:scale-110 transition-transform duration-200">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <h4 class="font-semibold text-secondary-900">Missions</h4>
                    <p class="text-sm text-secondary-600 mt-1">Mission planning</p>
                </div>
            </a>

            <a href="{{ route('admin.amenities.index') }}" 
               class="group p-6 bg-gradient-to-br from-accent-50 to-accent-100 hover:from-accent-100 hover:to-accent-200 rounded-xl transition-all duration-200 hover:shadow-md">
                <div class="flex flex-col items-center text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-accent-500 text-white rounded-xl mb-4 group-hover:scale-110 transition-transform duration-200">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                    </div>
                    <h4 class="font-semibold text-secondary-900">Amenities</h4>
                    <p class="text-sm text-secondary-600 mt-1">Facility amenities</p>
                </div>
            </a>

            @if (Route::has('admin.monitoring.index'))
            <a href="{{ route('admin.monitoring.index') }}" 
               class="group p-6 bg-gradient-to-br from-secondary-50 to-secondary-100 hover:from-secondary-100 hover:to-secondary-200 rounded-xl transition-all duration-200 hover:shadow-md">
                <div class="flex flex-col items-center text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-secondary-500 text-white rounded-xl mb-4 group-hover:scale-110 transition-transform duration-200">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <h4 class="font-semibold text-secondary-900">Monitoring</h4>
                    <p class="text-sm text-secondary-600 mt-1">System monitoring</p>
                </div>
            </a>
            @else
            <div class="group p-6 bg-secondary-50 rounded-xl opacity-60 cursor-not-allowed" title="Monitoring route not available">
                <div class="flex flex-col items-center text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-secondary-300 text-white rounded-xl mb-4">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <h4 class="font-semibold text-secondary-700">Monitoring</h4>
                    <p class="text-sm text-secondary-500 mt-1">System monitoring</p>
                </div>
            </div>
            @endif
        </div>
    </x-card>
</x-app-layout>
