<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('entity_type')->nullable();
            $table->string('entity_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_statistics');
        Schema::dropIfExists('student_activities');
    }
};
