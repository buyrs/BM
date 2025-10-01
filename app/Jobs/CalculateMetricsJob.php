<?php

namespace App\Jobs;

use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CalculateMetricsJob extends BaseJob
{
    protected string $period;

    /**
     * Create a new job instance.
     */
    public function __construct(string $period = 'daily')
    {
        parent::__construct();
        $this->period = $period;
    }

    /**
     * Execute the job.
     */
    public function handle(AnalyticsService $analyticsService): void
    {
        try {
            Log::info("Starting metrics calculation for period: {$this->period}");

            // Clear existing cache to ensure fresh data
            $analyticsService->clearCache();

            // Calculate metrics for different time periods
            $periods = $this->getCalculationPeriods();

            foreach ($periods as $periodName => $dates) {
                Log::info("Calculating metrics for {$periodName}", [
                    'start_date' => $dates['start']->toDateString(),
                    'end_date' => $dates['end']->toDateString(),
                ]);

                // Pre-calculate and cache metrics
                $analyticsService->getMissionMetrics($dates['start'], $dates['end']);
                $analyticsService->getUserPerformanceMetrics($dates['start'], $dates['end']);
                $analyticsService->getPropertyMetrics($dates['start'], $dates['end']);
                $analyticsService->getMaintenanceMetrics($dates['start'], $dates['end']);

                // Calculate trending data
                $trendingMetrics = ['missions_created', 'missions_completed', 'checklists_completed'];
                foreach ($trendingMetrics as $metric) {
                    $analyticsService->getTrendingData($metric, $dates['start'], $dates['end'], 'daily');
                }
            }

            // Always calculate system metrics
            $analyticsService->getSystemMetrics();

            Log::info("Metrics calculation completed successfully for period: {$this->period}");

        } catch (\Exception $e) {
            Log::error("Failed to calculate metrics for period: {$this->period}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get calculation periods based on job period
     */
    private function getCalculationPeriods(): array
    {
        $now = Carbon::now();

        switch ($this->period) {
            case 'hourly':
                return [
                    'last_24_hours' => [
                        'start' => $now->copy()->subHours(24),
                        'end' => $now,
                    ],
                ];

            case 'daily':
                return [
                    'last_7_days' => [
                        'start' => $now->copy()->subDays(7),
                        'end' => $now,
                    ],
                    'last_30_days' => [
                        'start' => $now->copy()->subDays(30),
                        'end' => $now,
                    ],
                ];

            case 'weekly':
                return [
                    'last_4_weeks' => [
                        'start' => $now->copy()->subWeeks(4),
                        'end' => $now,
                    ],
                    'last_12_weeks' => [
                        'start' => $now->copy()->subWeeks(12),
                        'end' => $now,
                    ],
                ];

            case 'monthly':
                return [
                    'last_3_months' => [
                        'start' => $now->copy()->subMonths(3),
                        'end' => $now,
                    ],
                    'last_12_months' => [
                        'start' => $now->copy()->subMonths(12),
                        'end' => $now,
                    ],
                ];

            default:
                return [
                    'default' => [
                        'start' => $now->copy()->subDays(30),
                        'end' => $now,
                    ],
                ];
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['analytics', 'metrics', $this->period];
    }
}