<?php

namespace App\Jobs;

abstract class FileProcessingJob extends BaseJob
{
    /**
     * The queue this job should be dispatched to.
     */
    public string $queue = 'files';

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public int $timeout = 600; // 10 minutes for file processing

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue($this->queue);
    }

    /**
     * Handle job failure specific to file processing jobs
     */
    protected function handleJobFailure(\Throwable $exception): void
    {
        $this->logJobError($exception, [
            'queue' => $this->queue,
            'job_type' => 'file_processing'
        ]);
    }

    /**
     * Calculate backoff for file processing jobs
     */
    public function backoff(): array
    {
        return [120, 600, 1800]; // 2 minutes, 10 minutes, 30 minutes
    }
}