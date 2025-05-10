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
            case 'data':
                return $this->processData($request);

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
        $txRef = 'TXN_' . $user->id . time();

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
            Log::info('Flutterwave Airtime Purchase Response:', ['response_data' => $data]);
            // Check if the response is successful
            if (isset($data['status']) && $data['status'] === 'success') {
                // deduct user balance
                $user->decrement('balance', $amount);
                // Create the transaction record in your database
                $transaction = \App\Models\Transaction::create([
                    'user_id' => $user->id,
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

    public function getDataPlans(Request $request)
    {
        $request->validate([
            'billercode' => 'required|string',
        ]);

        $billerCode = $request->input('billercode');

        try {
            $response = Http::withToken(env('FLW_SECRET_KEY')) // set your Flutterwave secret key in .env
                ->get("https://api.flutterwave.com/v3/billers/{$billerCode}/items");

            if ($response->successful()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Filtered bill items',
                    'data' => $response->json()['data'],
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch data plans from Flutterwave',
                'data' => [],
            ], $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    private function processData(Request $request)
{
    $user = auth('web')->user();

    if (!$user) {
        return response()->json(['error' => 'Unauthenticated.'], 401);
    }

    $txRef = 'TXN_' . $user->id . time();

    // Validate request
    $request->validate([
        'biller_code' => 'required|string',
        'item_code' => 'required|string',
        'phoneNumber' => 'required|string',
        'amount' => 'required|numeric',
        'shortplan' => 'required|string',
    ]);

    $billerCode = $request->input('biller_code');
    $itemCode = $request->input('item_code');
    $phoneNumber = $request->input('phoneNumber');
    $amount = $request->input('amount');
    $shortplan = $request->input('shortplan');

    if ($user->balance < $amount) {
        return response()->json(['error' => 'Insufficient balance.'], 400);
    }

    try {
        $response = Http::withToken(env('FLW_SECRET_KEY'))
            ->post("https://api.flutterwave.com/v3/country/NG/billers/{$billerCode}/items/{$itemCode}/payment", [
                'customer' => $phoneNumber,
                'reference' => $txRef,
                'amount' => $amount,
            ]);

        $data = $response->json();

        Log::info('Flutterwave Data Purchase Response:', ['response_data' => $data]);
        
        if (isset($data['status']) && $data['status'] === 'success') {
            // Deduct balance
            $user->decrement('balance', $amount);

            // Store transaction
            $transaction = \App\Models\Transaction::create([
                'user_id' => $user->id,
                'transaction_type_id' => 3, // 3 for data purchase
                'amount' => $amount,
                'status' => 'successful',
                'reference' => $txRef,
                'description' => $shortplan .'-'. $phoneNumber,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Data bundle purchased successfully!',
                'transaction' => $transaction,
                'new_balance' => $user->fresh()->balance,
            ]);
        } else {
            Log::error('Data Purchase Failed', ['response' => $data]);

            $transaction = \App\Models\Transaction::create([
                'user_id' => $user->id,
                'transaction_type_id' => 3,
                'amount' => $amount,
                'status' => 'failed',
                'reference' => $txRef,
                'description' => $shortplan .'-'. $phoneNumber,
            ]);

            return response()->json([
                'status' => 'failed',
                'message' => 'Data purchase failed. Please try again.',
                'transaction' => $transaction,
            ]);
        }
    } catch (\Exception $e) {
        Log::error('Error during Data Purchase', [
            'exception' => $e->getMessage(),
            'stack' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'status' => 'failed',
            'message' => 'An error occurred. Please try again.',
        ]);
    }
}

}
