<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $fillable = ['name', 'description', 'predicate_class', 'parameters', 'is_active'];

    protected $casts = ['parameters' => 'array'];
}
