<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionType extends Model
{
    protected $fillable = [
        'name', 'description'
    ];

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
     * Get the transactions associated with the transaction type.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
