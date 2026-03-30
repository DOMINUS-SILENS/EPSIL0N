<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Anomaly extends Model
{
    public $timestamps = false;

    protected $fillable = ['type', 'context', 'detected_at', 'resolved'];

    protected $casts = ['context' => 'array', 'detected_at' => 'datetime'];
}
