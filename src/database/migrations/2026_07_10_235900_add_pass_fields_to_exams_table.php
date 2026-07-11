<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->integer('pass_percentage')->default(50)->after('sort_order');
            $table->boolean('is_blocking')->default(true)->after('pass_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['pass_percentage', 'is_blocking']);
        });
    }
};
