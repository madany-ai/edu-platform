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
        Schema::table('enrollments', function (Blueprint $table) {
            $table->index('student_id');
            $table->index('course_id');
        });

        Schema::table('entitlements', function (Blueprint $table) {
            $table->index('student_id');
            $table->index('lecture_id');
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->index('student_id');
            $table->index('exam_id');
        });

        Schema::table('student_activities', function (Blueprint $table) {
            $table->index(['student_id', 'type']);
            $table->index(['entity_type', 'entity_id']);
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->index('attempt_id');
            $table->index('question_id');
        });

        Schema::table('lectures', function (Blueprint $table) {
            $table->index('section_id');
        });

        Schema::table('course_sections', function (Blueprint $table) {
            $table->index('course_id');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex(['student_id']);
            $table->dropIndex(['course_id']);
        });

        Schema::table('entitlements', function (Blueprint $table) {
            $table->dropIndex(['student_id']);
            $table->dropIndex(['lecture_id']);
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropIndex(['student_id']);
            $table->dropIndex(['exam_id']);
        });

        Schema::table('student_activities', function (Blueprint $table) {
            $table->dropIndex(['student_id', 'type']);
            $table->dropIndex(['entity_type', 'entity_id']);
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->dropIndex(['attempt_id']);
            $table->dropIndex(['question_id']);
        });

        Schema::table('lectures', function (Blueprint $table) {
            $table->dropIndex(['section_id']);
        });

        Schema::table('course_sections', function (Blueprint $table) {
            $table->dropIndex(['course_id']);
        });
    }
};
