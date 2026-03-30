
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_outbox', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('domain_event_id');
            $table->string('integration_type', 50);
            $table->string('target', 255);
            $table->json('payload');
            $table->string('idempotency_key', 255);
            $table->enum('status', ['pending', 'processed'])->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['integration_type', 'idempotency_key']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_outbox');
    }
};
