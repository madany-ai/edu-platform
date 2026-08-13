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
        Schema::table('groups', function (Blueprint $table) {
            if (Schema::hasColumn('groups', 'grade_level_id')) {
                $table->dropForeign(['grade_level_id']);
                $table->dropColumn('grade_level_id');
            }
            if (!Schema::hasColumn('groups', 'academic_year')) {
                $table->string('academic_year')->default('sec_3')->after('academic_year_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            if (Schema::hasColumn('groups', 'academic_year')) {
                $table->dropColumn('academic_year');
            }
            if (!Schema::hasColumn('groups', 'grade_level_id')) {
                $table->foreignUuid('grade_level_id')->nullable()->constrained('grade_levels')->cascadeOnDelete();
            }
        });
    }
};
