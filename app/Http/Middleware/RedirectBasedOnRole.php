<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectBasedOnRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        if ($request->user()->hasRole('super-admin')) {
            return redirect()->route('super-admin.dashboard');
        }

        if ($request->user()->hasRole('checker')) {
            return redirect()->route('checker.dashboard');
        }

        return $next($request);
    }
}