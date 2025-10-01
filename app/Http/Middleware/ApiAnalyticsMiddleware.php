<?php

namespace App\Http\Middleware;

use App\Services\ApiAnalyticsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAnalyticsMiddleware
{
    public function __construct(
        private ApiAnalyticsService $analyticsService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Record start time
        $startTime = microtime(true);
        $request->server->set('REQUEST_TIME_FLOAT', $startTime);

        // Process the request
        $response = $next($request);

        // Track the request after processing
        $this->analyticsService->trackRequest($request, $response);

        return $response;
    }
}