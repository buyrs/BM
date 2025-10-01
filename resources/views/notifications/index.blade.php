<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Notifications') }}
            </h2>
            <div class="flex items-center space-x-4">
                <button id="mark-all-read" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    Mark All as Read
                </button>
                <button id="enable-notifications" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    Enable Browser Notifications
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Filter Tabs -->
            <div class="mb-6">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8">
                        <a href="{{ route('notifications.index', ['filter' => 'all']) }}" 
                           class="py-2 px-1 border-b-2 font-medium text-sm {{ $filter === 'all' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            All ({{ $counts['all'] }})
                        </a>
                        <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" 
                           class="py-2 px-1 border-b-2 font-medium text-sm {{ $filter === 'unread' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Unread ({{ $counts['unread'] }})
                        </a>
                        <a href="{{ route('notifications.index', ['filter' => 'action_required']) }}" 
                           class="py-2 px-1 border-b-2 font-medium text-sm {{ $filter === 'action_required' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Action Required ({{ $counts['action_required'] }})
                        </a>
                        <a href="{{ route('notifications.index', ['filter' => 'read']) }}" 
                           class="py-2 px-1 border-b-2 font-medium text-sm {{ $filter === 'read' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Read
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Notifications List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($notifications->count() > 0)
                        <div class="space-y-4" id="notifications-list">
                            @foreach($notifications as $notification)
                                <div class="notification-item border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow {{ !$notification->isRead() ? 'bg-blue-50 border-blue-200' : '' }}" 
                                     data-notification-id="{{ $notification->id }}">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2 mb-2">
                                                @if(!$notification->isRead())
                                                    <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                                @endif
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                    {{ $notification->priority === 'urgent' ? 'bg-red-100 text-red-800' : '' }}
                                                    {{ $notification->priority === 'high' ? 'bg-orange-100 text-orange-800' : '' }}
                                                    {{ $notification->priority === 'medium' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                    {{ $notification->priority === 'low' ? 'bg-green-100 text-green-800' : '' }}">
                                                    {{ ucfirst($notification->priority) }}
                                                </span>
                                                @if($notification->requires_action && !$notification->isActionTaken())
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                                        Action Required
                                                    </span>
                                                @endif
                                            </div>
                                            
                                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                                {{ $notification->title }}
                                            </h3>
                                            
                                            <p class="text-gray-600 mb-2">{{ $notification->message }}</p>
                                            
                                            <div class="text-sm text-gray-500">
                                                <p>{{ $notification->created_at->diffForHumans() }}</p>
                                                @if($notification->mission)
                                                    <p><strong>Property:</strong> {{ $notification->mission->property->name }}</p>
                                                @endif
                                                @if($notification->checklist)
                                                    <p><strong>Checklist:</strong> {{ $notification->checklist->name }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <div class="ml-4 flex flex-col space-y-2">
                                            @if(!$notification->isRead())
                                                <button class="mark-as-read px-3 py-1 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700"
                                                        data-notification-id="{{ $notification->id }}">
                                                    Mark as Read
                                                </button>
                                            @endif
                                            
                                            @if($notification->requires_action && !$notification->isActionTaken())
                                                <button class="mark-action-taken px-3 py-1 bg-purple-600 text-white text-sm rounded-md hover:bg-purple-700"
                                                        data-notification-id="{{ $notification->id }}">
                                                    Mark Action Taken
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $notifications->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-500">No notifications found for the selected filter.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Sound -->
    <audio id="notification-sound" preload="auto">
        <source src="{{ asset('sounds/notification.mp3') }}" type="audio/mpeg">
        <source src="{{ asset('sounds/notification.ogg') }}" type="audio/ogg">
    </audio>

    @push('scripts')
    <script>
        // Notification management
        document.addEventListener('DOMContentLoaded', function() {
            // Mark individual notification as read
            document.querySelectorAll('.mark-as-read').forEach(button => {
                button.addEventListener('click', function() {
                    const notificationId = this.dataset.notificationId;
                    markAsRead(notificationId);
                });
            });

            // Mark action as taken
            document.querySelectorAll('.mark-action-taken').forEach(button => {
                button.addEventListener('click', function() {
                    const notificationId = this.dataset.notificationId;
                    markActionTaken(notificationId);
                });
            });

            // Mark all as read
            document.getElementById('mark-all-read').addEventListener('click', function() {
                markAllAsRead();
            });

            // Enable browser notifications
            document.getElementById('enable-notifications').addEventListener('click', function() {
                enableBrowserNotifications();
            });
        });

        function markAsRead(notificationId) {
            fetch(`/notifications/${notificationId}/mark-as-read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
                    notificationElement.classList.remove('bg-blue-50', 'border-blue-200');
                    notificationElement.querySelector('.mark-as-read').remove();
                    const unreadIndicator = notificationElement.querySelector('.w-2.h-2.bg-blue-500');
                    if (unreadIndicator) {
                        unreadIndicator.remove();
                    }
                }
            });
        }

        function markActionTaken(notificationId) {
            fetch(`/notifications/${notificationId}/mark-action-taken`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
                    notificationElement.querySelector('.mark-action-taken').remove();
                    const actionRequiredBadge = notificationElement.querySelector('.bg-purple-100');
                    if (actionRequiredBadge) {
                        actionRequiredBadge.remove();
                    }
                }
            });
        }

        function markAllAsRead() {
            fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }

        function enableBrowserNotifications() {
            if ('Notification' in window) {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        // Store permission in user preferences
                        fetch('/notifications/enable-browser', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                        
                        // Show success message
                        new Notification('Notifications Enabled', {
                            body: 'You will now receive browser notifications.',
                            icon: '{{ asset("images/notification-icon.png") }}'
                        });
                    }
                });
            }
        }

        // Real-time notification handling
        if ('Notification' in window && Notification.permission === 'granted') {
            // Listen for real-time notifications via WebSocket/Pusher
            // This would be implemented with your WebSocket solution
            window.Echo?.private(`user.{{ auth()->id() }}`)
                .listen('NotificationSent', (e) => {
                    showBrowserNotification(e.notification);
                    playNotificationSound(e.notification.priority);
                    updateNotificationCounts();
                });
        }

        function showBrowserNotification(notification) {
            if ('Notification' in window && Notification.permission === 'granted') {
                const browserNotification = new Notification(notification.title, {
                    body: notification.message,
                    icon: '{{ asset("images/notification-icon.png") }}',
                    tag: `notification-${notification.id}`,
                    requireInteraction: notification.priority === 'urgent'
                });

                browserNotification.onclick = function() {
                    window.focus();
                    // Navigate to relevant page based on notification type
                    if (notification.data.url) {
                        window.location.href = notification.data.url;
                    }
                    this.close();
                };
            }
        }

        function playNotificationSound(priority) {
            const audio = document.getElementById('notification-sound');
            if (audio) {
                audio.volume = priority === 'urgent' ? 1.0 : 0.7;
                audio.play().catch(e => console.log('Could not play notification sound:', e));
            }
        }

        function updateNotificationCounts() {
            fetch('/notifications/counts')
                .then(response => response.json())
                .then(data => {
                    // Update notification counts in the UI
                    document.querySelectorAll('[data-notification-count]').forEach(element => {
                        const type = element.dataset.notificationCount;
                        if (data[type] !== undefined) {
                            element.textContent = data[type];
                        }
                    });
                });
        }
    </script>
    @endpush
</x-app-layout>