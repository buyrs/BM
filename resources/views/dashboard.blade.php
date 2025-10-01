<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-secondary-900">Dashboard</h1>
                <p class="text-secondary-600 mt-1">Welcome! Here's your overview.</p>
            </div>
            <div class="flex items-center space-x-3">
                <x-badge variant="primary">{{ now()->format('M d, Y') }}</x-badge>
            </div>
        </div>
    </x-slot>

    <!-- Welcome Card -->
    <x-card variant="elevated" padding="default" class="mb-8">
        <div class="text-center py-8">
            <div class="flex items-center justify-center w-16 h-16 bg-primary-100 rounded-full mx-auto mb-4">
                <svg class="w-8 h-8 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-secondary-900 mb-2">{{ __("You're logged in!") }}</h2>
            <p class="text-secondary-600 max-w-md mx-auto">Welcome to your dashboard. Start exploring the features available to you.</p>
        </div>
    </x-card>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Profile Status -->
        <x-card variant="elevated" padding="default">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-secondary-600">Profile Status</p>
                    <p class="text-lg font-bold text-secondary-900 mt-1">Complete</p>
                    <p class="text-sm text-success-600 mt-1">All information filled</p>
                </div>
                <div class="flex items-center justify-center w-10 h-10 bg-success-100 rounded-lg">
                    <svg class="w-5 h-5 text-success-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </x-card>

        <!-- Activity Level -->
        <x-card variant="elevated" padding="default">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-secondary-600">Activity Level</p>
                    <p class="text-lg font-bold text-secondary-900 mt-1">Active</p>
                    <p class="text-sm text-primary-600 mt-1">Last seen today</p>
                </div>
                <div class="flex items-center justify-center w-10 h-10 bg-primary-100 rounded-lg">
                    <svg class="w-5 h-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </div>
        </x-card>

        <!-- Account Status -->
        <x-card variant="elevated" padding="default">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-secondary-600">Account Status</p>
                    <p class="text-lg font-bold text-secondary-900 mt-1">Verified</p>
                    <p class="text-sm text-success-600 mt-1">All permissions active</p>
                </div>
                <div class="flex items-center justify-center w-10 h-10 bg-success-100 rounded-lg">
                    <svg class="w-5 h-5 text-success-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
            </div>
        </x-card>
    </div>

    <!-- Actions & Information -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Quick Actions -->
        <x-card variant="elevated" padding="default">
            <x-slot name="header">
                <h3 class="text-lg font-semibold text-secondary-900">Quick Actions</h3>
                <p class="text-sm text-secondary-600 mt-1">Common tasks and shortcuts</p>
            </x-slot>

            <div class="space-y-3">
                <a href="#" class="group flex items-center p-4 bg-secondary-50 hover:bg-primary-50 rounded-lg transition-colors duration-200">
                    <div class="flex items-center justify-center w-10 h-10 bg-primary-100 group-hover:bg-primary-200 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-secondary-900">Update Profile Settings</p>
                        <p class="text-xs text-secondary-600">Manage your account preferences</p>
                    </div>
                    <svg class="w-4 h-4 text-secondary-400 group-hover:text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>

                <a href="#" class="group flex items-center p-4 bg-secondary-50 hover:bg-success-50 rounded-lg transition-colors duration-200">
                    <div class="flex items-center justify-center w-10 h-10 bg-success-100 group-hover:bg-success-200 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-success-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-secondary-900">View Documentation</p>
                        <p class="text-xs text-secondary-600">Learn how to use the system</p>
                    </div>
                    <svg class="w-4 h-4 text-secondary-400 group-hover:text-success-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </x-card>

        <!-- System Information -->
        <x-card variant="elevated" padding="default">
            <x-slot name="header">
                <h3 class="text-lg font-semibold text-secondary-900">System Information</h3>
                <p class="text-sm text-secondary-600 mt-1">Current system status</p>
            </x-slot>

            <div class="space-y-4">
                <div class="flex items-center justify-between py-3 border-b border-secondary-100">
                    <span class="text-sm font-medium text-secondary-900">System Status</span>
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-success-500 rounded-full"></div>
                        <span class="text-sm text-success-600">Operational</span>
                    </div>
                </div>
                
                <div class="flex items-center justify-between py-3 border-b border-secondary-100">
                    <span class="text-sm font-medium text-secondary-900">Server Load</span>
                    <x-badge variant="success" size="sm">Normal</x-badge>
                </div>
                
                <div class="flex items-center justify-between py-3 border-b border-secondary-100">
                    <span class="text-sm font-medium text-secondary-900">Last Update</span>
                    <span class="text-sm text-secondary-600">{{ now()->subHours(2)->diffForHumans() }}</span>
                </div>
                
                <div class="flex items-center justify-between py-3">
                    <span class="text-sm font-medium text-secondary-900">Version</span>
                    <x-badge variant="outline" size="sm">v2.1.0</x-badge>
                </div>
            </div>
        </x-card>
    </div>
</x-app-layout>
