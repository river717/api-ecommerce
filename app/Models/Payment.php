<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'stripe_payment_intent_id',
        'amount',
        'currency',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    //Orden asociada al pago
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
