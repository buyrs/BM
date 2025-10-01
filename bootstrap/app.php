<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API and Web middleware
        $middleware->alias([
            'api.auth' => \App\Http\Middleware\ApiAuthMiddleware::class,
            'api.role' => \App\Http\Middleware\ApiRoleMiddleware::class,
            'api.exception' => \App\Http\Middleware\ApiExceptionHandler::class,
            'api.version' => \App\Http\Middleware\ApiVersionMiddleware::class,
            'api.analytics' => \App\Http\Middleware\ApiAnalyticsMiddleware::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'performance.monitor' => \App\Http\Middleware\PerformanceMonitoringMiddleware::class,
        ]);

        // Apply rate limiting to API routes
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // Apply analytics and versioning to API routes
        $middleware->api(append: [
            'api.analytics',
            'api.version:v1',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
