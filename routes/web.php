<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\Logincontroller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Route for email verification
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
     ->middleware(['auth', 'signed'])
     ->name('verification.verify');

// Route for resending verification email
Route::post('/email/verification-notification', [VerificationController::class, 'resend'])
     ->middleware(['auth', 'throttle:6,1'])
     ->name('verification.resend');

// Route to show verification notice
Route::get('/email/verify', function () {
    return view('auth.verify');  // Make sure you have the verify.blade.php view.
})->middleware('auth')->name('verification.notice');

Route::get('auth/redirect/google', [GoogleController::class, 'redirectToGoogle'])->name('google.login');
Route::get('auth/callback/google', [GoogleController::class, 'handleGoogleCallback']);

Route::get('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->middleware('auth')->name('logout');

// Route::middleware('cors')->get('/your-api-endpoint', 'YourController@yourMethod');

Auth::routes();

Route::get('/dashboard', [App\Http\Controllers\HomeController::class, 'index'])->middleware('auth')->name('dashboard');

Route::get('/login', function () {return view('auth.login'); })->name('login');

Route::post('/login', [LoginController::class, 'login'])->name('login');

Route::post('/register', [RegisterController::class, 'register'])->name('register');

Route::get('/test', function () {return view('test'); })->name('test');

Route::post('/user/{id}/update-balance', [UserController::class, 'updateBalance']);

Route::post('/webhook', function (Request $request) {
    Log::info('Webhook received', $request->all());

    // Run git pull - make sure this works on your server
    $output = shell_exec('cd /home/YOUR_CPNANEL_USERNAME/public_html && git pull 2>&1');
    Log::info('Git Pull Output: ' . $output);

    return response()->json(['status' => 'Webhook received', 'output' => $output]);
});
