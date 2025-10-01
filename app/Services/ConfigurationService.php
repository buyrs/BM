<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ConfigurationService extends BaseService
{
    protected array $environmentConfigs = [];

    protected function getDefaultConfig(): array
    {
        return [
            'environments' => ['local', 'staging', 'production'],
            'config_cache_ttl' => 3600,
        ];
    }

    /**
     * Get environment-specific configuration
     */
    public function getEnvironmentConfig(string $key, $default = null)
    {
        $environment = app()->environment();
        $cacheKey = "env_config:{$environment}:{$key}";

        return $this->getCached($cacheKey, function () use ($key, $default, $environment) {
            // Try environment-specific config first
            $envKey = "{$key}_{$environment}";
            $value = config($envKey);

            if ($value !== null) {
                return $value;
            }

            // Fall back to general config
            return config($key, $default);
        }, $this->config['config_cache_ttl']);
    }

    /**
     * Set environment-specific configuration
     */
    public function setEnvironmentConfig(string $key, $value): void
    {
        $environment = app()->environment();
        $envKey = "{$key}_{$environment}";
        
        Config::set($envKey, $value);
        
        // Clear cache
        $cacheKey = "env_config:{$environment}:{$key}";
        $this->forgetCached($cacheKey);

        $this->logInfo('Environment configuration updated', [
            'key' => $key,
            'environment' => $environment
        ]);
    }

    /**
     * Get production-ready configuration
     */
    public function getProductionConfig(): array
    {
        return [
            'app' => [
                'debug' => false,
                'env' => 'production',
                'log_level' => 'error',
            ],
            'database' => [
                'default' => 'mysql', // or postgresql
                'connections' => [
                    'mysql' => [
                        'strict' => true,
                        'engine' => 'InnoDB',
                    ]
                ]
            ],
            'cache' => [
                'default' => 'redis',
            ],
            'session' => [
                'driver' => 'redis',
                'secure' => true,
                'http_only' => true,
                'same_site' => 'strict',
            ],
            'queue' => [
                'default' => 'redis',
            ],
            'mail' => [
                'default' => $this->getEnvironmentConfig('mail.production_mailer', 'smtp'),
                'queue_emails' => true,
            ],
            'logging' => [
                'default' => 'stack',
                'channels' => [
                    'stack' => [
                        'driver' => 'stack',
                        'channels' => ['daily', 'slack'],
                    ]
                ]
            ]
        ];
    }

    /**
     * Validate production configuration
     */
    public function validateProductionConfig(): array
    {
        $issues = [];
        $recommendations = [];

        // Check app configuration
        if (config('app.debug') === true) {
            $issues[] = 'APP_DEBUG is enabled in production';
            $recommendations[] = 'Set APP_DEBUG=false in production environment';
        }

        // Check database configuration
        if (config('database.default') === 'sqlite') {
            $issues[] = 'Using SQLite database in production';
            $recommendations[] = 'Use MySQL or PostgreSQL for production database';
        }

        // Check cache configuration
        if (config('cache.default') !== 'redis') {
            $issues[] = 'Not using Redis for caching';
            $recommendations[] = 'Set CACHE_STORE=redis for better performance';
        }

        // Check session configuration
        if (config('session.driver') !== 'redis') {
            $issues[] = 'Not using Redis for sessions';
            $recommendations[] = 'Set SESSION_DRIVER=redis for scalability';
        }

        // Check queue configuration
        if (config('queue.default') !== 'redis') {
            $issues[] = 'Not using Redis for queues';
            $recommendations[] = 'Set QUEUE_CONNECTION=redis for better queue performance';
        }

        // Check mail configuration
        if (config('mail.default') === 'log') {
            $issues[] = 'Using log mailer in production';
            $recommendations[] = 'Configure SMTP or mail service provider';
        }

        // Check HTTPS configuration
        if (!config('session.secure')) {
            $issues[] = 'Session cookies not marked as secure';
            $recommendations[] = 'Set SESSION_SECURE_COOKIE=true for HTTPS';
        }

        return [
            'status' => empty($issues) ? 'ready' : 'needs_attention',
            'issues' => $issues,
            'recommendations' => $recommendations,
            'checked_at' => now()->toISOString(),
        ];
    }

    /**
     * Apply production configuration optimizations
     */
    public function applyProductionOptimizations(): array
    {
        $applied = [];

        try {
            // Enable OPcache if available
            if (function_exists('opcache_get_status')) {
                $applied[] = 'OPcache is available';
            }

            // Set production-specific config values
            $productionConfig = $this->getProductionConfig();
            
            foreach ($productionConfig as $section => $configs) {
                foreach ($configs as $key => $value) {
                    if (is_array($value)) {
                        foreach ($value as $subKey => $subValue) {
                            Config::set("{$section}.{$key}.{$subKey}", $subValue);
                        }
                    } else {
                        Config::set("{$section}.{$key}", $value);
                    }
                }
            }

            $applied[] = 'Production configuration applied';

            $this->logInfo('Production optimizations applied', [
                'optimizations' => $applied
            ]);

        } catch (\Exception $e) {
            $this->logError('Failed to apply production optimizations', [
                'error' => $e->getMessage()
            ]);
        }

        return $applied;
    }

    /**
     * Get configuration health status
     */
    public function getConfigHealth(): array
    {
        $validation = $this->validateProductionConfig();
        
        return [
            'service' => 'configuration',
            'status' => $validation['status'],
            'issues_count' => count($validation['issues']),
            'recommendations_count' => count($validation['recommendations']),
            'environment' => app()->environment(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Check if service configuration is valid
     */
    protected function isConfigValid(): bool
    {
        $validation = $this->validateProductionConfig();
        return $validation['status'] === 'ready';
    }
}