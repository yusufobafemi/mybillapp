<?php

// app/Models/TopUp.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'amount', 'gateway', 'transaction_reference', 'status'
    ];

    /**
     * Get the user that owns the top-up.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
