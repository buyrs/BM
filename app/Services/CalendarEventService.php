<?php

namespace App\Services;

use App\Models\Mission;
use App\Models\BailMobilite;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Carbon\Carbon;

class CalendarEventService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle mission creation event.
     */
    public function handleMissionCreated(Mission $mission): void
    {
        Log::info("Calendar Event: Mission created", [
            'mission_id' => $mission->id,
            'type' => $mission->mission_type,
            'scheduled_at' => $mission->scheduled_at?->toISOString(),
        ]);

        // Send notifications for mission creation
        if ($mission->bailMobilite) {
            $this->notificationService->sendCalendarMissionCreationNotification($mission->bailMobilite);
        }

        // Schedule reminder notifications
        $this->scheduleReminderNotifications($mission);
    }

    /**
     * Handle mission assignment event.
     */
    public function handleMissionAssigned(Mission $mission, ?User $previousAgent = null): void
    {
        Log::info("Calendar Event: Mission assigned", [
            'mission_id' => $mission->id,
            'agent_id' => $mission->agent_id,
            'previous_agent_id' => $previousAgent?->id,
            'assigned_by' => auth()->id(),
        ]);

        // Send assignment notifications
        $this->notificationService->sendCalendarMissionAssignmentNotification($mission);

        // Cancel previous agent notifications if reassigned
        if ($previousAgent && $previousAgent->id !== $mission->agent_id) {
            $this->cancelAgentNotifications($mission, $previousAgent);
        }

        // Schedule new agent notifications
        $this->scheduleAgentNotifications($mission);
    }

    /**
     * Handle mission status change event.
     */
    public function handleMissionStatusChanged(Mission $mission, string $oldStatus, string $newStatus): void
    {
        Log::info("Calendar Event: Mission status changed", [
            'mission_id' => $mission->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => auth()->id(),
        ]);

        // Send status change notifications
        $this->notificationService->sendCalendarMissionStatusNotification($mission, $oldStatus, $newStatus);

        // Handle specific status transitions
        switch ($newStatus) {
            case 'in_progress':
                $this->handleMissionStarted($mission);
                break;
            case 'completed':
                $this->handleMissionCompleted($mission);
                break;
            case 'cancelled':
                $this->handleMissionCancelled($mission);
                break;
        }
    }

    /**
     * Handle mission rescheduling event.
     */
    public function handleMissionRescheduled(Mission $mission, Carbon $oldDate, Carbon $newDate): void
    {
        Log::info("Calendar Event: Mission rescheduled", [
            'mission_id' => $mission->id,
            'old_date' => $oldDate->toISOString(),
            'new_date' => $newDate->toISOString(),
            'rescheduled_by' => auth()->id(),
        ]);

        // Send rescheduling notifications
        $this->notificationService->sendCalendarMissionUpdateNotification($mission, 'rescheduled', [
            'old_date' => $oldDate->toDateString(),
            'new_date' => $newDate->toDateString(),
            'rescheduled_by' => auth()->user()->name ?? 'System'
        ]);

        // Update reminder notifications
        $this->rescheduleReminderNotifications($mission, $oldDate, $newDate);
    }

    /**
     * Handle mission deletion event.
     */
    public function handleMissionDeleted(Mission $mission): void
    {
        Log::info("Calendar Event: Mission deleted", [
            'mission_id' => $mission->id,
            'deleted_by' => auth()->id(),
        ]);

        // Cancel all related notifications
        $this->cancelAllMissionNotifications($mission);
    }

    /**
     * Handle mission started event.
     */
    protected function handleMissionStarted(Mission $mission): void
    {
        // Update bail mobilité status if this is an entry mission
        if ($mission->isEntryMission() && $mission->bailMobilite) {
            $bailMobilite = $mission->bailMobilite;
            if ($bailMobilite->status === 'assigned') {
                $bailMobilite->update(['status' => 'in_progress']);
            }
        }

        // Schedule completion reminder
        $this->scheduleCompletionReminder($mission);
    }

    /**
     * Handle mission completed event.
     */
    protected function handleMissionCompleted(Mission $mission): void
    {
        // Send checklist validation alert
        $this->notificationService->sendChecklistValidationAlert($mission);

        // Update bail mobilité status if this is an exit mission
        if ($mission->isExitMission() && $mission->bailMobilite) {
            $bailMobilite = $mission->bailMobilite;
            $entryMission = $bailMobilite->entryMission;
            
            // If both entry and exit missions are completed, mark bail mobilité as completed
            if ($entryMission && $entryMission->status === 'completed') {
                $bailMobilite->update(['status' => 'completed']);
            }
        }
    }

    /**
     * Handle mission cancelled event.
     */
    protected function handleMissionCancelled(Mission $mission): void
    {
        // Cancel all related notifications
        $this->cancelAllMissionNotifications($mission);

        // Update bail mobilité status if needed
        if ($mission->bailMobilite) {
            $bailMobilite = $mission->bailMobilite;
            
            // If this was the only active mission, revert bail mobilité status
            $activeMissions = $bailMobilite->missions()
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->count();
                
            if ($activeMissions === 0) {
                $bailMobilite->update(['status' => 'assigned']);
            }
        }
    }

    /**
     * Schedule reminder notifications for a mission.
     */
    protected function scheduleReminderNotifications(Mission $mission): void
    {
        if (!$mission->scheduled_at || !$mission->agent) {
            return;
        }

        $scheduledDate = $mission->scheduled_at;
        
        // Schedule 24-hour reminder
        $reminderDate24h = $scheduledDate->copy()->subDay();
        if ($reminderDate24h->isFuture()) {
            // This would integrate with a job queue system
            Log::info("Scheduling 24h reminder for mission {$mission->id} at {$reminderDate24h->toISOString()}");
        }

        // Schedule 2-hour reminder
        $reminderDate2h = $scheduledDate->copy()->subHours(2);
        if ($reminderDate2h->isFuture()) {
            Log::info("Scheduling 2h reminder for mission {$mission->id} at {$reminderDate2h->toISOString()}");
        }
    }

    /**
     * Schedule agent-specific notifications.
     */
    protected function scheduleAgentNotifications(Mission $mission): void
    {
        if (!$mission->agent) {
            return;
        }

        // Send immediate assignment notification
        $this->notificationService->sendMissionAssignedNotification($mission);
    }

    /**
     * Cancel agent notifications for a mission.
     */
    protected function cancelAgentNotifications(Mission $mission, User $agent): void
    {
        // This would cancel scheduled notifications for the specific agent
        Log::info("Cancelling agent notifications for mission {$mission->id} and agent {$agent->id}");
    }

    /**
     * Reschedule reminder notifications when mission is rescheduled.
     */
    protected function rescheduleReminderNotifications(Mission $mission, Carbon $oldDate, Carbon $newDate): void
    {
        // Cancel old reminders
        Log::info("Cancelling old reminders for mission {$mission->id}");
        
        // Schedule new reminders
        $this->scheduleReminderNotifications($mission);
    }

    /**
     * Schedule completion reminder for in-progress mission.
     */
    protected function scheduleCompletionReminder(Mission $mission): void
    {
        if (!$mission->scheduled_at) {
            return;
        }

        // Schedule reminder 1 hour after scheduled time if not completed
        $reminderTime = $mission->scheduled_at->copy()->addHour();
        Log::info("Scheduling completion reminder for mission {$mission->id} at {$reminderTime->toISOString()}");
    }

    /**
     * Cancel all notifications related to a mission.
     */
    protected function cancelAllMissionNotifications(Mission $mission): void
    {
        if ($mission->bailMobilite) {
            $this->notificationService->cancelScheduledNotifications($mission->bailMobilite, [
                'mission_assigned',
                'calendar_update'
            ]);
        }
        
        Log::info("Cancelled all notifications for mission {$mission->id}");
    }

    /**
     * Get upcoming calendar events for a date range.
     */
    public function getUpcomingEvents(Carbon $startDate, Carbon $endDate): array
    {
        $missions = Mission::whereBetween('scheduled_at', [$startDate, $endDate])
            ->with(['bailMobilite', 'agent'])
            ->orderBy('scheduled_at')
            ->get();

        $events = [];
        
        foreach ($missions as $mission) {
            $events[] = [
                'id' => $mission->id,
                'title' => $this->getMissionEventTitle($mission),
                'start' => $mission->scheduled_at->toISOString(),
                'type' => $mission->mission_type,
                'status' => $mission->status,
                'agent' => $mission->agent?->name,
                'address' => $mission->address,
                'tenant_name' => $mission->bailMobilite?->tenant_name ?? $mission->tenant_name,
            ];
        }

        return $events;
    }

    /**
     * Get event title for a mission.
     */
    protected function getMissionEventTitle(Mission $mission): string
    {
        $type = $mission->mission_type === 'entry' ? 'Entrée' : 'Sortie';
        $tenant = $mission->bailMobilite?->tenant_name ?? $mission->tenant_name ?? 'Unknown';
        
        return "{$type} - {$tenant}";
    }

    /**
     * Get calendar statistics for dashboard.
     */
    public function getCalendarStats(): array
    {
        $today = now()->startOfDay();
        $tomorrow = now()->addDay()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $nextWeek = now()->addWeek()->startOfWeek();

        return [
            'today_missions' => Mission::whereDate('scheduled_at', $today)->count(),
            'tomorrow_missions' => Mission::whereDate('scheduled_at', $tomorrow)->count(),
            'this_week_missions' => Mission::whereBetween('scheduled_at', [$thisWeek, $thisWeek->copy()->endOfWeek()])->count(),
            'next_week_missions' => Mission::whereBetween('scheduled_at', [$nextWeek, $nextWeek->copy()->endOfWeek()])->count(),
            'overdue_missions' => Mission::where('scheduled_at', '<', $today)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count(),
            'unassigned_missions' => Mission::where('status', 'unassigned')->count(),
        ];
    }
}