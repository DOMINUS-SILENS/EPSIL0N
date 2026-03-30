<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE TABLE dashboard_sales (
                entreprise_id BIGINT UNSIGNED NOT NULL,
                date DATE NOT NULL,
                route_id BIGINT UNSIGNED NOT NULL,
                subtotal_amount DECIMAL(15,2) DEFAULT 0,
                total_amount DECIMAL(15,2) DEFAULT 0,
                nb_orders INT DEFAULT 0,
                nb_clients_visited INT DEFAULT 0,
                last_event_id BIGINT UNSIGNED,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                PRIMARY KEY (entreprise_id, date, route_id)
            ) PARTITION BY HASH(entreprise_id) PARTITIONS 16;
        ");

        DB::statement("
            CREATE TABLE dashboard_top_articles (
                entreprise_id BIGINT UNSIGNED NOT NULL,
                date DATE NOT NULL,
                article_id BIGINT UNSIGNED NOT NULL,
                quantity_sold DECIMAL(15,4) DEFAULT 0,
                amount_ht DECIMAL(15,2) DEFAULT 0,
                last_event_id BIGINT UNSIGNED,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                PRIMARY KEY (entreprise_id, date, article_id)
            ) PARTITION BY HASH(entreprise_id) PARTITIONS 16;
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_top_articles');
        Schema::dropIfExists('dashboard_sales');
    }
};
