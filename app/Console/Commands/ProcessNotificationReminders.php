<?php

namespace App\Console\Commands;

use App\Models\BailMobilite;
use App\Models\Notification;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessNotificationReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notifications:process-reminders 
                            {--dry-run : Show what would be processed without actually processing}
                            {--type= : Process specific notification type only}';

    /**
     * The console command description.
     */
    protected $description = 'Process scheduled notification reminders and automatic notifications';

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Processing notification reminders...');
        
        $dryRun = $this->option('dry-run');
        $type = $this->option('type');
        
        $processedCount = 0;
        
        // Process scheduled notifications that are ready to be sent
        $processedCount += $this->processScheduledNotifications($dryRun, $type);
        
        // Check for bail mobilités ending soon and create reminders
        $processedCount += $this->processBailMobiliteEndReminders($dryRun);
        
        // Check for overdue missions and create alerts
        $processedCount += $this->processOverdueMissionAlerts($dryRun);
        
        // Clean up old processed notifications
        $cleanedCount = $this->cleanupOldNotifications($dryRun);
        
        $this->info("Processed {$processedCount} notifications");
        $this->info("Cleaned up {$cleanedCount} old notifications");
        
        return Command::SUCCESS;
    }

    /**
     * Process scheduled notifications that are ready to be sent.
     */
    protected function processScheduledNotifications(bool $dryRun, ?string $type): int
    {
        $query = Notification::scheduledForSending();
        
        if ($type) {
            $query->where('type', $type);
        }
        
        $notifications = $query->get();
        
        if ($notifications->isEmpty()) {
            $this->info('No scheduled notifications to process');
            return 0;
        }
        
        $this->info("Found {$notifications->count()} scheduled notifications to process");
        
        $processedCount = 0;
        
        foreach ($notifications as $notification) {
            try {
                if ($dryRun) {
                    $this->line("Would process notification {$notification->id} ({$notification->type})");
                } else {
                    $this->notificationService->processScheduledNotifications();
                    $this->line("Processed notification {$notification->id} ({$notification->type})");
                }
                $processedCount++;
            } catch (\Exception $e) {
                $this->error("Failed to process notification {$notification->id}: " . $e->getMessage());
                Log::error("Failed to process scheduled notification {$notification->id}", [
                    'error' => $e->getMessage(),
                    'notification' => $notification->toArray()
                ]);
            }
        }
        
        return $processedCount;
    }

    /**
     * Process bail mobilité end reminders.
     */
    protected function processBailMobiliteEndReminders(bool $dryRun): int
    {
        // Find bail mobilités ending in 10 days that don't have exit reminders scheduled
        $endDate = Carbon::now()->addDays(10)->toDateString();
        
        $bailMobilites = BailMobilite::where('status', 'in_progress')
            ->whereDate('end_date', $endDate)
            ->whereDoesntHave('notifications', function ($query) {
                $query->where('type', Notification::TYPE_EXIT_REMINDER)
                      ->where('status', 'pending');
            })
            ->get();
        
        if ($bailMobilites->isEmpty()) {
            $this->info('No bail mobilités need exit reminders');
            return 0;
        }
        
        $this->info("Found {$bailMobilites->count()} bail mobilités needing exit reminders");
        
        $processedCount = 0;
        
        foreach ($bailMobilites as $bailMobilite) {
            try {
                if ($dryRun) {
                    $this->line("Would create exit reminder for BM {$bailMobilite->id} ({$bailMobilite->tenant_name})");
                } else {
                    $notification = $this->notificationService->scheduleExitReminder($bailMobilite);
                    if ($notification) {
                        $this->line("Created exit reminder for BM {$bailMobilite->id} ({$bailMobilite->tenant_name})");
                        $processedCount++;
                    }
                }
            } catch (\Exception $e) {
                $this->error("Failed to create exit reminder for BM {$bailMobilite->id}: " . $e->getMessage());
                Log::error("Failed to create exit reminder", [
                    'bail_mobilite_id' => $bailMobilite->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $processedCount;
    }

    /**
     * Process overdue mission alerts.
     */
    protected function processOverdueMissionAlerts(bool $dryRun): int
    {
        // Find missions that are overdue (scheduled more than 2 hours ago and still pending)
        $overdueTime = Carbon::now()->subHours(2);
        
        $overdueMissions = \App\Models\Mission::where('status', 'assigned')
            ->where('scheduled_at', '<', $overdueTime)
            ->whereNotExists(function ($query) {
                $query->select(\DB::raw(1))
                      ->from('notifications')
                      ->whereColumn('notifications.mission_id', 'missions.id')
                      ->where('notifications.type', 'mission_overdue')
                      ->where('notifications.status', 'pending')
                      ->whereDate('notifications.created_at', today());
            })
            ->with(['bailMobilite', 'agent'])
            ->get();
        
        if ($overdueMissions->isEmpty()) {
            $this->info('No overdue missions found');
            return 0;
        }
        
        $this->info("Found {$overdueMissions->count()} overdue missions");
        
        $processedCount = 0;
        
        foreach ($overdueMissions as $mission) {
            try {
                if ($dryRun) {
                    $this->line("Would create overdue alert for Mission {$mission->id}");
                } else {
                    // Create overdue mission notification
                    $opsUsers = \App\Models\User::role('ops')->get();
                    
                    foreach ($opsUsers as $opsUser) {
                        Notification::create([
                            'type' => 'mission_overdue',
                            'recipient_id' => $opsUser->id,
                            'bail_mobilite_id' => $mission->bail_mobilite_id,
                            'mission_id' => $mission->id,
                            'scheduled_at' => now(),
                            'status' => 'pending',
                            'data' => [
                                'mission_id' => $mission->id,
                                'mission_type' => $mission->mission_type,
                                'scheduled_at' => $mission->scheduled_at->toDateTimeString(),
                                'hours_overdue' => $mission->scheduled_at->diffInHours(now()),
                                'checker_name' => $mission->agent->name ?? 'Unassigned',
                                'tenant_name' => $mission->bailMobilite->tenant_name ?? 'Unknown'
                            ]
                        ]);
                    }
                    
                    $this->line("Created overdue alert for Mission {$mission->id}");
                    $processedCount++;
                }
            } catch (\Exception $e) {
                $this->error("Failed to create overdue alert for Mission {$mission->id}: " . $e->getMessage());
                Log::error("Failed to create overdue mission alert", [
                    'mission_id' => $mission->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $processedCount;
    }

    /**
     * Clean up old processed notifications.
     */
    protected function cleanupOldNotifications(bool $dryRun): int
    {
        // Delete notifications older than 30 days that have been sent
        $cutoffDate = Carbon::now()->subDays(30);
        
        $query = Notification::where('status', 'sent')
            ->where('sent_at', '<', $cutoffDate);
        
        $count = $query->count();
        
        if ($count === 0) {
            $this->info('No old notifications to clean up');
            return 0;
        }
        
        if ($dryRun) {
            $this->line("Would delete {$count} old notifications");
        } else {
            $deleted = $query->delete();
            $this->line("Deleted {$deleted} old notifications");
            return $deleted;
        }
        
        return $count;
    }
}