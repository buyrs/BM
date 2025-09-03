<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = Auth::user();
        if ($user->hasRole('super-admin')) {
            return redirect()->route('super-admin.dashboard');
        }
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->hasRole('checker')) {
            return redirect()->route('checker.dashboard');
        }
        if ($user->hasRole('ops')) {
            return redirect()->route('ops.dashboard');
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $userRole = null;
        
        // Determine user role before logout
        if ($user) {
            if ($user->hasRole('ops')) {
                $userRole = 'ops';
            } elseif ($user->hasRole('admin') || $user->hasRole('super-admin')) {
                $userRole = 'admin';
            } elseif ($user->hasRole('checker')) {
                $userRole = 'checker';
            }
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // Redirect based on user role
        switch ($userRole) {
            case 'ops':
                return redirect()->route('ops.login');
            case 'admin':
                return redirect()->route('admin.login');
            case 'checker':
                return redirect()->route('checker.login');
            default:
                return redirect('/');
        }
    }
}
