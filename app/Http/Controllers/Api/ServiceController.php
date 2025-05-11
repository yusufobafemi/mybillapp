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

        // Validate request - Ensure all needed fields are present
        // Amount is CRUCIAL for the /v3/bills endpoint payload
        $validatedData = $request->validate([
            'biller_code' => 'required|string', // e.g., BIL110 (MTN NG)
            'item_code' => 'required|string',   // e.g., MD136 (1GB daily)
            'phoneNumber' => 'required|string', // Customer phone number (e.g., 08012345678)
            'amount' => 'required|numeric|min:0', // The exact 'fee' of the data plan item (must be numeric > 0)
            'shortplan' => 'required|string',   // Description for your transaction record (e.g., "MTN 1GB Daily")
            'planname' => 'required|string',
        ]);

        // Use validated data
        $billerCode = $validatedData['biller_code'];
        $itemCode = $validatedData['item_code'];
        $phoneNumber = $validatedData['phoneNumber'];
        $amount = $validatedData['amount'];
        $shortplan = $validatedData['shortplan'];
        $planname = $validatedData['planname'];

        // Important: Check if user has enough balance BEFORE calling the API
        if ($user->balance < $amount) {
            return response()->json(['error' => 'Insufficient balance.'], 400);
        }

        // Debit the user's balance *before* calling the external API.
        // This is a common pattern to prevent double-spending issues.
        // If the API call fails, you reverse the debit.
        $user->decrement('balance', $amount);
        Log::info('User balance debited for Data Purchase attempt', [
            'user_id' => $user->id,
            'amount_debited' => $amount,
            'tx_ref' => $txRef,
            'new_balance' => $user->fresh()->balance,
        ]);


        try {
            // *** USE THE CORRECT, STANDARD BILLS ENDPOINT ***
            // The endpoint for purchasing bills, including data bundles,
            // is typically /v3/bills. The specific item details go IN THE PAYLOAD.
            $flutterwaveUrl = "https://api.flutterwave.com/v3/country/NG/billers/'.$billerCode.'/items/'.$itemCode.'/payment";

            // Prepare the payload for the /v3/bills endpoint for DATA_BUNDLE
            $payload = [
                'country' => 'NG',              // Required for /v3/bills
                'customer' => $phoneNumber,     // Customer identifier (phone number for data)
                'amount' => $amount,  
                'name'  =>  $planname,      // The price/fee of the item - REQUIRED FOR /v3/bills
                'recurrence' => 'ONCE',         // Usually ONCE for a single purchase
                'type' => 'DATA_BUNDLE',        // Specify the bill type
                'reference' => $txRef,          // Your unique transaction reference
                'customer_email' => $user->email, // Get email from authenticated user
            ];

            // Log the details *before* making the request
            Log::info('Attempting Flutterwave Data Purchase (/v3/bills endpoint):', [
                'url' => $flutterwaveUrl, // Should now log the correct /v3/bills URL
                'payload' => $payload,
                'user_id' => $user->id,
                'tx_ref' => $txRef,
            ]);

            // Make the POST request to the generic bills endpoint
            $response = Http::withToken(env('FLW_SECRET_KEY'))
                ->acceptJson() // Ensure Accept: application/json header
                ->post($flutterwaveUrl, $payload); // POSTing to /v3/bills

            // Log the raw response for debugging
            $statusCode = $response->status();
            $rawBody = $response->body(); // Get the raw string body
            $data = $response->json(); // Attempt to parse JSON

            Log::info('Flutterwave Data Purchase Raw Response (/v3/bills endpoint):', [
                'status_code' => $statusCode,
                'successful' => $response->successful(), // Check if HTTP status is 2xx
                'raw_body' => $rawBody,
                'parsed_json' => $data, // This will be null if rawBody isn't valid JSON
                'tx_ref' => $txRef,
            ]);

            // *** CHECK THE RESPONSE STATUS AND DATA ***
            // Check if the HTTP call was successful (2xx status)
            if ($response->successful()) {
                // Now check the JSON payload status provided by Flutterwave
                if (isset($data['status']) && $data['status'] === 'success') {

                    // Transaction is successful! Balance was already debited.

                    // Store successful transaction
                    $transaction = \App\Models\Transaction::create([
                        'user_id' => $user->id,
                        'transaction_type_id' => 3, // Assuming 3 is for data purchase
                        'amount' => $amount,
                        'status' => 'successful',
                        'reference' => $txRef,
                        'description' => $shortplan . ' - ' . $phoneNumber,
                        'response_data' => $data, // Store the full FW response
                        // You could parse out data['data']['id'], data['data']['currency'] etc. here if your table has columns for them.
                        // E.g., 'fw_transaction_id' => $data['data']['id'] ?? null,
                        // 'currency' => $data['data']['currency'] ?? null,
                    ]);

                    Log::info('Data Purchase Successful (FW status success):', [
                        'tx_ref' => $txRef,
                        'transaction_id' => $transaction->id,
                        'new_balance' => $user->fresh()->balance,
                        'flutterwave_response_id' => $data['data']['id'] ?? 'N/A', // Log the FW transaction ID
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Data bundle purchased successfully!',
                        'transaction' => $transaction,
                        'new_balance' => $user->fresh()->balance,
                        'flutterwave_response' => $data, // Optionally return FW response details
                    ]);
                } else {
                    // HTTP call was 2xx, but Flutterwave's API status was 'failed' or something else
                    // In this case, the user's balance was debited, so we might need to reverse it
                    // depending on your system's design for soft failures vs hard failures.
                    // A simple approach is to mark as failed and manually check/refund if needed,
                    // or implement a refund mechanism here. For now, let's just log and mark as failed.

                    Log::error('Data Purchase Failed (FW JSON Status Not Success):', [
                        'tx_ref' => $txRef,
                        'response_status_code' => $statusCode,
                        'response_body' => $rawBody, // Log raw body for inspection
                        'parsed_json' => $data,
                    ]);

                    // Store failed transaction
                    $transaction = \App\Models\Transaction::create([
                        'user_id' => $user->id,
                        'transaction_type_id' => 3,
                        'amount' => $amount, // Still record the intended amount
                        'status' => 'failed', // Mark as failed
                        'reference' => $txRef,
                        'description' => $shortplan . ' - ' . $phoneNumber . ' (FW Status Fail)',
                        'response_data' => $data ?? ['status_code' => $statusCode, 'body' => $rawBody], // Store error details
                    ]);

                    // Construct user-friendly error message from Flutterwave's response
                    $fwErrorMessage = 'Data purchase failed. Please try again.';
                    if ($data && is_array($data) && isset($data['message'])) {
                        $fwErrorMessage = 'Data purchase failed: ' . $data['message'];
                    } elseif (!empty($rawBody)) {
                        // Attempt to extract message from raw body if JSON parsing failed or no 'message' key
                        $rawErrorMessage = json_decode($rawBody, true);
                        if ($rawErrorMessage && isset($rawErrorMessage['message'])) {
                            $fwErrorMessage = 'Data purchase failed: ' . $rawErrorMessage['message'];
                        } else {
                            // Fallback to showing a snippet of the raw body
                            $fwErrorMessage .= ' Response: ' . substr($rawBody, 0, 150) . '...';
                        }
                    }

                    // Consider refunding the user's balance if the Flutterwave call indicates a non-recoverable failure
                    // or if your business logic dictates it. This is a critical decision point.
                    // Example: $user->increment('balance', $amount); // Uncomment this line if you want to auto-refund on FW non-success status

                    return response()->json([
                        'status' => 'failed',
                        'message' => $fwErrorMessage,
                        'transaction' => $transaction,
                        'flutterwave_response' => $data,
                    ], 400); // Use 400 as it's a business logic failure, not usually a server error on your end

                }
            } else {
                // HTTP call itself failed (e.g., 400, 500 from FW, network error before getting 2xx)
                Log::error('Data Purchase Failed (HTTP Error from Flutterwave):', [
                    'tx_ref' => $txRef,
                    'response_status_code' => $statusCode,
                    'response_body' => $rawBody,
                    'exception' => $response->toException()->getMessage(), // Capture the HTTP exception message
                ]);

                // Store failed transaction
                $transaction = \App\Models\Transaction::create([
                    'user_id' => $user->id,
                    'transaction_type_id' => 3,
                    'amount' => $amount,
                    'status' => 'failed',
                    'reference' => $txRef,
                    'description' => $shortplan . ' - ' . $phoneNumber . ' (HTTP Fail)',
                    'response_data' => ['status_code' => $statusCode, 'body' => $rawBody, 'exception_message' => $response->toException()->getMessage()],
                ]);

                // The user's balance was already debited. You MUST have a process
                // to handle potential refunds/reversals for transactions that fail
                // at this stage (HTTP error after debit but before FW success confirmation).
                // Consider auto-refunding here, or rely on reconciliation/manual check.
                // Example: $user->increment('balance', $amount); // Uncomment this line if you want to auto-refund on HTTP errors

                return response()->json([
                    'status' => 'failed',
                    'message' => 'Failed to communicate with payment provider. Please try again.',
                    'transaction' => $transaction,
                ], $statusCode >= 400 ? $statusCode : 500); // Use FW status code if it's a client/server error
            }
        } catch (\Exception $e) {
            // Catch any unexpected exceptions during the process (e.g., network issues *before* getting a response, coding errors)
            Log::error('Error during Data Purchase Process (Exception Caught):', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'tx_ref' => $txRef ?? 'N/A',
                'request_data' => $request->all(),
            ]);

            // The user's balance was already debited. You MUST handle the refund/reversal
            // for exceptions occurring after the debit but before confirmation.
            // Example: $user->increment('balance', $amount); // Uncomment this line if you want to auto-refund on Exceptions


            // Store failed transaction due to exception
            try {
                \App\Models\Transaction::create([
                    'user_id' => $user ? $user->id : null,
                    'transaction_type_id' => 3,
                    'amount' => $amount ?? $request->input('amount') ?? 0,
                    'status' => 'failed',
                    'reference' => $txRef ?? 'EXCEPTION_' . time(),
                    'description' => ($shortplan ?? 'Data') . ' - ' . ($phoneNumber ?? 'N/A') . ' (System Exception)',
                    'response_data' => ['error' => $e->getMessage(), 'trace' => substr($e->getTraceAsString(), 0, 500)],
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
