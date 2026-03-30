<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\HasCanonicalRouting;

class Customer extends Model
{
    use HasFactory, HasCanonicalRouting;

    protected $legacyTable = 'customers'; // Legacy
    protected $canonicalTable = 'customers'; // Canonical Target

    protected $fillable = [
        'entreprise_id',
        'name',
        'phone',
        'email',
        'credit_limit',
        'is_active',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
