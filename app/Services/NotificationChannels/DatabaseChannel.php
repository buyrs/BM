<?php

namespace App\Services\NotificationChannels;

use App\Contracts\NotificationChannelInterface;
use App\Models\User;
use App\Models\Notification;

class DatabaseChannel implements NotificationChannelInterface
{
    public function send(User $user, Notification $notification): bool
    {
        // For database channel, the notification is already saved
        // This method is called after the notification is created
        return true;
    }

    public function supports(string $type): bool
    {
        // Database channel supports all notification types
        return true;
    }

    public function getName(): string
    {
        return 'database';
    }

    public function isAvailable(): bool
    {
        return true;
    }
}