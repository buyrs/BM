<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Suspicious Activity') }}
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
                    <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <form method="GET" action="{{ route('admin.audit-logs.suspicious') }}" class="flex items-center space-x-4">
                            <div>
                                <label for="days" class="block text-sm font-medium text-gray-700">Analysis Period</label>
                                <select name="days" id="days" class="mt-1 block rounded-md border-gray-300 shadow-sm">
                                    <option value="1" {{ $days == 1 ? 'selected' : '' }}>Last 24 hours</option>
                                    <option value="7" {{ $days == 7 ? 'selected' : '' }}>Last 7 days</option>
                                    <option value="30" {{ $days == 30 ? 'selected' : '' }}>Last 30 days</option>
                                    <option value="90" {{ $days == 90 ? 'selected' : '' }}>Last 90 days</option>
                                </select>
                            </div>
                            <div class="pt-6">
                                <button type="submit" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                                    Update
                                </button>
                            </div>
                        </form>
                    </div>

                    @if($suspiciousLogs->count() > 0)
                        <!-- Alert Summary -->
                        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">
                                        {{ $suspiciousLogs->count() }} Suspicious Activities Detected
                                    </h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <p>The following activities have been flagged as potentially suspicious in the last {{ $days }} days. Please review and investigate as necessary.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Activity Breakdown -->
                        <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                            @php
                                $activityTypes = $suspiciousLogs->groupBy('action');
                            @endphp
                            
                            @foreach($activityTypes as $action => $logs)
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="font-semibold text-gray-900">{{ ucwords(str_replace('_', ' ', $action)) }}</h4>
                                    <p class="text-2xl font-bold text-red-600">{{ $logs->count() }}</p>
                                    <p class="text-sm text-gray-600">incidents</p>
                                </div>
                            @endforeach
                        </div>

                        <!-- Suspicious Activities Table -->
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
                                            Activity Type
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            IP Address
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Severity
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($suspiciousLogs as $log)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                                                <div class="text-xs text-gray-500">{{ $log->created_at->diffForHumans() }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $log->user->name ?? 'Unknown' }}
                                                @if($log->user)
                                                    <div class="text-xs text-gray-500">ID: {{ $log->user->id }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    {{ ucwords(str_replace('_', ' ', $log->action)) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $log->ip_address ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @php
                                                    $severity = 'medium';
                                                    if (str_contains($log->action, 'privilege_escalation') || str_contains($log->action, 'bulk_delete')) {
                                                        $severity = 'high';
                                                    } elseif (str_contains($log->action, 'multiple_failed_logins')) {
                                                        $severity = 'low';
                                                    }
                                                @endphp
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    @if($severity === 'high') bg-red-100 text-red-800
                                                    @elseif($severity === 'medium') bg-yellow-100 text-yellow-800
                                                    @else bg-orange-100 text-orange-800
                                                    @endif
                                                ">
                                                    {{ ucfirst($severity) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="{{ route('admin.audit-logs.show', $log->id) }}" 
                                                   class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                    View Details
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Recommendations -->
                        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-blue-800 mb-2">Security Recommendations</h3>
                            <ul class="text-sm text-blue-700 space-y-1">
                                <li>• Review user accounts with multiple failed login attempts</li>
                                <li>• Investigate privilege escalation attempts immediately</li>
                                <li>• Monitor IP addresses with suspicious activity patterns</li>
                                <li>• Consider implementing additional security measures for flagged users</li>
                                <li>• Review and update access controls as necessary</li>
                            </ul>
                        </div>

                    @else
                        <!-- No Suspicious Activity -->
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No Suspicious Activity</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                No suspicious activities have been detected in the last {{ $days }} days.
                            </p>
                            <div class="mt-6 bg-green-50 border border-green-200 rounded-lg p-4">
                                <p class="text-sm text-green-700">
                                    ✓ All user activities appear normal<br>
                                    ✓ No failed login attempts detected<br>
                                    ✓ No privilege escalation attempts<br>
                                    ✓ No unusual activity patterns identified
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>