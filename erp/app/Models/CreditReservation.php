<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditReservation extends Model
{
    public $timestamps = false;

    protected $fillable = ['entreprise_id', 'customer_id', 'order_id', 'amount', 'expires_at', 'status', 'sequence'];

    protected $casts = ['expires_at' => 'datetime'];
}
