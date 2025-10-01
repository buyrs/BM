<?php

namespace App\Contracts;

use App\Models\User;
use App\Models\Notification;

interface NotificationChannelInterface
{
    /**
     * Send a notification through this channel
     */
    public function send(User $user, Notification $notification): bool;

    /**
     * Check if this channel supports the given notification type
     */
    public function supports(string $type): bool;

    /**
     * Get the channel name
     */
    public function getName(): string;

    /**
     * Check if the channel is available/configured
     */
    public function isAvailable(): bool;
}