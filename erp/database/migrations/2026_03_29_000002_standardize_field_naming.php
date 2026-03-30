<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Standardize field naming conventions across tables
 * 
 * Changes:
 * - Add entreprise_id as alias for entreprise_id on core tables
 * - Standardize status fields (active, is_active, status)
 * - Ensure consistent timestamp naming
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Article table - add entreprise_id alias for entreprise_id
        Schema::table('article', function (Blueprint $table) {
            if (!$this->columnExists('article', 'entreprise_id') && $this->columnExists('article', 'entreprise_id')) {
                $table->unsignedBigInteger('entreprise_id')
                    ->virtualAs('entreprise_id')
                    ->after('entreprise_id')
                    ->index('idx_art_company');
            }
            // Standardize active field naming
            if ($this->columnExists('article', 'active') && !$this->columnExists('article', 'is_active')) {
                $table->tinyInteger('is_active')->virtualAs('active')->after('active');
            }
        });

        // 2. Article Mouvement - add entreprise_id alias
        Schema::table('article_mouvement', function (Blueprint $table) {
            if (!$this->columnExists('article_mouvement', 'entreprise_id') && $this->columnExists('article_mouvement', 'entreprise_id')) {
                $table->unsignedBigInteger('entreprise_id')
                    ->virtualAs('entreprise_id')
                    ->after('entreprise_id')
                    ->index('idx_am_company');
            }
        });

        // 3. Depot - add entreprise_id alias and standardize active field
        Schema::table('depot', function (Blueprint $table) {
            if (!$this->columnExists('depot', 'entreprise_id') && $this->columnExists('depot', 'entreprise_id')) {
                $table->unsignedBigInteger('entreprise_id')
                    ->virtualAs('entreprise_id')
                    ->after('entreprise_id')
                    ->index('idx_dep_company');
            }
            // Standardize is_used to is_active
            if ($this->columnExists('depot', 'is_used') && !$this->columnExists('depot', 'is_active')) {
                $table->tinyInteger('is_active')->virtualAs('is_used')->after('is_used');
            }
        });

        // 4. Balance Stock - add entreprise_id (derived from article's company)
        // Note: This would need a trigger or application logic to populate
        Schema::table('balance_stock', function (Blueprint $table) {
            if (!$this->columnExists('balance_stock', 'entreprise_id')) {
                $table->unsignedBigInteger('entreprise_id')->nullable()->after('article_id')->index('idx_bs_company');
            }
        });

        // 5. Article Famille - add entreprise_id alias
        Schema::table('article_famille', function (Blueprint $table) {
            if (!$this->columnExists('article_famille', 'entreprise_id') && $this->columnExists('article_famille', 'entreprise_id')) {
                $table->unsignedBigInteger('entreprise_id')
                    ->virtualAs('entreprise_id')
                    ->after('entreprise_id')
                    ->index('idx_af_company');
            }
        });

        // 6. Article Marque - add entreprise_id alias  
        Schema::table('article_marque', function (Blueprint $table) {
            if (!$this->columnExists('article_marque', 'entreprise_id') && $this->columnExists('article_marque', 'entreprise_id')) {
                $table->unsignedBigInteger('entreprise_id')
                    ->virtualAs('entreprise_id')
                    ->after('entreprise_id')
                    ->index('idx_amrq_company');
            }
        });

        // 7. Article Unite - add entreprise_id alias
        Schema::table('article_unite', function (Blueprint $table) {
            if (!$this->columnExists('article_unite', 'entreprise_id') && $this->columnExists('article_unite', 'entreprise_id')) {
                $table->unsignedBigInteger('entreprise_id')
                    ->virtualAs('entreprise_id')
                    ->after('entreprise_id')
                    ->index('idx_au_company');
            }
        });

        // 8. Article Groupe Prix - add entreprise_id alias
        Schema::table('article_groupe_prix', function (Blueprint $table) {
            if (!$this->columnExists('article_groupe_prix', 'entreprise_id') && $this->columnExists('article_groupe_prix', 'entreprise_id')) {
                $table->unsignedBigInteger('entreprise_id')
                    ->virtualAs('entreprise_id')
                    ->after('entreprise_id')
                    ->index('idx_agp_company');
            }
        });

        // 9. Mouvement - add entreprise_id alias
        Schema::table('mouvement', function (Blueprint $table) {
            if (!$this->columnExists('mouvement', 'entreprise_id') && $this->columnExists('mouvement', 'entreprise_id')) {
                $table->unsignedBigInteger('entreprise_id')
                    ->virtualAs('entreprise_id')
                    ->after('entreprise_id')
                    ->index('idx_mvt_company');
            }
        });

        // 10. Mouvement Ligne - add entreprise_id alias
        Schema::table('mouvement_ligne', function (Blueprint $table) {
            if (!$this->columnExists('mouvement_ligne', 'entreprise_id') && $this->columnExists('mouvement_ligne', 'entreprise_id')) {
                $table->unsignedBigInteger('entreprise_id')
                    ->virtualAs('entreprise_id')
                    ->after('entreprise_id')
                    ->index('idx_ml_company');
            }
        });
    }

    public function down(): void
    {
        // Article
        Schema::table('article', function (Blueprint $table) {
            $this->dropColumnIfExists($table, 'entreprise_id');
            $this->dropColumnIfExists($table, 'is_active');
        });

        // Article Mouvement
        Schema::table('article_mouvement', function (Blueprint $table) {
            $this->dropColumnIfExists($table, 'entreprise_id');
        });

        // Depot
        Schema::table('depot', function (Blueprint $table) {
            $this->dropColumnIfExists($table, 'entreprise_id');
            $this->dropColumnIfExists($table, 'is_active');
        });

        // Balance Stock
        Schema::table('balance_stock', function (Blueprint $table) {
            $this->dropColumnIfExists($table, 'entreprise_id');
        });

        // Article Famille
        Schema::table('article_famille', function (Blueprint $table) {
            $this->dropColumnIfExists($table, 'entreprise_id');
        });

        // Article Marque
        Schema::table('article_marque', function (Blueprint $table) {
            $this->dropColumnIfExists($table, 'entreprise_id');
        });

        // Article Unite
        Schema::table('article_unite', function (Blueprint $table) {
            $this->dropColumnIfExists($table, 'entreprise_id');
        });

        // Article Groupe Prix
        Schema::table('article_groupe_prix', function (Blueprint $table) {
            $this->dropColumnIfExists($table, 'entreprise_id');
        });

        // Mouvement
        Schema::table('mouvement', function (Blueprint $table) {
            $this->dropColumnIfExists($table, 'entreprise_id');
        });

        // Mouvement Ligne
        Schema::table('mouvement_ligne', function (Blueprint $table) {
            $this->dropColumnIfExists($table, 'entreprise_id');
        });
    }

    /**
     * Check if column exists on table
     */
    private function columnExists(string $tableName, string $columnName): bool
    {
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'mysql') {
            $columns = \DB::select("SHOW COLUMNS FROM `{$tableName}` WHERE Field = ?", [$columnName]);
            return count($columns) > 0;
        }
        
        if ($driver === 'pgsql') {
            $columns = \DB::select(
                "SELECT column_name FROM information_schema.columns WHERE table_name = ? AND column_name = ?",
                [$tableName, $columnName]
            );
            return count($columns) > 0;
        }
        
        return false;
    }

    /**
     * Drop column if it exists
     */
    private function dropColumnIfExists(Blueprint $table, string $columnName): void
    {
        $tableName = $table->getTable();
        if ($this->columnExists($tableName, $columnName)) {
            $table->dropColumn($columnName);
        }
    }
};
