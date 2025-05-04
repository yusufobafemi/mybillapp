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
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle callback from Google.
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user(); // stateless for callback to work reliably on shared hosting

            $user = User::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'password' => bcrypt(Str::random(16)), // Set dummy password only on first creation
                ]
            );

            Auth::login($user);

            return redirect('/dashboard');
        } catch (\Exception $e) {
            Log::error('Google Login Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect('/login')->with('error', 'Google login failed. Please try again.');
        }
    }
}