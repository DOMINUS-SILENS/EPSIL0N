<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleMovement extends Model
{
    protected $table = 'article_mouvement';

    protected $primaryKey = 'article_mouvement_id';

    public $incrementing = true;

    protected $fillable = [
        'article_mouvement_id',
        'entreprise_id',
        'founisseur_id',
        'article_id',
        'article_reference_id',
        'barcode',
        'article_qr_code',
        'article_mouvement_date',
        'article_mouvement_date_day',
        'article_production_date',
        'article_production_date_day',
        'article_expiration_date',
        'article_expiration_date_day',
        'article_etat_id',
        'article_serial_number',
        'depot_id_source',
        'depot_id_destination',
        'rangement_id',
        'rangement_path',
        'article_mouvement_unite_id',
        'article_mouvement_quantite',
        'article_mouvement_quantite_retour',
        'article_mouvement_vente_operation_type_id',
        'article_mouvement_quantite_restante',
        'article_mouvement_quantite_totale',
        'article_mouvement_epuise',
        'article_mouvement_operation_type_id',
        'article_mouvement_created_by',
        'article_mouvement_created_date',
        'article_mouvement_created_date_day',
        'article_mouvement_id_destoquage',
        'article_mouvement_lot_debut',
        'article_mouvement_lot_fin',
        'article_mouvement_ordre',
        'mouvement_id_aquisition',
        'mouvement_ligne_id_aquisition',
        'mouvement_ligne_aquisition_date',
        'mouvement_ligne_aquisition_date_day',
        'mouvement_id_destockage',
        'mouvement_ligne_id_destockage',
        'montant_achat_unitaire',
        'montant_achat_total',
        'montant_achat_monnaie',
        'cout_commande',
        'cout_stockage',
        'cout_emballage',
        'cout_livraison',
        'validated_stock_by',
        'validated_stock_date',
        'validated_stock_date_day',
        'is_packaged',
        'package_type_id',
        'archived',
        'ressource',
        'ressource_id',
        'ressource_magasinage_by',
        'article_mouvement_stock_entree_date',
        'article_mouvement_stock_entree_date_day',
        'article_mouvement_stock_sortie_date',
        'article_mouvement_stock_sortie_date_day',
        'article_mouvement_stock_sortie',
        'lang_id',
        'article_mouvement_designation_lang_text_id',
        'article_mouvement_show_quantity',
        'article_mouvement_show_supplier',
        'article_mouvement_hidden',
        'article_mouvement_weight',
        'article_mouvement_volume',
        'logistique_tracking_number',
        'logistique_next_depot_id',
        'logistique_final_depot_id',
        'logistique_is_delivered',
        'logistique_deliver_validated_customer',
        'logistique_deliver_validated_date',
        'logistique_deliver_validated_date_day',
        'logistique_deliver_validated_signature',
        'logistique_customer_notified',
        'comptabilite_type',
        'entrepot_source',
        'entrepot_destination',
        'stock_operation_type',
        'montant_achat_unit_pondere',
        'montant_achat_valeur_total_stock',
        'montant_vente_unitaire',
        'montant_vente_total',
        'article_mouvement_couleur',
        'article_mouvement_taille',
        'article_mouvement_version',
        'article_mouvement_commentaire',
        'relation_article_mouvement_id',
        'package_cost',
        'relation_etat_id',
        'sync_id',
        'sync_data',
        'unite_id',
    ];

    protected $casts = [
        'stock_operation_type' => 'integer',
        'article_mouvement_quantite' => 'integer',
        'article_mouvement_quantite_restante' => 'integer',
        'article_mouvement_quantite_totale' => 'integer',
        'is_packaged' => 'boolean',
        'archived' => 'boolean',
        'ressource' => 'boolean',
        'logistique_is_delivered' => 'boolean',
        'logistique_deliver_validated_customer' => 'boolean',
        'logistique_customer_notified' => 'boolean',
        'article_mouvement_show_quantity' => 'boolean',
        'article_mouvement_show_supplier' => 'boolean',
        'article_mouvement_hidden' => 'boolean',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    public function sourceDepot()
    {
        return $this->belongsTo(Depot::class, 'depot_id_source');
    }

    public function destinationDepot()
    {
        return $this->belongsTo(Depot::class, 'depot_id_destination');
    }
}
