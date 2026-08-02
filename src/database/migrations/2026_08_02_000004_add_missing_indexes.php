<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->index('lecture_id');
            $table->unique(['lecture_id', 'is_assignment']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->index('exam_id');
        });

        Schema::table('choices', function (Blueprint $table) {
            $table->index('question_id');
        });

        Schema::table('questions_posts', function (Blueprint $table) {
            $table->index('lecture_id');
            $table->index('student_id');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('questions_posts', function (Blueprint $table) {
            $table->dropIndex(['student_id']);
            $table->dropIndex(['lecture_id']);
        });

        Schema::table('choices', function (Blueprint $table) {
            $table->dropIndex(['question_id']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['exam_id']);
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropUnique(['lecture_id', 'is_assignment']);
            $table->dropIndex(['lecture_id']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });
    }
};
