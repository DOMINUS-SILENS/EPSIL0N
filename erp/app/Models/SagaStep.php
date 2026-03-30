<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SagaStep extends Model
{
    protected $fillable = [
        'saga_id', 
        'step_index', 
        'command_type', 
        'command_payload', 
        'compensation_type', 
        'compensation_payload', 
        'status', 
        'executed_at',
        'retry_count',
        'max_retries',
        'last_error'
    ];

    protected $casts = [
        'command_payload' => 'array',
        'compensation_payload' => 'array',
        'executed_at' => 'datetime',
    ];
}
