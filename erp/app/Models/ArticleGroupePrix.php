<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleGroupePrix extends Model
{
    protected $table = 'article_groupe_prix';

    protected $primaryKey = 'article_groupe_prix_id';

    public $incrementing = true;

    protected $fillable = [
        'article_groupe_prix_id',
        'entreprise_id',
        'article_groupe_prix_designation',
        'article_groupe_prix_pourcentage',
        'column_name',
    ];

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class, 'entreprise_id', 'entreprise_id');
    }
}
