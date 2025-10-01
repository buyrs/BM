<?php

namespace App\Console\Commands;

use App\Jobs\CalculateMetricsJob;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

class CalculateAnalyticsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'analytics:calculate 
                            {--period=daily : The calculation period (hourly, daily, weekly, monthly)}
                            {--queue : Run the calculation in the background queue}
                            {--clear-cache : Clear analytics cache before calculation}';

    /**
     * The console command description.
     */
    protected $description = 'Calculate and cache analytics metrics';

    /**
     * Execute the console command.
     */
    public function handle(AnalyticsService $analyticsService): int
    {
        $period = $this->option('period');
        $useQueue = $this->option('queue');
        $clearCache = $this->option('clear-cache');

        $this->info("Starting analytics calculation for period: {$period}");

        if ($clearCache) {
            $this->info('Clearing analytics cache...');
            $analyticsService->clearCache();
            $this->info('Cache cleared successfully.');
        }

        if ($useQueue) {
            $this->info('Dispatching metrics calculation job to queue...');
            CalculateMetricsJob::dispatch($period);
            $this->info('Job dispatched successfully. Check queue status for progress.');
        } else {
            $this->info('Calculating metrics synchronously...');
            
            try {
                $job = new CalculateMetricsJob($period);
                $job->handle($analyticsService);
                
                $this->info('Metrics calculation completed successfully.');
                
                // Display some sample metrics
                $this->displaySampleMetrics($analyticsService);
                
            } catch (\Exception $e) {
                $this->error("Failed to calculate metrics: {$e->getMessage()}");
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Display sample metrics for verification
     */
    private function displaySampleMetrics(AnalyticsService $analyticsService): void
    {
        $this->info("\n--- Sample Metrics ---");
        
        try {
            $missionMetrics = $analyticsService->getMissionMetrics();
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Missions', $missionMetrics['total_missions']],
                    ['Completed Missions', $missionMetrics['completed_missions']],
                    ['Completion Rate', $missionMetrics['completion_rate'] . '%'],
                    ['Avg Completion Time', $missionMetrics['avg_completion_time_hours'] . ' hours'],
                ]
            );

            $systemMetrics = $analyticsService->getSystemMetrics();
            $this->table(
                ['System Metric', 'Value'],
                [
                    ['Total Users', $systemMetrics['total_users']],
                    ['Active Users (7 days)', $systemMetrics['active_users']],
                    ['User Activity Rate', $systemMetrics['user_activity_rate'] . '%'],
                    ['Recent Missions (7 days)', $systemMetrics['recent_missions']],
                ]
            );

        } catch (\Exception $e) {
            $this->warn("Could not display sample metrics: {$e->getMessage()}");
        }
    }
}