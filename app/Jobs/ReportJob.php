<?php

namespace App\Jobs;

abstract class ReportJob extends BaseJob
{
    /**
     * The queue this job should be dispatched to.
     */
    public string $queue = 'reports';

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public int $timeout = 1800; // 30 minutes for report generation

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue($this->queue);
    }

    /**
     * Handle job failure specific to report jobs
     */
    protected function handleJobFailure(\Throwable $exception): void
    {
        $this->logJobError($exception, [
            'queue' => $this->queue,
            'job_type' => 'report'
        ]);
    }

    /**
     * Calculate backoff for report jobs
     */
    public function backoff(): array
    {
        return [300, 1800]; // 5 minutes, 30 minutes
    }
}