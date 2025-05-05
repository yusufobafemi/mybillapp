<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Transaction; // Import the Transaction model
use App\Models\TopUp;
use App\Models\TransactionType; // Import the TransactionType model
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    // Define the expected name in the transaction_types table for deposits
    const TRANSACTION_TYPE_DEPOSIT_DB_NAME = 'Account Deposit'; // <-- Use the exact name from your TYPES array or transaction_types table

    private $depositTransactionTypeId = null; // To store the fetched ID

    public function __construct()
    {
        // Fetch the deposit transaction type ID from the database once
        try {
            $this->depositTransactionTypeId = TransactionType::where('name', self::TRANSACTION_TYPE_DEPOSIT_DB_NAME)->value('id');

            if (is_null($this->depositTransactionTypeId)) {
                 // Log a critical error if the transaction type doesn't exist in the DB
                Log::critical('Transaction type "' . self::TRANSACTION_TYPE_DEPOSIT_DB_NAME . '" not found in the transaction_types table.');
                // Depending on how critical, you might disable functionality or throw an exception
                // For now, we'll just log and check for null later when creating the transaction record.
            }
        } catch (\Exception $e) {
            // Catch database connection errors or other exceptions during fetch
            Log::critical('Failed to fetch transaction type ID from database: ' . $e->getMessage());
             // The ID will remain null, handled below
        }
    }


    /**
     * Prepare a top-up transaction by creating a pending record.
     * Called via AJAX from the frontend.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function prepareTopUp(Request $request)
    {
        $user = Auth::user(); // Get the authenticated user

        // Validate the incoming amount
        $request->validate([
            'amount' => 'required|numeric|min:1000', // Adjust min amount as needed
        ]);

        $amount = $request->input('amount');

        // Generate a unique transaction reference on the server
        $txRef = 'TXN_' . $user->id . '_' . time() . '_' . Str::random(8);
        // Consider adding a check to ensure this txRef is unique in the database
        // before using it, especially if using random strings.

        DB::beginTransaction();
        try {
            // Create a pending TopUp record in the database
            $topUp = TopUp::create([
                'user_id' => $user->id,
                'amount' => $amount, // Store the requested amount
                'gateway' => 'Flutterwave', // Or leave null until verification
                'transaction_reference' => $txRef,
                'status' => 'Pending', // Set status to Pending
                // Add other relevant fields initialized to null/default
            ]);

            DB::commit();
            Log::info('Pending TopUp record created:', ['topUpId' => $topUp->id, 'txRef' => $txRef, 'amount' => $amount, 'userId' => $user->id]);

            // Return the generated txRef and user details to the frontend
            return response()->json([
                'message' => 'Top-up prepared',
                'tx_ref' => $txRef,
                'customer' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phonenumber' => $user->phone, // Make sure phone is available/handled if null
                ],
                'amount' => $amount, // Return the amount too for clarity in JS
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to create pending TopUp record:', ['exception' => $e->getMessage(), 'userId' => $user->id, 'amount' => $amount]);
            return response()->json(['error' => 'Could not prepare transaction. Please try again.'], 500);
        }
         // Add ValidationException handling if you want specific JSON response for validation errors
         // catch (ValidationException $e) {
         //     return response()->json(['error' => $e->errors()], 422);
         // }
    }

    /**
     * Handles the callback from Flutterwave after a payment.
     * This is where you verify the transaction.
     * Called via GET redirect from Flutterwave.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function verifyPayment(Request $request)
    {
        // Log the incoming request details for debugging
        Log::info('Verify Payment Callback Received:', $request->all());

        // Get the transaction ID from the request parameters
        // Flutterwave typically sends transaction_id and tx_ref in the GET query string
        $transactionID = $request->query('transaction_id');
        $txRefFromFlutterwave = $request->query('tx_ref'); // Get tx_ref from the redirect too

        // --- Check if required parameters are missing ---
        if (empty($transactionID)) {
            Log::error('Verify Payment: transaction_id is missing from GET request.');
            // Redirect to dashboard with error, as this was a browser redirect
            return redirect('/dashboard')->with('error', 'Payment verification failed: Missing transaction ID.');
        }
         if (empty($txRefFromFlutterwave)) {
            Log::error('Verify Payment: tx_ref is missing from GET request.');
            // Redirect to dashboard with error
            return redirect('/dashboard')->with('error', 'Payment verification failed: Missing transaction reference.');
        }


        // --- Find the pending TopUp record using the tx_ref from Flutterwave ---
        $pendingTopUp = TopUp::where('transaction_reference', $txRefFromFlutterwave)->first();

        // If the pending record isn't found, it's a major issue or potentially fraud
        if (!$pendingTopUp) {
            Log::error('Verify Payment: Pending TopUp record not found for tx_ref from Flutterwave.', ['tx_ref' => $txRefFromFlutterwave, 'transaction_id' => $transactionID]);
             // Redirect to dashboard with error
             return redirect('/dashboard')->with('error', 'Payment verification failed: Transaction record not found.');
        }

        // Check if this transaction has already been processed (against the pending record)
        if ($pendingTopUp->status === 'Successful') {
             Log::warning('Verify Payment: Attempted double credit for pending record.', ['topUpId' => $pendingTopUp->id, 'tx_ref' => $txRefFromFlutterwave, 'transaction_id' => $transactionID]);
             // Redirect to dashboard with success message as it was already successful
             return redirect('/dashboard')->with('success', 'This payment was already processed successfully.');
        }


        // --- Verification Step ---
        // Make a server-to-server call to Flutterwave to verify the transaction status
        try {
            $response = Http::withToken(env('FLW_SECRET_KEY'))
                            ->get("https://api.flutterwave.com/v3/transactions/{$transactionID}/verify");

        } catch (\Exception $e) {
            // Handle network errors or other exceptions during the API call itself
             Log::error('Exception during Flutterwave verification API call:', ['exception' => $e->getMessage(), 'transaction_id' => $transactionID, 'tx_ref' => $txRefFromFlutterwave]);
             // Update pending record status if possible before redirecting
             $pendingTopUp->status = 'Failed';
             $pendingTopUp->save(); // Save the failed status
             return redirect('/dashboard')->with('error', 'Payment verification failed: Error communicating with gateway.');
        }


        // Check if the API call itself was successful (HTTP status 2xx)
        if (!$response->successful()) { // Use successful() which checks for 2xx range
             Log::error('Flutterwave Verification API Error Response:', ['status' => $response->status(), 'body' => $response->body(), 'transaction_id' => $transactionID, 'tx_ref' => $txRefFromFlutterwave]);
             // Update pending record status
             $pendingTopUp->status = 'Failed';
             $pendingTopUp->save();
             return redirect('/dashboard')->with('error', 'Payment verification failed: Could not verify with gateway.');
        }

        // Get the JSON body of the response
        $responseData = $response->json(); // <-- Correctly parse JSON body

        // Log the Flutterwave API response for debugging
        Log::info('Flutterwave Verification API Response (Parsed):', $responseData);


        // *** IMPORTANT CHECK: Verify the top-level API response status and data payload ***
        if (!is_array($responseData) || !isset($responseData['status']) || $responseData['status'] !== 'success' || !isset($responseData['data'])) {
            // This means the API call was successful (200 OK) but the body wasn't the expected 'success' status with a 'data' payload.
            Log::warning('Flutterwave Verification API Call Status Not "success" or missing data (after 200 OK):', ['responseData' => $responseData, 'transaction_id' => $transactionID, 'tx_ref' => $txRefFromFlutterwave]);
             // Update pending record status
             $pendingTopUp->status = 'Failed'; // Or a more specific 'Invalid Gateway Response'
             $pendingTopUp->save();
             return redirect('/dashboard')->with('error', 'Payment verification failed: Invalid response format.');
        }

        // Now access the 'data' key from the *parsed* response body
        $data = $responseData['data'];


        // --- Important Checks from Flutterwave's Verified Data ---

        // 1. Verify the transaction status from the 'data' payload
        if (!isset($data['status']) || $data['status'] !== 'successful') {
            // The API call succeeded, but the transaction status itself is not 'successful'
            Log::warning('Flutterwave Transaction Status Not "successful":', ['tx_ref' => $data['tx_ref'] ?? $txRefFromFlutterwave, 'status' => $data['status'] ?? 'N/A', 'transaction_id' => $transactionID]);
             // Update pending record status based on Flutterwave's status
             $pendingTopUp->status = $data['status'] ?? 'Failed'; // Use the actual status if available
             $pendingTopUp->save();
             return redirect('/dashboard')->with('error', 'Payment was not successfully completed (' . ($data['status'] ?? 'Unknown Status') . ').');
        }

        // 2. Verify that the tx_ref matches the one from your pending record
        if (!isset($data['tx_ref']) || $data['tx_ref'] !== $pendingTopUp->transaction_reference) {
            Log::error('Flutterwave Verification: tx_ref mismatch between pending record and verification response!', [
                'pending_tx_ref' => $pendingTopUp->transaction_reference,
                'verified_tx_ref' => $data['tx_ref'] ?? 'N/A',
                'transaction_id' => $transactionID,
            ]);
             // This is a critical error, potentially fraud or a serious misconfiguration
             $pendingTopUp->status = 'Failed (tx_ref mismatch)'; // Add specific status if needed
             $pendingTopUp->save();
             return redirect('/dashboard')->with('error', 'Payment verification failed: Transaction reference mismatch.');
        }


        // 3. Verify amount and currency against your pending record
        if (!isset($data['amount']) || !isset($data['currency']) || $data['amount'] < $pendingTopUp->amount || $data['currency'] !== 'NGN') {
            Log::warning('Flutterwave Verification: Amount/Currency Mismatch against pending record:', [
                'tx_ref' => $data['tx_ref'],
                'verified_amount' => $data['amount'] ?? 'N/A',
                'expected_amount' => $pendingTopUp->amount,
                'verified_currency' => $data['currency'] ?? 'N/A',
                'expected_currency' => 'NGN',
                'transaction_id' => $transactionID,
            ]);
             // Update pending record status
             $pendingTopUp->status = 'Failed (Amount/Currency Mismatch)'; // Add specific status if needed
             $pendingTopUp->save();
             return redirect('/dashboard')->with('error', 'Payment verification failed: Amount or currency mismatch.');
        }

        // --- If all checks pass, proceed to update the user's balance ---
        // The transaction is confirmed successful by Flutterwave AND matches our pending record
        // Use the user associated with the pending TopUp record as the recipient
        $user = User::find($pendingTopUp->user_id);

        // IMPORTANT: Re-check if the pending record status is still Pending
         if ($pendingTopUp->status !== 'Pending') {
             Log::warning('Verify Payment: Pending record status was not Pending right before processing!', ['topUpId' => $pendingTopUp->id, 'status' => $pendingTopUp->status, 'tx_ref' => $txRefFromFlutterwave, 'transaction_id' => $transactionID]);
             // Redirect with success message as it was likely processed concurrently
             // Or handle as a potential duplicate if it somehow wasn't caught by the first status check
             return redirect('/dashboard')->with('success', 'This payment was already processed.');
         }

        // --- Database Transaction for Atomicity ---
        DB::beginTransaction();
        try {
            // Update the pending TopUp record status to Successful
            $pendingTopUp->status = 'Successful';
             // Optionally store the Flutterwave Transaction ID in your TopUp record
             // Make sure you have a 'flutterwave_transaction_id' column (bigint UNSIGNED)
             // $pendingTopUp->flutterwave_transaction_id = $transactionID;
             // Optionally store the verified amount in the record if you didn't trust the initial request amount
             // $pendingTopUp->amount = $data['amount']; // Update amount if needed, otherwise keep the requested amount
            $pendingTopUp->save();


            // Increment user's balance using the *verified* amount from Flutterwave
            if ($user) { // Ensure user object is valid
                 $user->increment('balance', $data['amount']); // Use $data['amount'] for the actual credited amount
                 // User balance is automatically saved by increment()
             } else {
                 // This case should ideally not happen if the user_id in pendingTopUp is correct
                 Log::error('Verify Payment: User not found for balance update based on pending record user_id!', ['userId_from_pending' => $pendingTopUp->user_id, 'topUpId' => $pendingTopUp->id, 'tx_ref' => $txRefFromFlutterwave, 'transaction_id' => $transactionID]);
                 // Rollback the transaction because we can't credit the user
                 throw new \Exception("User not found for balance update.");
             }


            // Create a general Transaction record for tracking all money movements
             // Check if the deposit transaction type ID was successfully fetched in the constructor
             if (is_null($this->depositTransactionTypeId)) {
                 Log::critical('Deposit Transaction Type ID is not set. Skipping creation of general transaction record for TopUp: ' . $pendingTopUp->id);
                 // The main TopUp record is created and balance is incremented, just the general transaction record is missing.
                 // Decide if this is acceptable or if the entire transaction should roll back.
                 // For now, it will just skip creating this record.
             } else {
                 Transaction::create([
                     'user_id' => $user->id, // Use the user ID you are crediting
                     'transaction_type_id' => $this->depositTransactionTypeId, // Use the fetched ID
                     'amount' => $data['amount'], // Use the verified amount
                     'status' => 'successful', // Status for your internal transaction record
                     'reference' => $data['tx_ref'], // Use the verified tx_ref
                     // Add other relevant fields from $data if needed
                 ]);
             }

            DB::commit(); // Commit the database changes
            Log::info('Payment Successfully Verified and Processed:', ['topUpId' => $pendingTopUp->id, 'tx_ref' => $data['tx_ref'], 'amount' => $data['amount'], 'userId' => $user->id, 'transaction_id' => $transactionID]);

            // Redirect to dashboard with success message
            return redirect('/dashboard')->with('success', 'Wallet funded successfully!');

        } catch (\Exception $e) {
            DB::rollback(); // Rollback any database changes if an error occurred within the transaction block
            Log::error('Database transaction failed during payment verification processing:', ['exception' => $e->getMessage(), 'topUpId' => $pendingTopUp->id, 'tx_ref' => $txRefFromFlutterwave, 'transaction_id' => $transactionID]);

            // Try to update the pending record's status to Failed if it wasn't already
             if ($pendingTopUp->status === 'Pending') { // Only update if still Pending
                 try {
                    $pendingTopUp->status = 'Failed';
                     // Optionally store the Flutterwave transaction ID here too if it failed after verification
                     // $pendingTopUp->flutterwave_transaction_id = $transactionID;
                    $pendingTopUp->save();
                 } catch (\Exception $rollbackEx) {
                    // Log if even saving the failed status fails
                     Log::error('Failed to update pending TopUp status to Failed after processing error:', ['exception' => $rollbackEx->getMessage(), 'topUpId' => $pendingTopUp->id]);
                 }
             }

            // Redirect to dashboard with a general error message
            return redirect('/dashboard')->with('error', 'An error occurred while finalizing your payment.');
        }
    }
}