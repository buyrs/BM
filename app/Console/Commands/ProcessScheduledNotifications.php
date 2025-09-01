<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class ProcessScheduledNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:process-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled notifications that are ready to be sent';

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
        $this->info('Processing scheduled notifications...');
        
        try {
            $processedCount = $this->notificationService->processScheduledNotifications();
            
            if ($processedCount > 0) {
                $this->info("Successfully processed {$processedCount} notifications.");
            } else {
                $this->info('No notifications to process.');
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to process scheduled notifications: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
