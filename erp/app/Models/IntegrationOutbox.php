<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationOutbox extends Model
{
    public $timestamps = false;

    protected $table = 'integration_outbox';

    protected $fillable = [
        'domain_event_id',
        'integration_type',
        'target',
        'payload',
        'idempotency_key',
        'status',        // <-- add this
        'attempts',
        'last_error',
    ];

    protected $casts = ['payload' => 'array'];
}
