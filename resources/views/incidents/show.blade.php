@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-2xl font-bold">Incident #{{ $incident->id }}</h2>
                        <p class="text-gray-600 dark:text-gray-400">
                            Created {{ $incident->created_at->format('M j, Y H:i') }}
                        </p>
                    </div>
                    
                    <div class="flex space-x-2">
                        <span class="px-3 py-1 rounded-full text-sm font-semibold 
                            {{ $incident->severity === 'low' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $incident->severity === 'medium' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $incident->severity === 'high' ? 'bg-orange-100 text-orange-800' : '' }}
                            {{ $incident->severity === 'critical' ? 'bg-red-100 text-red-800' : '' }}
                        ">
                            {{ ucfirst($incident->severity) }}
                        </span>
                        
                        <span class="px-3 py-1 rounded-full text-sm font-semibold 
                            {{ $incident->status === 'open' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $incident->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $incident->status === 'resolved' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $incident->status === 'closed' ? 'bg-gray-100 text-gray-800' : '' }}
                        ">
                            {{ str_replace('_', ' ', $incident->status) }}
                        </span>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Incident Details -->
                    <div class="lg:col-span-2">
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-6">
                            <h3 class="text-lg font-semibold mb-3">Incident Details</h3>
                            
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                    <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $incident->description }}</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                                    <p class="mt-1 text-gray-900 dark:text-gray-100">{{ ucfirst($incident->type) }}</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
                                    <p class="mt-1 text-gray-900 dark:text-gray-100">{{ ucfirst($incident->category) }}</p>
                                </div>
                                
                                @if($incident->bailMobilite)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bail Mobilité</label>
                                        <a href="{{ route('bail-mobilites.show', $incident->bailMobilite) }}" class="mt-1 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                            {{ $incident->bailMobilite->tenant_name }} - {{ $incident->bailMobilite->address }}
                                        </a>
                                    </div>
                                @endif
                                
                                @if($incident->mission)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Related Mission</label>
                                        <a href="{{ route('missions.show', $incident->mission) }}" class="mt-1 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                            {{ $incident->mission->type }} - {{ $incident->mission->address }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Corrective Actions -->
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold mb-3">Corrective Actions</h3>
                            
                            @if($incident->correctiveActions->count() > 0)
                                <div class="space-y-3">
                                    @foreach($incident->correctiveActions as $action)
                                        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-3">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <h4 class="font-medium">{{ $action->action }}</h4>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $action->description }}</p>
                                                </div>
                                                <span class="px-2 py-1 text-xs rounded-full 
                                                    {{ $action->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}
                                                ">
                                                    {{ ucfirst($action->status) }}
                                                </span>
                                            </div>
                                            <div class="mt-2 text-xs text-gray-500 dark:text-gray-500">
                                                Assigned to: {{ $action->assignedUser->name ?? 'Unassigned' }}
                                                • Due: {{ $action->due_date ? $action->due_date->format('M j, Y') : 'No due date' }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 dark:text-gray-400">No corrective actions defined yet.</p>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Status Update -->
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <h3 class="text-lg font-semibold mb-3">Update Status</h3>
                            
                            <form action="{{ route('incidents.update-status', $incident) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                
                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                                    <select name="status" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md">
                                        <option value="open" {{ $incident->status === 'open' ? 'selected' : '' }}>Open</option>
                                        <option value="in_progress" {{ $incident->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                        <option value="resolved" {{ $incident->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                                        <option value="closed" {{ $incident->status === 'closed' ? 'selected' : '' }}>Closed</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Severity</label>
                                    <select name="severity" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md">
                                        <option value="low" {{ $incident->severity === 'low' ? 'selected' : '' }}>Low</option>
                                        <option value="medium" {{ $incident->severity === 'medium' ? 'selected' : '' }}>Medium</option>
                                        <option value="high" {{ $incident->severity === 'high' ? 'selected' : '' }}>High</option>
                                        <option value="critical" {{ $incident->severity === 'critical' ? 'selected' : '' }}>Critical</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                                    Update Status
                                </button>
                            </form>
                        </div>
                        
                        <!-- Assignment -->
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <h3 class="text-lg font-semibold mb-3">Assignment</h3>
                            
                            <form action="{{ route('incidents.assign', $incident) }}" method="POST">
                                @csrf
                                
                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assign To</label>
                                    <select name="assigned_to" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md">
                                        <option value="">Select User</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ $incident->assigned_to === $user->id ? 'selected' : '' }}>
                                                {{ $user->name }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                                    Assign
                                </button>
                            </form>
                        </div>
                        
                        <!-- Timeline -->
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <h3 class="text-lg font-semibold mb-3">Timeline</h3>
                            
                            <div class="space-y-2">
                                <div class="text-sm">
                                    <span class="font-medium">Created:</span>
                                    <span class="text-gray-600 dark:text-gray-400">{{ $incident->created_at->format('M j, Y H:i') }}</span>
                                </div>
                                
                                @if($incident->updated_at->gt($incident->created_at))
                                    <div class="text-sm">
                                        <span class="font-medium">Last Updated:</span>
                                        <span class="text-gray-600 dark:text-gray-400">{{ $incident->updated_at->format('M j, Y H:i') }}</span>
                                    </div>
                                @endif
                                
                                @if($incident->resolved_at)
                                    <div class="text-sm">
                                        <span class="font-medium">Resolved:</span>
                                        <span class="text-gray-600 dark:text-gray-400">{{ $incident->resolved_at->format('M j, Y H:i') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection