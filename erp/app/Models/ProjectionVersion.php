<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectionVersion extends Model
{
    protected $table = 'projection_versions';
    protected $primaryKey = 'projector_name';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    public $timestamps = true;
    public const CREATED_AT = null;
}
