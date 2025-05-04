<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionType extends Model
{
    protected $fillable = [
        'name', 'description'
    ];

    /**
     * Get the transactions associated with the transaction type.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
