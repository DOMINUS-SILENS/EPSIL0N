<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\HasCanonicalRouting;

class Depot extends Model
{
    use HasFactory, HasCanonicalRouting;

    protected $legacyTable = 'depot'; // Legacy
    protected $canonicalTable = 'depots'; // Canonical Target

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $guarded = ['id'];

    protected $fillable = [
        'entreprise_id',
        'designation',
        'code',
        'address',
        'is_active',
    ];

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class, 'entreprise_id');
    }
}
