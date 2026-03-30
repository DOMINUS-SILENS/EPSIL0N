
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aggregate_sequences', function (Blueprint $table) {
            $table->string('aggregate_type', 100);
            $table->unsignedBigInteger('aggregate_id');
            $table->unsignedBigInteger('seq')->default(0);
            $table->primary(['aggregate_type', 'aggregate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aggregate_sequences');
    }
};
