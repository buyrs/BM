<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiRoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Authentication required',
                ]
            ], 401);
        }

        // Check if user has any of the required roles
        if (!in_array($user->role, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
                'error' => [
                    'code' => 'INSUFFICIENT_PERMISSIONS',
                    'message' => 'You do not have permission to access this resource',
                    'required_roles' => $roles,
                    'user_role' => $user->role,
                ]
            ], 403);
        }

        return $next($request);
    }
}