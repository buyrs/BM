@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h2 class="text-2xl font-bold mb-6">Mission Validation - {{ $mission->type }} at {{ $mission->address }}</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h3 class="text-lg font-semibold mb-3">Mission Details</h3>
                        <div class="space-y-2">
                            <p><strong>Tenant:</strong> {{ $mission->tenant_name }}</p>
                            <p><strong>Address:</strong> {{ $mission->address }}</p>
                            <p><strong>Scheduled:</strong> {{ $mission->scheduled_at->format('M j, Y H:i') }}</p>
                            <p><strong>Assigned To:</strong> {{ $mission->agent->name ?? 'Unassigned' }}</p>
                            <p><strong>Status:</strong> 
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $mission->status === 'pending_validation' ? 'bg-purple-100 text-purple-800' : '' }}
                                ">
                                    {{ str_replace('_', ' ', $mission->status) }}
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-semibold mb-3">Validation</h3>
                        @if($mission->checklist)
                            <form action="{{ route('missions.validate-mission', $mission) }}" method="POST">
                                @csrf
                                
                                <div class="mb-4">
                                    <label for="validation_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Validation Decision
                                    </label>
                                    <select id="validation_status" name="validation_status" required class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Select decision</option>
                                        <option value="approved">Approve</option>
                                        <option value="rejected">Reject</option>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="validation_comments" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Comments
                                    </label>
                                    <textarea id="validation_comments" name="validation_comments" rows="3" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Add validation comments..."></textarea>
                                </div>
                                
                                <div class="flex space-x-3">
                                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                                        Submit Validation
                                    </button>
                                    <a href="{{ route('ops.dashboard') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        @else
                            <p class="text-red-600 dark:text-red-400">No checklist submitted for validation.</p>
                        @endif
                    </div>
                </div>
                
                @if($mission->checklist)
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold mb-3">Checklist Details</h3>
                        
                        <div class="mb-4">
                            <h4 class="font-medium mb-2">General Information</h4>
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-md">
                                <pre class="text-sm whitespace-pre-wrap">{{ json_encode($mission->checklist->general_info, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h4 class="font-medium mb-2">Rooms</h4>
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-md">
                                <pre class="text-sm whitespace-pre-wrap">{{ json_encode($mission->checklist->rooms, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h4 class="font-medium mb-2">Utilities</h4>
                            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-md">
                                <pre class="text-sm whitespace-pre-wrap">{{ json_encode($mission->checklist->utilities, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                        
                        @if($mission->checklist->items->count() > 0)
                            <div class="mb-4">
                                <h4 class="font-medium mb-2">Photos</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach($mission->checklist->items as $item)
                                        @foreach($item->photos as $photo)
                                            <div class="border rounded-md p-2">
                                                <img src="{{ Storage::url($photo->photo_path) }}" alt="Checklist photo" class="w-full h-32 object-cover rounded">
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $item->item_name }}</p>
                                            </div>
                                        @endforeach
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection