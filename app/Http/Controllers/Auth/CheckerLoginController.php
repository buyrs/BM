<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\Checklist;

class CheckerLoginController extends Controller
{
    public function create(): View
    {
        return view('auth.checker-login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('checker')->attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->intended(route('checker.dashboard', absolute: false));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function dashboard(): View
    {
        $checklists = Checklist::whereHas('mission', function ($query) {
            $query->where('checker_id', Auth::id());
        })->with('mission')->get();

        return view('checker.dashboard', compact('checklists'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('checker')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
