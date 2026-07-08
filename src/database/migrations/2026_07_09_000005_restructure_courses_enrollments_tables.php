<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropUnique(['slug']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['slug', 'short_description', 'level', 'duration_minutes', 'language', 'is_published']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('thumbnail');
        });

        Schema::dropIfExists('enrollments');

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->integer('progress')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'course_id']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique();
            $table->string('short_description')->nullable();
            $table->string('level')->default('beginner');
            $table->integer('duration_minutes')->default(0);
            $table->string('language')->default('العربية');
            $table->boolean('is_published')->default(false);
        });
    }
};
