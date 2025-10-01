<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Audit Log Statistics') }}
            </h2>
            <a href="{{ route('admin.audit-logs.index') }}" 
               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Audit Logs
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <!-- Time Range Filter -->
                    <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                        <form method="GET" action="{{ route('admin.audit-logs.statistics') }}" class="flex items-center space-x-4">
                            <div>
                                <label for="days" class="block text-sm font-medium text-gray-700">Analysis Period</label>
                                <select name="days" id="days" class="mt-1 block rounded-md border-gray-300 shadow-sm">
                                    <option value="7" {{ $days == 7 ? 'selected' : '' }}>Last 7 days</option>
                                    <option value="30" {{ $days == 30 ? 'selected' : '' }}>Last 30 days</option>
                                    <option value="90" {{ $days == 90 ? 'selected' : '' }}>Last 90 days</option>
                                    <option value="365" {{ $days == 365 ? 'selected' : '' }}>Last year</option>
                                </select>
                            </div>
                            <div class="pt-6">
                                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Update
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div class="bg-blue-50 rounded-lg p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-blue-600">Total Logs</p>
                                    <p class="text-2xl font-semibold text-blue-900">{{ number_format($totalLogs) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-green-50 rounded-lg p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-green-600">Active Users</p>
                                    <p class="text-2xl font-semibold text-green-900">{{ $userStats->count() }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-yellow-50 rounded-lg p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-yellow-600">Unique Actions</p>
                                    <p class="text-2xl font-semibold text-yellow-900">{{ $actionStats->count() }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-purple-50 rounded-lg p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-purple-600">Resource Types</p>
                                    <p class="text-2xl font-semibold text-purple-900">{{ $resourceStats->count() }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        <!-- Top Actions Chart -->
                        <div class="bg-white border rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top 10 Actions</h3>
                            @if($actionStats->count() > 0)
                                <div class="space-y-3">
                                    @foreach($actionStats as $stat)
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-600">{{ ucwords(str_replace('_', ' ', $stat->action)) }}</span>
                                            <div class="flex items-center space-x-2">
                                                <div class="w-32 bg-gray-200 rounded-full h-2">
                                                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ ($stat->count / $actionStats->first()->count) * 100 }}%"></div>
                                                </div>
                                                <span class="text-sm font-semibold text-gray-900 w-12 text-right">{{ $stat->count }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500">No action data available</p>
                            @endif
                        </div>

                        <!-- Top Users Chart -->
                        <div class="bg-white border rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Most Active Users</h3>
                            @if($userStats->count() > 0)
                                <div class="space-y-3">
                                    @foreach($userStats as $stat)
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-600">{{ $stat->user->name ?? 'Unknown' }}</span>
                                            <div class="flex items-center space-x-2">
                                                <div class="w-32 bg-gray-200 rounded-full h-2">
                                                    <div class="bg-green-600 h-2 rounded-full" style="width: {{ ($stat->count / $userStats->first()->count) * 100 }}%"></div>
                                                </div>
                                                <span class="text-sm font-semibold text-gray-900 w-12 text-right">{{ $stat->count }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500">No user data available</p>
                            @endif
                        </div>
                    </div>

                    <!-- Resource Types and Daily Activity -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Resource Types -->
                        <div class="bg-white border rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Resource Types</h3>
                            @if($resourceStats->count() > 0)
                                <div class="space-y-3">
                                    @foreach($resourceStats as $stat)
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-600">{{ class_basename($stat->resource_type) }}</span>
                                            <div class="flex items-center space-x-2">
                                                <div class="w-32 bg-gray-200 rounded-full h-2">
                                                    <div class="bg-purple-600 h-2 rounded-full" style="width: {{ ($stat->count / $resourceStats->first()->count) * 100 }}%"></div>
                                                </div>
                                                <span class="text-sm font-semibold text-gray-900 w-12 text-right">{{ $stat->count }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500">No resource data available</p>
                            @endif
                        </div>

                        <!-- Daily Activity -->
                        <div class="bg-white border rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Daily Activity (Last 10 Days)</h3>
                            @if($dailyStats->count() > 0)
                                <div class="space-y-3">
                                    @foreach($dailyStats->take(10) as $stat)
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-600">{{ \Carbon\Carbon::parse($stat->date)->format('M j, Y') }}</span>
                                            <div class="flex items-center space-x-2">
                                                <div class="w-32 bg-gray-200 rounded-full h-2">
                                                    <div class="bg-yellow-600 h-2 rounded-full" style="width: {{ ($stat->count / $dailyStats->max('count')) * 100 }}%"></div>
                                                </div>
                                                <span class="text-sm font-semibold text-gray-900 w-12 text-right">{{ $stat->count }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500">No daily activity data available</p>
                            @endif
                        </div>
                    </div>

                    <!-- Data Tables -->
                    <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Actions Table -->
                        <div class="bg-white border rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions Breakdown</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Count</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($actionStats as $stat)
                                            <tr>
                                                <td class="px-4 py-2 text-sm text-gray-900">{{ ucwords(str_replace('_', ' ', $stat->action)) }}</td>
                                                <td class="px-4 py-2 text-sm text-gray-900">{{ $stat->count }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Users Table -->
                        <div class="bg-white border rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">User Activity</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Count</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($userStats as $stat)
                                            <tr>
                                                <td class="px-4 py-2 text-sm text-gray-900">{{ $stat->user->name ?? 'Unknown' }}</td>
                                                <td class="px-4 py-2 text-sm text-gray-900">{{ $stat->count }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Resources Table -->
                        <div class="bg-white border rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Resource Activity</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Resource</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Count</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($resourceStats as $stat)
                                            <tr>
                                                <td class="px-4 py-2 text-sm text-gray-900">{{ class_basename($stat->resource_type) }}</td>
                                                <td class="px-4 py-2 text-sm text-gray-900">{{ $stat->count }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>