<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; // Laravel 8+
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function initiatePayment(Request $request)
    {
        $tx_ref = 'TXN_' . Str::random(10); // Generate a unique transaction reference

        $response = Http::withToken(env('FLW_SECRET_KEY'))
            ->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref' => $tx_ref,
                'amount' => $request->amount, // Amount user is paying
                'currency' => 'NGN', // Only Naira
                'redirect_url' => env('FLW_PAYMENT_REDIRECT_URL'),
                'customer' => [
                    'email' => $request->user()->email,
                    'phonenumber' => $request->user()->phone,
                    'name' => $request->user()->name,
                ],
                'customizations' => [
                    'title' => 'Top up Balance',
                    'description' => 'Add money to your account',
                    'logo' => 'https://yourdomain.com/logo.png',
                ],
            ]);

        $paymentLink = $response['data']['link'];

        // Redirect user to the payment page
        return redirect($paymentLink);
    }

    // THIS IS TO VERIFY PAYMENT
        public function paymentCallback(Request $request)
    {
        $transactionID = $request->query('transaction_id'); // From the Flutterwave redirect

        $response = Http::withToken(env('FLW_SECRET_KEY'))
            ->get("https://api.flutterwave.com/v3/transactions/{$transactionID}/verify");

        $paymentData = $response['data'];

        if ($paymentData['status'] == 'successful') {
            // Payment was successful
            $user = \App\Models\User::find(auth()->id()); // The logged-in user
            $amountPaid = $paymentData['amount'];

            // Update user's balance
            $user->increment('balance', $amountPaid);

            // Record the transaction (optional but recommended)
            \App\Models\Transaction::create([
                'user_id' => $user->id,
                'transaction_type_id' => 1, // 1 for "Wallet Topup" maybe
                'amount' => $amountPaid,
                'status' => 'Successful',
                'reference' => $paymentData['tx_ref'],
            ]);

            return redirect('/dashboard')->with('success', 'Payment successful! Wallet credited.');
        } else {
            return redirect('/dashboard')->with('error', 'Payment failed or canceled.');
        }
    }
    
}
