<?php

namespace App\Contracts;

interface QueueMonitoringInterface
{
    /**
     * Get queue statistics
     */
    public function getQueueStats(): array;

    /**
     * Get information for a specific queue
     */
    public function getQueueInfo(string $queue): array;

    /**
     * Clear failed jobs for a queue
     */
    public function clearFailedJobs(string $queue = null): int;

    /**
     * Retry failed jobs for a queue
     */
    public function retryFailedJobs(string $queue = null): int;

    /**
     * Get queue health status
     */
    public function getQueueHealth(): array;

    /**
     * Purge all jobs from a queue
     */
    public function purgeQueue(string $queue): bool;
}