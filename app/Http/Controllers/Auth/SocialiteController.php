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
        return Socialite::driver('google')->redirect();
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
            
            // Check if user already exists
            $existingUser = User::where('email', $user->email)->first();
            
            if ($existingUser) {
                // If user exists, log them in
                Auth::login($existingUser);
            } else {
                // Create a new user if they don't exist
                $newUser = User::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'password' => Hash::make(config('app.key') . $user->id), // Generate a secure password
                    'email_verified_at' => now(),
                ]);
                
                Auth::login($newUser);
            }

            return redirect()->intended('/dashboard');
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Google OAuth error: ' . $e->getMessage());
            
            return redirect('/')->withErrors([
                'email' => 'Google authentication failed. Please try again.'
            ]);
        }
    }
}