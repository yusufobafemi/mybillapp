<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Transaction;
use App\Models\TopUp;

class PaymentController extends Controller
{
    /**
     * Start the Flutterwave payment process.
     */
    public function initiatePayment(Request $request)
    {
        $user = $request->user();

        $response = Http::withToken(env('FLW_SECRET_KEY'))->post('https://api.flutterwave.com/v3/payments', [
            'tx_ref' => $request['data']['link'],
            'amount' => $request->amount,
            'currency' => 'NGN',
            'redirect_url' => route('payment.callback'), // use named route
            'customer' => [
                'email' => $user->email,
                'phonenumber' => $user->phone,
                'name' => $user->name,
            ],
            'customizations' => [
                'title' => 'Top up Balance',
                'description' => 'Add money to your account',
                'logo' => asset('logo.png'), // change to your logo
            ],
        ]);

        if ($response->successful() && isset($response['data']['link'])) {
            return redirect($response['data']['link']);
        }

        return back()->with('error', 'Unable to initiate payment.');
    }

    public function verifyPayment(Request $request)
    {
        $transactionID = $request->input('transaction_id');

        $response = Http::withToken(env('FLW_SECRET_KEY'))
            ->get("https://api.flutterwave.com/v3/transactions/{$transactionID}/verify");

        if (!$response->ok()) {
            return response()->json(['error' => 'Verification failed'], 422);
        }

        $data = $response['data'];

        if ($data['status'] !== 'successful') {
            return response()->json(['error' => 'Payment was not successful'], 422);
        }

        $user = Auth::user();
        $amount = $data['amount'];
        $tx_ref = $data['tx_ref'];

        // Prevent duplicate entries
        if (\App\Models\Transaction::where('reference', $tx_ref)->exists()) {
            return response()->json(['message' => 'Transaction already processed']);
        }

        $user->increment('balance', $amount);

        \App\Models\TopUp::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'gateway' => 'Flutterwave',
            'transaction_reference' => $tx_ref,
            'status' => 'Successful',
        ]);

        \App\Models\Transaction::create([
            'user_id' => $user->id,
            'transaction_type_id' => \App\Models\Transaction::TYPE_DEPOSIT,
            'amount' => $amount,
            'status' => 'successful',
            'reference' => $tx_ref,
        ]);

        return response()->json(['message' => 'Wallet funded']);
    }
}
