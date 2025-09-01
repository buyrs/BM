<?php

namespace App\Http\Middleware;

use App\Services\AccessLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermissionWithLogging
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        
        if (!$user) {
            AccessLogService::logUnauthorizedAccess($request, 'No authenticated user for permission check');
            abort(401, 'Authentication required.');
        }

        $hasPermission = $user->can($permission);
        
        // Log the permission check
        AccessLogService::logPermissionCheck(
            $user,
            $permission,
            $hasPermission,
            $request->route()?->getName() ?? 'unknown_route'
        );

        if (!$hasPermission) {
            AccessLogService::logUnauthorizedAccess(
                $request,
                "Permission denied: {$permission}",
                $user,
                ['required_permission' => $permission]
            );
            abort(403, "Access denied. Permission '{$permission}' required.");
        }

        return $next($request);
    }
}