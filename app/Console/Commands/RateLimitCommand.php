<?php

namespace App\Console\Commands;

use App\Services\RateLimitService;
use Illuminate\Console\Command;

class RateLimitCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'rate-limit:manage 
                            {action : The action to perform (stats|clear|config)}
                            {--key= : Specific key to clear (for clear action)}
                            {--route= : Route name to configure (for config action)}
                            {--attempts= : Max attempts (for config action)}
                            {--decay= : Decay minutes (for config action)}';

    /**
     * The console command description.
     */
    protected $description = 'Manage API rate limiting';

    public function __construct(
        private RateLimitService $rateLimitService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'stats' => $this->showStats(),
            'clear' => $this->clearRateLimit(),
            'config' => $this->showOrUpdateConfig(),
            default => $this->error("Unknown action: {$action}. Use: stats, clear, or config")
        };
    }

    /**
     * Show rate limit statistics.
     */
    private function showStats(): int
    {
        $stats = $this->rateLimitService->getRateLimitStats();

        if (empty($stats)) {
            $this->info('No active rate limits found.');
            return 0;
        }

        $this->info('Current Rate Limit Statistics:');
        $this->newLine();

        $headers = ['Identifier', 'Route', 'Attempts', 'TTL (seconds)', 'Expires At'];
        $rows = [];

        foreach ($stats as $stat) {
            $rows[] = [
                $stat['identifier'],
                $stat['route'],
                $stat['attempts'],
                $stat['ttl'],
                $stat['expires_at']
            ];
        }

        $this->table($headers, $rows);

        return 0;
    }

    /**
     * Clear rate limit for a specific key or all keys.
     */
    private function clearRateLimit(): int
    {
        $key = $this->option('key');

        if ($key) {
            $this->rateLimitService->clearRateLimit($key);
            $this->info("Rate limit cleared for key: {$key}");
        } else {
            if ($this->confirm('Are you sure you want to clear ALL rate limits?')) {
                $stats = $this->rateLimitService->getRateLimitStats();
                $count = 0;

                foreach ($stats as $stat) {
                    $fullKey = "rate_limit:{$stat['identifier']}:{$stat['route']}";
                    $this->rateLimitService->clearRateLimit($fullKey);
                    $count++;
                }

                $this->info("Cleared {$count} rate limit entries.");
            } else {
                $this->info('Operation cancelled.');
            }
        }

        return 0;
    }

    /**
     * Show or update rate limit configuration.
     */
    private function showOrUpdateConfig(): int
    {
        $route = $this->option('route');
        $attempts = $this->option('attempts');
        $decay = $this->option('decay');

        if ($route && $attempts && $decay) {
            // Update configuration
            $this->rateLimitService->updateRateLimit($route, (int) $attempts, (int) $decay);
            $this->info("Updated rate limit for {$route}: {$attempts} attempts per {$decay} minutes");
            return 0;
        }

        // Show current configuration
        $config = $this->rateLimitService->getAllRateLimits();
        
        $this->info('Current Rate Limit Configuration:');
        $this->newLine();

        $headers = ['Route', 'Max Attempts', 'Decay (minutes)'];
        $rows = [];

        foreach ($config as $routeName => $limits) {
            $rows[] = [
                $routeName,
                $limits['attempts'],
                $limits['decay']
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->info('To update a route configuration:');
        $this->line('php artisan rate-limit:manage config --route=api.auth.login --attempts=10 --decay=15');

        return 0;
    }
}