@props(['user'])

@php
    $unreadCount = $user->unreadNotifications()->count();
    $actionRequiredCount = $user->actionRequiredNotifications()->count();
@endphp

<div class="relative">
    <a href="{{ route('notifications.index') }}" class="relative p-2 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
        <!-- Bell Icon -->
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
        </svg>
        
        <!-- Notification Badge -->
        @if($unreadCount > 0)
            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
        
        <!-- Action Required Indicator -->
        @if($actionRequiredCount > 0)
            <span class="absolute -bottom-1 -right-1 w-3 h-3 bg-orange-500 rounded-full border-2 border-white"></span>
        @endif
    </a>
    
    <!-- Dropdown Menu -->
    <div class="notification-dropdown absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50 hidden">
        <div class="py-1">
            <div class="px-4 py-2 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium text-gray-900">Notifications</h3>
                    <a href="{{ route('notifications.index') }}" class="text-xs text-indigo-600 hover:text-indigo-500">View All</a>
                </div>
            </div>
            
            <div class="max-h-64 overflow-y-auto" id="notification-preview-list">
                <!-- Notifications will be loaded here via JavaScript -->
                <div class="px-4 py-3 text-sm text-gray-500 text-center">
                    Loading notifications...
                </div>
            </div>
            
            @if($unreadCount > 0)
                <div class="px-4 py-2 border-t border-gray-200">
                    <button id="mark-all-read-dropdown" class="w-full text-center text-xs text-indigo-600 hover:text-indigo-500">
                        Mark All as Read
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const notificationBell = document.querySelector('.relative');
    const dropdown = document.querySelector('.notification-dropdown');
    let isDropdownOpen = false;

    // Toggle dropdown
    notificationBell.addEventListener('click', function(e) {
        if (e.target.closest('a[href="{{ route('notifications.index') }}"]') && !e.target.closest('.notification-dropdown')) {
            return; // Allow normal navigation
        }
        
        e.preventDefault();
        e.stopPropagation();
        
        if (isDropdownOpen) {
            closeDropdown();
        } else {
            openDropdown();
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!notificationBell.contains(e.target)) {
            closeDropdown();
        }
    });

    function openDropdown() {
        dropdown.classList.remove('hidden');
        isDropdownOpen = true;
        loadNotificationPreview();
    }

    function closeDropdown() {
        dropdown.classList.add('hidden');
        isDropdownOpen = false;
    }

    function loadNotificationPreview() {
        fetch('/api/notifications?limit=5&unread_only=false')
            .then(response => response.json())
            .then(data => {
                const list = document.getElementById('notification-preview-list');
                
                if (data.notifications.length === 0) {
                    list.innerHTML = '<div class="px-4 py-3 text-sm text-gray-500 text-center">No notifications</div>';
                    return;
                }

                list.innerHTML = data.notifications.map(notification => `
                    <div class="px-4 py-3 hover:bg-gray-50 ${!notification.is_read ? 'bg-blue-50' : ''}">
                        <div class="flex items-start space-x-3">
                            ${!notification.is_read ? '<div class="w-2 h-2 bg-blue-500 rounded-full mt-2 flex-shrink-0"></div>' : '<div class="w-2 h-2 flex-shrink-0"></div>'}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">${notification.title}</p>
                                <p class="text-xs text-gray-500 truncate">${notification.message}</p>
                                <p class="text-xs text-gray-400">${notification.created_at}</p>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${getPriorityClasses(notification.priority)}">
                                    ${notification.priority}
                                </span>
                            </div>
                        </div>
                    </div>
                `).join('');
            })
            .catch(error => {
                console.error('Error loading notifications:', error);
                document.getElementById('notification-preview-list').innerHTML = 
                    '<div class="px-4 py-3 text-sm text-red-500 text-center">Error loading notifications</div>';
            });
    }

    function getPriorityClasses(priority) {
        switch(priority) {
            case 'urgent': return 'bg-red-100 text-red-800';
            case 'high': return 'bg-orange-100 text-orange-800';
            case 'medium': return 'bg-yellow-100 text-yellow-800';
            case 'low': return 'bg-green-100 text-green-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    }

    // Mark all as read from dropdown
    const markAllReadBtn = document.getElementById('mark-all-read-dropdown');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function() {
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
        });
    }

    // Auto-refresh notification counts every 30 seconds
    setInterval(function() {
        fetch('/notifications/counts')
            .then(response => response.json())
            .then(data => {
                updateNotificationBadge(data.unread, data.action_required);
            })
            .catch(error => console.error('Error updating notification counts:', error));
    }, 30000);

    function updateNotificationBadge(unreadCount, actionRequiredCount) {
        const badge = document.querySelector('.bg-red-600');
        const actionIndicator = document.querySelector('.bg-orange-500');
        
        if (unreadCount > 0) {
            if (badge) {
                badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
            }
        } else {
            if (badge) {
                badge.remove();
            }
        }
        
        if (actionRequiredCount > 0) {
            if (!actionIndicator) {
                // Add action indicator if it doesn't exist
                const bellContainer = document.querySelector('.relative p-2');
                const indicator = document.createElement('span');
                indicator.className = 'absolute -bottom-1 -right-1 w-3 h-3 bg-orange-500 rounded-full border-2 border-white';
                bellContainer.appendChild(indicator);
            }
        } else {
            if (actionIndicator) {
                actionIndicator.remove();
            }
        }
    }
});
</script>
@endpush