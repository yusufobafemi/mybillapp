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
use App\Http\Controllers\PaymentController;

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

Route::middleware(['auth'])->group(function () {
    // Route for the frontend AJAX call to prepare the top-up
    // This creates the pending record and returns tx_ref etc.
    Route::post('/prepare-topup', [PaymentController::class, 'prepareTopUp'])->name('payment.prepare'); // Added middleware here too

    // Route that Flutterwave redirects to after payment attempt
    // Changed to GET method as browser redirects are GET
    Route::get('/verify-payment', [PaymentController::class, 'verifyPayment'])->name('verify.payment'); // Added middleware here too
});

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

// Auth::routes();

Route::get('/dashboard', [App\Http\Controllers\HomeController::class, 'index'])->middleware('auth')->name('dashboard');

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', [LoginController::class, 'login'])->name('login');

Route::post('/register', [RegisterController::class, 'register'])->name('register');

Route::get('/test', function () {
    return view('test');
})->name('test');

Route::post('/user/{id}/update-balance', [UserController::class, 'updateBalance']);

Route::post('/webhook', function (Request $request) {
    // 1. Validate GitHub signature
    $githubSecret = env('GITHUB_SECRET');

    $signature = 'sha256=' . hash_hmac('sha256', $request->getContent(), $githubSecret);
    $githubHeader = $request->header('X-Hub-Signature-256');

    if (!hash_equals($signature, $githubHeader)) {
        Log::warning('Invalid GitHub webhook signature.', [
            'expected' => $signature,
            'received' => $githubHeader,
        ]);
        abort(403, 'Unauthorized');
    }

    // 2. Log and run deploy script
    Log::info('Valid GitHub webhook received.');

    $output = shell_exec('/bin/bash /home/mybifqgl/main.mybillapp.com/deploy.sh 2>&1');
    Log::info("Deploy Output: " . $output);

    return response()->json(['status' => 'Deployment triggered', 'output' => $output]);
});
