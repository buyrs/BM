<?php

namespace App\Console\Commands;

use App\Services\CacheService;
use Illuminate\Console\Command;

class WarmCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm {--force : Force cache warming even if cache is not empty}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up the application cache with frequently accessed data';

    protected CacheService $cacheService;

    /**
     * Create a new command instance.
     */
    public function __construct(CacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting cache warming process...');

        try {
            // Check if we should force warming
            $force = $this->option('force');

            if (!$force) {
                $this->info('Checking current cache status...');
                $stats = $this->cacheService->getStats();
                $this->table(['Metric', 'Value'], [
                    ['Memory Used', $stats['memory_used'] ?? 'unknown'],
                    ['Hit Rate', $stats['hit_rate'] ?? 'unknown'],
                    ['Connected Clients', $stats['connected_clients'] ?? 'unknown'],
                ]);
            }

            // Warm the cache
            $this->cacheService->warmCache();

            $this->info('✅ Cache warming completed successfully!');

            // Show updated stats
            $this->info('Updated cache statistics:');
            $stats = $this->cacheService->getStats();
            $this->table(['Metric', 'Value'], [
                ['Memory Used', $stats['memory_used'] ?? 'unknown'],
                ['Hit Rate', $stats['hit_rate'] ?? 'unknown'],
                ['Connected Clients', $stats['connected_clients'] ?? 'unknown'],
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Cache warming failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}