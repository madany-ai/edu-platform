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
        Schema::table('lecture_videos', function (Blueprint $table) {
            $table->string('bunny_video_id')->nullable()->change();
            $table->string('original_filename')->nullable();
            $table->string('video_path')->nullable();
            $table->string('encryption_key', 64)->nullable();
            $table->string('status')->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lecture_videos', function (Blueprint $table) {
            $table->string('bunny_video_id')->nullable(false)->change();
            $table->dropColumn(['original_filename', 'video_path', 'encryption_key', 'status']);
        });
    }
};
