<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated via Sanctum
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Authentication token is required',
                ]
            ], 401);
        }

        // Check if token is expired (Sanctum handles this automatically, but we can add custom logic)
        $token = $request->user()->currentAccessToken();
        
        if ($token && $token->expires_at && $token->expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Token expired',
                'error' => [
                    'code' => 'TOKEN_EXPIRED',
                    'message' => 'Authentication token has expired',
                ]
            ], 401);
        }

        // Update last used timestamp
        if ($token) {
            $token->forceFill(['last_used_at' => now()])->save();
        }

        return $next($request);
    }
}