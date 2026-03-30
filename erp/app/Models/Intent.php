<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Intent extends Model
{
    protected $fillable = ['command_type', 'verifier_class', 'parameters', 'is_active'];

    protected $casts = ['parameters' => 'array'];
}
