<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleUniteArticleGroupePrix extends Model
{
    protected $table = 'article_unite_article_groupe_prix';

    protected $primaryKey = null;

    public $incrementing = false;

    protected $fillable = [
        'article_id',
        'article_unite_id',
        'article_groupe_prix_id',
        'prix_vente_ht',
        'prix_pourcentage',
        'date_debut',
        'date_fin',
        'last_updated_by_id',
    ];
}
