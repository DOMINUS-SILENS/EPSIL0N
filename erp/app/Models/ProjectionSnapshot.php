<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectionSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = ['projector_name', 'aggregate_id', 'snapshot', 'last_event_id'];

    protected $casts = ['snapshot' => 'array'];
}
