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
        $validatedData = $request->validate([ // Store validated data for easier access
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
            $flutterwaveUrl = "https://api.flutterwave.com/v3/country/NG/billers/{$billerCode}/items/{$itemCode}/payment";
            $payload = [
                'customer' => $phoneNumber,
                'reference' => $txRef,
                'amount' => $amount,
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

            // --- NEW & IMPROVED LOGGING ---
            $statusCode = $response->status();
            $rawBody = $response->body(); // Get the raw response body as a string
            $data = $response->json();   // Attempt to parse the JSON

            Log::info('Flutterwave Data Purchase Raw Response:', [
                'status_code' => $statusCode,
                'successful' => $response->successful(), // Check if status is 2xx
                'raw_body' => $rawBody, // This is what you need to see
                'parsed_json' => $data, // Will be null if parsing failed or body was empty/invalid
                'tx_ref' => $txRef, // Keep reference for correlation
            ]);
            // --- END NEW LOGGING ---


            // First, check if the HTTP call itself was successful (2xx status)
            // If not, the JSON status check below might fail or be irrelevant.
            if (!$response->successful()) {
                Log::error('Flutterwave API call non-successful status:', [
                    'status_code' => $statusCode,
                    'raw_body' => $rawBody, // Log raw body again in error case
                    'tx_ref' => $txRef,
                ]);

                // Create a failed transaction immediately for non-successful API calls
                \App\Models\Transaction::create([
                    'user_id' => $user->id,
                    'transaction_type_id' => 3,
                    'amount' => $amount, // Log the intended amount
                    'status' => 'failed',
                    'reference' => $txRef,
                    'description' => $shortplan . '-' . $phoneNumber . ' (API Status Fail)',
                    'response_data' => ['status_code' => $statusCode, 'body' => $rawBody], // Store raw response details
                ]);

                // Return a more informative error response
                $errorMessage = "Flutterwave API call failed with status code {$statusCode}.";
                // Try to get a specific error message from the raw body if it's not JSON or parsing failed
                if ($rawBody) {
                    $errorMessage .= " Response: " . substr($rawBody, 0, 255); // Limit length for log/response
                }

                return response()->json([
                    'status' => 'failed',
                    'message' => $errorMessage,
                    'tx_ref' => $txRef,
                ], $statusCode >= 400 ? $statusCode : 500); // Use actual status if client/server error, otherwise 500
            }


            // If the API call was successful (2xx), now check the *parsed JSON* status field
            // Ensure $data is not null and is an array before accessing the 'status' key
            if ($data && is_array($data) && isset($data['status']) && $data['status'] === 'success') {
                // Deduct balance
                $user->decrement('balance', $amount);

                // Store transaction
                $transaction = \App\Models\Transaction::create([ // Use the alias
                    'user_id' => $user->id,
                    'transaction_type_id' => 3, // 3 for data purchase
                    'amount' => $amount,
                    'status' => 'successful',
                    'reference' => $txRef,
                    'description' => $shortplan . '-' . $phoneNumber,
                    'response_data' => $data, // Store the successful response data
                ]);

                Log::info('Data Purchase Successful (Flutterwave status success):', [
                    'tx_ref' => $txRef,
                    'transaction_id' => $transaction->id,
                    'new_balance' => $user->fresh()->balance,
                    'flutterwave_response' => $data, // Log the full parsed success response
                ]);


                return response()->json([
                    'status' => 'success',
                    'message' => 'Data bundle purchased successfully!',
                    'transaction' => $transaction,
                    'new_balance' => $user->fresh()->balance,
                    'flutterwave_response' => $data, // Include FW response in success response
                ]);
            } else {
                // The API call was successful (2xx) but the JSON status was not 'success'
                Log::error('Data Purchase Failed (Flutterwave JSON Status Not Success):', [
                    'tx_ref' => $txRef,
                    'response_status' => $statusCode,
                    'raw_body' => $rawBody, // Log raw body again in error path
                    'parsed_json' => $data, // Log parsed data (this should contain FW error details if parsing worked)
                ]);

                // Store failed transaction
                $transaction = \App\Models\Transaction::create([ // Use the alias
                    'user_id' => $user->id,
                    'transaction_type_id' => 3,
                    'amount' => $amount, // Log intended amount
                    'status' => 'failed',
                    'reference' => $txRef,
                    'description' => $shortplan . '-' . $phoneNumber . ' (FW Status Fail)',
                    'response_data' => $data ?? ['raw_body' => $rawBody], // Store the parsed response data (contains error) or raw if parsing failed
                ]);

                // Try to extract a meaningful error message from the FW response if available
                $fwErrorMessage = 'Data purchase failed. Please try again.';
                if ($data && is_array($data) && isset($data['message'])) {
                    $fwErrorMessage = 'Data purchase failed: ' . $data['message'];
                } elseif ($rawBody) {
                    // Fallback to raw body if JSON message not found
                    $fwErrorMessage .= " Response: " . substr($rawBody, 0, 255); // Limit length
                }


                return response()->json([
                    'status' => 'failed',
                    'message' => $fwErrorMessage,
                    'transaction' => $transaction,
                    'flutterwave_response' => $data, // Include FW response in failure response
                ]);
            }
        } catch (\Exception $e) {
            // This catch block handles exceptions like network errors, curl errors, etc.
            Log::error('Error during Data Purchase Process (Exception Caught):', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(), // Use 'trace' instead of 'stack'
                'tx_ref' => $txRef ?? 'N/A', // Log txRef if generated before exception
                'request_data' => $request->all(), // Log incoming data
            ]);

            // Create a failed transaction for uncaught exceptions as a fallback
            // (You might have a more sophisticated system where a transaction is created as 'pending' earlier)
            try {
                \App\Models\Transaction::create([
                    'user_id' => $user ? $user->id : null, // user might be null in theory if error before auth
                    'transaction_type_id' => 3,
                    'amount' => $amount ?? $request->input('amount') ?? 0, // Use amount if set, otherwise try request
                    'status' => 'failed',
                    'reference' => $txRef ?? 'EXCEPTION_' . time(), // Generate ref if txRef wasn't set
                    'description' => $shortplan . '-' . $phoneNumber . ' (System Exception)',
                    'response_data' => ['error' => $e->getMessage(), 'trace' => substr($e->getTraceAsString(), 0, 500)], // Log exception details
                ]);
            } catch (\Exception $te) {
                Log::error('Failed to record transaction for caught exception', ['exception' => $te->getMessage()]);
            }


            return response()->json([
                'status' => 'failed',
                'message' => 'An internal error occurred. Please try again later.',
            ], 500); // Use 500 for internal server errors
        }
    }
}
