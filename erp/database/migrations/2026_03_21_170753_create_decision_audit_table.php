<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('decision_audit')) {
            Schema::create('decision_audit', function (Blueprint $table) {
                $table->id();
                $table->string('decision_type'); // contract, intent, saga
                $table->json('context');
                $table->boolean('result');
                $table->string('correlation_id', 36);
                $table->timestamp('made_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('decision_audit');
    }
};
