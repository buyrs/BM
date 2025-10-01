<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TwoFactorChallengeController extends Controller
{
    public function __construct(
        private TwoFactorService $twoFactorService
    ) {}

    /**
     * Show the two-factor authentication challenge form.
     */
    public function create()
    {
        if (!session('login.id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    /**
     * Verify the two-factor authentication code.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'nullable|string',
            'recovery_code' => 'nullable|string',
        ]);

        $user = Auth::getProvider()->retrieveById(session('login.id'));

        if (!$user) {
            throw ValidationException::withMessages([
                'code' => ['The provided credentials are invalid.']
            ]);
        }

        $verified = false;

        if ($request->code) {
            $secret = $this->twoFactorService->getDecryptedSecret($user);
            $verified = $this->twoFactorService->verifyCode($secret, $request->code);
        } elseif ($request->recovery_code) {
            $verified = $this->twoFactorService->verifyRecoveryCode($user, $request->recovery_code);
        }

        if (!$verified) {
            throw ValidationException::withMessages([
                'code' => ['The provided two-factor authentication code is invalid.']
            ]);
        }

        // Update last login time
        $user->update(['last_login_at' => now()]);

        Auth::login($user, session('login.remember'));

        session()->forget(['login.id', 'login.remember']);

        return redirect()->intended(route('dashboard'));
    }
}