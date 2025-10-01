<?php

namespace App\Providers;

use App\Services\AuditLogger;
use Illuminate\Support\ServiceProvider;

class AuditServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AuditLogger::class, function ($app) {
            return new AuditLogger();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register audit middleware globally for web routes
        $this->app['router']->pushMiddlewareToGroup('web', \App\Http\Middleware\AuditMiddleware::class);
        
        // Register audit middleware for API routes
        $this->app['router']->pushMiddlewareToGroup('api', \App\Http\Middleware\AuditMiddleware::class);
    }
}