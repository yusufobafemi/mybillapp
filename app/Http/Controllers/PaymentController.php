<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Transaction;
use App\Models\TopUp;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class PaymentController extends Controller
{
    /**
     * Start the Flutterwave payment process.
     */
    public function initiatePayment(Request $request)
    {
        $user = $request->user();

        $response = Http::withToken(env('FLW_SECRET_KEY'))->post('https://api.flutterwave.com/v3/payments', [
            'tx_ref' => $request['data']['tx_ref'],
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
        // Log the incoming request details for debugging
        Log::info('Verify Payment Callback Received:', $request->all());
        // get the token to test
        Log::debug('Using FLW secret token:', ['token' => env('FLW_SECRET_KEY')]);

    
        $transactionID = $request->input('transaction_id');
    
        if (empty($transactionID)) {
            // Handle case where transaction_id is missing (e.g., bad redirect)
            Log::error('Verify Payment: transaction_id is missing from request.');
            return response()->json(['error' => 'Missing transaction ID.'], 400); // Bad Request
        }
    
        // Make the server-to-server verification call
        try {
            $response = Http::withToken(env('FLW_SECRET_KEY'))
                ->get("https://api.flutterwave.com/v3/transactions/{$transactionID}/verify");
    
        } catch (\Exception $e) {
            // Handle network errors or other exceptions during the API call
            Log::error('Exception during Flutterwave verification API call:', ['exception' => $e->getMessage(), 'transaction_id' => $transactionID]);
            return response()->json(['error' => 'Error communicating with payment gateway.'], 500); // Internal Server Error
        }
    
    
        // Check if the API call itself was successful (HTTP status 2xx)
        if (!$response->successful()) { // Use successful() which checks for 2xx range
             Log::error('Flutterwave Verification API Error Response:', ['status' => $response->status(), 'body' => $response->body()]);
            return response()->json(['error' => 'Verification failed with payment gateway.'], 422);
        }
    
        // Get the JSON body of the response
        $responseData = $response->json(); // <-- Correctly parse JSON body
    
        // Log the Flutterwave API response for debugging
        Log::info('Flutterwave Verification API Response:', $responseData);
    
    
        // *** IMPORTANT CHECK: Verify the top-level API response status ***
        // The API call itself must be a success before we check the transaction status
        if (!is_array($responseData) || !isset($responseData['status']) || $responseData['status'] !== 'success' || !isset($responseData['data'])) {
            // This means the API call was successful (200 OK) but the body wasn't
            // the expected 'success' status with a 'data' payload.
            Log::warning('Flutterwave Verification API Call Status Not "success" or missing data:', $responseData);
            // You might want to return a specific error or just the general one
             return response()->json(['error' => 'Invalid or unexpected response format from gateway.'], 422);
        }
    
    
        // Now access the 'data' key from the *parsed* response body
        $data = $responseData['data']; // <-- This should now work if $responseData is an array/object and has 'data'

        // --- If all checks pass, proceed to update the user's balance ---
        $user = Auth::user();
        $amount = $data['amount']; // Use the verified amount from Flutterwave
        $tx_ref = $data['tx_ref'];
    
        // *** IMPORTANT CHECK: Verify the transaction status from the 'data' payload ***
        if (!isset($data['status']) || $data['status'] !== 'successful') {
            // The API call succeeded, but the transaction status itself is not 'successful'
            Log::warning('Flutterwave Transaction Status Not "successful":', ['tx_ref' => $data['tx_ref'] ?? 'N/A', 'status' => $data['status'] ?? 'N/A']);
            // You might want to handle 'cancelled' or 'failed' statuses specifically
            return response()->json(['error' => 'Payment was not successfully completed.'], 422);
        }
    
        // *** IMPORTANT CHECK: Verify amount and currency to prevent fraud ***
        // You should ideally compare the verified amount ($data['amount'])
        // against the amount you expected for this transaction reference ($data['tx_ref']).
        // For this, you'd need to have stored the expected amount beforehand
        // (e.g., in a pending top_up record created when the user initiated the process).
        // Example (requires fetching a pending top_up record):
        $pendingTopUp = \App\Models\TopUp::where('transaction_reference', $data['tx_ref'])->first();
        if (!$pendingTopUp || $data['amount'] < $pendingTopUp->amount || $data['currency'] !== 'NGN') {
            Log::warning('Flutterwave Verification: Amount/Currency Mismatch or Pending TopUp Not Found:', ['tx_ref' => $data['tx_ref'], 'verified_amount' => $data['amount'], 'expected_amount' => $pendingTopUp->amount ?? 'N/A']);
            // This could be a fraud attempt or a system issue
            return response()->json(['error' => 'Amount or currency mismatch.'], 422);
        }
    
    
        // Prevent duplicate entries (Good check!)
        // This check relies on tx_ref. Also consider checking if the transaction ID from Flutterwave
        // has already been processed if you store it.
        if (\App\Models\Transaction::where('reference', $tx_ref)->exists()) {
            Log::warning('Flutterwave Verification: Duplicate transaction reference detected:', ['tx_ref' => $tx_ref]);
            // Although it's a duplicate, if verification was successful, maybe return a success message
            // as the user's balance should have already been updated by the first processing.
            return response()->json(['message' => 'Transaction already processed.']); // Consider a 200 status
        }
    
        // Use a database transaction for atomicity!
        DB::beginTransaction();
        try {
            $user->increment('balance', $amount); // Increment balance
    
            // Update or Create TopUp record
            // If you pre-created a 'Pending' TopUp record, find and update it here.
            // Otherwise, create a new one.
            \App\Models\TopUp::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'gateway' => 'Flutterwave', // Or get from $data['payment_type'] if available/needed
                'transaction_reference' => $tx_ref,
                'status' => 'Successful',
                // Optionally store Flutterwave's internal ID: 'flutterwave_id' => $data['id'],
                // Add other details from $data as needed
            ]);
    
            // Create a general Transaction record
            // Make sure TYPE_DEPOSIT is defined somewhere (e.g., in your Transaction model as a const)
            // Or fetch the ID from the transaction_types table:
            // $depositTypeId = \App\Models\TransactionType::where('name', 'Deposit')->value('id');
            \App\Models\Transaction::create([
                'user_id' => $user->id,
                'transaction_type_id' => \App\Models\Transaction::TYPE_DEPOSIT, // Replace with actual ID
                'amount' => $amount, // Use the verified amount
                'status' => 'successful',
                'reference' => $tx_ref,
                // Add other details as needed
            ]);
    
            DB::commit(); // Commit the transaction
            Log::info('Payment Successfully Verified and Processed:', ['tx_ref' => $tx_ref, 'amount' => $amount, 'userId' => $user->id]);
    
            return response()->json(['message' => 'Wallet funded successfully.']); // Success!
    
        } catch (\Exception $e) {
            DB::rollback(); // Rollback database changes if anything went wrong
            Log::error('Database transaction failed during payment verification:', ['exception' => $e->getMessage(), 'tx_ref' => $tx_ref]);
    
            // Optionally update the TopUp record status to failed if it was pre-created
            // $pendingTopUp?->update(['status' => 'Failed']); // Requires PHP 8+ for nullsafe operator
    
            return response()->json(['error' => 'Failed to update wallet after payment verification.'], 500);
        }
    }
}
