<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            $user = User::where('google_id', $googleUser->id)->first();
            
            if ($user) {
                Auth::login($user);
                return $this->getRoleBasedRedirect($user);
            }

            // Check if user exists with same email
            $existingUser = User::where('email', $googleUser->email)->first();
            if ($existingUser) {
                // Link Google account to existing user
                $existingUser->update([
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                ]);
                
                Auth::login($existingUser);
                return $this->getRoleBasedRedirect($existingUser);
            }

            // Create new user
            $newUser = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'google_id' => $googleUser->id,
                'avatar' => $googleUser->avatar,
                'password' => bcrypt(uniqid()), // Random password for OAuth users
                'email_verified_at' => now(),
            ]);

            // Assign default role
            $newUser->assignRole('checker');
            
            Auth::login($newUser);
            
            return $this->getRoleBasedRedirect($newUser);
            
        } catch (\Exception $e) {
            \Log::error('Google OAuth Error: ' . $e->getMessage());
            return redirect()->route('login')->withErrors([
                'email' => 'Google authentication failed. Please try again.',
            ]);
        }
    }

    /**
     * Get role-based redirect URL
     */
    private function getRoleBasedRedirect(User $user)
    {
        if ($user->hasRole('super-admin')) {
            return redirect()->route('super-admin.dashboard');
        }
        
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }
        
        if ($user->hasRole('ops')) {
            return redirect()->route('ops.dashboard');
        }
        
        if ($user->hasRole('checker')) {
            return redirect()->route('checker.dashboard');
        }

        // Default fallback
        return redirect()->route('dashboard');
    }
}