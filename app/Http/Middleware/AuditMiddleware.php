<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditMiddleware
{
    /**
     * Handle an incoming request and log the action
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Process the request
        $response = $next($request);
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // Duration in milliseconds

        // Log the request after processing
        $this->logRequest($request, $response, $duration);

        return $response;
    }

    /**
     * Log the request details
     */
    private function logRequest(Request $request, Response $response, float $duration): void
    {
        // Skip logging for certain routes to avoid noise
        if ($this->shouldSkipLogging($request)) {
            return;
        }

        $user = auth()->user();
        $routeName = $request->route()?->getName();
        $method = $request->method();
        $url = $request->fullUrl();
        $statusCode = $response->getStatusCode();

        // Determine event type based on HTTP method and route
        $eventType = $this->determineEventType($method, $routeName, $statusCode);
        
        // Determine severity based on status code
        $severity = $this->determineSeverity($statusCode);
        
        // Check if this is a sensitive operation
        $isSensitive = $this->isSensitiveOperation($request, $routeName);

        // Prepare metadata
        $metadata = [
            'duration_ms' => $duration,
            'response_size' => $response->headers->get('Content-Length'),
            'request_size' => $request->header('Content-Length'),
            'route_parameters' => $request->route()?->parameters() ?? [],
            'query_parameters' => $request->query(),
            'has_files' => $request->hasFile('*'),
            'is_ajax' => $request->ajax(),
            'is_json' => $request->expectsJson(),
            'accept_header' => $request->header('Accept'),
            'content_type' => $request->header('Content-Type')
        ];

        // Add request body for sensitive operations (sanitized)
        if ($isSensitive && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $metadata['request_data'] = $this->sanitizeRequestData($request->all());
        }

        // Log the action
        AuditService::logAction(
            $eventType,
            $this->generateActionDescription($method, $routeName, $url),
            null, // No specific model for general requests
            $user,
            [], // No old values for general requests
            [], // No new values for general requests
            $metadata,
            $severity,
            $isSensitive
        );

        // Log additional details for API endpoints
        if ($request->is('api/*')) {
            AuditService::logApiAccess(
                $request->path(),
                $method,
                $statusCode,
                $user,
                [
                    'duration_ms' => $duration,
                    'route_name' => $routeName
                ]
            );
        }
    }

    /**
     * Determine if logging should be skipped for this request
     */
    private function shouldSkipLogging(Request $request): bool
    {
        $skipRoutes = [
            'debugbar.*',
            'horizon.*',
            'telescope.*',
            '_ignition.*',
            'livewire.*'
        ];

        $skipPaths = [
            'favicon.ico',
            'robots.txt',
            'health',
            'ping',
            'status'
        ];

        $routeName = $request->route()?->getName();
        $path = $request->path();

        // Skip based on route name patterns
        foreach ($skipRoutes as $pattern) {
            if ($routeName && fnmatch($pattern, $routeName)) {
                return true;
            }
        }

        // Skip based on path patterns
        foreach ($skipPaths as $skipPath) {
            if (str_contains($path, $skipPath)) {
                return true;
            }
        }

        // Skip asset requests
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$/i', $path)) {
            return true;
        }

        // Skip OPTIONS requests
        if ($request->method() === 'OPTIONS') {
            return true;
        }

        return false;
    }

    /**
     * Determine event type based on request details
     */
    private function determineEventType(string $method, ?string $routeName, int $statusCode): string
    {
        // Handle authentication routes
        if ($routeName && str_contains($routeName, 'login')) {
            return $statusCode < 400 ? 'login_success' : 'login_failed';
        }

        if ($routeName && str_contains($routeName, 'logout')) {
            return 'logout';
        }

        if ($routeName && str_contains($routeName, 'register')) {
            return $statusCode < 400 ? 'registration_success' : 'registration_failed';
        }

        // Handle CRUD operations
        switch ($method) {
            case 'GET':
                return $statusCode < 400 ? 'view_request' : 'view_failed';
            case 'POST':
                return $statusCode < 400 ? 'create_request' : 'create_failed';
            case 'PUT':
            case 'PATCH':
                return $statusCode < 400 ? 'update_request' : 'update_failed';
            case 'DELETE':
                return $statusCode < 400 ? 'delete_request' : 'delete_failed';
            default:
                return 'http_request';
        }
    }

    /**
     * Determine severity based on status code
     */
    private function determineSeverity(int $statusCode): string
    {
        if ($statusCode >= 500) {
            return 'critical';
        } elseif ($statusCode >= 400) {
            return 'error';
        } elseif ($statusCode >= 300) {
            return 'warning';
        } else {
            return 'info';
        }
    }

    /**
     * Check if this is a sensitive operation
     */
    private function isSensitiveOperation(Request $request, ?string $routeName): bool
    {
        $sensitiveRoutes = [
            'login',
            'logout',
            'register',
            'password',
            'signature',
            'contract',
            'admin',
            'user',
            'permission',
            'role'
        ];

        $sensitivePaths = [
            'admin',
            'signature',
            'contract',
            'user',
            'auth',
            'password'
        ];

        // Check route name
        if ($routeName) {
            foreach ($sensitiveRoutes as $sensitive) {
                if (str_contains(strtolower($routeName), $sensitive)) {
                    return true;
                }
            }
        }

        // Check path
        $path = strtolower($request->path());
        foreach ($sensitivePaths as $sensitive) {
            if (str_contains($path, $sensitive)) {
                return true;
            }
        }

        // Check for authentication failures
        if ($request->method() === 'POST' && str_contains($path, 'login')) {
            return true;
        }

        // Check for admin panel access
        if (str_contains($path, 'admin')) {
            return true;
        }

        return false;
    }

    /**
     * Generate action description
     */
    private function generateActionDescription(string $method, ?string $routeName, string $url): string
    {
        if ($routeName) {
            return "{$method} {$routeName}";
        }

        // Extract meaningful part of URL
        $path = parse_url($url, PHP_URL_PATH);
        $pathParts = array_filter(explode('/', $path));
        
        if (count($pathParts) > 0) {
            $resource = end($pathParts);
            return "{$method} /{$resource}";
        }

        return "{$method} {$url}";
    }

    /**
     * Sanitize request data to remove sensitive information
     */
    private function sanitizeRequestData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'token',
            'api_key',
            'secret',
            'private_key',
            'signature_data',
            'credit_card',
            'ssn',
            'social_security'
        ];

        $sanitized = [];

        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);
            
            if (in_array($lowerKey, $sensitiveFields) || str_contains($lowerKey, 'password')) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeRequestData($value);
            } elseif (is_string($value) && strlen($value) > 500) {
                // Truncate very long strings
                $sanitized[$key] = substr($value, 0, 500) . '... [TRUNCATED]';
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}