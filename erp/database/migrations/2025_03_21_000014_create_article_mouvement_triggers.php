<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration 
{
    public function up(): void
    {
        DB::unprepared('
            CREATE TRIGGER after_article_mouvement_insert
            AFTER INSERT ON article_mouvement
            FOR EACH ROW
            BEGIN
                IF NEW.stock_operation_type = 1 AND NEW.depot_id_destination IS NOT NULL THEN
                    INSERT INTO article_unite_depot (article_id, unite_id, depot_id, quantite)
                    VALUES (NEW.article_id, NEW.article_mouvement_unite_id, NEW.depot_id_destination, NEW.article_mouvement_quantite_restante)
                    ON DUPLICATE KEY UPDATE quantite = quantite + NEW.article_mouvement_quantite_restante;
                ELSEIF NEW.stock_operation_type = -1 AND NEW.depot_id_source IS NOT NULL THEN
                    UPDATE article_unite_depot
                    SET quantite = quantite - NEW.article_mouvement_quantite_restante
                    WHERE article_id = NEW.article_id
                      AND unite_id = NEW.article_mouvement_unite_id
                      AND depot_id = NEW.depot_id_source;
                END IF;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER after_article_mouvement_update
            AFTER UPDATE ON article_mouvement
            FOR EACH ROW
            BEGIN
                IF OLD.stock_operation_type != NEW.stock_operation_type
                   OR OLD.article_mouvement_quantite_restante != NEW.article_mouvement_quantite_restante
                   OR OLD.depot_id_destination != NEW.depot_id_destination
                   OR OLD.depot_id_source != NEW.depot_id_source THEN
                    -- Revert old movement
                    IF OLD.stock_operation_type = 1 AND OLD.depot_id_destination IS NOT NULL THEN
                        UPDATE article_unite_depot
                        SET quantite = quantite - OLD.article_mouvement_quantite_restante
                        WHERE article_id = OLD.article_id
                          AND unite_id = OLD.article_mouvement_unite_id
                          AND depot_id = OLD.depot_id_destination;
                    ELSEIF OLD.stock_operation_type = -1 AND OLD.depot_id_source IS NOT NULL THEN
                        UPDATE article_unite_depot
                        SET quantite = quantite + OLD.article_mouvement_quantite_restante
                        WHERE article_id = OLD.article_id
                          AND unite_id = OLD.article_mouvement_unite_id
                          AND depot_id = OLD.depot_id_source;
                    END IF;
                    -- Apply new movement
                    IF NEW.stock_operation_type = 1 AND NEW.depot_id_destination IS NOT NULL THEN
                        INSERT INTO article_unite_depot (article_id, unite_id, depot_id, quantite)
                        VALUES (NEW.article_id, NEW.article_mouvement_unite_id, NEW.depot_id_destination, NEW.article_mouvement_quantite_restante)
                        ON DUPLICATE KEY UPDATE quantite = quantite + NEW.article_mouvement_quantite_restante;
                    ELSEIF NEW.stock_operation_type = -1 AND NEW.depot_id_source IS NOT NULL THEN
                        UPDATE article_unite_depot
                        SET quantite = quantite - NEW.article_mouvement_quantite_restante
                        WHERE article_id = NEW.article_id
                          AND unite_id = NEW.article_mouvement_unite_id
                          AND depot_id = NEW.depot_id_source;
                    END IF;
                END IF;
            END
        ');

        DB::unprepared('
            CREATE TRIGGER after_article_mouvement_delete
            AFTER DELETE ON article_mouvement
            FOR EACH ROW
            BEGIN
                IF OLD.stock_operation_type = 1 AND OLD.depot_id_destination IS NOT NULL THEN
                    UPDATE article_unite_depot
                    SET quantite = quantite - OLD.article_mouvement_quantite_restante
                    WHERE article_id = OLD.article_id
                      AND unite_id = OLD.article_mouvement_unite_id
                      AND depot_id = OLD.depot_id_destination;
                ELSEIF OLD.stock_operation_type = -1 AND OLD.depot_id_source IS NOT NULL THEN
                    UPDATE article_unite_depot
                    SET quantite = quantite + OLD.article_mouvement_quantite_restante
                    WHERE article_id = OLD.article_id
                      AND unite_id = OLD.article_mouvement_unite_id
                      AND depot_id = OLD.depot_id_source;
                END IF;
            END
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS after_article_mouvement_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS after_article_mouvement_update');
        DB::unprepared('DROP TRIGGER IF EXISTS after_article_mouvement_delete');
    }
};
