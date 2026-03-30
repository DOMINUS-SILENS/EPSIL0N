<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainEvent extends Model
{
    public const UPDATED_AT = null;
    public const CREATED_AT = 'recorded_at';

    protected $table = 'domain_events';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'event_time' => 'datetime',
        'recorded_at' => 'datetime',
    ];
}
