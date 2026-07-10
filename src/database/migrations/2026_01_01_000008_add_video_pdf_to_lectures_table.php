<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lectures', function (Blueprint $table) {
            $table->string('bunny_video_id')->nullable()->after('sort_order');
            $table->string('pdf_url')->nullable()->after('bunny_video_id');
        });
    }

    public function down(): void
    {
        Schema::table('lectures', function (Blueprint $table) {
            $table->dropColumn(['bunny_video_id', 'pdf_url']);
        });
    }
};
