<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMonitoringMiddleware
{
    /**
     * Handle an incoming request and monitor performance metrics.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;
        
        // Record metrics
        $this->recordMetrics($request, $response, $responseTime);
        
        return $response;
    }

    private function recordMetrics(Request $request, Response $response, float $responseTime)
    {
        // Record response time
        $this->recordResponseTime($responseTime);
        
        // Record requests per minute
        $this->recordRequestsPerMinute();
        
        // Record error rate
        if ($response->getStatusCode() >= 500) {
            $this->recordError();
        }
        
        // Record specific endpoint metrics
        $this->recordEndpointMetrics($request, $responseTime);
    }

    private function recordResponseTime(float $responseTime)
    {
        // Store rolling average of response times
        $times = Cache::get('response_times', collect());
        $times->push($responseTime);
        
        // Keep only last 100 response times
        if ($times->count() > 100) {
            $times = $times->slice(-100);
        }
        
        Cache::put('response_times', $times, 3600); // 1 hour
        
        // Calculate and store average
        $avgResponseTime = $times->avg();
        Cache::put('avg_response_time', $avgResponseTime, 3600);
    }

    private function recordRequestsPerMinute()
    {
        $currentMinute = now()->format('Y-m-d H:i');
        $requestsKey = "requests_per_minute_{$currentMinute}";
        
        $requestsPerMinute = Cache::get($requestsKey, 0);
        Cache::put($requestsKey, $requestsPerMinute + 1, 120); // Keep for 2 minutes
        
        // Calculate rolling average
        $this->calculateRequestsPerMinute();
    }

    private function calculateRequestsPerMinute()
    {
        $totalRequests = 0;
        $validMinutes = 0;
        
        // Look at last 5 minutes
        for ($i = 0; $i < 5; $i++) {
            $minute = now()->subMinutes($i)->format('Y-m-d H:i');
            $requestsKey = "requests_per_minute_{$minute}";
            $requests = Cache::get($requestsKey, 0);
            
            if ($requests > 0) {
                $totalRequests += $requests;
                $validMinutes++;
            }
        }
        
        $rpm = $validMinutes > 0 ? $totalRequests / $validMinutes : 0;
        Cache::put('requests_per_minute', $rpm, 3600);
    }

    private function recordError()
    {
        $currentHour = now()->format('Y-m-d H');
        $errorsKey = "errors_per_hour_{$currentHour}";
        
        $errors = Cache::get($errorsKey, 0);
        Cache::put($errorsKey, $errors + 1, 7200); // Keep for 2 hours
        
        // Calculate error rate
        $this->calculateErrorRate();
    }

    private function calculateErrorRate()
    {
        $currentHour = now()->format('Y-m-d H');
        $errorsKey = "errors_per_hour_{$currentHour}";
        $requestsKey = "requests_this_hour_{$currentHour}";
        
        $errors = Cache::get($errorsKey, 0);
        $requests = Cache::get($requestsKey, 1); // Default to 1 to avoid division by zero
        
        $errorRate = $requests > 0 ? $errors / $requests : 0;
        Cache::put('error_rate', $errorRate, 3600);
    }

    private function recordEndpointMetrics(Request $request, float $responseTime)
    {
        $endpoint = $request->route()?->getName() ?? $request->path();
        $endpointKey = "endpoint_{$endpoint}";
        
        // Record response time for this endpoint
        $endpointTimes = Cache::get("{$endpointKey}_times", collect());
        $endpointTimes->push($responseTime);
        
        if ($endpointTimes->count() > 50) {
            $endpointTimes = $endpointTimes->slice(-50);
        }
        
        Cache::put("{$endpointKey}_times", $endpointTimes, 3600);
        
        // Record average response time for this endpoint
        $avgEndpointTime = $endpointTimes->avg();
        Cache::put("{$endpointKey}_avg_time", $avgEndpointTime, 3600);
    }
}