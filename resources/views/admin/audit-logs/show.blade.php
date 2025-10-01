<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Audit Log Details') }}
            </h2>
            <a href="{{ route('admin.audit-logs.index') }}" 
               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to List
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Basic Information -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Basic Information</h3>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">ID</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $auditLog->id }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">User</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ $auditLog->user->name ?? 'System' }}
                                    @if($auditLog->user)
                                        <span class="text-gray-500">(ID: {{ $auditLog->user->id }})</span>
                                    @endif
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Action</label>
                                <p class="mt-1">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        @if(str_contains($auditLog->action, 'failed') || str_contains($auditLog->action, 'suspicious'))
                                            bg-red-100 text-red-800
                                        @elseif(str_contains($auditLog->action, 'login') || str_contains($auditLog->action, 'created'))
                                            bg-green-100 text-green-800
                                        @elseif(str_contains($auditLog->action, 'updated'))
                                            bg-yellow-100 text-yellow-800
                                        @elseif(str_contains($auditLog->action, 'deleted'))
                                            bg-red-100 text-red-800
                                        @else
                                            bg-gray-100 text-gray-800
                                        @endif
                                    ">
                                        {{ ucwords(str_replace('_', ' ', $auditLog->action)) }}
                                    </span>
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Timestamp</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ $auditLog->created_at->format('Y-m-d H:i:s T') }}
                                    <span class="text-gray-500">({{ $auditLog->created_at->diffForHumans() }})</span>
                                </p>
                            </div>
                        </div>

                        <!-- Resource Information -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Resource Information</h3>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Resource Type</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    {{ $auditLog->resource_type ? class_basename($auditLog->resource_type) : 'N/A' }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Resource ID</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $auditLog->resource_id ?? 'N/A' }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">IP Address</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $auditLog->ip_address ?? 'N/A' }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">User Agent</label>
                                <p class="mt-1 text-sm text-gray-900 break-all">
                                    {{ $auditLog->user_agent ?? 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Changes Information -->
                    @if($auditLog->changes)
                        <div class="mt-8">
                            <h3 class="text-lg font-semibold text-gray-900 border-b pb-2 mb-4">Changes/Details</h3>
                            
                            <div class="bg-gray-50 rounded-lg p-4">
                                <pre class="text-sm text-gray-800 whitespace-pre-wrap">{{ json_encode($auditLog->changes, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                    @endif

                    <!-- Suspicious Activity Warning -->
                    @if(str_contains($auditLog->action, 'suspicious') || str_contains($auditLog->action, 'multiple_failed') || str_contains($auditLog->action, 'privilege_escalation'))
                        <div class="mt-8 bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">
                                        Suspicious Activity Detected
                                    </h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <p>This audit log entry has been flagged as potentially suspicious activity. Please review the details carefully and take appropriate action if necessary.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Related Logs -->
                    @if($auditLog->user_id)
                        <div class="mt-8">
                            <h3 class="text-lg font-semibold text-gray-900 border-b pb-2 mb-4">Recent Activity by Same User</h3>
                            
                            @php
                                $recentLogs = \App\Models\AuditLog::with('user')
                                    ->where('user_id', $auditLog->user_id)
                                    ->where('id', '!=', $auditLog->id)
                                    ->where('created_at', '>=', $auditLog->created_at->subHours(2))
                                    ->where('created_at', '<=', $auditLog->created_at->addHours(2))
                                    ->orderBy('created_at', 'desc')
                                    ->limit(5)
                                    ->get();
                            @endphp

                            @if($recentLogs->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resource</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($recentLogs as $relatedLog)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {{ $relatedLog->created_at->format('H:i:s') }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {{ ucwords(str_replace('_', ' ', $relatedLog->action)) }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        @if($relatedLog->resource_type)
                                                            {{ class_basename($relatedLog->resource_type) }}
                                                            @if($relatedLog->resource_id)
                                                                #{{ $relatedLog->resource_id }}
                                                            @endif
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <a href="{{ route('admin.audit-logs.show', $relatedLog->id) }}" 
                                                           class="text-indigo-600 hover:text-indigo-900">
                                                            View
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-gray-500">No related activity found within 2 hours of this log entry.</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>