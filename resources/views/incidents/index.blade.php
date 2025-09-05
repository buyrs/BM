@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h2 class="text-2xl font-bold mb-6">Incident Reports</h2>
                
                <!-- Filters -->
                <div class="mb-6 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                            <select name="status" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md">
                                <option value="">All Status</option>
                                <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                                <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Severity</label>
                            <select name="severity" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md">
                                <option value="">All Severity</option>
                                <option value="low" {{ request('severity') == 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ request('severity') == 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ request('severity') == 'high' ? 'selected' : '' }}>High</option>
                                <option value="critical" {{ request('severity') == 'critical' ? 'selected' : '' }}>Critical</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bail Mobilité</label>
                            <select name="bail_mobilite_id" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md">
                                <option value="">All Bail Mobilités</option>
                                @foreach($bailMobilites as $bm)
                                    <option value="{{ $bm->id }}" {{ request('bail_mobilite_id') == $bm->id ? 'selected' : '' }}>
                                        {{ $bm->tenant_name }} - {{ $bm->address }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="flex items-end">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                                Filter
                            </button>
                            <a href="{{ route('incidents.index') }}" class="ml-2 bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                                Clear
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Incidents Table -->
                @if($incidents->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Severity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Created</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($incidents as $incident)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            #{{ $incident->id }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ Str::limit($incident->description, 50) }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $incident->bailMobilite->tenant_name ?? 'N/A' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                {{ $incident->severity === 'low' ? 'bg-green-100 text-green-800' : '' }}
                                                {{ $incident->severity === 'medium' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                {{ $incident->severity === 'high' ? 'bg-orange-100 text-orange-800' : '' }}
                                                {{ $incident->severity === 'critical' ? 'bg-red-100 text-red-800' : '' }}
                                            ">
                                                {{ ucfirst($incident->severity) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                {{ $incident->status === 'open' ? 'bg-blue-100 text-blue-800' : '' }}
                                                {{ $incident->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                {{ $incident->status === 'resolved' ? 'bg-green-100 text-green-800' : '' }}
                                                {{ $incident->status === 'closed' ? 'bg-gray-100 text-gray-800' : '' }}
                                            ">
                                                {{ str_replace('_', ' ', $incident->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                            {{ $incident->created_at->format('M j, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('incidents.show', $incident) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $incidents->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <p class="text-gray-500 dark:text-gray-400">No incidents found.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection