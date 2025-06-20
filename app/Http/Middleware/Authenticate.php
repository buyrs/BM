<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo($request)
    {
        if ($request->is('admin/*')) {
            return route('admin.login');
        }
        if ($request->is('checker/*')) {
            return route('checker.login');
        }
        // fallback
        return route('admin.login');
    }
} 