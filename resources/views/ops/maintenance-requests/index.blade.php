<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Maintenance Requests') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Status Filter Tabs -->
            <div class="mb-6">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8">
                        <a href="{{ route('ops.maintenance-requests.index', ['status' => 'pending']) }}" 
                           class="py-2 px-1 border-b-2 font-medium text-sm {{ $status === 'pending' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Pending ({{ $statusCounts['pending'] }})
                        </a>
                        <a href="{{ route('ops.maintenance-requests.index', ['status' => 'approved']) }}" 
                           class="py-2 px-1 border-b-2 font-medium text-sm {{ $status === 'approved' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Approved ({{ $statusCounts['approved'] }})
                        </a>
                        <a href="{{ route('ops.maintenance-requests.index', ['status' => 'in_progress']) }}" 
                           class="py-2 px-1 border-b-2 font-medium text-sm {{ $status === 'in_progress' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            In Progress ({{ $statusCounts['in_progress'] }})
                        </a>
                        <a href="{{ route('ops.maintenance-requests.index', ['status' => 'completed']) }}" 
                           class="py-2 px-1 border-b-2 font-medium text-sm {{ $status === 'completed' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Completed ({{ $statusCounts['completed'] }})
                        </a>
                        <a href="{{ route('ops.maintenance-requests.index', ['status' => 'all']) }}" 
                           class="py-2 px-1 border-b-2 font-medium text-sm {{ $status === 'all' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            All
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Priority Filter -->
            <div class="mb-6">
                <form method="GET" class="flex items-center space-x-4">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <select name="priority" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <option value="">All Priorities</option>
                        <option value="urgent" {{ $priority === 'urgent' ? 'selected' : '' }}>Urgent</option>
                        <option value="high" {{ $priority === 'high' ? 'selected' : '' }}>High</option>
                        <option value="medium" {{ $priority === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="low" {{ $priority === 'low' ? 'selected' : '' }}>Low</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Filter
                    </button>
                </form>
            </div>

            <!-- Maintenance Requests List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($maintenanceRequests->count() > 0)
                        <div class="space-y-4">
                            @foreach($maintenanceRequests as $request)
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2 mb-2">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                    {{ $request->priority === 'urgent' ? 'bg-red-100 text-red-800' : '' }}
                                                    {{ $request->priority === 'high' ? 'bg-orange-100 text-orange-800' : '' }}
                                                    {{ $request->priority === 'medium' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                    {{ $request->priority === 'low' ? 'bg-green-100 text-green-800' : '' }}">
                                                    {{ ucfirst($request->priority) }}
                                                </span>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                    {{ $request->status === 'pending' ? 'bg-gray-100 text-gray-800' : '' }}
                                                    {{ $request->status === 'approved' ? 'bg-blue-100 text-blue-800' : '' }}
                                                    {{ $request->status === 'in_progress' ? 'bg-indigo-100 text-indigo-800' : '' }}
                                                    {{ $request->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $request->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                                                    {{ ucfirst(str_replace('_', ' ', $request->status)) }}
                                                </span>
                                            </div>
                                            
                                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                                {{ $request->mission->property->name }}
                                            </h3>
                                            
                                            <p class="text-gray-600 mb-2">{{ $request->description }}</p>
                                            
                                            <div class="text-sm text-gray-500 space-y-1">
                                                <p><strong>Reported by:</strong> {{ $request->reportedBy->name }}</p>
                                                @if($request->assignedTo)
                                                    <p><strong>Assigned to:</strong> {{ $request->assignedTo->name }}</p>
                                                @endif
                                                @if($request->estimated_cost)
                                                    <p><strong>Estimated Cost:</strong> ${{ number_format($request->estimated_cost, 2) }}</p>
                                                @endif
                                                <p><strong>Created:</strong> {{ $request->created_at->format('M j, Y g:i A') }}</p>
                                                @if($request->completed_at)
                                                    <p><strong>Completed:</strong> {{ $request->completed_at->format('M j, Y g:i A') }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <div class="ml-4">
                                            <a href="{{ route('ops.maintenance-requests.show', $request) }}" 
                                               class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                                View Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $maintenanceRequests->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-500">No maintenance requests found for the selected filters.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>