<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description');
            $table->string('thumbnail')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('status')->default('draft');
            $table->foreignUuid('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('course_sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('lectures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('section_id')->constrained('course_sections')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('duration')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('lecture_videos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lecture_id')->constrained()->cascadeOnDelete();
            $table->string('bunny_video_id');
            $table->integer('duration')->default(0);
            $table->timestamps();
        });

        Schema::create('lecture_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lecture_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('file_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecture_files');
        Schema::dropIfExists('lecture_videos');
        Schema::dropIfExists('lectures');
        Schema::dropIfExists('course_sections');
        Schema::dropIfExists('courses');
    }
};
