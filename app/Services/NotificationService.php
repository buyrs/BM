<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use App\Models\Mission;
use App\Models\Checklist;
use App\Contracts\NotificationChannelInterface;
use App\Services\NotificationChannels\DatabaseChannel;
use App\Services\NotificationChannels\EmailChannel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class NotificationService extends BaseService
{
    private array $channels = [];

    public function __construct(
        private EmailService $emailService
    ) {
        parent::__construct();
        $this->registerChannels();
    }

    /**
     * Create and send a notification
     */
    public function create(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data = [],
        array $channels = ['database'],
        string $priority = 'medium',
        bool $requiresAction = false,
        ?Mission $mission = null,
        ?Checklist $checklist = null
    ): Notification {
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'channels' => $channels,
            'priority' => $priority,
            'requires_action' => $requiresAction,
            'mission_id' => $mission?->id,
            'checklist_id' => $checklist?->id,
        ]);

        $this->sendThroughChannels($user, $notification, $channels);

        return $notification;
    }

    /**
     * Send notification for mission assignment
     */
    public function notifyMissionAssigned(User $checker, Mission $mission): Notification
    {
        return $this->create(
            user: $checker,
            type: 'mission_assigned',
            title: 'New Mission Assigned',
            message: "You have been assigned a new mission for property: {$mission->property->name}",
            data: [
                'mission_id' => $mission->id,
                'property_name' => $mission->property->name,
                'due_date' => $mission->due_date?->format('Y-m-d H:i'),
            ],
            channels: ['database', 'email', 'websocket'],
            priority: 'medium',
            mission: $mission
        );
    }

    /**
     * Send notification for mission completion
     */
    public function notifyMissionCompleted(User $creator, Mission $mission): Notification
    {
        return $this->create(
            user: $creator,
            type: 'mission_completed',
            title: 'Mission Completed',
            message: "Mission for property {$mission->property->name} has been completed by {$mission->assignedUser->name}",
            data: [
                'mission_id' => $mission->id,
                'property_name' => $mission->property->name,
                'completed_by' => $mission->assignedUser->name,
                'completed_at' => $mission->completed_at?->format('Y-m-d H:i'),
            ],
            channels: ['database', 'email', 'websocket'],
            priority: 'medium',
            mission: $mission
        );
    }

    /**
     * Send notification for checklist completion
     */
    public function notifyChecklistCompleted(User $missionCreator, Checklist $checklist): Notification
    {
        return $this->create(
            user: $missionCreator,
            type: 'checklist_completed',
            title: 'Checklist Completed',
            message: "Checklist '{$checklist->name}' has been completed for mission at {$checklist->mission->property->name}",
            data: [
                'checklist_id' => $checklist->id,
                'checklist_name' => $checklist->name,
                'mission_id' => $checklist->mission_id,
                'property_name' => $checklist->mission->property->name,
                'completed_by' => $checklist->mission->assignedUser->name,
            ],
            channels: ['database', 'email', 'websocket'],
            priority: 'medium',
            mission: $checklist->mission,
            checklist: $checklist
        );
    }

    /**
     * Send bulk notifications to multiple users
     */
    public function createBulk(
        Collection $users,
        string $type,
        string $title,
        string $message,
        array $data = [],
        array $channels = ['database'],
        string $priority = 'medium'
    ): Collection {
        $notifications = collect();

        foreach ($users as $user) {
            $notifications->push(
                $this->create($user, $type, $title, $message, $data, $channels, $priority)
            );
        }

        return $notifications;
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification): bool
    {
        $notification->markAsRead();
        return true;
    }

    /**
     * Mark multiple notifications as read
     */
    public function markMultipleAsRead(Collection $notifications): bool
    {
        $notifications->each(fn($notification) => $notification->markAsRead());
        return true;
    }

    /**
     * Get unread notifications for user
     */
    public function getUnreadForUser(User $user): Collection
    {
        return Notification::forUser($user)
            ->unread()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get notifications requiring action for user
     */
    public function getRequiringActionForUser(User $user): Collection
    {
        return Notification::forUser($user)
            ->requiringAction()
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Register notification channels
     */
    private function registerChannels(): void
    {
        $this->channels['database'] = new DatabaseChannel();
        $this->channels['email'] = new EmailChannel($this->emailService);
        $this->channels['websocket'] = new \App\Services\NotificationChannels\WebSocketChannel();
    }

    /**
     * Send notification through specified channels
     */
    private function sendThroughChannels(User $user, Notification $notification, array $channels): void
    {
        foreach ($channels as $channelName) {
            if (!isset($this->channels[$channelName])) {
                Log::warning("Unknown notification channel: {$channelName}");
                continue;
            }

            $channel = $this->channels[$channelName];

            if (!$channel->isAvailable()) {
                Log::warning("Notification channel {$channelName} is not available");
                continue;
            }

            if (!$channel->supports($notification->type)) {
                Log::info("Channel {$channelName} does not support notification type: {$notification->type}");
                continue;
            }

            try {
                $success = $channel->send($user, $notification);
                if (!$success) {
                    Log::error("Failed to send notification through channel: {$channelName}", [
                        'notification_id' => $notification->id,
                        'user_id' => $user->id
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("Exception sending notification through channel: {$channelName}", [
                    'notification_id' => $notification->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}