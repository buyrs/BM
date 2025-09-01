<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Send mission reminders every hour
        $schedule->command('missions:send-reminders')->hourly();
        // Reset checker downgrades and refusal counts every 20 days
        $schedule->command('checkers:reset-downgrades')->cron('0 0 */20 * *');
        // Process scheduled notifications every 15 minutes
        $schedule->command('notifications:process-scheduled')->everyFifteenMinutes();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
} 