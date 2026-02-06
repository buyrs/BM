<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->setScopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    /**
     * Handle the callback from Google authentication.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleGoogleCallback()
    {
        try {
            $user = Socialite::driver('google')->user();
            
            // Check if this is the admin email
            $isAdmin = ($user->email === 'buyrsapp@gmail.com');
            
            // Check if user already exists
            $existingUser = User::where('email', $user->email)->first();
            
            if ($existingUser) {
                // Log them in with appropriate guard
                if ($isAdmin) {
                    Auth::guard('admin')->login($existingUser);
                    return redirect()->route('admin.dashboard');
                } else {
                    Auth::login($existingUser);
                    return redirect()->intended('/dashboard');
                }
            } else {
                // Only allow Google signup for admin email
                if (!$isAdmin) {
                    return redirect('/')->withErrors([
                        'email' => 'This email is not authorized for Google login.'
                    ]);
                }
                
                // Create admin user
                $newUser = User::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'password' => Hash::make(config('app.key') . $user->id),
                    'email_verified_at' => now(),
                ]);
                
                Auth::guard('admin')->login($newUser);
                return redirect()->route('admin.dashboard');
            }
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Google OAuth error: ' . $e->getMessage());
            
            return redirect('/')->withErrors([
                'email' => 'Google authentication failed. Please try again.'
            ]);
        }
    }
}