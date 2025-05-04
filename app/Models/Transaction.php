<?php

// app/Models/Transaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'transaction_type_id', 'amount', 'status', 'reference'
    ];

    // Transaction Types
    public const TYPES = [
        1 => 'Account Deposit',
        2 => 'Airtime Purchase',
        3 => 'Data Bundle Purchase',
        4 => 'Cable TV Payment',
        5 => 'Electricity Bill Payment',
    ];

    public const CATEGORY = [
        1 => 'Deposit',
        2 => 'Airtime',
        3 => 'Data',
        4 => 'Cable TV',
        5 => 'Electricity',
    ];

    /**
     * Get the user that owns the transaction.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transaction type associated with the transaction.
     */
    public function transactionType()
    {
        return $this->belongsTo(TransactionType::class);
    }
}

