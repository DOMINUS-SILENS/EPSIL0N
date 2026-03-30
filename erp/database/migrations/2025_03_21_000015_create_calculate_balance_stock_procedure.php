<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop procedure if it exists
        DB::unprepared('DROP PROCEDURE IF EXISTS calcule_balance_stock');

        DB::unprepared('
            CREATE PROCEDURE calcule_balance_stock()
            BEGIN
                -- Insert entries for new dates (for all articles that had movements)
                INSERT INTO balance_stock (article_id, date_day, quantite_new, quantite_old, quantite_entre, quantite_retour, quantite_sortie, sorties)
                SELECT
                    m.article_id,
                    m.date_day,
                    COALESCE(m.quantite_new1, 0),
                    COALESCE(m.quantite_old1, 0),
                    COALESCE(m.quantite_entre1, 0),
                    COALESCE(m.quantite_retour1, 0),
                    COALESCE(m.quantite_sortie1, 0),
                    COALESCE(m.sorties1, 0)
                FROM (
                    SELECT
                        ml.article_id,
                        inv.mouvement_date_day AS date_day,
                        SUM(CASE WHEN ml.relation_mouvement_ligne_id IS NULL THEN ml.article_quantite END) AS quantite_new1,
                        SUM(CASE WHEN ml.relation_mouvement_ligne_id IS NOT NULL THEN ml.article_quantite END) AS quantite_old1,
                        0 AS quantite_entre1,
                        0 AS quantite_retour1,
                        0 AS quantite_sortie1,
                        0 AS sorties1
                    FROM (
                        SELECT
                            ml.article_id,
                            MAX(ml.mouvement_ligne_id) AS mouvement_ligne_id,
                            m.mouvement_date_day
                        FROM mouvement_ligne ml
                        INNER JOIN mouvement m ON m.mouvement_id = ml.mouvement_id
                        WHERE m.mouvement_type_id = 35
                          AND m.is_stocked = 1
                          AND ml.relation_mouvement_ligne_id IS NULL
                        GROUP BY m.mouvement_date_day, ml.article_id
                    ) inv
                    INNER JOIN mouvement_ligne ml ON ml.mouvement_ligne_id = inv.mouvement_ligne_id
                    GROUP BY ml.article_id, inv.mouvement_date_day
                ) m
                ON DUPLICATE KEY UPDATE
                    quantite_new = VALUES(quantite_new),
                    quantite_old = VALUES(quantite_old),
                    quantite_entre = VALUES(quantite_entre),
                    quantite_retour = VALUES(quantite_retour),
                    quantite_sortie = VALUES(quantite_sortie),
                    sorties = VALUES(sorties);

                -- Insert entries for stock entries (e.g., receptions)
                INSERT INTO balance_stock (article_id, date_day, quantite_entre)
                SELECT
                    am.article_id,
                    m.mouvement_date_day,
                    SUM(am.article_mouvement_quantite)
                FROM mouvement m
                INNER JOIN article_mouvement am ON m.mouvement_id = am.mouvement_id_aquisition
                WHERE m.mouvement_type_id = 45
                  AND am.stock_operation_type = 1
                GROUP BY am.article_id, m.mouvement_date_day
                ON DUPLICATE KEY UPDATE quantite_entre = VALUES(quantite_entre);

                -- Insert entries for returns (e.g., from clients)
                INSERT INTO balance_stock (article_id, date_day, quantite_retour)
                SELECT
                    am.article_id,
                    ms.date_depart,
                    SUM(am.article_mouvement_quantite)
                FROM article_mouvement am
                INNER JOIN mouvement m ON m.mouvement_id = am.mouvement_id_aquisition
                INNER JOIN mission ms ON ms.mission_id = m.mission_id
                WHERE m.mouvement_type_id = 51
                  AND m.entrepot_destination = 1
                GROUP BY am.article_id, ms.date_depart
                ON DUPLICATE KEY UPDATE quantite_retour = VALUES(quantite_retour);

                -- Insert entries for outbound movements (e.g., shipments)
                INSERT INTO balance_stock (article_id, date_day, quantite_sortie)
                SELECT
                    am.article_id,
                    ms.date_depart,
                    SUM(am.article_mouvement_quantite)
                FROM article_mouvement am
                INNER JOIN mouvement m ON m.mouvement_id = am.mouvement_id_aquisition
                INNER JOIN mission ms ON ms.mission_id = m.mission_id
                WHERE m.mouvement_type_id = 51
                  AND m.entrepot_source = 6
                GROUP BY am.article_id, ms.date_depart
                ON DUPLICATE KEY UPDATE quantite_sortie = VALUES(quantite_sortie);

                -- Insert entries for sales (type 8, 10, 43, 49, 54, 55, 61, 56)
                INSERT INTO balance_stock (article_id, date_day, sorties)
                SELECT
                    am.article_id,
                    m.mouvement_date_day,
                    SUM(am.article_mouvement_quantite)
                FROM mouvement m
                INNER JOIN article_mouvement am ON m.mouvement_id = am.mouvement_id_destockage
                WHERE m.is_stocked = 1
                  AND m.mouvement_type_id IN (8,10,43,49,54,55,61,56)
                  AND am.stock_operation_type = -1
                GROUP BY am.article_id, m.mouvement_date_day
                ON DUPLICATE KEY UPDATE sorties = VALUES(sorties);

                -- Update ecart_jour
                UPDATE balance_stock bs
                SET bs.ecart_jour = COALESCE(bs.quantite_new,0)
                                   + COALESCE(bs.quantite_entre,0)
                                   + COALESCE(bs.quantite_retour,0)
                                   - COALESCE(bs.quantite_sortie,0)
                                   - COALESCE(bs.sorties,0)
                                   - COALESCE(bs.quantite_physique,0);
            END
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS calcule_balance_stock');
    }
};
