<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('canonical_projection_events')) {
            Schema::create('canonical_projection_events', function (Blueprint $table) {
                $table->unsignedBigInteger('event_id');
                $table->string('projector', 128);
                $table->unsignedBigInteger('aggregate_id')->nullable();
                $table->timestamp('processed_at')->useCurrent();

                $table->primary(['event_id', 'projector']);
                $table->index(['projector', 'processed_at']);
                $table->index('aggregate_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('canonical_projection_events');
    }
};
