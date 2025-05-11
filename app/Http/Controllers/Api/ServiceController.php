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
        $validatedData = $request->validate([
            'biller_code' => 'required|string',
            'item_code' => 'required|string',
            'phoneNumber' => 'required|string',
            'amount' => 'required|numeric',
            'shortplan' => 'required|string',
        ]);
    
        // Use validated data
        $billerCode = $validatedData['biller_code'];
        $itemCode = $validatedData['item_code'];
        $phoneNumber = $validatedData['phoneNumber'];
        $amount = $validatedData['amount'];
        $shortplan = $validatedData['shortplan'];
    
        if ($user->balance < $amount) {
            return response()->json(['error' => 'Insufficient balance.'], 400);
        }
    
        try {
            // Use the correct Flutterwave bills endpoint
            $flutterwaveUrl = "https://api.flutterwave.com/v3/bills";
            $payload = [
                'country' => 'NG',
                'customer' => $phoneNumber,
                'amount' => $amount,
                'recurrence' => 'ONCE',
                'type' => 'DATA_BUNDLE', // Specify the type as DATA_BUNDLE
                'reference' => $txRef,
                'biller_code' => $billerCode, // Include biller code
                'item_code' => $itemCode,     // Include item code
            ];
    
            // Log the details *before* making the request
            Log::info('Attempting Flutterwave Data Purchase:', [
                'url' => $flutterwaveUrl,
                'payload' => $payload,
                'user_id' => $user->id,
                'tx_ref' => $txRef,
            ]);
    
            $response = Http::withToken(env('FLW_SECRET_KEY'))
                ->post($flutterwaveUrl, $payload);
    
            // Log the response
            $statusCode = $response->status();
            $rawBody = $response->body();
            $data = $response->json();
    
            Log::info('Flutterwave Data Purchase Raw Response:', [
                'status_code' => $statusCode,
                'successful' => $response->successful(),
                'raw_body' => $rawBody,
                'parsed_json' => $data,
                'tx_ref' => $txRef,
            ]);
    
            // Check if the HTTP call was successful (2xx status)
            if (!$response->successful()) {
                Log::error('Flutterwave API call non-successful status:', [
                    'status_code' => $statusCode,
                    'raw_body' => $rawBody,
                    'tx_ref' => $txRef,
                ]);
    
                // Create a failed transaction
                \App\Models\Transaction::create([
                    'user_id' => $user->id,
                    'transaction_type_id' => 3,
                    'amount' => $amount,
                    'status' => 'failed',
                    'reference' => $txRef,
                    'description' => $shortplan . '-' . $phoneNumber . ' (API Status Fail)',
                    'response_data' => ['status_code' => $statusCode, 'body' => $rawBody],
                ]);
    
                $errorMessage = "Flutterwave API call failed with status code {$statusCode}.";
                if ($rawBody) {
                    $errorMessage .= " Response: " . substr($rawBody, 0, 255);
                }
    
                return response()->json([
                    'status' => 'failed',
                    'message' => $errorMessage,
                    'tx_ref' => $txRef,
                ], $statusCode >= 400 ? $statusCode : 500);
            }
    
            // Check the JSON status field
            if ($data && is_array($data) && isset($data['status']) && $data['status'] === 'success') {
                // Deduct balance
                $user->decrement('balance', $amount);
    
                // Store transaction
                $transaction = \App\Models\Transaction::create([
                    'user_id' => $user->id,
                    'transaction_type_id' => 3, // 3 for data purchase
                    'amount' => $amount,
                    'status' => 'successful',
                    'reference' => $txRef,
                    'description' => $shortplan . '-' . $phoneNumber,
                    'response_data' => $data,
                ]);
    
                Log::info('Data Purchase Successful (Flutterwave status success):', [
                    'tx_ref' => $txRef,
                    'transaction_id' => $transaction->id,
                    'new_balance' => $user->fresh()->balance,
                    'flutterwave_response' => $data,
                ]);
    
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data bundle purchased successfully!',
                    'transaction' => $transaction,
                    'new_balance' => $user->fresh()->balance,
                    'flutterwave_response' => $data,
                ]);
            } else {
                // API call was successful but JSON status was not 'success'
                Log::error('Data Purchase Failed (Flutterwave JSON Status Not Success):', [
                    'tx_ref' => $txRef,
                    'response_status' => $statusCode,
                    'raw_body' => $rawBody,
                    'parsed_json' => $data,
                ]);
    
                // Store failed transaction
                $transaction = \App\Models\Transaction::create([
                    'user_id' => $user->id,
                    'transaction_type_id' => 3,
                    'amount' => $amount,
                    'status' => 'failed',
                    'reference' => $txRef,
                    'description' => $shortplan . '-' . $phoneNumber . ' (FW Status Fail)',
                    'response_data' => $data ?? ['raw_body' => $rawBody],
                ]);
    
                $fwErrorMessage = 'Data purchase failed. Please try again.';
                if ($data && is_array($data) && isset($data['message'])) {
                    $fwErrorMessage = 'Data purchase failed: ' . $data['message'];
                } elseif ($rawBody) {
                    $fwErrorMessage .= " Response: " . substr($rawBody, 0, 255);
                }
    
                return response()->json([
                    'status' => 'failed',
                    'message' => $fwErrorMessage,
                    'transaction' => $transaction,
                    'flutterwave_response' => $data,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error during Data Purchase Process (Exception Caught):', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'tx_ref' => $txRef ?? 'N/A',
                'request_data' => $request->all(),
            ]);
    
            // Create a failed transaction
            try {
                \App\Models\Transaction::create([
                    'user_id' => $user ? $user->id : null,
                    'transaction_type_id' => 3,
                    'amount' => $amount ?? $request->input('amount') ?? 0,
                    'status' => 'failed',
                    'reference' => $txRef ?? 'EXCEPTION_' . time(),
                    'description' => $shortplan . '-' . $phoneNumber . ' (System Exception)',
                    'response_data' => ['error' => $e->getMessage(), 'trace' => substr($e->getTraceAsString(), 0, 500)],
                ]);
            } catch (\Exception $te) {
                Log::error('Failed to record transaction for caught exception', ['exception' => $te->getMessage()]);
            }
    
            return response()->json([
                'status' => 'failed',
                'message' => 'An internal error occurred. Please try again later.',
            ], 500);
        }
    }
}
