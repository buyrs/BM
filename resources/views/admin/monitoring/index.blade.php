@extends('layouts.app')

@section('title', 'System Monitoring')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white">System Monitoring Dashboard</h1>
        <div class="flex space-x-2">
            <a href="{{ route('admin.monitoring.clear-cache') }}" 
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-300">
                Clear Cache
            </a>
            <a href="{{ route('admin.monitoring.restart-workers') }}" 
               class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-300">
                Restart Workers
            </a>
        </div>
    </div>

    <!-- System Health Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 dark:bg-green-900 mr-4">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">System Status</h3>
                    <p class="text-2xl font-bold {{ $systemHealth['overall'] ? 'text-green-600' : 'text-red-600' }}">
                        {{ $systemHealth['overall'] ? 'Healthy' : 'Issues Detected' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900 mr-4">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Total Users</h3>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ $metrics['total_users'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900 mr-4">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Total Missions</h3>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ $metrics['total_missions'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900 mr-4">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Total Checklists</h3>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ $metrics['total_checklists'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- System Components Status -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">System Components</h2>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-gray-700 dark:text-gray-300">Database</span>
                    <span class="{{ $systemHealth['database'] ? 'text-green-600' : 'text-red-600' }} font-semibold">
                        {{ $systemHealth['database'] ? 'Online' : 'Offline' }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-700 dark:text-gray-300">Redis Cache</span>
                    <span class="{{ $systemHealth['redis'] ? 'text-green-600' : 'text-red-600' }} font-semibold">
                        {{ $systemHealth['redis'] ? 'Online' : 'Offline' }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-700 dark:text-gray-300">Disk Space</span>
                    <span class="{{ $systemHealth['disk_space'] ? 'text-green-600' : 'text-red-600' }} font-semibold">
                        {{ $systemHealth['disk_space'] ? 'Healthy' : 'Low Space' }}
                        ({{ $systemHealth['disk_percentage'] ?? 0 }}% used)
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-700 dark:text-gray-300">Memory Usage</span>
                    <span class="{{ $systemHealth['memory'] ? 'text-green-600' : 'text-red-600' }} font-semibold">
                        {{ $systemHealth['memory'] ? 'Healthy' : 'High Usage' }}
                        ({{ $systemHealth['memory_usage_mb'] ?? 0 }} MB)
                    </span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">Queue Status</h2>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-gray-700 dark:text-gray-300">Pending Jobs</span>
                    <span class="{{ ($queueStatus['pending_jobs'] ?? 0) < 100 ? 'text-green-600' : 'text-red-600' }} font-semibold">
                        {{ $queueStatus['pending_jobs'] ?? 0 }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-700 dark:text-gray-300">Failed Jobs</span>
                    <span class="{{ ($queueStatus['failed_jobs'] ?? 0) == 0 ? 'text-green-600' : 'text-red-600' }} font-semibold">
                        {{ $queueStatus['failed_jobs'] ?? 0 }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-700 dark:text-gray-300">Queue Health</span>
                    <span class="{{ $queueStatus['queue_health'] ?? false ? 'text-green-600' : 'text-red-600' }} font-semibold">
                        {{ $queueStatus['queue_health'] ?? false ? 'Healthy' : 'Overloaded' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Database Metrics -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">Database Metrics</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="border rounded-lg p-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">Database Size</h3>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $databaseMetrics['size_mb'] ?? 0 }} MB</p>
            </div>
            <div class="border rounded-lg p-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">Active Connections</h3>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $databaseMetrics['active_connections'] ?? 0 }}</p>
            </div>
            <div class="border rounded-lg p-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-2">Slow Queries</h3>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $databaseMetrics['slow_queries'] ?? 0 }}</p>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">Recent Activity</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Metric</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Value</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">Active Users (Last Hour)</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ $metrics['active_users_last_hour'] ?? 0 }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Normal
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">Pending Missions</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ $metrics['pending_missions'] ?? 0 }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ ($metrics['pending_missions'] ?? 0) > 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                                {{ ($metrics['pending_missions'] ?? 0) > 50 ? 'High' : 'Normal' }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">Completed Checklists (Today)</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ $metrics['completed_checklists_today'] ?? 0 }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Normal
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection