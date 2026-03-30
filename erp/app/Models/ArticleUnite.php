<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleUnite extends Model
{
    protected $table = 'article_unite';

    protected $primaryKey = 'article_unite_id';

    public $incrementing = true;

    protected $fillable = [
        'article_unite_id',
        'article_id',
        'barcode',
        'article_qr_code',
        'article_poids',
        'article_volume',
        'article_longueur',
        'article_largeur',
        'article_hauteur',
        'article_prix_revient',
        'article_prix_achat_moyen',
        'article_prix_vente',
        'article_prix_min_autorise',
        'article_prix_online_show',
        'article_prix_online_prix',
        'is_article_prix_change_autorised',
        'article_taux_fidelite',
        'article_montant_fidelite',
        'active',
        'is_default',
        'article_unite_quantite',
        'artilce_unite_quantite_min',
        'artilce_unite_quantite_in_use',
        'quantite_per_unit',
        'article_unite_quantite_virtuel',
        'bouteille_id',
        'caisse_id',
        'bouteille_quantite',
        'caisse_quantite',
    ];

    protected $casts = [
        'active' => 'boolean',
        'is_default' => 'boolean',
        'is_article_prix_change_autorised' => 'boolean',
        'article_prix_online_show' => 'boolean',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class , 'article_id');
    }
}
