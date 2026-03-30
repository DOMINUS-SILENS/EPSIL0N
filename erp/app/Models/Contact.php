<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $table = 'contacts';
    protected $primaryKey = 'id'; // Or contact_id if used as PK, but projector uses 'id' for auto-inc and contact_id for business key
    
    protected $fillable = [
        'entreprise_id',
        'contact_id',
        'contact_nom',
        'contact_prenom',
        'entreprise_id',
        'contact_raison_sociale',
        'montant_max_credit',
        'montant_credit_en_cours',
        'last_event_id'
    ];

    protected $casts = [
        'montant_max_credit' => 'float',
        'montant_credit_en_cours' => 'float',
        'last_event_id' => 'integer',
    ];
}
