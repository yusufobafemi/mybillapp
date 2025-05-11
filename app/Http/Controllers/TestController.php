<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Import the Log facade

class TestController extends Controller
{
    /**
     * Process a data purchase via Flutterwave.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processData(Request $request)
    {
        // $user = Auth::user(); // Optional - uncomment if user authentication is required

        // --- Hardcoded Test Data ---
        // NOTE: For production, this data should come from the request or your database.
        $phoneNumber = '07015096318'; // Example Nigerian number
        $amount     = 100;           // Example amount (e.g., Naira)
        $billerCode = 'BIL110';      // Example Biller Code (AIRTEL NG) - **VERIFY THIS**
        $itemCode   = 'MD136';       // Example Item Code (AIRTEL 100MB) - **VERIFY THIS**
        // $email      = 'test@example.com'; // Not used in this specific endpoint payload
        // $name       = 'AIRTEL 100 MB data bundle'; // Not used in this specific endpoint payload

        $txRef = 'TX-' . uniqid(); // Generate a unique transaction reference

        // --- API Configuration ---
        // Ensure these are set in your .env file
        $secretKey     = env('FLW_SECRET_KEY');
        // Provide a fallback URL for the callback if env is not set
        $callbackUrl   = env('FLW_CALLBACK_URL', url('/api/flutterwave/callback'));

        if (empty($secretKey)) {
             Log::error('Flutterwave Secret Key not set in environment.');
             return response()->json([
                 'message' => 'Configuration error: Flutterwave secret key is missing.',
             ], 500);
        }

        // Construct the Flutterwave API endpoint URL
        $flutterwaveUrl = "https://api.flutterwave.com/v3/billers/{$billerCode}/items/{$itemCode}/payment";

        // Prepare the payload for the API request
        $payload = [
            "country"     => "NG", // Assuming Nigerian data bundles
            "customer_id" => $phoneNumber, // The number receiving the data
            "amount"      => $amount, // The price of the bundle
            "reference"   => $txRef, // Your unique transaction reference
            "callback_url" => $callbackUrl, // URL Flutterwave will call back
            // "customer_email" => $email, // Optional: add if needed and available
            // "customer_name" => $name,   // Optional: add if needed and available
        ];

        // Log the request details before sending
        Log::info('Attempting Flutterwave Data Purchase', [
            'url' => $flutterwaveUrl,
            'payload' => $payload,
            // Be cautious logging full secret keys in production logs
            // 'headers' => ['Authorization' => 'Bearer ' . substr($secretKey, 0, 5) . '...'],
            'reference' => $txRef,
        ]);

        try {
            // Make the HTTP POST request to Flutterwave
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ])->post($flutterwaveUrl, $payload);

            Log::info('Flutterwave Request Headers:', [
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                // Add any other headers you are sending
            ]);
            Log::info('Flutterwave Request Payload:', $payload);

            // Log the raw response details immediately after receiving
            Log::info('Received Flutterwave Data Purchase Response', [
                'reference' => $txRef,
                'http_status' => $response->status(), // Log the HTTP status code
                'response_body' => $response->body(), // Log the raw response body string
            ]);

            // Attempt to parse the JSON response
            $result = $response->json();

            // Log the parsed JSON response
            Log::info('Parsed Flutterwave Data Purchase Response', [
                'reference' => $txRef,
                'parsed_response' => $result, // Log the parsed array
            ]);


            // Check if the HTTP request was successful AND the Flutterwave API status is 'success'
            if ($response->successful() && isset($result['status']) && $result['status'] === 'success') {
                // Log success
                Log::info('Flutterwave Data Purchase Succeeded', [
                    'reference' => $txRef,
                    'flutterwave_status' => $result['status'],
                    'flutterwave_message' => $result['message'] ?? 'No message',
                    'data' => $result['data'] ?? null, // Log the 'data' part if available
                ]);

                // Return success response
                return response()->json([
                    'message'   => 'Data purchase successful (direct item endpoint)',
                    'reference' => $txRef,
                    'response'  => $result, // Include full API response for client
                ]);

            } else {
                // Log the failure with more detail
                Log::error('Flutterwave Data Purchase Failed (API Status Not Success)', [
                    'reference' => $txRef,
                    'http_status' => $response->status(),
                    'parsed_response' => $result, // Log the full parsed response again
                    'payload' => $payload, // Include the payload sent
                ]);

                // Determine an appropriate status code for the response back to the client
                // Use the remote HTTP status if it's a client error (4xx), otherwise default
                $statusCodeToReturn = $response->status();
                if ($statusCodeToReturn < 400 || $statusCodeToReturn >= 500) {
                    // If remote status is not a 4xx, use a generic client error code or server error
                    $statusCodeToReturn = 422; // Unprocessable Entity
                    if ($response->serverError()) {
                         $statusCodeToReturn = 502; // Bad Gateway (remote server error)
                    }
                }

                // Return failure response with details
                return response()->json([
                    'message'   => 'Data purchase failed',
                    'error'     => $result['message'] ?? ($response->failed() ? 'HTTP Request failed' : 'Unknown API error'),
                    'flutterwave_response'  => $result, // Include the full Flutterwave response body
                    'http_status_from_flutterwave' => $response->status(), // Include HTTP status from Flutterwave
                ], $statusCodeToReturn); // Use determined status code

            }

        } catch (\Exception $e) {
            // Log the exception with trace
            Log::error('Exception Occurred During Flutterwave Data Purchase', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(), // Log the full stack trace
                'reference' => $txRef,
                'payload' => $payload, // Include the payload for context
                'url' => $flutterwaveUrl, // Include the URL for context
            ]);

            // Return a generic error response for the client, optionally including trace in debug mode
            return response()->json([
                'message' => 'An unexpected server error occurred.',
                'error'   => $e->getMessage(), // Exposing message can be risky in production
                'trace'   => (bool)config('app.debug') ? $e->getTraceAsString() : 'Tracing disabled.', // Only show trace in debug mode
            ], 500);
        }
    }
}