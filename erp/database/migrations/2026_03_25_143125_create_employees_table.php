<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id')->index();
            $table->unsignedBigInteger('employee_id')->index();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('job_title')->nullable();
            $table->date('hire_date')->nullable();
            $table->decimal('salary', 15, 2)->default(0);
            $table->unsignedBigInteger('last_event_id')->nullable();
            $table->timestamps();

            $table->unique(['entreprise_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
