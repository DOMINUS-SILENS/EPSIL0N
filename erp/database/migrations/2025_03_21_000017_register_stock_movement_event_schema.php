// 2025_03_21_000017_register_stock_movement_event_schema.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create event_schemas table if it doesn't exist
        if (! Schema::hasTable('event_schemas')) {
            Schema::create('event_schemas', function (Blueprint $table) {
                $table->id();
                $table->string('event_type', 100)->unique();
                $table->json('schema');
                $table->json('compatibility_rules')->nullable();
                $table->unsignedInteger('version')->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // Now insert the schema
        DB::table('event_schemas')->updateOrInsert(
            ['event_type' => 'stock.movement.created'],
            [
                'schema' => json_encode([
                    'type' => 'object',
                    'properties' => [
                        'article_id' => ['type' => 'integer'],
                        'warehouse_id' => ['type' => 'integer'],
                        'quantity' => ['type' => 'number'],
                        'type' => ['type' => 'integer'],
                        'movement_id' => ['type' => 'integer'],
                        'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    ],
                    'required' => ['article_id', 'warehouse_id', 'quantity', 'type', 'movement_id', 'created_at'],
                ]),
                'version' => 1,
                'is_active' => true,
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('event_schemas')->where('event_type', 'stock.movement.created')->delete();
        // Do NOT drop the table here; it might be shared with other events
    }
};
