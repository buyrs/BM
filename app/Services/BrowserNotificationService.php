<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;

class BrowserNotificationService extends BaseService
{
    /**
     * Generate browser notification data
     */
    public function generateNotificationData(Notification $notification): array
    {
        $icon = $this->getNotificationIcon($notification->type, $notification->priority);
        $sound = $this->getNotificationSound($notification->priority);
        
        return [
            'title' => $notification->title,
            'body' => $notification->message,
            'icon' => $icon,
            'badge' => asset('images/notification-badge.png'),
            'tag' => "notification-{$notification->id}",
            'data' => [
                'notification_id' => $notification->id,
                'type' => $notification->type,
                'priority' => $notification->priority,
                'requires_action' => $notification->requires_action,
                'url' => $this->getNotificationUrl($notification),
                'sound' => $sound,
            ],
            'actions' => $this->getNotificationActions($notification),
            'requireInteraction' => $notification->priority === 'urgent',
            'silent' => false,
        ];
    }

    /**
     * Get notification icon based on type and priority
     */
    private function getNotificationIcon(string $type, string $priority): string
    {
        $iconMap = [
            'mission_assigned' => 'mission-icon.png',
            'mission_completed' => 'mission-complete-icon.png',
            'checklist_completed' => 'checklist-icon.png',
            'maintenance_request' => 'maintenance-icon.png',
            'maintenance_approved' => 'maintenance-approved-icon.png',
            'maintenance_completed' => 'maintenance-complete-icon.png',
        ];

        $icon = $iconMap[$type] ?? 'default-notification-icon.png';
        
        // Use urgent icon for urgent notifications
        if ($priority === 'urgent') {
            $icon = 'urgent-notification-icon.png';
        }

        return asset("images/notifications/{$icon}");
    }

    /**
     * Get notification sound based on priority
     */
    private function getNotificationSound(string $priority): string
    {
        $soundMap = [
            'urgent' => 'urgent-notification.mp3',
            'high' => 'high-priority-notification.mp3',
            'medium' => 'notification.mp3',
            'low' => 'soft-notification.mp3',
        ];

        return asset("sounds/{$soundMap[$priority]}");
    }

    /**
     * Get notification actions
     */
    private function getNotificationActions(Notification $notification): array
    {
        $actions = [
            [
                'action' => 'view',
                'title' => 'View',
                'icon' => asset('images/view-icon.png'),
            ],
            [
                'action' => 'dismiss',
                'title' => 'Dismiss',
                'icon' => asset('images/dismiss-icon.png'),
            ],
        ];

        if ($notification->requires_action) {
            array_unshift($actions, [
                'action' => 'take_action',
                'title' => 'Take Action',
                'icon' => asset('images/action-icon.png'),
            ]);
        }

        return $actions;
    }

    /**
     * Get URL for notification
     */
    private function getNotificationUrl(Notification $notification): string
    {
        switch ($notification->type) {
            case 'mission_assigned':
            case 'mission_completed':
                return $notification->mission_id 
                    ? route('missions.show', $notification->mission_id)
                    : route('dashboard');
                    
            case 'checklist_completed':
                return $notification->checklist_id 
                    ? route('checklists.show', $notification->checklist_id)
                    : route('dashboard');
                    
            case 'maintenance_request':
            case 'maintenance_approved':
            case 'maintenance_completed':
                $maintenanceRequestId = $notification->data['maintenance_request_id'] ?? null;
                return $maintenanceRequestId 
                    ? route('ops.maintenance-requests.show', $maintenanceRequestId)
                    : route('dashboard');
                    
            default:
                return route('notifications.index');
        }
    }

    /**
     * Check if user has browser notification permission
     */
    public function hasPermission(User $user): bool
    {
        // This would typically be stored in user preferences
        return $user->preferences['browser_notifications'] ?? false;
    }

    /**
     * Enable browser notifications for user
     */
    public function enableForUser(User $user): void
    {
        $preferences = $user->preferences ?? [];
        $preferences['browser_notifications'] = true;
        $user->update(['preferences' => $preferences]);
    }

    /**
     * Disable browser notifications for user
     */
    public function disableForUser(User $user): void
    {
        $preferences = $user->preferences ?? [];
        $preferences['browser_notifications'] = false;
        $user->update(['preferences' => $preferences]);
    }
}