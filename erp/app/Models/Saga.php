<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Saga extends Model
{
    protected $fillable = ['saga_type', 'saga_id', 'state', 'context', 'current_step', 'version'];

    protected $casts = ['context' => 'array'];
}
