<?php

namespace App\Console\Commands;

use App\Services\QueueMonitoringService;
use Illuminate\Console\Command;

class QueueHealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:health {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check queue health status for monitoring systems';

    protected QueueMonitoringService $queueMonitoring;

    /**
     * Create a new command instance.
     */
    public function __construct(QueueMonitoringService $queueMonitoring)
    {
        parent::__construct();
        $this->queueMonitoring = $queueMonitoring;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $health = $this->queueMonitoring->getQueueHealth();
        $stats = $this->queueMonitoring->getQueueStats();

        if ($this->option('json')) {
            $this->outputJson($health, $stats);
        } else {
            $this->outputHuman($health, $stats);
        }

        // Return appropriate exit code for monitoring systems
        return match ($health['status']) {
            'healthy' => Command::SUCCESS,
            'warning' => 1,
            'critical' => 2,
            default => Command::FAILURE
        };
    }

    /**
     * Output health information in JSON format
     */
    protected function outputJson(array $health, array $stats): void
    {
        $output = [
            'timestamp' => now()->toISOString(),
            'status' => $health['status'],
            'issues' => $health['issues'],
            'recommendations' => $health['recommendations'],
            'queue_stats' => $stats
        ];

        $this->line(json_encode($output, JSON_PRETTY_PRINT));
    }

    /**
     * Output health information in human-readable format
     */
    protected function outputHuman(array $health, array $stats): void
    {
        $statusColor = match ($health['status']) {
            'healthy' => 'green',
            'warning' => 'yellow',
            'critical' => 'red',
            default => 'white'
        };

        $this->line("Queue Health: <fg={$statusColor}>" . strtoupper($health['status']) . '</fg>');

        if (!empty($health['issues'])) {
            $this->line('');
            $this->line('Issues:');
            foreach ($health['issues'] as $issue) {
                $this->line("  - {$issue}");
            }
        }

        if (!empty($health['recommendations'])) {
            $this->line('');
            $this->line('Recommendations:');
            foreach ($health['recommendations'] as $recommendation) {
                $this->line("  - {$recommendation}");
            }
        }

        $this->line('');
        $this->line('Queue Summary:');
        foreach ($stats as $queueName => $queueStats) {
            $waiting = $queueStats['waiting'];
            $failed = $queueStats['failed'];
            $this->line("  {$queueName}: {$waiting} waiting, {$failed} failed");
        }
    }
}