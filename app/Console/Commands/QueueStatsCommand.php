<?php

namespace App\Console\Commands;

use App\Services\QueueMonitoringService;
use Illuminate\Console\Command;

class QueueStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:stats {--queue= : Show stats for specific queue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display queue statistics and health information';

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
        $queue = $this->option('queue');

        if ($queue) {
            $this->showQueueStats($queue);
        } else {
            $this->showAllQueueStats();
        }

        $this->showQueueHealth();

        return Command::SUCCESS;
    }

    /**
     * Show stats for a specific queue
     */
    protected function showQueueStats(string $queue): void
    {
        $this->info("Queue Statistics for: {$queue}");
        $this->line('');

        $stats = $this->queueMonitoring->getQueueInfo($queue);

        $this->table(['Metric', 'Value'], [
            ['Queue Name', $stats['name']],
            ['Waiting Jobs', $stats['waiting']],
            ['Delayed Jobs', $stats['delayed']],
            ['Reserved Jobs', $stats['reserved']],
            ['Failed Jobs', $stats['failed']],
            ['Processed Today', $stats['processed_today']],
        ]);
    }

    /**
     * Show stats for all queues
     */
    protected function showAllQueueStats(): void
    {
        $this->info('Queue Statistics Overview');
        $this->line('');

        $stats = $this->queueMonitoring->getQueueStats();

        $tableData = [];
        foreach ($stats as $queueStats) {
            $tableData[] = [
                $queueStats['name'],
                $queueStats['waiting'],
                $queueStats['delayed'],
                $queueStats['reserved'],
                $queueStats['failed'],
                $queueStats['processed_today'],
            ];
        }

        $this->table([
            'Queue',
            'Waiting',
            'Delayed',
            'Reserved',
            'Failed',
            'Processed Today'
        ], $tableData);
    }

    /**
     * Show queue health information
     */
    protected function showQueueHealth(): void
    {
        $this->line('');
        $this->info('Queue Health Status');
        $this->line('');

        $health = $this->queueMonitoring->getQueueHealth();

        // Show status with appropriate color
        $statusColor = match ($health['status']) {
            'healthy' => 'green',
            'warning' => 'yellow',
            'critical' => 'red',
            default => 'white'
        };

        $this->line("Status: <fg={$statusColor}>" . strtoupper($health['status']) . '</fg>');

        if (!empty($health['issues'])) {
            $this->line('');
            $this->warn('Issues Found:');
            foreach ($health['issues'] as $issue) {
                $this->line("  • {$issue}");
            }
        }

        if (!empty($health['recommendations'])) {
            $this->line('');
            $this->info('Recommendations:');
            foreach ($health['recommendations'] as $recommendation) {
                $this->line("  • {$recommendation}");
            }
        }
    }
}