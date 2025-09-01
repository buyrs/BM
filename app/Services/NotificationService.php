<?php

namespace App\Services;

use App\Models\BailMobilite;
use App\Models\Mission;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\BailMobiliteExitReminder;
use App\Notifications\ChecklistValidationNotification;
use App\Notifications\IncidentAlertNotification;
use App\Notifications\MissionAssignedNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Schedule an exit reminder notification 10 days before end date.
     */
    public function scheduleExitReminder(BailMobilite $bailMobilite): ?Notification
    {
        // Calculate the notification date (10 days before end date)
        $notificationDate = Carbon::parse($bailMobilite->end_date)->subDays(10);
        
        // Don't schedule if the date is in the past
        if ($notificationDate->isPast()) {
            Log::info("Exit reminder not scheduled for BM {$bailMobilite->id}: date is in the past");
            return null;
        }

        // Cancel any existing exit reminder notifications for this BM
        $this->cancelExitReminders($bailMobilite);

        // Get all ops users to notify
        $opsUsers = User::role('ops')->get();
        
        $notifications = collect();
        
        foreach ($opsUsers as $opsUser) {
            $notification = Notification::create([
                'type' => Notification::TYPE_EXIT_REMINDER,
                'recipient_id' => $opsUser->id,
                'bail_mobilite_id' => $bailMobilite->id,
                'scheduled_at' => $notificationDate,
                'status' => 'pending',
                'data' => [
                    'bail_mobilite_id' => $bailMobilite->id,
                    'tenant_name' => $bailMobilite->tenant_name,
                    'address' => $bailMobilite->address,
                    'end_date' => $bailMobilite->end_date->toDateString(),
                    'days_remaining' => 10
                ]
            ]);
            
            $notifications->push($notification);
        }

        Log::info("Exit reminder scheduled for BM {$bailMobilite->id} on {$notificationDate->toDateString()}");
        
        return $notifications->first(); // Return first notification for consistency
    }

    /**
     * Send ops alert for checklist validation.
     */
    public function sendChecklistValidationAlert(Mission $mission): Collection
    {
        $bailMobilite = $mission->bailMobilite;
        if (!$bailMobilite) {
            Log::warning("Cannot send checklist validation alert: Mission {$mission->id} has no associated BailMobilite");
            return collect();
        }

        // Get all ops users to notify
        $opsUsers = User::role('ops')->get();
        
        $notifications = collect();
        
        foreach ($opsUsers as $opsUser) {
            // Create notification record
            $notification = Notification::create([
                'type' => Notification::TYPE_CHECKLIST_VALIDATION,
                'recipient_id' => $opsUser->id,
                'bail_mobilite_id' => $bailMobilite->id,
                'scheduled_at' => now(),
                'status' => 'pending',
                'data' => [
                    'mission_id' => $mission->id,
                    'mission_type' => $mission->mission_type,
                    'bail_mobilite_id' => $bailMobilite->id,
                    'tenant_name' => $bailMobilite->tenant_name,
                    'address' => $bailMobilite->address,
                    'checker_name' => $mission->agent->name ?? 'Unknown'
                ]
            ]);
            
            // Send immediate notification
            $opsUser->notify(new ChecklistValidationNotification($mission, $bailMobilite));
            $notification->markAsSent();
            
            $notifications->push($notification);
        }

        Log::info("Checklist validation alert sent for Mission {$mission->id}");
        
        return $notifications;
    }

    /**
     * Send incident alert notification.
     */
    public function sendIncidentAlert(BailMobilite $bailMobilite, string $incidentReason = null): Collection
    {
        // Get all ops users to notify
        $opsUsers = User::role('ops')->get();
        
        $notifications = collect();
        
        foreach ($opsUsers as $opsUser) {
            // Create notification record
            $notification = Notification::create([
                'type' => Notification::TYPE_INCIDENT_ALERT,
                'recipient_id' => $opsUser->id,
                'bail_mobilite_id' => $bailMobilite->id,
                'scheduled_at' => now(),
                'status' => 'pending',
                'data' => [
                    'bail_mobilite_id' => $bailMobilite->id,
                    'tenant_name' => $bailMobilite->tenant_name,
                    'address' => $bailMobilite->address,
                    'incident_reason' => $incidentReason,
                    'status' => $bailMobilite->status
                ]
            ]);
            
            // Send immediate notification
            $opsUser->notify(new IncidentAlertNotification($bailMobilite, $incidentReason));
            $notification->markAsSent();
            
            $notifications->push($notification);
        }

        Log::info("Incident alert sent for BM {$bailMobilite->id}: {$incidentReason}");
        
        return $notifications;
    }

    /**
     * Send mission assigned notification to checker.
     */
    public function sendMissionAssignedNotification(Mission $mission): ?Notification
    {
        if (!$mission->agent) {
            Log::warning("Cannot send mission assigned notification: Mission {$mission->id} has no assigned agent");
            return null;
        }

        $bailMobilite = $mission->bailMobilite;
        
        // Create notification record
        $notification = Notification::create([
            'type' => Notification::TYPE_MISSION_ASSIGNED,
            'recipient_id' => $mission->agent->id,
            'bail_mobilite_id' => $bailMobilite?->id,
            'scheduled_at' => now(),
            'status' => 'pending',
            'data' => [
                'mission_id' => $mission->id,
                'mission_type' => $mission->mission_type,
                'address' => $mission->address,
                'scheduled_at' => $mission->scheduled_at?->toDateTimeString(),
                'tenant_name' => $bailMobilite?->tenant_name ?? $mission->tenant_name,
                'assigned_by' => $mission->opsAssignedBy?->name ?? 'System'
            ]
        ]);
        
        // Send immediate notification
        $mission->agent->notify(new MissionAssignedNotification($mission, $bailMobilite));
        $notification->markAsSent();

        Log::info("Mission assigned notification sent to agent {$mission->agent->id} for Mission {$mission->id}");
        
        return $notification;
    }

    /**
     * Cancel scheduled notifications for a bail mobilité.
     */
    public function cancelScheduledNotifications(BailMobilite $bailMobilite, array $types = null): int
    {
        $query = Notification::where('bail_mobilite_id', $bailMobilite->id)
                            ->where('status', 'pending');
        
        if ($types) {
            $query->whereIn('type', $types);
        }
        
        $notifications = $query->get();
        $cancelledCount = 0;
        
        foreach ($notifications as $notification) {
            $notification->cancel();
            $cancelledCount++;
        }

        Log::info("Cancelled {$cancelledCount} notifications for BM {$bailMobilite->id}");
        
        return $cancelledCount;
    }

    /**
     * Cancel exit reminder notifications specifically.
     */
    public function cancelExitReminders(BailMobilite $bailMobilite): int
    {
        return $this->cancelScheduledNotifications($bailMobilite, [Notification::TYPE_EXIT_REMINDER]);
    }

    /**
     * Process scheduled notifications that are ready to be sent.
     */
    public function processScheduledNotifications(): int
    {
        $notifications = Notification::scheduledForSending()->get();
        $processedCount = 0;
        
        foreach ($notifications as $notification) {
            try {
                $this->sendScheduledNotification($notification);
                $processedCount++;
            } catch (\Exception $e) {
                Log::error("Failed to send scheduled notification {$notification->id}: " . $e->getMessage());
            }
        }

        Log::info("Processed {$processedCount} scheduled notifications");
        
        return $processedCount;
    }

    /**
     * Send a specific scheduled notification.
     */
    protected function sendScheduledNotification(Notification $notification): void
    {
        switch ($notification->type) {
            case Notification::TYPE_EXIT_REMINDER:
                $this->sendExitReminderNotification($notification);
                break;
            case Notification::TYPE_CHECKLIST_VALIDATION:
                $this->sendChecklistValidationFromScheduled($notification);
                break;
            case Notification::TYPE_INCIDENT_ALERT:
                $this->sendIncidentAlertFromScheduled($notification);
                break;
            case Notification::TYPE_MISSION_ASSIGNED:
                $this->sendMissionAssignedFromScheduled($notification);
                break;
            default:
                Log::warning("Unknown notification type: {$notification->type}");
        }
    }

    /**
     * Send exit reminder notification.
     */
    protected function sendExitReminderNotification(Notification $notification): void
    {
        $bailMobilite = $notification->bailMobilite;
        if (!$bailMobilite) {
            Log::warning("Cannot send exit reminder: BailMobilite not found for notification {$notification->id}");
            return;
        }

        $notification->recipient->notify(new BailMobiliteExitReminder($bailMobilite));
        $notification->markAsSent();
    }

    /**
     * Send checklist validation notification from scheduled.
     */
    protected function sendChecklistValidationFromScheduled(Notification $notification): void
    {
        $bailMobilite = $notification->bailMobilite;
        $missionId = $notification->data['mission_id'] ?? null;
        
        if (!$bailMobilite || !$missionId) {
            Log::warning("Cannot send checklist validation: Missing data for notification {$notification->id}");
            return;
        }

        $mission = Mission::find($missionId);
        if (!$mission) {
            Log::warning("Cannot send checklist validation: Mission {$missionId} not found");
            return;
        }

        $notification->recipient->notify(new ChecklistValidationNotification($mission, $bailMobilite));
        $notification->markAsSent();
    }

    /**
     * Send incident alert notification from scheduled.
     */
    protected function sendIncidentAlertFromScheduled(Notification $notification): void
    {
        $bailMobilite = $notification->bailMobilite;
        if (!$bailMobilite) {
            Log::warning("Cannot send incident alert: BailMobilite not found for notification {$notification->id}");
            return;
        }

        $incidentReason = $notification->data['incident_reason'] ?? null;
        $notification->recipient->notify(new IncidentAlertNotification($bailMobilite, $incidentReason));
        $notification->markAsSent();
    }

    /**
     * Send mission assigned notification from scheduled.
     */
    protected function sendMissionAssignedFromScheduled(Notification $notification): void
    {
        $missionId = $notification->data['mission_id'] ?? null;
        if (!$missionId) {
            Log::warning("Cannot send mission assigned: Missing mission_id for notification {$notification->id}");
            return;
        }

        $mission = Mission::find($missionId);
        if (!$mission) {
            Log::warning("Cannot send mission assigned: Mission {$missionId} not found");
            return;
        }

        $bailMobilite = $mission->bailMobilite;
        $notification->recipient->notify(new MissionAssignedNotification($mission, $bailMobilite));
        $notification->markAsSent();
    }

    /**
     * Get pending notifications for a user.
     */
    public function getPendingNotificationsForUser(User $user): Collection
    {
        return Notification::forRecipient($user->id)
                          ->pending()
                          ->with(['bailMobilite'])
                          ->orderBy('scheduled_at', 'desc')
                          ->get();
    }

    /**
     * Get notification history for a user.
     */
    public function getNotificationHistoryForUser(User $user, int $limit = 50): Collection
    {
        return Notification::forRecipient($user->id)
                          ->with(['bailMobilite'])
                          ->orderBy('created_at', 'desc')
                          ->limit($limit)
                          ->get();
    }

    /**
     * Get notifications for a specific bail mobilité.
     */
    public function getNotificationsForBailMobilite(BailMobilite $bailMobilite): Collection
    {
        return $bailMobilite->notifications()
                           ->with(['recipient'])
                           ->orderBy('created_at', 'desc')
                           ->get();
    }

    /**
     * Mark notification as read/handled.
     */
    public function markNotificationAsHandled(Notification $notification): void
    {
        if ($notification->status === 'pending') {
            $notification->update(['status' => 'sent', 'sent_at' => now()]);
        }
    }

    /**
     * Reschedule exit reminder when bail mobilité dates change.
     */
    public function rescheduleExitReminder(BailMobilite $bailMobilite): ?Notification
    {
        // Cancel existing exit reminders
        $this->cancelExitReminders($bailMobilite);
        
        // Schedule new exit reminder if BM is in progress
        if ($bailMobilite->status === 'in_progress') {
            return $this->scheduleExitReminder($bailMobilite);
        }
        
        return null;
    }

    /**
     * Get notification statistics for ops dashboard.
     */
    public function getNotificationStats(User $opsUser = null): array
    {
        $query = Notification::query();
        
        if ($opsUser) {
            $query->forRecipient($opsUser->id);
        }

        return [
            'total_pending' => (clone $query)->pending()->count(),
            'exit_reminders_pending' => (clone $query)->pending()->exitReminders()->count(),
            'checklist_validations_pending' => (clone $query)->pending()->checklistValidations()->count(),
            'incident_alerts_pending' => (clone $query)->pending()->incidentAlerts()->count(),
            'total_sent_today' => (clone $query)->sent()->whereDate('sent_at', today())->count(),
            'total_sent_this_week' => (clone $query)->sent()->whereBetween('sent_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];
    }
}