<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSchema extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'event_type';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['event_type', 'schema', 'version', 'is_active'];

    protected $casts = ['schema' => 'array'];
}
