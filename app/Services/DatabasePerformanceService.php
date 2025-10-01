<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Event;

class DatabasePerformanceService extends BaseService
{
    protected array $slowQueries = [];
    protected float $slowQueryThreshold;
    protected bool $queryLoggingEnabled;

    public function __construct()
    {
        $this->slowQueryThreshold = config('database.slow_query_threshold', 1000); // 1 second
        $this->queryLoggingEnabled = config('database.log_slow_queries', false);
        
        if ($this->queryLoggingEnabled) {
            $this->enableSlowQueryLogging();
        }
    }

    /**
     * Enable slow query logging
     */
    public function enableSlowQueryLogging(): void
    {
        Event::listen(QueryExecuted::class, function (QueryExecuted $query) {
            if ($query->time > $this->slowQueryThreshold) {
                $this->logSlowQuery($query);
            }
        });
    }

    /**
     * Log slow query for analysis
     */
    protected function logSlowQuery(QueryExecuted $query): void
    {
        $slowQuery = [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time,
            'connection' => $query->connectionName,
            'timestamp' => now()->toISOString(),
        ];

        $this->slowQueries[] = $slowQuery;

        Log::warning('Slow query detected', $slowQuery);

        // Store in cache for analysis
        $cacheKey = 'slow_queries:' . date('Y-m-d-H');
        $existingQueries = Cache::get($cacheKey, []);
        $existingQueries[] = $slowQuery;
        Cache::put($cacheKey, $existingQueries, now()->addHours(24));
    }

    /**
     * Get slow queries from cache
     */
    public function getSlowQueries(int $hours = 24): array
    {
        $queries = [];
        
        for ($i = 0; $i < $hours; $i++) {
            $cacheKey = 'slow_queries:' . now()->subHours($i)->format('Y-m-d-H');
            $hourlyQueries = Cache::get($cacheKey, []);
            $queries = array_merge($queries, $hourlyQueries);
        }

        return collect($queries)
            ->sortByDesc('time')
            ->take(100)
            ->values()
            ->all();
    }

    /**
     * Analyze query performance and suggest optimizations
     */
    public function analyzeQueryPerformance(): array
    {
        $slowQueries = $this->getSlowQueries();
        $analysis = [
            'total_slow_queries' => count($slowQueries),
            'average_execution_time' => 0,
            'most_frequent_slow_queries' => [],
            'suggestions' => [],
        ];

        if (empty($slowQueries)) {
            return $analysis;
        }

        // Calculate average execution time
        $totalTime = array_sum(array_column($slowQueries, 'time'));
        $analysis['average_execution_time'] = $totalTime / count($slowQueries);

        // Find most frequent slow queries
        $queryFrequency = [];
        foreach ($slowQueries as $query) {
            $normalizedSql = $this->normalizeSql($query['sql']);
            if (!isset($queryFrequency[$normalizedSql])) {
                $queryFrequency[$normalizedSql] = [
                    'sql' => $normalizedSql,
                    'count' => 0,
                    'total_time' => 0,
                    'avg_time' => 0,
                ];
            }
            $queryFrequency[$normalizedSql]['count']++;
            $queryFrequency[$normalizedSql]['total_time'] += $query['time'];
        }

        // Calculate average time and sort by frequency
        foreach ($queryFrequency as &$queryData) {
            $queryData['avg_time'] = $queryData['total_time'] / $queryData['count'];
        }

        $analysis['most_frequent_slow_queries'] = collect($queryFrequency)
            ->sortByDesc('count')
            ->take(10)
            ->values()
            ->all();

        // Generate optimization suggestions
        $analysis['suggestions'] = $this->generateOptimizationSuggestions($slowQueries);

        return $analysis;
    }

    /**
     * Normalize SQL query for frequency analysis
     */
    protected function normalizeSql(string $sql): string
    {
        // Remove specific values and normalize for pattern matching
        $normalized = preg_replace('/\b\d+\b/', '?', $sql);
        $normalized = preg_replace('/\'[^\']*\'/', '?', $normalized);
        $normalized = preg_replace('/\"[^\"]*\"/', '?', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        return trim($normalized);
    }

    /**
     * Generate optimization suggestions based on slow queries
     */
    protected function generateOptimizationSuggestions(array $slowQueries): array
    {
        $suggestions = [];

        foreach ($slowQueries as $query) {
            $sql = strtolower($query['sql']);

            // Check for missing indexes
            if (strpos($sql, 'where') !== false && strpos($sql, 'index') === false) {
                $suggestions[] = [
                    'type' => 'missing_index',
                    'message' => 'Consider adding indexes for WHERE clause conditions',
                    'query' => $query['sql'],
                    'time' => $query['time'],
                ];
            }

            // Check for N+1 queries
            if (strpos($sql, 'select') !== false && $query['time'] < 100) {
                $suggestions[] = [
                    'type' => 'n_plus_one',
                    'message' => 'Potential N+1 query detected. Consider using eager loading',
                    'query' => $query['sql'],
                    'time' => $query['time'],
                ];
            }

            // Check for large result sets without limits
            if (strpos($sql, 'select') !== false && strpos($sql, 'limit') === false) {
                $suggestions[] = [
                    'type' => 'missing_limit',
                    'message' => 'Query without LIMIT clause may return large result sets',
                    'query' => $query['sql'],
                    'time' => $query['time'],
                ];
            }

            // Check for inefficient JOINs
            if (strpos($sql, 'join') !== false && $query['time'] > 500) {
                $suggestions[] = [
                    'type' => 'inefficient_join',
                    'message' => 'JOIN operation is slow. Check if proper indexes exist on join columns',
                    'query' => $query['sql'],
                    'time' => $query['time'],
                ];
            }
        }

        return array_unique($suggestions, SORT_REGULAR);
    }

    /**
     * Optimize database connection settings
     */
    public function optimizeConnectionSettings(): array
    {
        $optimizations = [];

        try {
            $driver = config('database.default');
            
            switch ($driver) {
                case 'mysql':
                    $optimizations = $this->optimizeMysqlSettings();
                    break;
                case 'pgsql':
                    $optimizations = $this->optimizePostgresSettings();
                    break;
                case 'sqlite':
                    $optimizations = $this->optimizeSqliteSettings();
                    break;
            }

            Log::info('Database connection optimizations applied', $optimizations);
        } catch (\Exception $e) {
            Log::error('Failed to apply database optimizations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $optimizations;
    }

    /**
     * Optimize MySQL connection settings
     */
    protected function optimizeMysqlSettings(): array
    {
        $optimizations = [];

        try {
            // Set connection timeout
            DB::statement("SET SESSION wait_timeout = 28800");
            $optimizations[] = 'Set wait_timeout to 8 hours';

            // Optimize query cache
            DB::statement("SET SESSION query_cache_type = ON");
            $optimizations[] = 'Enabled query cache';

            // Set transaction isolation level for better performance
            DB::statement("SET SESSION transaction_isolation = 'READ-COMMITTED'");
            $optimizations[] = 'Set transaction isolation to READ-COMMITTED';

        } catch (\Exception $e) {
            Log::warning('Some MySQL optimizations failed', ['error' => $e->getMessage()]);
        }

        return $optimizations;
    }

    /**
     * Optimize PostgreSQL connection settings
     */
    protected function optimizePostgresSettings(): array
    {
        $optimizations = [];

        try {
            // Set work memory for complex queries
            DB::statement("SET work_mem = '64MB'");
            $optimizations[] = 'Set work_mem to 64MB';

            // Enable parallel query execution
            DB::statement("SET max_parallel_workers_per_gather = 2");
            $optimizations[] = 'Enabled parallel query execution';

        } catch (\Exception $e) {
            Log::warning('Some PostgreSQL optimizations failed', ['error' => $e->getMessage()]);
        }

        return $optimizations;
    }

    /**
     * Optimize SQLite connection settings
     */
    protected function optimizeSqliteSettings(): array
    {
        $optimizations = [];

        try {
            // Enable WAL mode for better concurrency
            DB::statement("PRAGMA journal_mode = WAL");
            $optimizations[] = 'Enabled WAL journal mode';

            // Set cache size
            DB::statement("PRAGMA cache_size = -64000"); // 64MB
            $optimizations[] = 'Set cache size to 64MB';

            // Enable memory-mapped I/O
            DB::statement("PRAGMA mmap_size = 268435456"); // 256MB
            $optimizations[] = 'Enabled memory-mapped I/O';

            // Optimize synchronous mode
            DB::statement("PRAGMA synchronous = NORMAL");
            $optimizations[] = 'Set synchronous mode to NORMAL';

        } catch (\Exception $e) {
            Log::warning('Some SQLite optimizations failed', ['error' => $e->getMessage()]);
        }

        return $optimizations;
    }

    /**
     * Clear query analysis cache
     */
    public function clearAnalysisCache(): void
    {
        $pattern = 'slow_queries:*';
        $keys = Cache::getRedis()->keys($pattern);
        
        if (!empty($keys)) {
            Cache::getRedis()->del($keys);
        }

        $this->slowQueries = [];
    }

    /**
     * Get database performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        $metrics = [
            'slow_query_count' => count($this->getSlowQueries()),
            'slow_query_threshold' => $this->slowQueryThreshold,
            'query_logging_enabled' => $this->queryLoggingEnabled,
            'connection_info' => $this->getConnectionInfo(),
        ];

        return $metrics;
    }

    /**
     * Get database connection information
     */
    protected function getConnectionInfo(): array
    {
        try {
            $driver = config('database.default');
            $connection = DB::connection();
            
            return [
                'driver' => $driver,
                'database' => $connection->getDatabaseName(),
                'host' => config("database.connections.{$driver}.host"),
                'port' => config("database.connections.{$driver}.port"),
            ];
        } catch (\Exception $e) {
            return ['error' => 'Unable to retrieve connection info'];
        }
    }
}