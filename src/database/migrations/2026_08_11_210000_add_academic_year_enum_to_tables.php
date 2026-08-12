<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Students table
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'grade_level_id')) {
                $table->dropForeign(['grade_level_id']);
                $table->dropColumn('grade_level_id');
            }
            if (Schema::hasColumn('students', 'academic_track_id')) {
                $table->dropForeign(['academic_track_id']);
                $table->dropColumn('academic_track_id');
            }
            $table->string('academic_year')->default('sec_3')->after('guardian_job');
        });

        // 2. Courses table
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'academic_year')) {
                $table->string('academic_year')->default('sec_3')->after('status');
            }
        });

        // 3. Products table
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'academic_year')) {
                $table->string('academic_year')->default('sec_3')->after('is_active');
            }
        });

        // 4. Bundles table (if exists)
        if (Schema::hasTable('bundles')) {
            Schema::table('bundles', function (Blueprint $table) {
                if (!Schema::hasColumn('bundles', 'academic_year')) {
                    $table->string('academic_year')->default('sec_3');
                }
            });
        }

        // 5. Center Groups (if exists)
        if (Schema::hasTable('center_groups')) {
            Schema::table('center_groups', function (Blueprint $table) {
                if (Schema::hasColumn('center_groups', 'grade_level_id')) {
                    $table->dropForeign(['grade_level_id']);
                    $table->dropColumn('grade_level_id');
                }
                if (Schema::hasColumn('center_groups', 'academic_year_id')) {
                    $table->dropForeign(['academic_year_id']);
                    $table->dropColumn('academic_year_id');
                }
                if (!Schema::hasColumn('center_groups', 'academic_year')) {
                    $table->string('academic_year')->default('sec_3');
                }
            });
        }

        // 6. Center Exams (if exists)
        if (Schema::hasTable('center_exams')) {
            Schema::table('center_exams', function (Blueprint $table) {
                if (Schema::hasColumn('center_exams', 'grade_level_id')) {
                    $table->dropForeign(['grade_level_id']);
                    $table->dropColumn('grade_level_id');
                }
                if (Schema::hasColumn('center_exams', 'academic_year_id')) {
                    $table->dropForeign(['academic_year_id']);
                    $table->dropColumn('academic_year_id');
                }
                if (!Schema::hasColumn('center_exams', 'academic_year')) {
                    $table->string('academic_year')->default('sec_3');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('academic_year');
        });
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('academic_year');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('academic_year');
        });
        if (Schema::hasTable('bundles')) {
            Schema::table('bundles', function (Blueprint $table) {
                $table->dropColumn('academic_year');
            });
        }
    }
};
