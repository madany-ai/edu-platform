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
        Schema::table('exams', function (Blueprint $table) {
            $table->boolean('is_assignment')->default(false)->after('title');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('question');
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->decimal('score', 5, 2)->nullable()->after('answer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->dropColumn('score');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('is_assignment');
        });
    }
};
