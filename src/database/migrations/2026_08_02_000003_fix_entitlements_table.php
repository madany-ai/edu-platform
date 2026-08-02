<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entitlements', function (Blueprint $table) {
            $table->uuid('order_id')->nullable()->change();
            $table->unique(['student_id', 'lecture_id']);
        });
    }

    public function down(): void
    {
        Schema::table('entitlements', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'lecture_id']);
            $table->uuid('order_id')->nullable(false)->change();
        });
    }
};
