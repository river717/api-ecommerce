<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'total',
    ];

    protected $casts = [
        'total' => 'decimal:2',
    ];

    //Cliente que realizó la orden
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    //Productos de la orden
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    //Pagos asociados a la orden
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
