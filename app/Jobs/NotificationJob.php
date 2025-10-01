<?php

namespace App\Jobs;

abstract class NotificationJob extends BaseJob
{
    /**
     * The queue this job should be dispatched to.
     */
    public string $queue = 'notifications';

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 4;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public int $timeout = 60; // 1 minute

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue($this->queue);
    }

    /**
     * Handle job failure specific to notification jobs
     */
    protected function handleJobFailure(\Throwable $exception): void
    {
        $this->logJobError($exception, [
            'queue' => $this->queue,
            'job_type' => 'notification'
        ]);
    }

    /**
     * Calculate backoff for notification jobs
     */
    public function backoff(): array
    {
        return [30, 180, 600, 1800]; // 30s, 3m, 10m, 30m
    }
}