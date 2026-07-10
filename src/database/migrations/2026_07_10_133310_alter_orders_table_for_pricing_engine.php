<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropColumn('course_id');
            $table->morphs('purchasable');
            $table->dropColumn('amount');
            $table->unsignedInteger('amount_cents')->default(0);
            $table->timestamp('paid_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropMorphs('purchasable');
            $table->dropColumn('amount_cents');
            $table->dropColumn('paid_at');
            $table->decimal('amount', 10, 2)->default(0);
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
        });
    }
};
