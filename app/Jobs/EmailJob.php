<?php

namespace App\Jobs;

use App\Services\EmailService;

abstract class EmailJob extends BaseJob
{
    /**
     * The queue this job should be dispatched to.
     */
    public $queue = 'emails';

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 5;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public int $timeout = 120; // 2 minutes

    protected EmailService $emailService;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue($this->queue);
    }

    /**
     * Handle job failure specific to email jobs
     */
    protected function handleJobFailure(\Throwable $exception): void
    {
        // Could implement email-specific failure handling
        // such as storing failed emails for manual retry
        $this->logJobError($exception, [
            'queue' => $this->queue,
            'job_type' => 'email'
        ]);
    }

    /**
     * Calculate backoff for email jobs
     */
    public function backoff(): array
    {
        return [30, 120, 300, 600, 1800]; // 30s, 2m, 5m, 10m, 30m
    }
}