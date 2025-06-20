<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Agent;

class ResetCheckerDowngrades extends Command
{
    protected $signature = 'checkers:reset-downgrades';
    protected $description = 'Reset checker downgrades and refusal counts every 20 days';

    public function handle()
    {
        Agent::query()->update([
            'is_downgraded' => false,
            'refusals_count' => 0,
        ]);
        $this->info('All checker downgrades and refusal counts have been reset.');
    }
} 