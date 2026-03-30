<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleMarque extends Model
{
    protected $table = 'article_marque';

    protected $primaryKey = 'article_marque_id';

    public $incrementing = true;

    protected $fillable = [
        'article_marque_id',
        'entreprise_id',
        'article_marque_designation',
        'article_marque_created_by',
        'article_marque_created_date',
        'article_famille',
        'ressource_icon',
    ];
}
