<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

abstract class BaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Determine if the job should be retried.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [60, 300, 900]; // 1 minute, 5 minutes, 15 minutes
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job failed permanently', [
            'job' => static::class,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'attempts' => $this->attempts()
        ]);

        $this->handleJobFailure($exception);
    }

    /**
     * Handle job failure - to be implemented by child classes
     */
    protected function handleJobFailure(\Throwable $exception): void
    {
        // Override in child classes for specific failure handling
    }

    /**
     * Log job start
     */
    protected function logJobStart(array $context = []): void
    {
        Log::info('Job started', array_merge([
            'job' => static::class,
            'attempt' => $this->attempts()
        ], $context));
    }

    /**
     * Log job completion
     */
    protected function logJobComplete(array $context = []): void
    {
        Log::info('Job completed successfully', array_merge([
            'job' => static::class,
            'attempt' => $this->attempts()
        ], $context));
    }

    /**
     * Log job error
     */
    protected function logJobError(\Throwable $exception, array $context = []): void
    {
        Log::error('Job error occurred', array_merge([
            'job' => static::class,
            'error' => $exception->getMessage(),
            'attempt' => $this->attempts()
        ], $context));
    }
}