<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Update user's phone and balance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $userId
     * @return \Illuminate\Http\Response
     */
    public function updateBalance(Request $request, $userId)
    {
        // Validate the incoming request data
        $request->validate([
            'phone' => 'required|unique:users,phone,' . $userId, // Ensure phone is unique for the user
            'balance' => 'required|numeric|min:0', // Validate balance as numeric and non-negative
        ]);

        // Find the user by ID
        $user = User::findOrFail($userId);

        // Update the user's phone and balance
        $user->phone = $request->input('phone');
        $user->balance = $request->input('balance');
        $user->save(); // Save the changes

        // Return a response with the updated user data
        return response()->json([
            'message' => 'User balance and phone updated successfully.',
            'user' => $user
        ]);
    }
}