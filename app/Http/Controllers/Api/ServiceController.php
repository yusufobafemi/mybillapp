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

        // Validate request - Adjusted validation based on required fields for this endpoint/logic
        $validatedData = $request->validate([
            'biller_code' => 'required|string',
            'item_code' => 'required|string',
            'phoneNumber' => 'required|string', // Customer phone number
            'amount' => 'required|numeric', // Amount of the plan (this should match the 'fee' from Get Data Plans)
            'shortplan' => 'required|string', // Description of the plan (e.g., "1GB MTN") for transaction description
        ]);

        // Use validated data
        $billerCode = $validatedData['biller_code'];
        $itemCode = $validatedData['item_code'];
        $phoneNumber = $validatedData['phoneNumber'];
        $amount = $validatedData['amount']; // This should be the price (fee) of the data plan item
        $shortplan = $validatedData['shortplan'];

        // Important: Check if user has enough balance BEFORE calling the API
        if ($user->balance < $amount) {
            return response()->json(['error' => 'Insufficient balance.'], 400);
        }

        try {
            // *** CONSTRUCT THE CORRECT URL BASED ON YOUR WORKING EXAMPLE ***
            $flutterwaveUrl = "https://api.flutterwave.com/v3/country/NG/billers/{$billerCode}/items/{$itemCode}/payment";

            // Prepare the payload for this specific endpoint
            // For this specific item payment endpoint, you generally only need
            // the amount, the customer identifier, and your reference.
            $payload = [
                'customer' => $phoneNumber, // Or potentially ['phone_number' => $phoneNumber]? Let's stick to string first as in Airtime example. FW docs would confirm specific structure. String is common.
                'reference' => $txRef,
                "type" => "data_bundle",
                "customer_email" => auth()->user()->email,
            ];

            // Log the details *before* making the request
            Log::info('Attempting Flutterwave Data Purchase (Specific Item Endpoint):', [
                'url' => $flutterwaveUrl,
                'payload' => $payload,
                'user_id' => $user->id,
                'tx_ref' => $txRef,
            ]);

            // Make the POST request to the specific biller/item payment endpoint
            $response = Http::withToken(env('FLW_SECRET_KEY'))
                ->post($flutterwaveUrl, $payload);

            // Log the raw response for debugging
            $statusCode = $response->status();
            $rawBody = $response->body();
            $data = $response->json(); // Attempt to parse JSON

            Log::info('Flutterwave Data Purchase Raw Response (Specific Item Endpoint):', [
                'status_code' => $statusCode,
                'successful' => $response->successful(), // Check if HTTP status is 2xx
                'raw_body' => $rawBody,
                'parsed_json' => $data,
                'tx_ref' => $txRef,
            ]);

            // Check if the HTTP call was successful (2xx status) AND the Flutterwave API status is 'success'
            if ($response->successful() && isset($data['status']) && $data['status'] === 'success') {
                // Deduct balance ONLY ON SUCCESS
                $user->decrement('balance', $amount);

                // Store successful transaction
                $transaction = \App\Models\Transaction::create([ // Use imported model
                    'user_id' => $user->id,
                    'transaction_type_id' => 3, // Assuming 3 is for data purchase
                    'amount' => $amount,
                    'status' => 'successful',
                    'reference' => $txRef,
                    'description' => $shortplan . ' - ' . $phoneNumber, // Use shortplan for description
                    'response_data' => $data, // Store FW response
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
                    'flutterwave_response' => $data, // Optionally return FW response details
                ]);

            } else {
                // API call was successful HTTP-wise, but Flutterwave JSON status was not 'success'
                // OR HTTP call itself was not successful (e.g., 400, 500 from FW)
                Log::error('Data Purchase Failed (Flutterwave Status Not Success or HTTP Error):', [
                    'tx_ref' => $txRef,
                    'response_status_code' => $statusCode,
                    'response_body' => $rawBody,
                    'parsed_json' => $data,
                ]);

                // Store failed transaction
                $transaction = \App\Models\Transaction::create([ // Use imported model
                    'user_id' => $user->id,
                    'transaction_type_id' => 3, // Assuming 3 is for data purchase
                    'amount' => $amount,
                    'status' => 'failed',
                    'reference' => $txRef,
                    'description' => $shortplan . ' - ' . $phoneNumber . ' (FW Fail)',
                    'response_data' => $data ?? ['status_code' => $statusCode, 'body' => $rawBody], // Store error details
                ]);

                // Construct user-friendly error message
                $fwErrorMessage = 'Data purchase failed. Please try again.';
                if ($data && is_array($data) && isset($data['message'])) {
                    $fwErrorMessage = 'Data purchase failed: ' . $data['message'];
                } elseif ($rawBody) {
                    $fwErrorMessage .= " Response: " . substr($rawBody, 0, 255); // Add part of raw response if no message
                }

                return response()->json([
                    'status' => 'failed',
                    'message' => $fwErrorMessage,
                    'transaction' => $transaction,
                    'flutterwave_response' => $data, // Optionally return FW response details
                ], $statusCode >= 400 ? $statusCode : 500); // Use FW status code if applicable
            }

        } catch (\Exception $e) {
            // Catch any exceptions during the process (e.g., network issues, code errors)
            Log::error('Error during Data Purchase Process (Exception Caught):', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'tx_ref' => $txRef ?? 'N/A', // txRef might not be set if error happens early
                'request_data' => $request->all(),
            ]);

            // Store failed transaction due to exception
            try {
                \App\Models\Transaction::create([ // Use imported model
                    'user_id' => $user ? $user->id : null, // User might be null if auth failed
                    'transaction_type_id' => 3,
                    'amount' => $amount ?? $request->input('amount') ?? 0, // Attempt to get amount
                    'status' => 'failed',
                    'reference' => $txRef ?? 'EXCEPTION_' . time(), // Generate a reference if txRef isn't set
                    'description' => ($shortplan ?? 'Data') . ' - ' . ($phoneNumber ?? 'N/A') . ' (System Exception)',
                    'response_data' => ['error' => $e->getMessage(), 'trace' => substr($e->getTraceAsString(), 0, 500)], // Store error info
                ]);
            } catch (\Exception $te) {
                Log::error('Failed to record transaction for caught exception during Data purchase', ['exception' => $te->getMessage()]);
            }


            return response()->json([
                'status' => 'failed',
                'message' => 'An internal error occurred. Please try again later.',
            ], 500);
        }
    }
}
