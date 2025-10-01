<?php

namespace App\Services\NotificationChannels;

use App\Contracts\NotificationChannelInterface;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Broadcast;

class WebSocketChannel implements NotificationChannelInterface
{
    public function send(User $user, Notification $notification): bool
    {
        try {
            // Broadcast the notification to the user's private channel
            $data = [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'message' => $notification->message,
                'priority' => $notification->priority,
                'requires_action' => $notification->requires_action,
                'created_at' => $notification->created_at->toISOString(),
                'data' => $notification->data ?? [],
            ];

            // Broadcast to user's private channel
            Broadcast::channel("user.{$user->id}", function () use ($data) {
                return $data;
            });

            // Also broadcast to role-based channels if applicable
            if ($notification->priority === 'urgent') {
                Broadcast::channel("urgent-notifications", function () use ($data, $user) {
                    return array_merge($data, ['user_id' => $user->id]);
                });
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send WebSocket notification', [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function supports(string $type): bool
    {
        // WebSocket channel supports all notification types
        return true;
    }

    public function getName(): string
    {
        return 'websocket';
    }

    public function isAvailable(): bool
    {
        // Check if broadcasting is configured
        return config('broadcasting.default') !== 'null';
    }
}