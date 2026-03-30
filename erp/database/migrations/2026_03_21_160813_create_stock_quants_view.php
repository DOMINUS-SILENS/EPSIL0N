<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS stock_quants');
        DB::statement("
            CREATE VIEW stock_quants AS
            SELECT
                product_id,
                warehouse_id,
                NULL AS lot_id,
                COALESCE(SUM(CASE WHEN type IN ('in', 'adjustment_in') THEN qty WHEN type IN ('out', 'adjustment_out') THEN -qty END), 0) AS qty
            FROM stock_moves
            GROUP BY product_id, warehouse_id
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS stock_quants');
    }
};
