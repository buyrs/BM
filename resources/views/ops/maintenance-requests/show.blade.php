<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Maintenance Request Details') }}
            </h2>
            <a href="{{ route('ops.maintenance-requests.index') }}" 
               class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                Back to List
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <!-- Status and Priority -->
                    <div class="flex items-center space-x-4 mb-6">
                        <span class="px-3 py-1 text-sm font-semibold rounded-full
                            {{ $maintenanceRequest->priority === 'urgent' ? 'bg-red-100 text-red-800' : '' }}
                            {{ $maintenanceRequest->priority === 'high' ? 'bg-orange-100 text-orange-800' : '' }}
                            {{ $maintenanceRequest->priority === 'medium' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $maintenanceRequest->priority === 'low' ? 'bg-green-100 text-green-800' : '' }}">
                            {{ ucfirst($maintenanceRequest->priority) }} Priority
                        </span>
                        <span class="px-3 py-1 text-sm font-semibold rounded-full
                            {{ $maintenanceRequest->status === 'pending' ? 'bg-gray-100 text-gray-800' : '' }}
                            {{ $maintenanceRequest->status === 'approved' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $maintenanceRequest->status === 'in_progress' ? 'bg-indigo-100 text-indigo-800' : '' }}
                            {{ $maintenanceRequest->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $maintenanceRequest->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ ucfirst(str_replace('_', ' ', $maintenanceRequest->status)) }}
                        </span>
                    </div>

                    <!-- Property and Mission Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Property Information</h3>
                            <div class="space-y-2 text-sm">
                                <p><strong>Property:</strong> {{ $maintenanceRequest->mission->property->name }}</p>
                                <p><strong>Address:</strong> {{ $maintenanceRequest->mission->property->address }}</p>
                                <p><strong>Mission ID:</strong> {{ $maintenanceRequest->mission->id }}</p>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Request Information</h3>
                            <div class="space-y-2 text-sm">
                                <p><strong>Reported by:</strong> {{ $maintenanceRequest->reportedBy->name }}</p>
                                @if($maintenanceRequest->assignedTo)
                                    <p><strong>Assigned to:</strong> {{ $maintenanceRequest->assignedTo->name }}</p>
                                @endif
                                @if($maintenanceRequest->estimated_cost)
                                    <p><strong>Estimated Cost:</strong> ${{ number_format($maintenanceRequest->estimated_cost, 2) }}</p>
                                @endif
                                <p><strong>Created:</strong> {{ $maintenanceRequest->created_at->format('M j, Y g:i A') }}</p>
                                @if($maintenanceRequest->completed_at)
                                    <p><strong>Completed:</strong> {{ $maintenanceRequest->completed_at->format('M j, Y g:i A') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Description</h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-gray-700">{{ $maintenanceRequest->description }}</p>
                        </div>
                    </div>

                    <!-- Checklist Item (if applicable) -->
                    @if($maintenanceRequest->checklistItem)
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Related Checklist Item</h3>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p><strong>Checklist:</strong> {{ $maintenanceRequest->checklist->name }}</p>
                                <p><strong>Item:</strong> {{ $maintenanceRequest->checklistItem->description }}</p>
                            </div>
                        </div>
                    @endif

                    <!-- Notes -->
                    @if($maintenanceRequest->notes)
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Notes</h3>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-gray-700">{{ $maintenanceRequest->notes }}</p>
                            </div>
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
                        
                        @if($maintenanceRequest->isPending())
                            <div class="flex flex-wrap gap-4">
                                <!-- Approve Form -->
                                <form method="POST" action="{{ route('ops.maintenance-requests.approve', $maintenanceRequest) }}" class="inline">
                                    @csrf
                                    <div class="flex items-center space-x-2">
                                        <select name="assigned_to" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                            <option value="">Assign to...</option>
                                            @foreach($opsUsers as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                            Approve
                                        </button>
                                    </div>
                                </form>

                                <!-- Reject Form -->
                                <form method="POST" action="{{ route('ops.maintenance-requests.reject', $maintenanceRequest) }}" class="inline">
                                    @csrf
                                    <div class="flex items-center space-x-2">
                                        <input type="text" name="reason" placeholder="Rejection reason..." required
                                               class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                            Reject
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif

                        @if($maintenanceRequest->isApproved())
                            <div class="flex flex-wrap gap-4">
                                <!-- Start Work -->
                                <form method="POST" action="{{ route('ops.maintenance-requests.start-work', $maintenanceRequest) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                        Start Work
                                    </button>
                                </form>

                                <!-- Update Assignment -->
                                <form method="POST" action="{{ route('ops.maintenance-requests.update-assignment', $maintenanceRequest) }}" class="inline">
                                    @csrf
                                    <div class="flex items-center space-x-2">
                                        <select name="assigned_to" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                            @foreach($opsUsers as $user)
                                                <option value="{{ $user->id }}" {{ $maintenanceRequest->assigned_to === $user->id ? 'selected' : '' }}>
                                                    {{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                                            Update Assignment
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif

                        @if($maintenanceRequest->isInProgress())
                            <!-- Complete Form -->
                            <form method="POST" action="{{ route('ops.maintenance-requests.complete', $maintenanceRequest) }}" class="inline">
                                @csrf
                                <div class="flex items-start space-x-2">
                                    <textarea name="notes" placeholder="Completion notes (optional)..." rows="3"
                                              class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                        Complete
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>