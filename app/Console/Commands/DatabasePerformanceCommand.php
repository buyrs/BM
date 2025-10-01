<?php

namespace App\Console\Commands;

use App\Services\DatabasePerformanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DatabasePerformanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:performance 
                            {action : The action to perform (analyze|optimize|clear|metrics)}
                            {--hours=24 : Hours of data to analyze}
                            {--threshold=1000 : Slow query threshold in milliseconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze and optimize database performance';

    protected DatabasePerformanceService $performanceService;

    public function __construct(DatabasePerformanceService $performanceService)
    {
        parent::__construct();
        $this->performanceService = $performanceService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'analyze':
                return $this->analyzePerformance();
            case 'optimize':
                return $this->optimizeDatabase();
            case 'clear':
                return $this->clearAnalysisData();
            case 'metrics':
                return $this->showMetrics();
            default:
                $this->error("Unknown action: {$action}");
                $this->info('Available actions: analyze, optimize, clear, metrics');
                return 1;
        }
    }

    /**
     * Analyze database performance
     */
    protected function analyzePerformance(): int
    {
        $this->info('Analyzing database performance...');

        $hours = (int) $this->option('hours');
        $analysis = $this->performanceService->analyzeQueryPerformance();

        if ($analysis['total_slow_queries'] === 0) {
            $this->info('No slow queries found in the last ' . $hours . ' hours.');
            return 0;
        }

        $this->info("Found {$analysis['total_slow_queries']} slow queries");
        $this->info("Average execution time: {$analysis['average_execution_time']}ms");

        if (!empty($analysis['most_frequent_slow_queries'])) {
            $this->info("\nMost frequent slow queries:");
            $this->table(
                ['SQL Pattern', 'Count', 'Avg Time (ms)', 'Total Time (ms)'],
                collect($analysis['most_frequent_slow_queries'])->map(function ($query) {
                    return [
                        substr($query['sql'], 0, 80) . '...',
                        $query['count'],
                        round($query['avg_time'], 2),
                        round($query['total_time'], 2),
                    ];
                })->toArray()
            );
        }

        if (!empty($analysis['suggestions'])) {
            $this->info("\nOptimization suggestions:");
            foreach ($analysis['suggestions'] as $suggestion) {
                $this->warn("• {$suggestion['message']}");
                if (isset($suggestion['query'])) {
                    $this->line("  Query: " . substr($suggestion['query'], 0, 100) . '...');
                }
            }
        }

        return 0;
    }

    /**
     * Optimize database settings
     */
    protected function optimizeDatabase(): int
    {
        $this->info('Optimizing database connection settings...');

        try {
            $optimizations = $this->performanceService->optimizeConnectionSettings();

            if (empty($optimizations)) {
                $this->info('No optimizations were applied.');
                return 0;
            }

            $this->info('Applied optimizations:');
            foreach ($optimizations as $optimization) {
                $this->line("• {$optimization}");
            }

            $this->info('Database optimization completed successfully.');
            return 0;

        } catch (\Exception $e) {
            $this->error('Failed to optimize database: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Clear analysis data
     */
    protected function clearAnalysisData(): int
    {
        $this->info('Clearing performance analysis data...');

        try {
            $this->performanceService->clearAnalysisCache();
            $this->info('Performance analysis data cleared successfully.');
            return 0;

        } catch (\Exception $e) {
            $this->error('Failed to clear analysis data: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show performance metrics
     */
    protected function showMetrics(): int
    {
        $this->info('Database Performance Metrics');
        $this->info('============================');

        try {
            $metrics = $this->performanceService->getPerformanceMetrics();

            $this->info("Slow Query Count: {$metrics['slow_query_count']}");
            $this->info("Slow Query Threshold: {$metrics['slow_query_threshold']}ms");
            $this->info("Query Logging: " . ($metrics['query_logging_enabled'] ? 'Enabled' : 'Disabled'));

            if (isset($metrics['connection_info'])) {
                $this->info("\nConnection Information:");
                foreach ($metrics['connection_info'] as $key => $value) {
                    $this->info("  {$key}: {$value}");
                }
            }

            // Show database size information
            $this->showDatabaseSize();

            return 0;

        } catch (\Exception $e) {
            $this->error('Failed to retrieve metrics: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show database size information
     */
    protected function showDatabaseSize(): void
    {
        try {
            $driver = config('database.default');

            switch ($driver) {
                case 'mysql':
                    $this->showMysqlSize();
                    break;
                case 'pgsql':
                    $this->showPostgresSize();
                    break;
                case 'sqlite':
                    $this->showSqliteSize();
                    break;
            }
        } catch (\Exception $e) {
            $this->warn('Could not retrieve database size information: ' . $e->getMessage());
        }
    }

    /**
     * Show MySQL database size
     */
    protected function showMysqlSize(): void
    {
        $database = config('database.connections.mysql.database');
        $result = DB::select("
            SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
            FROM information_schema.tables 
            WHERE table_schema = ?
        ", [$database]);

        if (!empty($result)) {
            $this->info("Database Size: {$result[0]->size_mb} MB");
        }
    }

    /**
     * Show PostgreSQL database size
     */
    protected function showPostgresSize(): void
    {
        $database = config('database.connections.pgsql.database');
        $result = DB::select("SELECT pg_size_pretty(pg_database_size(?)) as size", [$database]);

        if (!empty($result)) {
            $this->info("Database Size: {$result[0]->size}");
        }
    }

    /**
     * Show SQLite database size
     */
    protected function showSqliteSize(): void
    {
        $database = config('database.connections.sqlite.database');
        
        if (file_exists($database)) {
            $size = filesize($database);
            $sizeMb = round($size / 1024 / 1024, 2);
            $this->info("Database Size: {$sizeMb} MB");
        }
    }
}
