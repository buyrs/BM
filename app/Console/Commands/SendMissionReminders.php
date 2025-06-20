<?php

namespace App\Console\Commands;

use App\Models\Mission;
use App\Notifications\MissionReminder;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\User;
use App\Notifications\CheckoutReminder;

class SendMissionReminders extends Command
{
    protected $signature = 'missions:send-reminders';
    protected $description = 'Send reminders for upcoming missions';

    public function handle()
    {
        $this->info('Sending mission reminders...');

        // 24-hour reminders
        $missions = Mission::where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->where('scheduled_at', '>=', Carbon::now())
            ->where('scheduled_at', '<=', Carbon::now()->addDay())
            ->whereHas('agent')
            ->get();

        // 10-day checkout alerts
        $checkoutMissions = Mission::where('type', 'checkout')
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->where('scheduled_at', '>=', Carbon::now()->addDays(10))
            ->where('scheduled_at', '<=', Carbon::now()->addDays(11))
            ->get();

        $count = 0;
        foreach ($missions as $mission) {
            if ($mission->agent) {
                $mission->agent->notify(new MissionReminder($mission));
                $count++;
            }
        }

        foreach ($checkoutMissions as $mission) {
            // Notify super admin
            $superAdmin = User::role('super-admin')->first();
            if ($superAdmin) {
                $superAdmin->notify(new CheckoutReminder($mission));
                $count++;
            }
        }

        $this->info("Sent {$count} reminders.");
    }
} 