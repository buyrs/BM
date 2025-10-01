<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuditMiddleware
{
    protected AuditLogger $auditLogger;

    protected array $excludedRoutes = [
        'api/health',
        'api/status',
        '_debugbar',
        'telescope',
    ];

    protected array $excludedMethods = [
        'GET', // Generally don't audit read operations unless specifically needed
    ];

    protected array $sensitiveRoutes = [
        'login',
        'logout',
        'password',
        'two-factor',
        'admin',
        'users',
    ];

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Skip audit logging for excluded routes and methods
        if ($this->shouldSkipAudit($request)) {
            return $response;
        }

        try {
            $this->logRequest($request, $response);
        } catch (\Exception $e) {
            // Don't let audit logging break the application
            \Log::error('Audit logging failed', [
                'error' => $e->getMessage(),
                'route' => $request->route()?->getName(),
                'url' => $request->url(),
            ]);
        }

        return $response;
    }

    /**
     * Determine if audit logging should be skipped
     */
    protected function shouldSkipAudit(Request $request): bool
    {
        // Skip excluded methods
        if (in_array($request->method(), $this->excludedMethods)) {
            return true;
        }

        // Skip excluded routes
        $path = $request->path();
        foreach ($this->excludedRoutes as $excludedRoute) {
            if (str_contains($path, $excludedRoute)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log the request
     */
    protected function logRequest(Request $request, Response $response): void
    {
        $action = $this->determineAction($request);
        $changes = $this->extractChanges($request, $response);

        // Determine if this is a sensitive operation
        $isSensitive = $this->isSensitiveRoute($request);

        $this->auditLogger->log(
            $action,
            null, // Resource will be determined by the specific controller if needed
            $changes,
            Auth::user(),
            $request
        );

        // Log sensitive data access separately
        if ($isSensitive) {
            $this->auditLogger->logSensitiveAccess(
                $this->getSensitiveDataType($request),
                null,
                Auth::user()
            );
        }
    }

    /**
     * Determine the action based on the request
     */
    protected function determineAction(Request $request): string
    {
        $method = strtolower($request->method());
        $route = $request->route();
        $routeName = $route?->getName() ?? '';
        $path = $request->path();

        // Handle authentication routes
        if (str_contains($path, 'login')) {
            return 'login_attempt';
        }
        if (str_contains($path, 'logout')) {
            return 'logout_attempt';
        }
        if (str_contains($path, 'password')) {
            return 'password_change_attempt';
        }
        if (str_contains($path, 'two-factor')) {
            return 'two_factor_action';
        }

        // Handle CRUD operations
        switch ($method) {
            case 'post':
                return str_contains($path, 'bulk') ? 'bulk_create' : 'create_attempt';
            case 'put':
            case 'patch':
                return str_contains($path, 'bulk') ? 'bulk_update' : 'update_attempt';
            case 'delete':
                return str_contains($path, 'bulk') ? 'bulk_delete' : 'delete_attempt';
            default:
                return 'request_' . $method;
        }
    }

    /**
     * Extract relevant changes from the request
     */
    protected function extractChanges(Request $request, Response $response): array
    {
        $changes = [
            'method' => $request->method(),
            'url' => $request->url(),
            'route' => $request->route()?->getName(),
            'status_code' => $response->getStatusCode(),
            'timestamp' => now()->toISOString(),
        ];

        // Add request data for non-GET requests (excluding sensitive fields)
        if (!in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            $requestData = $request->except([
                'password',
                'password_confirmation',
                'current_password',
                'two_factor_secret',
                '_token',
                '_method',
            ]);

            if (!empty($requestData)) {
                $changes['request_data'] = $requestData;
            }
        }

        // Add response data for API requests
        if ($request->expectsJson() && $response->getStatusCode() >= 400) {
            $changes['response_data'] = [
                'status' => $response->getStatusCode(),
                'error' => true,
            ];
        }

        return $changes;
    }

    /**
     * Check if this is a sensitive route
     */
    protected function isSensitiveRoute(Request $request): bool
    {
        $path = $request->path();
        
        foreach ($this->sensitiveRoutes as $sensitiveRoute) {
            if (str_contains($path, $sensitiveRoute)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the type of sensitive data being accessed
     */
    protected function getSensitiveDataType(Request $request): string
    {
        $path = $request->path();

        if (str_contains($path, 'users')) {
            return 'user_data';
        }
        if (str_contains($path, 'admin')) {
            return 'admin_panel';
        }
        if (str_contains($path, 'password')) {
            return 'password_data';
        }
        if (str_contains($path, 'two-factor')) {
            return 'two_factor_data';
        }

        return 'sensitive_data';
    }
}