<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueueMonitoringService extends BaseService implements \App\Contracts\QueueMonitoringInterface
{
    protected array $queues = ['default', 'emails', 'notifications', 'files', 'reports'];

    /**
     * Get queue statistics
     */
    public function getQueueStats(): array
    {
        $stats = [];

        foreach ($this->queues as $queue) {
            $stats[$queue] = $this->getQueueInfo($queue);
        }

        return $stats;
    }

    /**
     * Get information for a specific queue
     */
    public function getQueueInfo(string $queue): array
    {
        try {
            $redis = Redis::connection('queues');
            
            return [
                'name' => $queue,
                'waiting' => $redis->llen("queues:{$queue}"),
                'delayed' => $redis->zcard("queues:{$queue}:delayed"),
                'reserved' => $redis->zcard("queues:{$queue}:reserved"),
                'failed' => $this->getFailedJobsCount($queue),
                'processed_today' => $this->getProcessedJobsToday($queue),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get queue info', [
                'queue' => $queue,
                'error' => $e->getMessage()
            ]);

            return [
                'name' => $queue,
                'waiting' => 'unknown',
                'delayed' => 'unknown',
                'reserved' => 'unknown',
                'failed' => 'unknown',
                'processed_today' => 'unknown',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get failed jobs count for a queue
     */
    protected function getFailedJobsCount(string $queue): int
    {
        try {
            return DB::table('failed_jobs')
                ->where('queue', $queue)
                ->count();
        } catch (\Exception $e) {
            Log::error('Failed to get failed jobs count', [
                'queue' => $queue,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get processed jobs count for today
     */
    protected function getProcessedJobsToday(string $queue): int
    {
        // This would require additional logging/tracking
        // For now, return 0 as placeholder
        return 0;
    }

    /**
     * Clear failed jobs for a queue
     */
    public function clearFailedJobs(string $queue = null): int
    {
        try {
            $query = DB::table('failed_jobs');
            
            if ($queue) {
                $query->where('queue', $queue);
            }

            $count = $query->count();
            $query->delete();

            Log::info('Cleared failed jobs', [
                'queue' => $queue ?? 'all',
                'count' => $count
            ]);

            return $count;
        } catch (\Exception $e) {
            Log::error('Failed to clear failed jobs', [
                'queue' => $queue,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Retry failed jobs for a queue
     */
    public function retryFailedJobs(string $queue = null): int
    {
        try {
            $query = DB::table('failed_jobs');
            
            if ($queue) {
                $query->where('queue', $queue);
            }

            $failedJobs = $query->get();
            $retryCount = 0;

            foreach ($failedJobs as $failedJob) {
                try {
                    // Decode the payload and re-dispatch
                    $payload = json_decode($failedJob->payload, true);
                    
                    if ($payload && isset($payload['data']['command'])) {
                        $command = unserialize($payload['data']['command']);
                        dispatch($command);
                        
                        // Remove from failed jobs table
                        DB::table('failed_jobs')->where('id', $failedJob->id)->delete();
                        $retryCount++;
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to retry job', [
                        'failed_job_id' => $failedJob->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Retried failed jobs', [
                'queue' => $queue ?? 'all',
                'retry_count' => $retryCount
            ]);

            return $retryCount;
        } catch (\Exception $e) {
            Log::error('Failed to retry failed jobs', [
                'queue' => $queue,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get queue health status
     */
    public function getQueueHealth(): array
    {
        $stats = $this->getQueueStats();
        $health = [
            'status' => 'healthy',
            'issues' => [],
            'recommendations' => []
        ];

        foreach ($stats as $queueName => $queueStats) {
            // Check for high number of waiting jobs
            if (is_numeric($queueStats['waiting']) && $queueStats['waiting'] > 1000) {
                $health['status'] = 'warning';
                $health['issues'][] = "Queue '{$queueName}' has {$queueStats['waiting']} waiting jobs";
                $health['recommendations'][] = "Consider scaling up workers for queue '{$queueName}'";
            }

            // Check for failed jobs
            if (is_numeric($queueStats['failed']) && $queueStats['failed'] > 10) {
                $health['status'] = 'warning';
                $health['issues'][] = "Queue '{$queueName}' has {$queueStats['failed']} failed jobs";
                $health['recommendations'][] = "Review and retry failed jobs for queue '{$queueName}'";
            }

            // Check for stuck reserved jobs
            if (is_numeric($queueStats['reserved']) && $queueStats['reserved'] > 100) {
                $health['status'] = 'critical';
                $health['issues'][] = "Queue '{$queueName}' has {$queueStats['reserved']} reserved jobs (possibly stuck)";
                $health['recommendations'][] = "Restart queue workers for queue '{$queueName}'";
            }
        }

        return $health;
    }

    /**
     * Purge all jobs from a queue
     */
    public function purgeQueue(string $queue): bool
    {
        try {
            $redis = Redis::connection('queues');
            
            // Clear waiting jobs
            $redis->del("queues:{$queue}");
            
            // Clear delayed jobs
            $redis->del("queues:{$queue}:delayed");
            
            // Clear reserved jobs
            $redis->del("queues:{$queue}:reserved");

            Log::info('Purged queue', ['queue' => $queue]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to purge queue', [
                'queue' => $queue,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}