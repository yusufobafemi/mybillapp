<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth; // Add this at the top

use App\Models\Transaction;
use App\Models\Activity;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */


    public function index()
    {
        $user = Auth::user();

        // Get all transactions for the logged-in user
        $transactions = Transaction::where('user_id', $user->id)
        ->where('transaction_type_id', '!=', 1)
        ->latest()
        ->paginate(10);

        $activities = Activity::where('user_id', $user->id)
        ->orderBy('occurred_at', 'desc')
        ->take(10)
        ->get();

        // Pass them to the view
        return view('dashboard', compact('user', 'transactions', 'activities'));
    }
}
