<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainOutbox extends Model
{
    public $timestamps = false;
    protected $table = 'domain_outbox';

    protected $guarded = [];

    protected $casts = [
        'next_retry_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * Relationship to the actual immutable domain event.
     */
    public function event()
    {
        return $this->belongsTo(DomainEvent::class, 'event_id');
    }

    /**
     * Delegate event_type to the related event model.
     */
    public function getEventTypeAttribute(): string
    {
        return $this->event->event_type;
    }

    /**
     * Delegate payload to the related event model.
     */
    public function getPayloadAttribute(): array
    {
        return is_string($this->event->payload) ? json_decode($this->event->payload, true) : $this->event->payload;
    }
}
