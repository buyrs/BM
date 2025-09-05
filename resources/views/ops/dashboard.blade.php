@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h2 class="text-2xl font-bold mb-6">Ops Dashboard</h2>
                
                <!-- Metrics Overview -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200">Total Missions</h3>
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-300">{{ $metrics['total_missions'] ?? 0 }}</p>
                    </div>
                    
                    <div class="bg-green-50 dark:bg-green-900 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-green-800 dark:text-green-200">Completed</h3>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-300">{{ $metrics['completed_missions'] ?? 0 }}</p>
                    </div>
                    
                    <div class="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200">Pending Validation</h3>
                        <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-300">{{ $metrics['pending_validation'] ?? 0 }}</p>
                    </div>
                    
                    <div class="bg-red-50 dark:bg-red-900 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-red-800 dark:text-red-200">Incidents</h3>
                        <p class="text-2xl font-bold text-red-600 dark:text-red-300">{{ $metrics['active_incidents'] ?? 0 }}</p>
                    </div>
                </div>
                
                <!-- Recent Notifications -->
                @if(isset($pendingNotifications) && $pendingNotifications->count() > 0)
                    <div class="mb-8">
                        <h3 class="text-xl font-semibold mb-4">Recent Notifications</h3>
                        <div class="space-y-3">
                            @foreach($pendingNotifications as $notification)
                                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $notification->message }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $notification->created_at->diffForHumans() }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                
                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <h3 class="text-lg font-semibold mb-3">Quick Actions</h3>
                        <div class="space-y-2">
                            <a href="{{ route('bail-mobilites.create') }}" class="block w-full text-left px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md">
                                Create New Bail Mobilité
                            </a>
                            <a href="{{ route('missions.create') }}" class="block w-full text-left px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md">
                                Create Mission
                            </a>
                            <a href="{{ route('ops.notifications') }}" class="block w-full text-left px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-md">
                                View All Notifications
                            </a>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <h3 class="text-lg font-semibold mb-3">System Status</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-700 dark:text-gray-300">Active Checkers</span>
                                <span class="font-semibold text-green-600 dark:text-green-400">{{ $metrics['active_checkers'] ?? 0 }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-700 dark:text-gray-300">Pending Signatures</span>
                                <span class="font-semibold text-yellow-600 dark:text-yellow-400">{{ $metrics['pending_signatures'] ?? 0 }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-700 dark:text-gray-300">Today's Missions</span>
                                <span class="font-semibold text-blue-600 dark:text-blue-400">{{ $metrics['todays_missions'] ?? 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection