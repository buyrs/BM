<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!$request->user()) {
            abort(403, 'Unauthorized action.');
        }

        // Handle multiple roles separated by |
        $roles = explode('|', $role);
        $hasRole = false;
        
        foreach ($roles as $singleRole) {
            if ($request->user()->hasRole(trim($singleRole))) {
                $hasRole = true;
                break;
            }
        }

        if (!$hasRole) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
} 