<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Auth; 
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log; 

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/dashboard'; // you can adjust to '/dashboard' if you want

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    

    // After user login, Laravel automatically calls this
    protected function authenticated(Request $request, $user)
    {
        Log::info('Authenticated user', ['user' => $user]);
    
        if (!$user->hasVerifiedEmail()) {
            // Log::info('User has not verified email', ['user' => $user]);
            // Auth::logout(); // Log out the user if not verified
            return response()->json([
                'email_verified' => false,
            ]);
        }
    
        // Log::info('User is verified', ['user' => $user]);
    
        return response()->json([
            'email_verified' => true,
            'redirect_to' => route('dashboard'), // Or wherever the user should go after logging in
        ]);
    }
}
