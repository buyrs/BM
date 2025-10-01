<x-modern-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Ops Dashboard</h1>
                <p class="text-secondary-600 mt-1">Welcome, Ops Manager! Manage operations efficiently.</p>
            </div>
            <div class="flex items-center space-x-3">
                <x-badge variant="primary" dot>{{ now()->format('M d, Y') }}</x-badge>
            </div>
        </div>
    </x-slot>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Active Checkers -->
        <x-card variant="elevated" padding="default">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-secondary-600">Active Checkers</p>
                    <p class="text-3xl font-bold text-secondary-900 mt-2">12</p>
                    <p class="text-sm text-success-600 mt-1">+3 this week</p>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-success-100 rounded-xl">
                    <svg class="w-6 h-6 text-success-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
            </div>
        </x-card>

        <!-- Total Missions -->
        <x-card variant="elevated" padding="default">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-secondary-600">Total Missions</p>
                    <p class="text-3xl font-bold text-secondary-900 mt-2">47</p>
                    <p class="text-sm text-primary-600 mt-1">15 in progress</p>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-primary-100 rounded-xl">
                    <svg class="w-6 h-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
            </div>
        </x-card>

        <!-- Completed Today -->
        <x-card variant="elevated" padding="default">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-secondary-600">Completed Today</p>
                    <p class="text-3xl font-bold text-secondary-900 mt-2">8</p>
                    <p class="text-sm text-success-600 mt-1">Above average</p>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-warning-100 rounded-xl">
                    <svg class="w-6 h-6 text-warning-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </x-card>

        <!-- Properties Managed -->
        <x-card variant="elevated" padding="default">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-secondary-600">Properties</p>
                    <p class="text-3xl font-bold text-secondary-900 mt-2">24</p>
                    <p class="text-sm text-secondary-600 mt-1">All monitored</p>
                </div>
                <div class="flex items-center justify-center w-12 h-12 bg-accent-100 rounded-xl">
                    <svg class="w-6 h-6 text-accent-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
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
                <p class="text-sm text-secondary-600 mt-1">Manage your operations efficiently</p>
            </x-slot>

            <div class="space-y-3">
                <a href="{{ route('ops.users.create') }}" 
                   class="group flex items-center p-4 bg-secondary-50 hover:bg-success-50 rounded-lg transition-colors duration-200">
                    <div class="flex items-center justify-center w-10 h-10 bg-success-100 group-hover:bg-success-200 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-success-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-secondary-900">Add New Checker</p>
                        <p class="text-xs text-secondary-600">Invite a new checker to the team</p>
                    </div>
                    <svg class="w-4 h-4 text-secondary-400 group-hover:text-success-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>

                <a href="{{ route('ops.missions.create') }}" 
                   class="group flex items-center p-4 bg-secondary-50 hover:bg-primary-50 rounded-lg transition-colors duration-200">
                    <div class="flex items-center justify-center w-10 h-10 bg-primary-100 group-hover:bg-primary-200 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-secondary-900">Create Mission</p>
                        <p class="text-xs text-secondary-600">Set up a new mission for checkers</p>
                    </div>
                    <svg class="w-4 h-4 text-secondary-400 group-hover:text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>

                <a href="{{ route('ops.properties.index') }}" 
                   class="group flex items-center p-4 bg-secondary-50 hover:bg-accent-50 rounded-lg transition-colors duration-200">
                    <div class="flex items-center justify-center w-10 h-10 bg-accent-100 group-hover:bg-accent-200 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-accent-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-secondary-900">View Properties</p>
                        <p class="text-xs text-secondary-600">Manage property assignments</p>
                    </div>
                    <svg class="w-4 h-4 text-secondary-400 group-hover:text-accent-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </x-card>

        <!-- Recent Activities -->
        <x-card variant="elevated" padding="default">
            <x-slot name="header">
                <h3 class="text-lg font-semibold text-secondary-900">Recent Activities</h3>
                <p class="text-sm text-secondary-600 mt-1">Latest operations updates</p>
            </x-slot>

            <div class="space-y-4">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-2 h-2 bg-success-500 rounded-full mt-2"></div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-secondary-900">Mission "Property Check - Villa A" completed</p>
                        <p class="text-xs text-secondary-600">2 hours ago by Sarah Johnson</p>
                    </div>
                </div>

                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-2 h-2 bg-primary-500 rounded-full mt-2"></div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-secondary-900">New checker "Mike Chen" added to team</p>
                        <p class="text-xs text-secondary-600">4 hours ago by Ops Manager</p>
                    </div>
                </div>

                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-2 h-2 bg-warning-500 rounded-full mt-2"></div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-secondary-900">Mission "Emergency Check - Sunset Resort" assigned</p>
                        <p class="text-xs text-secondary-600">6 hours ago by Ops Manager</p>
                    </div>
                </div>

                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-2 h-2 bg-success-500 rounded-full mt-2"></div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-secondary-900">Weekly report generated successfully</p>
                        <p class="text-xs text-secondary-600">1 day ago by System</p>
                    </div>
                </div>
            </div>

            <x-slot name="footer">
                <a href="#" class="text-sm font-medium text-primary-600 hover:text-primary-700">View all activities →</a>
            </x-slot>
        </x-card>
    </div>

    <!-- Management Cards -->
    <x-card variant="elevated" padding="default">
        <x-slot name="header">
            <h3 class="text-lg font-semibold text-secondary-900">Operations Management</h3>
            <p class="text-sm text-secondary-600 mt-1">Access all operational tools</p>
        </x-slot>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <a href="{{ route('ops.users.index') }}" 
               class="group p-6 bg-gradient-to-br from-success-50 to-success-100 hover:from-success-100 hover:to-success-200 rounded-xl transition-all duration-200 hover:shadow-md interactive-lift">
                <div class="flex flex-col items-center text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-success-500 text-white rounded-xl mb-4 group-hover:scale-110 transition-transform duration-200">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <h4 class="font-semibold text-secondary-900 mb-2">Manage Checkers</h4>
                    <p class="text-sm text-secondary-600">Oversee checker assignments and performance</p>
                    <x-badge variant="success" size="sm" class="mt-3">12 Active</x-badge>
                </div>
            </a>

            <a href="{{ route('ops.missions.index') }}" 
               class="group p-6 bg-gradient-to-br from-primary-50 to-primary-100 hover:from-primary-100 hover:to-primary-200 rounded-xl transition-all duration-200 hover:shadow-md interactive-lift">
                <div class="flex flex-col items-center text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-primary-500 text-white rounded-xl mb-4 group-hover:scale-110 transition-transform duration-200">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <h4 class="font-semibold text-secondary-900 mb-2">Manage Missions</h4>
                    <p class="text-sm text-secondary-600">Create and track mission progress</p>
                    <x-badge variant="primary" size="sm" class="mt-3">47 Total</x-badge>
                </div>
            </a>

            <a href="{{ route('ops.properties.index') }}" 
               class="group p-6 bg-gradient-to-br from-accent-50 to-accent-100 hover:from-accent-100 hover:to-accent-200 rounded-xl transition-all duration-200 hover:shadow-md interactive-lift">
                <div class="flex flex-col items-center text-center">
                    <div class="flex items-center justify-center w-12 h-12 bg-accent-500 text-white rounded-xl mb-4 group-hover:scale-110 transition-transform duration-200">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <h4 class="font-semibold text-secondary-900 mb-2">Manage Properties</h4>
                    <p class="text-sm text-secondary-600">Property oversight and maintenance</p>
                    <x-badge variant="accent" size="sm" class="mt-3">24 Properties</x-badge>
                </div>
            </a>
        </div>
    </x-card>
</x-modern-layout>