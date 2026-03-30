<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockReservation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'order_id',
        'qty',
        'expires_at',
        'status',        // <-- add
        'sequence',
    ];

    protected $casts = ['expires_at' => 'datetime'];
}
