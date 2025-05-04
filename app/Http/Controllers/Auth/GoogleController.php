<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleController extends Controller
{
    /**
     * Redirect the user to Google's OAuth page.
     */
    public function redirectToGoogle()
    {
        // Standard stateful redirect
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle callback from Google.
     */
    public function handleGoogleCallback()
    {
        try {
            // *** REMOVE ->stateless() HERE ***
            $googleUser = Socialite::driver('google')->user();

            $user = User::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    // You might consider if you truly need a dummy password if only using social login
                    // but if users can also register with email/password, keep this.
                    // If they can only use social, the password field might not be needed or could be nullable.
                    'password' => User::where('email', $googleUser->getEmail())->exists()
                                ? User::where('email', $googleUser->getEmail())->first()->password // Keep existing password if user already existed
                                : bcrypt(Str::random(16)), // Set dummy password only on first creation
                ]
            );

            Auth::login($user);

            // dd(Auth::check()); // This should now consistently show true and session should persist

            // Ensure /dashboard route is protected by auth middleware
            return redirect('/dashboard');

        } catch (\Exception $e) {
            Log::error('Google Login Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                // Optional: Include user info if available before exception
                 'google_id' => isset($googleUser) ? $googleUser->getId() : 'N/A',
                 'email' => isset($googleUser) ? $googleUser->getEmail() : 'N/A',
            ]);

            return redirect('/login')->with('error', 'Google login failed. Please try again.');
        }
    }
}