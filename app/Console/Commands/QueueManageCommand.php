<?php

namespace App\Console\Commands;

use App\Services\QueueMonitoringService;
use Illuminate\Console\Command;

class QueueManageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:manage 
                            {action : Action to perform (clear-failed, retry-failed, purge)}
                            {--queue= : Target specific queue}
                            {--force : Force action without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage queue operations (clear failed jobs, retry failed jobs, purge queues)';

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
        $action = $this->argument('action');
        $queue = $this->option('queue');
        $force = $this->option('force');

        return match ($action) {
            'clear-failed' => $this->clearFailedJobs($queue, $force),
            'retry-failed' => $this->retryFailedJobs($queue, $force),
            'purge' => $this->purgeQueue($queue, $force),
            default => $this->handleInvalidAction($action)
        };
    }

    /**
     * Clear failed jobs
     */
    protected function clearFailedJobs(?string $queue, bool $force): int
    {
        $target = $queue ? "queue '{$queue}'" : 'all queues';

        if (!$force && !$this->confirm("Are you sure you want to clear failed jobs for {$target}?")) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        $this->info("Clearing failed jobs for {$target}...");

        $count = $this->queueMonitoring->clearFailedJobs($queue);

        $this->info("✅ Cleared {$count} failed jobs.");

        return Command::SUCCESS;
    }

    /**
     * Retry failed jobs
     */
    protected function retryFailedJobs(?string $queue, bool $force): int
    {
        $target = $queue ? "queue '{$queue}'" : 'all queues';

        if (!$force && !$this->confirm("Are you sure you want to retry failed jobs for {$target}?")) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        $this->info("Retrying failed jobs for {$target}...");

        $count = $this->queueMonitoring->retryFailedJobs($queue);

        $this->info("✅ Retried {$count} failed jobs.");

        return Command::SUCCESS;
    }

    /**
     * Purge queue
     */
    protected function purgeQueue(?string $queue, bool $force): int
    {
        if (!$queue) {
            $this->error('Queue name is required for purge operation.');
            return Command::FAILURE;
        }

        if (!$force && !$this->confirm("Are you sure you want to purge all jobs from queue '{$queue}'? This action cannot be undone.")) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        $this->warn("Purging all jobs from queue '{$queue}'...");

        $success = $this->queueMonitoring->purgeQueue($queue);

        if ($success) {
            $this->info("✅ Successfully purged queue '{$queue}'.");
            return Command::SUCCESS;
        } else {
            $this->error("❌ Failed to purge queue '{$queue}'.");
            return Command::FAILURE;
        }
    }

    /**
     * Handle invalid action
     */
    protected function handleInvalidAction(string $action): int
    {
        $this->error("Invalid action: {$action}");
        $this->info('Available actions: clear-failed, retry-failed, purge');
        return Command::FAILURE;
    }
}