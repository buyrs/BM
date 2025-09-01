<?php

namespace App\Http\Middleware;

use App\Services\AccessLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckOpsAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission = null): Response
    {
        $user = $request->user();
        
        if (!$user) {
            AccessLogService::logUnauthorizedAccess($request, 'No authenticated user');
            abort(401, 'Authentication required.');
        }

        // Check if user has Ops role or higher (admin, super-admin)
        if (!$user->hasAnyRole(['ops', 'admin', 'super-admin'])) {
            AccessLogService::logUnauthorizedAccess($request, 'User does not have ops role or higher', $user);
            abort(403, 'Access denied. Ops role or higher required.');
        }

        // If specific permission is required, check it
        if ($permission && !$user->can($permission)) {
            AccessLogService::logUnauthorizedAccess(
                $request, 
                "Missing permission: {$permission}", 
                $user,
                ['required_permission' => $permission]
            );
            abort(403, "Access denied. Permission '{$permission}' required.");
        }

        // Prevent Ops users from accessing admin-only functions
        if ($user->hasRole('ops') && !$user->hasAnyRole(['admin', 'super-admin']) && $this->isAdminOnlyRoute($request)) {
            AccessLogService::logUnauthorizedAccess(
                $request, 
                'Ops user attempted access to admin-only route', 
                $user,
                ['admin_only_route' => true]
            );
            abort(403, 'Access denied. Administrator privileges required.');
        }

        // Log sensitive operations access
        if ($this->isSensitiveOperation($request)) {
            AccessLogService::logSensitiveAccess(
                $request,
                $user,
                $this->getSensitiveOperationName($request)
            );
        }

        return $next($request);
    }

    /**
     * Check if the route is admin-only
     */
    private function isAdminOnlyRoute(Request $request): bool
    {
        $adminOnlyRoutes = [
            'admin.*',
            'super-admin.*',
            '*.create-contract-templates',
            '*.edit-contract-templates',
            '*.delete-contract-templates',
            '*.sign-contract-templates',
            '*.delete-missions',
            '*.delete-bail-mobilite',
            '*.manage-users',
            '*.manage-roles',
            '*.view-system-logs',
            '*.access-admin-panel',
        ];

        $currentRoute = $request->route()?->getName();
        
        if (!$currentRoute) {
            return false;
        }
        
        foreach ($adminOnlyRoutes as $pattern) {
            if (fnmatch($pattern, $currentRoute)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the operation is sensitive and should be logged
     */
    private function isSensitiveOperation(Request $request): bool
    {
        $sensitiveRoutes = [
            '*.validate-entry',
            '*.validate-exit',
            '*.handle-incident',
            '*.assign-entry',
            '*.assign-exit',
            '*.validate-bail-mobilite-checklist',
            '*.archive-signatures',
            '*.validate-signatures',
        ];

        $currentRoute = $request->route()?->getName();
        
        if (!$currentRoute) {
            return false;
        }
        
        foreach ($sensitiveRoutes as $pattern) {
            if (fnmatch($pattern, $currentRoute)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the name of the sensitive operation for logging
     */
    private function getSensitiveOperationName(Request $request): string
    {
        $route = $request->route()?->getName();
        
        $operationMap = [
            'ops.bail-mobilites.validate-entry' => 'Bail Mobilité Entry Validation',
            'ops.bail-mobilites.validate-exit' => 'Bail Mobilité Exit Validation',
            'ops.bail-mobilites.handle-incident' => 'Incident Management',
            'ops.bail-mobilites.assign-entry' => 'Entry Mission Assignment',
            'ops.bail-mobilites.assign-exit' => 'Exit Mission Assignment',
            'missions.validate-bail-mobilite-checklist' => 'Checklist Validation',
            'signatures.archive' => 'Signature Archival',
            'signatures.validate' => 'Signature Validation',
        ];

        return $operationMap[$route] ?? 'Sensitive Operation: ' . $route;
    }
}