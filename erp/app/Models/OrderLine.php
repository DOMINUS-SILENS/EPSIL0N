<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\HasCanonicalRouting;

class OrderLine extends Model
{
    use HasCanonicalRouting;

    protected $table = 'order_lines'; // Legacy
    protected $canonicalTable = 'order_lines'; // Canonical Target

    protected $fillable = [
        'order_id',
        'article_id',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'quantity' => 'decimal:3',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }
}
