<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1. Rename Primary Keys and Prefix Columns
        
        // Article Table
        if (Schema::hasTable('article')) {
            Schema::table('article', function (Blueprint $table) {
                if (Schema::hasColumn('article', 'article_id')) {
                    $table->renameColumn('article_id', 'id');
                }
                if (Schema::hasColumn('article', 'designation')) {
                    $table->renameColumn('designation', 'designation');
                }
                if (Schema::hasColumn('article', 'article_abreviation')) {
                    $table->renameColumn('article_abreviation', 'abreviation');
                }
                if (Schema::hasColumn('article', 'ean13')) {
                    $table->renameColumn('ean13', 'ean13');
                }
                if (Schema::hasColumn('article', 'barcode')) {
                    $table->renameColumn('barcode', 'bar_code');
                }
                if (Schema::hasColumn('article', 'article_qr_code')) {
                    $table->renameColumn('article_qr_code', 'qr_code');
                }
                
                // Convert Quantities to Decimal(15,3) using raw DB statement directly for precision mapping
            });
            
            DB::statement('ALTER TABLE article MODIFY quantite_stock DECIMAL(15,3)');
            DB::statement('ALTER TABLE article MODIFY quantite_min DECIMAL(15,3)');
            DB::statement('ALTER TABLE article MODIFY article_quantite_optimale DECIMAL(15,3)');
            DB::statement('ALTER TABLE article MODIFY article_quantite_theorique DECIMAL(15,3)');
            DB::statement('ALTER TABLE article MODIFY article_project_modele_quantite DECIMAL(15,3)');
            
            Schema::table('article', function (Blueprint $table) {
                if (Schema::hasColumn('article', 'quantite_stock')) $table->renameColumn('quantite_stock', 'quantite_stock');
                if (Schema::hasColumn('article', 'quantite_min')) $table->renameColumn('quantite_min', 'quantite_min');
                if (Schema::hasColumn('article', 'article_quantite_optimale')) $table->renameColumn('article_quantite_optimale', 'quantite_optimale');
                if (Schema::hasColumn('article', 'article_quantite_theorique')) $table->renameColumn('article_quantite_theorique', 'quantite_theorique');
            });
            
            // Cleanup obsolete columns
            Schema::table('article', function (Blueprint $table) {
                $obsolete = ['article_created_date', 'article_updated_date'];
                foreach ($obsolete as $col) {
                    if (Schema::hasColumn('article', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
        
        // Depot Table
        if (Schema::hasTable('depot')) {
            Schema::table('depot', function (Blueprint $table) {
                if (Schema::hasColumn('depot', 'depot_id')) {
                    $table->renameColumn('depot_id', 'id');
                }
                if (Schema::hasColumn('depot', 'designation')) {
                    $table->renameColumn('designation', 'designation');
                }
                if (Schema::hasColumn('depot', 'depot_adresse')) {
                    $table->renameColumn('depot_adresse', 'adresse');
                }
            });
        }

        // Entreprise Table
        if (Schema::hasTable('entreprise')) {
            Schema::table('entreprise', function (Blueprint $table) {
                if (Schema::hasColumn('entreprise', 'entreprise_id')) {
                    $table->renameColumn('entreprise_id', 'id');
                }
            });
        }
        
        // Balance Stock Table
        if (Schema::hasTable('balance_stock')) {
            DB::statement('ALTER TABLE balance_stock MODIFY quantite_new DECIMAL(15,3)');
            DB::statement('ALTER TABLE balance_stock MODIFY quantite_old DECIMAL(15,3)');
            DB::statement('ALTER TABLE balance_stock MODIFY quantite_entre DECIMAL(15,3)');
            DB::statement('ALTER TABLE balance_stock MODIFY quantite_retour DECIMAL(15,3)');
            DB::statement('ALTER TABLE balance_stock MODIFY quantite_sortie DECIMAL(15,3)');
            DB::statement('ALTER TABLE balance_stock MODIFY sorties DECIMAL(15,3)');
            DB::statement('ALTER TABLE balance_stock MODIFY quantite_physique DECIMAL(15,3)');
            DB::statement('ALTER TABLE balance_stock MODIFY quantite_theorique DECIMAL(15,3)');
            DB::statement('ALTER TABLE balance_stock MODIFY ecart_jour DECIMAL(15,3)');
        }
        
        // Order Lines Table
        if (Schema::hasTable('order_lines')) {
            DB::statement('ALTER TABLE order_lines MODIFY quantity DECIMAL(15,3)');
        }
    }

    public function down(): void
    {
        // Reverting this mass normalization is strongly discouraged.
    }
};
