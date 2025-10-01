<?php

namespace App\Services\NotificationChannels;

use App\Contracts\NotificationChannelInterface;
use App\Models\User;
use App\Models\Notification;
use App\Services\EmailService;
use Illuminate\Support\Facades\Log;

class EmailChannel implements NotificationChannelInterface
{
    public function __construct(
        private EmailService $emailService
    ) {}

    public function send(User $user, Notification $notification): bool
    {
        try {
            $template = $this->getEmailTemplate($notification->type);
            
            $emailData = [
                'user' => $user,
                'notification' => $notification,
                'title' => $notification->title,
                'message' => $notification->message,
                'data' => $notification->data ?? [],
                'priority' => $notification->priority,
                'requires_action' => $notification->requires_action,
            ];

            return $this->emailService->send(
                to: $user->email,
                subject: $this->getEmailSubject($notification),
                template: $template,
                data: $emailData
            );
        } catch (\Exception $e) {
            Log::error('Failed to send email notification', [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function supports(string $type): bool
    {
        // Email channel supports most notification types except system messages
        return !in_array($type, ['system_maintenance', 'debug']);
    }

    public function getName(): string
    {
        return 'email';
    }

    public function isAvailable(): bool
    {
        return $this->emailService->isConfigured();
    }

    private function getEmailTemplate(string $type): string
    {
        return match($type) {
            'mission_assigned' => 'emails.notifications.mission-assigned',
            'mission_completed' => 'emails.notifications.mission-completed',
            'checklist_completed' => 'emails.notifications.checklist-completed',
            'maintenance_request' => 'emails.notifications.maintenance-request',
            'maintenance_approved' => 'emails.notifications.maintenance-approved',
            'maintenance_completed' => 'emails.notifications.maintenance-completed',
            default => 'emails.notifications.general'
        };
    }

    private function getEmailSubject(Notification $notification): string
    {
        $prefix = match($notification->priority) {
            'urgent' => '[URGENT] ',
            'high' => '[HIGH] ',
            default => ''
        };

        return $prefix . $notification->title;
    }
}