<?php

namespace App\Console\Commands;

use App\Models\Mission;
use App\Notifications\MissionReminder;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendMissionReminders extends Command
{
    protected $signature = 'missions:send-reminders';
    protected $description = 'Send reminders for upcoming missions';

    public function handle()
    {
        $this->info('Sending mission reminders...');

        // Get missions scheduled for the next 24 hours that haven't been completed
        $missions = Mission::where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->where('scheduled_at', '>=', Carbon::now())
            ->where('scheduled_at', '<=', Carbon::now()->addDay())
            ->whereHas('agent')
            ->get();

        $count = 0;
        foreach ($missions as $mission) {
            if ($mission->agent) {
                $mission->agent->notify(new MissionReminder($mission));
                $count++;
            }
        }

        $this->info("Sent {$count} mission reminders.");
    }
} 