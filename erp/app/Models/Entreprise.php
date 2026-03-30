<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\HasCanonicalRouting;

class Entreprise extends Model
{
    use HasCanonicalRouting;

    protected $legacyTable = 'entreprise'; // Legacy
    protected $canonicalTable = 'entreprises'; // Canonical Target

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $fillable = [
        'nom',
        'raison_sociale',
        'adresse',
        'telephone',
        'email',
    ];
}
