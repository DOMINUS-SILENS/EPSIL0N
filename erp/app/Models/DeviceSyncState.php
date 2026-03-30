<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceSyncState extends Model
{
    use HasFactory;

    protected $table = 'device_sync_states';

    protected $fillable = [
        'entreprise_id',
        'device_id',
        'entity_type',
        'last_sync_at',
        'last_sync_sequence',
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
        'last_sync_sequence' => 'integer',
    ];
}
