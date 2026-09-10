<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    //Orden a la que pertenece el detalle
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    //Producto asociado al detalle
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
