<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['entreprise_id', 'sequence', 'previous_hash', 'row_hash', 'action', 'model', 'model_id', 'old_values', 'new_values', 'reason', 'trace_id', 'event_time', 'recorded_at'];

    protected $casts = ['old_values' => 'array', 'new_values' => 'array', 'event_time' => 'datetime', 'recorded_at' => 'datetime'];
}
