<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleFamille extends Model
{
    protected $table = 'article_famille';

    protected $primaryKey = 'article_famille_id';

    public $incrementing = true;

    protected $fillable = [
        'article_famille_id',
        'entreprise_id',
        'article_famille_designation',
        'article_famille_parent_id',
        'article_famille_parent_left',
        'article_famille_parent_right',
        'article_famille_online_show',
        'article_famille_online_description',
        'active',
        'famille_codification',
        'mouvement_type_groupe_id',
        'ordre',
        'nature_id',
    ];
}
