<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    public function __construct(
        private TwoFactorService $twoFactorService
    ) {}

    /**
     * Show the two-factor authentication setup page.
     */
    public function show()
    {
        $user = Auth::user();
        
        if ($user->hasTwoFactorEnabled()) {
            return view('auth.two-factor.manage', [
                'recoveryCodes' => $user->getRecoveryCodes()
            ]);
        }

        $secret = $this->twoFactorService->generateSecretKey();
        $qrCodeUrl = $this->twoFactorService->getQrCodeUrl($user, $secret);

        return view('auth.two-factor.setup', [
            'secret' => $secret,
            'qrCodeUrl' => $qrCodeUrl
        ]);
    }

    /**
     * Enable two-factor authentication.
     */
    public function store(Request $request)
    {
        $request->validate([
            'secret' => 'required|string',
            'code' => 'required|string|size:6'
        ]);

        $user = Auth::user();
        
        if (!$this->twoFactorService->verifyCode($request->secret, $request->code)) {
            throw ValidationException::withMessages([
                'code' => ['The provided two-factor authentication code is invalid.']
            ]);
        }

        $this->twoFactorService->enableTwoFactor($user, $request->secret);

        return redirect()->route('two-factor.show')->with('status', 'Two-factor authentication has been enabled.');
    }

    /**
     * Disable two-factor authentication.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password'
        ]);

        $user = Auth::user();
        $this->twoFactorService->disableTwoFactor($user);

        return redirect()->route('two-factor.show')->with('status', 'Two-factor authentication has been disabled.');
    }

    /**
     * Generate new recovery codes.
     */
    public function generateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password'
        ]);

        $user = Auth::user();
        $recoveryCodes = $user->generateRecoveryCodes();

        return redirect()->route('two-factor.show')->with([
            'status' => 'New recovery codes have been generated.',
            'recoveryCodes' => $recoveryCodes
        ]);
    }
}