<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governorates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('governorate_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->nullable();
            $table->timestamps();
        });

        Schema::create('grade_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('academic_tracks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('student_code')->unique()->nullable();
            $table->string('first_name')->default('');
            $table->string('second_name');
            $table->string('third_name');
            $table->string('last_name')->default('');
            $table->string('phone');
            $table->string('father_phone');
            $table->string('mother_phone');
            $table->string('guardian_job');
            $table->foreignId('governorate_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('grade_level_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_track_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('gender', ['male', 'female']);
            $table->date('birth_date');
            $table->string('profile_image')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
        Schema::dropIfExists('academic_tracks');
        Schema::dropIfExists('grade_levels');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('governorates');
    }
};
