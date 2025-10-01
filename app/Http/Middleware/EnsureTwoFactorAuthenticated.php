<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorAuthenticated
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // If user is not authenticated, let other middleware handle it
        if (!$user) {
            return $next($request);
        }

        // If user has 2FA enabled but hasn't completed the challenge
        if ($user->hasTwoFactorEnabled() && session('login.id')) {
            return redirect()->route('two-factor.login');
        }

        return $next($request);
    }
}