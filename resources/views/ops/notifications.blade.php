@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h2 class="text-2xl font-bold mb-6">Notifications</h2>
                
                <!-- Filters -->
                <div class="mb-6 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                            <select name="status" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md">
                                <option value="">All Status</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>Read</option>
                                <option value="archived" {{ request('status') == 'archived' ? 'selected' : '' }}>Archived</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                            <select name="type" class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md">
                                <option value="">All Types</option>
                                <option value="mission" {{ request('type') == 'mission' ? 'selected' : '' }}>Mission</option>
                                <option value="signature" {{ request('type') == 'signature' ? 'selected' : '' }}>Signature</option>
                                <option value="incident" {{ request('type') == 'incident' ? 'selected' : '' }}>Incident</option>
                                <option value="system" {{ request('type') == 'system' ? 'selected' : '' }}>System</option>
                            </select>
                        </div>
                        
                        <div class="flex items-end">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                                Filter
                            </button>
                            <a href="{{ route('ops.notifications') }}" class="ml-2 bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                                Clear
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Notifications List -->
                @if($notifications->count() > 0)
                    <div class="space-y-4">
                        @foreach($notifications as $notification)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 {{ $notification->status === 'pending' ? 'bg-yellow-50 dark:bg-yellow-900' : 'bg-white dark:bg-gray-800' }}">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $notification->title }}
                                        </h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ $notification->message }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                                            {{ $notification->created_at->format('M j, Y H:i') }}
                                            • {{ ucfirst($notification->type) }}
                                            • {{ ucfirst($notification->status) }}
                                        </p>
                                    </div>
                                    
                                    <div class="flex space-x-2">
                                        @if($notification->status === 'pending')
                                            <form action="{{ route('notifications.mark-read', $notification) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300">
                                                    Mark Read
                                                </button>
                                            </form>
                                        @endif
                                        
                                        <form action="{{ route('notifications.archive', $notification) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-300">
                                                Archive
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                
                                @if($notification->bail_mobilite_id)
                                    <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                        <a href="{{ route('bail-mobilites.show', $notification->bail_mobilite_id) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm">
                                            View Bail Mobilité
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="mt-6">
                        {{ $notifications->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <p class="text-gray-500 dark:text-gray-400">No notifications found.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection