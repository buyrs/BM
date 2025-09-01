<?php

namespace App\Console\Commands;

use App\Models\BailMobilite;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class ScheduleExitReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:schedule-exit-reminders {--force : Force scheduling even if reminders already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedule exit reminder notifications for all in-progress bail mobilités';

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
        $this->info('Scheduling exit reminders for in-progress bail mobilités...');
        
        try {
            $bailMobilites = BailMobilite::inProgress()->get();
            $scheduledCount = 0;
            $skippedCount = 0;
            
            foreach ($bailMobilites as $bailMobilite) {
                // Check if exit reminder already exists unless force option is used
                if (!$this->option('force')) {
                    $existingReminder = $bailMobilite->notifications()
                        ->where('type', \App\Models\Notification::TYPE_EXIT_REMINDER)
                        ->where('status', 'pending')
                        ->exists();
                        
                    if ($existingReminder) {
                        $this->line("Skipping BM {$bailMobilite->id} - reminder already exists");
                        $skippedCount++;
                        continue;
                    }
                }
                
                $notification = $this->notificationService->scheduleExitReminder($bailMobilite);
                
                if ($notification) {
                    $this->line("Scheduled exit reminder for BM {$bailMobilite->id} - {$bailMobilite->tenant_name}");
                    $scheduledCount++;
                } else {
                    $this->line("Skipped BM {$bailMobilite->id} - notification date is in the past");
                    $skippedCount++;
                }
            }
            
            $this->info("Completed: {$scheduledCount} reminders scheduled, {$skippedCount} skipped.");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to schedule exit reminders: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
