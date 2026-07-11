<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('assistant_code')->nullable()->unique()->after('status');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->string('course_code')->nullable()->unique()->after('status');
        });

        // student_code already exists on students table
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropUnique(['course_code']);
            $table->dropColumn('course_code');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['assistant_code']);
            $table->dropColumn('assistant_code');
        });
    }
};
