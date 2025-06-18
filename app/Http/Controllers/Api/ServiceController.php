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
        $type = $request->input('type');

        // For cable service, check if type is getCableItemcode
        if ($serviceType === 'cable' && $type === 'getCableItemcode') {
            // Validate billercode for fetching packages
            $request->validate([
                'billercode' => 'required|string',
            ]);

            return $this->getCablePlans($request);
        }

        // Otherwise, proceed with existing service processing
        switch ($serviceType) {
            case 'airtime':
                return $this->processAirtime($request);
            case 'data':
                return $this->processData($request);
            case 'cable':
                return $this->processCable($request);
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
        // 1. Authenticate User
        $user = auth('web')->user(); // Using auth('web')->user() as in the first snippet

        if (!$user) {
            Log::warning('Unauthenticated attempt to process data purchase.');
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        // Generate Transaction Reference BEFORE validation/processing
        $txRef = 'TXN_' . $user->id  . time(); // Include user ID for better traceability

        // 2. Validate Request
        $validatedData = $request->validate([
            'biller_code' => 'required|string', // e.g., BIL110 (MTN NG)
            'item_code' => 'required|string',   // e.g., MD136 (1GB daily)
            'phoneNumber' => 'required|string', // Customer phone number (e.g., 08012345678)
            'amount' => 'required|numeric|min:0.01', // The exact 'fee' of the data plan item (must be numeric > 0) - Changed min to 0.01 as amount cannot be 0.
            'shortplan' => 'required|string',   // Description for your transaction record (e.g., "MTN 1GB Daily")
            'planname' => 'required|string',    // Full plan name (e.g., "MTN 1GB Daily Bundle")
        ]);

        // Use validated data
        $billerCode = $validatedData['biller_code'];
        $itemCode = $validatedData['item_code'];
        $phoneNumber = $validatedData['phoneNumber'];
        $amount = (float) $validatedData['amount']; // Cast to float to ensure numeric comparison
        $shortplan = $validatedData['shortplan'];
        $planname = $validatedData['planname'];

        // 3. Check User Balance
        if ($user->balance < $amount) {
            Log::warning('Insufficient balance for data purchase', [
                'user_id' => $user->id,
                'requested_amount' => $amount,
                'current_balance' => $user->balance,
                'tx_ref' => $txRef,
            ]);
            return response()->json(['error' => 'Insufficient balance.'], 400);
        }

        // 4. Debit User Balance *Before* Calling External API
        // This is a critical step to prevent double-spending.
        // If the API call fails for any reason *after* this point, you must have a process
        // to reverse the debit (refund) or reconcile it manually.
        $user->decrement('balance', $amount);
        Log::info('User balance debited for Data Purchase attempt', [
            'user_id' => $user->id,
            'amount_debited' => $amount,
            'tx_ref' => $txRef,
            'old_balance' => $user->balance + $amount, // Log old balance
            'new_balance' => $user->fresh()->balance, // Log new balance after decrement
            'plan' => $shortplan,
            'phone' => $phoneNumber,
        ]);


        try {
            // 5. Prepare and Make Flutterwave API Call (using the working endpoint)

            // Get Flutterwave Secret Key and Callback URL from environment
            $secretKey = env('FLW_SECRET_KEY');
            // Provide a fallback URL for the callback if env is not set
            $callbackUrl = env('FLW_CALLBACK_URL', url('/api/flutterwave/callback')); // Define a default callback URL

            if (empty($secretKey)) {
                // This is a critical configuration error
                Log::error('Flutterwave Secret Key not set in environment. Cannot proceed with API call.', ['tx_ref' => $txRef, 'user_id' => $user->id]);
                // Reversing debit as API call cannot be made
                $user->increment('balance', $amount);
                Log::info('User balance refunded due to missing FLW_SECRET_KEY', ['user_id' => $user->id, 'amount_refunded' => $amount, 'tx_ref' => $txRef, 'new_balance' => $user->fresh()->balance]);

                // Store failed transaction
                \App\Models\Transaction::create([
                    'user_id' => $user->id,
                    'transaction_type_id' => 3, // Assuming 3 is for data purchase
                    'amount' => $amount,
                    'status' => 'failed', // Mark as failed
                    'reference' => $txRef,
                    'description' => $shortplan . ' - ' . $phoneNumber . ' (Config Error)',
                    'response_data' => ['error' => 'Flutterwave Secret Key Missing'],
                ]);


                return response()->json([
                    'status' => 'failed',
                    'message' => 'Server configuration error preventing data purchase. Please contact support.',
                ], 500);
            }


            // Construct the Flutterwave API endpoint URL based on the working snippet
            // This endpoint requires biller_code and item_code in the URL path
            $flutterwaveUrl = "https://api.flutterwave.com/v3/billers/{$billerCode}/items/{$itemCode}/payment";

            // Prepare the payload for the API request (using the working snippet's structure)
            $payload = [
                "country"     => "NG", // Assuming Nigerian data bundles
                "customer_id" => $phoneNumber, // The phone number receiving the data
                "amount"      => $amount, // The price of the bundle (validated amount)
                "reference"   => $txRef, // Your unique transaction reference
                "callback_url" => $callbackUrl, // URL Flutterwave will call back for status updates
                "customer_email" => $user->email ?? null, // Optional: from authenticated user
                "customer_name" => $user->name ?? null,   // Optional: from authenticated user if available
                // The working endpoint /v3/billers/{billerCode}/items/{itemCode}/payment
                // does NOT typically require 'name', 'recurrence', 'type' like the generic /v3/bills endpoint.
                // Sticking to the parameters that worked in snippet 2 and are listed in docs for this endpoint.
            ];

            // Log the request details before sending
            Log::info('Attempting Flutterwave Data Purchase (Specific Item Endpoint):', [
                'url' => $flutterwaveUrl,
                'payload' => $payload,
                'user_id' => $user->id,
                'tx_ref' => $txRef,
                // Be cautious logging full secret keys in production logs
                // 'auth_header' => 'Bearer ' . substr($secretKey, 0, 5) . '...',
            ]);

            // Make the HTTP POST request to Flutterwave
            // Using withToken is equivalent to withHeaders(['Authorization' => 'Bearer ...'])
            $response = Http::withToken($secretKey)
                ->acceptJson() // Ensure Accept: application/json header
                ->post($flutterwaveUrl, $payload);

            // Log the raw response details immediately after receiving
            $statusCode = $response->status();
            $rawBody = $response->body(); // Get the raw string body
            $result = $response->json(); // Attempt to parse JSON

            Log::info('Received Flutterwave Data Purchase Raw Response (Specific Item Endpoint):', [
                'tx_ref' => $txRef,
                'http_status' => $statusCode, // Log the HTTP status code
                'successful_http' => $response->successful(), // Check if HTTP status is 2xx
                'raw_body' => $rawBody, // Log the raw response body string
                'parsed_json' => $result, // Log the parsed array (null if not JSON)
            ]);


            // 6. Process Flutterwave Response

            // Check if the HTTP call was successful (2xx status)
            if ($response->successful()) {
                // HTTP call successful, now check the JSON payload status from Flutterwave
                if (isset($result['status']) && $result['status'] === 'success') {

                    // Transaction is successful according to Flutterwave API status!
                    // Balance was already debited. Record the successful transaction.

                    $transaction = \App\Models\Transaction::create([
                        'user_id' => $user->id,
                        'transaction_type_id' => 3, // Assuming 3 is for data purchase
                        'amount' => $amount,
                        'status' => 'successful',
                        'reference' => $txRef,
                        'description' => $shortplan . ' - ' . $phoneNumber,
                        'response_data' => $result, // Store the full FW response
                        'fw_transaction_id' => $result['data']['id'] ?? null, // Capture FW's internal transaction ID if available
                        'currency' => $result['data']['currency'] ?? null, // Capture currency if available
                    ]);

                    Log::info('Data Purchase Successful (FW status success):', [
                        'tx_ref' => $txRef,
                        'transaction_id' => $transaction->id,
                        'user_id' => $user->id,
                        'new_balance' => $user->fresh()->balance,
                        'flutterwave_response_id' => $result['data']['id'] ?? 'N/A', // Log the FW transaction ID
                    ]);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Data bundle purchased successfully!',
                        'transaction_ref' => $txRef, // Return your reference
                        'flutterwave_response_ref' => $result['data']['reference'] ?? 'N/A', // Return FW's reference (might be same as yours)
                        'new_balance' => $user->fresh()->balance,
                        // Optionally include more FW details if needed by the frontend:
                        // 'flutterwave_data' => $result['data'] ?? null,
                    ]);
                } else {
                    // HTTP call was 2xx, but Flutterwave's API status was not 'success'
                    // This is a business logic failure on Flutterwave's side after they accepted the request.
                    // The user's balance was debited. You NEED to decide if you auto-refund here.
                    // Auto-refunding immediately is simplest but might be risky if FW's status is delayed.
                    // A robust system might mark as 'pending_manual_review' and refund later.
                    // For demonstration, let's log and indicate failure. Auto-refund is commented out.

                    Log::error('Data Purchase Failed (FW JSON Status Not Success):', [
                        'tx_ref' => $txRef,
                        'user_id' => $user->id,
                        'response_status_code' => $statusCode,
                        'raw_body' => $rawBody, // Log raw body for inspection
                        'parsed_json' => $result,
                        'payload' => $payload, // Log the payload sent
                    ]);

                    // Store failed transaction
                    $transaction = \App\Models\Transaction::create([
                        'user_id' => $user->id,
                        'transaction_type_id' => 3,
                        'amount' => $amount, // Still record the intended amount
                        'status' => 'failed', // Mark as failed
                        'reference' => $txRef,
                        'description' => $shortplan . ' - ' . $phoneNumber . ' (FW Status Fail)',
                        'response_data' => $result ?? ['status_code' => $statusCode, 'body' => $rawBody], // Store error details
                        'fw_transaction_id' => $result['data']['id'] ?? null, // Capture FW's internal transaction ID if available
                    ]);

                    // Construct user-friendly error message from Flutterwave's response
                    $fwErrorMessage = 'Data purchase failed. Please try again.';
                    if ($result && is_array($result) && isset($result['message'])) {
                        $fwErrorMessage = 'Data purchase failed: ' . $result['message'];
                    } elseif (!empty($rawBody)) {
                        // Attempt to extract message from raw body if JSON parsing failed or no 'message' key
                        $rawErrorMessage = json_decode($rawBody, true);
                        if ($rawErrorMessage && isset($rawErrorMessage['message'])) {
                            $fwErrorMessage = 'Data purchase failed: ' . $rawErrorMessage['message'];
                        } else {
                            // Fallback to showing a snippet of the raw body
                            if (strlen($rawBody) > 0) {
                                $fwErrorMessage .= ' Response snippet: ' . substr($rawBody, 0, 150) . '...';
                            }
                        }
                    }

                    // CONSIDER REFUNDING HERE IF THE FAILURE IS DEFINITIVE (e.g., invalid parameters according to FW)
                    // $user->increment('balance', $amount);
                    // Log::info('User balance refunded due to FW non-success status', ['user_id' => $user->id, 'amount_refunded' => $amount, 'tx_ref' => $txRef, 'new_balance' => $user->fresh()->balance]);


                    return response()->json([
                        'status' => 'failed',
                        'message' => $fwErrorMessage,
                        'transaction_ref' => $txRef,
                        // Optionally return FW response details for debugging/info on frontend:
                        // 'flutterwave_response' => $result,
                    ], 400); // Use 400 as it's a business logic failure from FW, not usually a server error on your end (unless it's a 5xx from FW)

                }
            } else {
                // 7. Handle HTTP Call Failure (e.g., network error, 4xx, 5xx from FW)
                // The user's balance was already debited. You MUST have a process
                // to handle potential refunds/reversals for transactions that fail
                // at this stage (HTTP error after debit but before confirmation).
                // Consider auto-refunding here, or rely on reconciliation/manual check.

                $errorMessage = 'Failed to communicate with payment provider.';
                $httpErrorCode = $statusCode;
                $exceptionMessage = $response->toException()->getMessage();


                Log::error('Data Purchase Failed (HTTP Error from Flutterwave):', [
                    'tx_ref' => $txRef,
                    'user_id' => $user->id,
                    'response_status_code' => $httpErrorCode,
                    'response_body' => $rawBody,
                    'exception' => $exceptionMessage, // Capture the HTTP exception message
                    'payload' => $payload, // Log the payload sent
                ]);

                // Store failed transaction
                $transaction = \App\Models\Transaction::create([
                    'user_id' => $user->id,
                    'transaction_type_id' => 3,
                    'amount' => $amount,
                    'status' => 'failed',
                    'reference' => $txRef,
                    'description' => $shortplan . ' - ' . $phoneNumber . ' (HTTP Fail)',
                    'response_data' => ['status_code' => $httpErrorCode, 'body' => $rawBody, 'exception_message' => $exceptionMessage],
                ]);

                // CONSIDER REFUNDING HERE IF THE HTTP ERROR IS LIKELY TRANSIENT OR A CLEAR FAILURE
                // $user->increment('balance', $amount);
                // Log::info('User balance refunded due to HTTP error from FW', ['user_id' => $user->id, 'amount_refunded' => $amount, 'tx_ref' => $txRef, 'new_balance' => $user->fresh()->balance]);


                return response()->json([
                    'status' => 'failed',
                    'message' => $errorMessage . ($httpErrorCode > 0 ? " Status: {$httpErrorCode}" : "") . ". Please try again.",
                    'transaction_ref' => $txRef,
                ], $httpErrorCode >= 400 ? $httpErrorCode : 502); // Use FW status code if it's a client/server error, default to 502 (Bad Gateway) for unexpected HTTP issues
            }
        } catch (\Exception $e) {
            // 8. Catch any unexpected exceptions during the process (e.g., network issues *before* getting a response, coding errors)

            // The user's balance was already debited. You MUST handle the refund/reversal
            // for exceptions occurring after the debit but before confirmation.

            $errorMessage = 'An internal error occurred while processing your request.';

            Log::error('Error during Data Purchase Process (Exception Caught):', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(), // Log the full trace for debugging
                'tx_ref' => $txRef, // Use the generated reference if available
                'user_id' => $user->id, // Use the user ID if available
                'request_data' => $request->all(), // Log the request data
                'payload_attempted' => $payload ?? 'N/A', // Log the payload if it was prepared
                'url_attempted' => $flutterwaveUrl ?? 'N/A', // Log the URL if it was prepared
            ]);

            // CONSIDER REFUNDING HERE IF AN EXCEPTION OCCURRED AFTER DEBIT
            // $user->increment('balance', $amount);
            // Log::info('User balance refunded due to system exception', ['user_id' => $user->id, 'amount_refunded' => $amount, 'tx_ref' => $txRef, 'new_balance' => $user->fresh()->balance]);


            // Attempt to record a failed transaction due to the exception
            try {
                \App\Models\Transaction::create([
                    'user_id' => $user ? $user->id : null, // Ensure user ID is saved if available
                    'transaction_type_id' => 3,
                    'amount' => $amount ?? $request->input('amount') ?? 0, // Try to get amount if variable wasn't set before exception
                    'status' => 'failed',
                    'reference' => $txRef ?? 'EXCEPTION_' . time(), // Use txRef if available, else generate a new one
                    'description' => ($shortplan ?? 'Data') . ' - ' . ($phoneNumber ?? 'N/A') . ' (System Exception)',
                    'response_data' => ['error' => $e->getMessage(), 'trace' => substr($e->getTraceAsString(), 0, 500)], // Store error details, limit trace length
                ]);
            } catch (\Exception $te) {
                Log::error('Failed to record transaction for caught exception during Data purchase', [
                    'main_exception' => $e->getMessage(),
                    'transaction_creation_exception' => $te->getMessage(),
                    'tx_ref' => $txRef ?? 'N/A',
                ]);
            }


            return response()->json([
                'status' => 'failed',
                'message' => $errorMessage . (config('app.debug') ? ' Debug: ' . $e->getMessage() : ''), // Show debug info only in debug mode
                'transaction_ref' => $txRef ?? 'N/A',
            ], 500);
        }
    }

    private function processCable(Request $request)
    {
        // Authenticate user
        $user = auth('web')->user();
        if (!$user) {
            Log::warning('Unauthenticated attempt to process cable subscription.');
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        // Generate transaction reference
        $txRef = 'TXN_' . $user->id  . time();

        // Validate request
        $validatedData = $request->validate([
            'smartCard' => 'required|string',
            'provider' => 'required|string',
            'package' => 'required|string',
            'billerCode' => 'required|string',
            'itemCode' => 'required|string',
            'packageName' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
        ]);

        // Extract validated data
        $smartCard = $validatedData['smartCard'];
        $provider = $validatedData['provider'];
        $itemCode = $validatedData['itemCode'];
        $billerCode = $validatedData['billerCode'];
        $packageName = $validatedData['packageName'];
        $amount = (float) $validatedData['amount'];

        // Check balance
        if ($user->balance < $amount) {
            Log::warning('Insufficient balance for cable subscription', [
                'user_id' => $user->id,
                'requested_amount' => $amount,
                'current_balance' => $user->balance,
                'tx_ref' => $txRef,
            ]);
            return response()->json(['error' => 'Insufficient balance.'], 400);
        }

        try {
            // Prepare Flutterwave API call
            $secretKey = env('FLW_SECRET_KEY');
            $callbackUrl = env('FLW_CALLBACK_URL', url('/api/flutterwave/callback'));
            if (empty($secretKey)) {
                Log::error('Flutterwave Secret Key not set.', ['tx_ref' => $txRef]);
                return response()->json(['status' => 'failed', 'message' => 'Server configuration error.'], 500);
            }

            $flutterwaveUrl = "https://api.flutterwave.com/v3/billers/{$billerCode}/items/{$itemCode}/payment";
            $payload = [
                'country' => 'NG',
                'customer_id' => $smartCard,
                'amount' => $amount,
                'reference' => $txRef,
                'callback_url' => $callbackUrl,
                'customer_email' => $user->email ?? null,
                'customer_name' => $user->name ?? null,
            ];

            // Make API call
            $response = Http::withToken($secretKey)->acceptJson()->post($flutterwaveUrl, $payload);
            $result = $response->json();

            Log::info('Flutterwave Cable Subscription Response:', [
                'tx_ref' => $txRef,
                'status_code' => $response->status(),
                'response' => $result,
            ]);

            // Handle response
            if ($response->successful() && isset($result['status']) && $result['status'] === 'success') {
                // Deduct balance after success
                $user->decrement('balance', $amount);

                // Record transaction
                \App\Models\Transaction::create([
                    'user_id' => $user->id,
                    'transaction_type_id' => 4, // Assuming 4 for cable
                    'amount' => $amount,
                    'status' => 'successful',
                    'reference' => $txRef,
                    'description' => "{$packageName} - {$smartCard}",
                    'response_data' => $result,
                    'fw_transaction_id' => $result['data']['id'] ?? null,
                    'currency' => $result['data']['currency'] ?? null,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Cable subscription successful!',
                    'transaction_ref' => $txRef,
                    'new_balance' => $user->fresh()->balance,
                ]);
            } else {
                // Handle failure
                $errorMessage = $result['message'] ?? 'Cable subscription failed.';
                Log::error('Cable Subscription Failed:', [
                    'tx_ref' => $txRef,
                    'response' => $result,
                ]);

                \App\Models\Transaction::create([
                    'user_id' => $user->id,
                    'transaction_type_id' => 4,
                    'amount' => $amount,
                    'status' => 'failed',
                    'reference' => $txRef,
                    'description' => "{$packageName} - {$smartCard} (Failed)",
                    'response_data' => $result,
                ]);

                return response()->json([
                    'status' => 'failed',
                    'message' => $errorMessage,
                    'transaction_ref' => $txRef,
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error during Cable Subscription:', [
                'tx_ref' => $txRef,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            \App\Models\Transaction::create([
                'user_id' => $user->id,
                'transaction_type_id' => 4,
                'amount' => $amount,
                'status' => 'failed',
                'reference' => $txRef,
                'description' => "{$packageName} - {$smartCard} (Exception)",
                'response_data' => ['error' => $e->getMessage()],
            ]);

            return response()->json([
                'status' => 'failed',
                'message' => 'An error occurred. Please try again.',
                'transaction_ref' => $txRef,
            ], 500);
        }
    }

    /**
     * Fetch cable TV subscription plans for a given biller code.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCablePlans(Request $request)
    {
        try {
            $billerCode = $request->input('billercode');
            if (!$billerCode) {
                return response()->json(['error' => 'Biller code is required'], 400);
            }

            $url = "https://api.flutterwave.com/v3/billers/{$billerCode}/items";
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('FLW_SECRET_KEY'),
                'Content-Type' => 'application/json',
            ])->get($url);

            if ($response->failed()) {
                Log::error('Flutterwave API error', ['response' => $response->json()]);
                return response()->json(['error' => 'Failed to fetch cable plans'], 500);
            }

            $responseData = $response->json();
            Log::info('Flutterwave response', ['response' => $responseData]);

            if ($responseData['status'] !== 'success' || empty($responseData['data'])) {
                return response()->json(['error' => 'No cable plans found'], 404);
            }

            // List of package names to exclude
            $excludedPackages = [
                'Compact + Asia',
                'Compact + French Touch',
                'Compact + French Touch + Xtraview',
                'Compact + Asia + Xtraview',
                'Compact + French Plus',
                'DStv French Touch Add-on Bouquet E36',
                'DStv Asian Add-on Bouquet E36',
                'DStv French Plus Add-on Bouquet E36',
                'Dstv Great Wall standalone Bouquet',
                'French 11 Bouquet E36',
                'French 11',
                'Premium + French',
                'Premium + French + Xtraview',
                'Premium + French Touch + HD/ExtraView',
                'PremiumFrench + Showmax',
                'Premium Asia + HD/ExtraView',
                'Asian + HD/ExtraView',
                'Asian + Showmax',
                'DSTV PREMIUM ASIA',
                'Compact Plus + Asia +Xtraview',
                'Compact Plus + French Touch',
                'Compact Plus + French Plus',
                'CompactPlus + French Plus + Xtraview',
                'Great Wall Standalone Bouquet E36 + Showmax',
                'PremiumAsia + Xtraview',
            ];

            // Filter out excluded packages (case-insensitive)
            $filteredData = array_filter($responseData['data'], function ($item) use ($excludedPackages) {
                return !in_array(strtolower($item['name']), array_map('strtolower', $excludedPackages));
            });

            // Reindex the array
            $filteredData = array_values($filteredData);

            if (empty($filteredData)) {
                return response()->json(['error' => 'No relevant cable plans found'], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Cable plans fetched successfully',
                'data' => $filteredData,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching cable plans', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'An error occurred'], 500);
        }
    }

    //verify electricity meter number
    public function verifyMeterNumber(Request $request)
    {
        $request->validate([
            'biller_code' => 'required|string',
            'meter_number' => 'required|string',
            'meter_type' => 'required|string',
        ]);

        try {
            $response = Http::withToken(env('FLUTTERWAVE_SECRET'))
                ->post('https://api.flutterwave.com/v3/billers/validate', [
                    'biller_code' => $request->biller_code,
                    'customer' => $request->meter_number,
                    'type' => $request->meter_type
                ]);

            $result = $response->json();

            if ($result['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'customer_name' => $result['data']['customer_name'],
                        'meter_number' => $request->meter_number,
                    ]
                ]);
            }

            return response()->json([
                'status' => 'fail',
                'message' => $result['message'] ?? 'Could not verify meter number'
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Server error while verifying meter number.'
            ], 500);
        }
    }

    // verify cable tv number
    public function verifyCable(Request $request)
    {
        // Validate request inputs
        $request->validate([
            'smart_card' => ['required', 'string', 'regex:/^[0-9]+$/'], // Numeric smart card
            'item_code' => 'required|string', // e.g., CB177
        ]);

        try {
            $smartCard = $request->input('smart_card');
            $itemCode = $request->input('item_code');

            // Call Flutterwave API to validate smart card
            $url = "https://api.flutterwave.com/v3/bill-items/{$itemCode}/validate?customer={$smartCard}";
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('FLW_SECRET_KEY'),
                'Content-Type' => 'application/json',
            ])->get($url); // Note: GET request as per the endpoint structure

            $result = $response->json();
            Log::info('Flutterwave cable validation response', ['response' => $result]);

            if ($response->successful() && $result['status'] === 'success' && !empty($result['data'])) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'customer_name' => $result['data']['name'] ?? 'Unknown', // Adjust field based on actual response
                        'smart_card' => $smartCard,
                        'status' => $result['data']['status'] ?? 'N/A',
                    ],
                ], 200);
            }

            return response()->json([
                'status' => 'fail',
                'message' => $result['message'] ?? 'Could not verify smart card.',
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error verifying cable smart card', [
                'error' => $e->getMessage(),
                'smart_card' => $request->input('smart_card'),
                'item_code' => $request->input('item_code'),
            ]);
            return response()->json([
                'status' => 'fail',
                'message' => 'Server error while verifying smart card.',
            ], 500);
        }
    }
}
