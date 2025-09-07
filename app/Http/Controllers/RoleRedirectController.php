<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleRedirectController extends Controller
{
    public function index()
    {
        // If user is not authenticated, show the role selection page
        if (!Auth::check()) {
            return view('role-selection');
        }

        // If user is authenticated, redirect based on their role
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

        // Fallback to the default dashboard if no specific role is found
        return redirect()->route('dashboard');
    }
}