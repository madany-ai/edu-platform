<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lectures', function (Blueprint $table) {
            $table->uuid('section_id')->nullable()->change();
            $table->foreignUuid('instructor_id')->nullable()->after('section_id')->constrained('users')->nullOnDelete();
            $table->string('status')->default('published')->after('sort_order');
            $table->decimal('price', 10, 2)->default(0)->after('status');
            $table->string('thumbnail')->nullable()->after('price');
            $table->index('instructor_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('lectures', function (Blueprint $table) {
            $table->dropForeign(['instructor_id']);
            $table->dropIndex(['instructor_id']);
            $table->dropIndex(['status']);
            $table->dropColumn(['instructor_id', 'status', 'price', 'thumbnail']);
            $table->uuid('section_id')->nullable(false)->change();
        });
    }
};
