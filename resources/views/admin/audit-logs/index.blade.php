<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Audit Logs') }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('admin.audit-logs.statistics') }}" 
                   class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Statistics
                </a>
                <a href="{{ route('admin.audit-logs.suspicious') }}" 
                   class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                    Suspicious Activity
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <!-- Filters -->
                    <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <!-- User Filter -->
                                <div>
                                    <label for="user_id" class="block text-sm font-medium text-gray-700">User</label>
                                    <select name="user_id" id="user_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                        <option value="">All Users</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ (request('user_id') == $user->id) ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Action Filter -->
                                <div>
                                    <label for="action" class="block text-sm font-medium text-gray-700">Action</label>
                                    <select name="action" id="action" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                        <option value="">All Actions</option>
                                        @foreach($actions as $action)
                                            <option value="{{ $action }}" {{ (request('action') == $action) ? 'selected' : '' }}>
                                                {{ ucwords(str_replace('_', ' ', $action)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Resource Type Filter -->
                                <div>
                                    <label for="resource_type" class="block text-sm font-medium text-gray-700">Resource Type</label>
                                    <select name="resource_type" id="resource_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                        <option value="">All Types</option>
                                        @foreach($resourceTypes as $type)
                                            <option value="{{ $type['value'] }}" {{ (request('resource_type') == $type['value']) ? 'selected' : '' }}>
                                                {{ $type['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Start Date -->
                                <div>
                                    <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                                    <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}" 
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                </div>

                                <!-- End Date -->
                                <div>
                                    <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                                    <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}" 
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                </div>

                                <!-- Search -->
                                <div>
                                    <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                                    <input type="text" name="search" id="search" value="{{ request('search') }}" 
                                           placeholder="Search in actions, resources, or changes..."
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                </div>
                            </div>

                            <div class="flex justify-between items-center">
                                <div class="flex space-x-2">
                                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        Filter
                                    </button>
                                    <a href="{{ route('admin.audit-logs.index') }}" 
                                       class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                        Clear
                                    </a>
                                </div>

                                <!-- Export Options -->
                                <div class="flex space-x-2">
                                    <a href="{{ route('admin.audit-logs.export', array_merge(request()->query(), ['format' => 'csv'])) }}" 
                                       class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                        Export CSV
                                    </a>
                                    <a href="{{ route('admin.audit-logs.export', array_merge(request()->query(), ['format' => 'json'])) }}" 
                                       class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                                        Export JSON
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Results Summary -->
                    <div class="mb-4 text-sm text-gray-600">
                        Showing {{ $auditLogs->firstItem() ?? 0 }} to {{ $auditLogs->lastItem() ?? 0 }} 
                        of {{ $auditLogs->total() }} results
                    </div>

                    <!-- Audit Logs Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Time
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        User
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Action
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Resource
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        IP Address
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($auditLogs as $log)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $log->created_at->format('Y-m-d H:i:s') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $log->user->name ?? 'System' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                @if(str_contains($log->action, 'failed') || str_contains($log->action, 'suspicious'))
                                                    bg-red-100 text-red-800
                                                @elseif(str_contains($log->action, 'login') || str_contains($log->action, 'created'))
                                                    bg-green-100 text-green-800
                                                @elseif(str_contains($log->action, 'updated'))
                                                    bg-yellow-100 text-yellow-800
                                                @elseif(str_contains($log->action, 'deleted'))
                                                    bg-red-100 text-red-800
                                                @else
                                                    bg-gray-100 text-gray-800
                                                @endif
                                            ">
                                                {{ ucwords(str_replace('_', ' ', $log->action)) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            @if($log->resource_type)
                                                {{ class_basename($log->resource_type) }}
                                                @if($log->resource_id)
                                                    #{{ $log->resource_id }}
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $log->ip_address ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('admin.audit-logs.show', $log->id) }}" 
                                               class="text-indigo-600 hover:text-indigo-900">
                                                View Details
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            No audit logs found matching your criteria.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $auditLogs->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>