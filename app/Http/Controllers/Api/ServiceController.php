<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;


class ServiceController extends Controller
{
    public function processService(Request $request)
    {
        $serviceType = $request->input('service');

        switch ($serviceType) {
            case 'airtime':
                return $this->processAirtime($request);

                // Later add: data, cable, etc.
            default:
                return response()->json(['error' => 'Unsupported service'], 400);
        }
    }

    private function processAirtime(Request $request)
    {
        $user = auth('web')->user();

        // Check if user is authenticated BEFORE trying to use $user
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }
        $txRef = 'TXN_' . $user->id . '_' . time() . '_' . Str::random(8);

        // Validate incoming request data
        $request->validate([
            'phoneNumber' => 'required|numeric',
            'network' => 'required|string',
            'amount' => 'required|numeric',
        ]);

        // Prepare the data for Flutterwave API
        $phoneNumber = $request->input('phoneNumber');
        $network = $request->input('network');
        $amount = $request->input('amount');
        $reference = $txRef;

        if ($user->balance < $amount) {
            return response()->json(['error' => 'Insufficient balance.'], 400);
       }

        try {
            // Call Flutterwave API to buy airtime
            $response = Http::withToken(env('FLW_SECRET_KEY'))
                ->post('https://api.flutterwave.com/v3/bills', [
                    'country' => 'NG',
                    'customer' => $request->phoneNumber,
                    'amount' => $request->amount,
                    'recurrence' => 'ONCE',
                    'type' => 'AIRTIME',
                    'reference' => $reference,
                ]);

            $data = $response->json();

            // Log the response from Flutterwave (for debugging)
            Log::info('Flutterwave Airtime Purchase Response: ', $data);
            // Check if the response is successful
            if (isset($data['status']) && $data['status'] === 'success') {
                // deduct user balance
                $user->decrement('balance', $amount);
                // Create the transaction record in your database
                $transaction = \App\Models\Transaction::create([
                    'user_id' => auth()->id(),
                    'transaction_type_id' => 2,  // 2 for airtime purchase
                    'amount' => $amount,
                    'status' => 'successful',
                    'reference' => $reference,
                    'description' => ucwords($network) . ' - ' . $phoneNumber,
                ]);

                // Return a successful response to the user
                return response()->json([
                    'status' => 'success',
                    'message' => 'Airtime purchase successful!',
                    'transaction' => $transaction,
                    'new_balance' => $user->fresh()->balance,
                ]);
            } else {
                // Log the failure in the response
                Log::error('Airtime Purchase Failed', ['response' => $data]);

                // Handle failure in the response
                $transaction = \App\Models\Transaction::create([
                    'user_id' => auth()->id(),
                    'transaction_type_id' => 2,  // 2 for airtime purchase
                    'amount' => $amount,
                    'status' => 'failed',
                    'reference' => $reference,
                    'description' => ucwords($network) . ' - ' . $phoneNumber,
                ]);
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Airtime purchase failed. Please try again.',
                    'transaction' => $transaction,
                ]);
            }
        } catch (\Exception $e) {
            // Log the error details for debugging
            Log::error('Error occurred during Airtime Purchase', [
                'exception' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);

            // Return a generic error message to the user
            return response()->json([
                'status' => 'failed',
                'message' => 'An error occurred. Please try again.',
            ]);
        }
    }
}
