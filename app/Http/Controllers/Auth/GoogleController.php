<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;

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

            // Find or create user
            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'password' => bcrypt(str()->random(16)), // Dummy password
                    'google_id' => $googleUser->getId(), // If you want to save
                ]
            );

            Auth::login($user);

            return redirect('/dashboard'); // or wherever you want
        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }
}
