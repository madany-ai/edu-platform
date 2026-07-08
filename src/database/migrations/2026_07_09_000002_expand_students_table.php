<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('student_code')->unique()->nullable()->after('user_id');
            $table->string('first_name')->default('')->after('student_code');
            $table->string('second_name')->nullable()->after('first_name');
            $table->string('third_name')->nullable()->after('second_name');
            $table->string('last_name')->default('')->after('third_name');
            $table->string('father_phone')->nullable()->after('phone');
            $table->string('mother_phone')->nullable()->after('father_phone');
            $table->string('guardian_job')->nullable()->after('mother_phone');
            $table->unsignedBigInteger('governorate_id')->nullable()->after('guardian_job');
            $table->unsignedBigInteger('city_id')->nullable()->after('governorate_id');
            $table->unsignedBigInteger('school_id')->nullable()->after('city_id');
            $table->unsignedBigInteger('grade_level_id')->nullable()->after('school_id');
            $table->unsignedBigInteger('academic_track_id')->nullable()->after('grade_level_id');
            $table->enum('gender', ['male', 'female'])->nullable()->after('academic_track_id');
            $table->date('birth_date')->nullable()->after('gender');
            $table->string('profile_image')->nullable()->after('birth_date');
            $table->boolean('is_verified')->default(false)->after('profile_image');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'student_code', 'first_name', 'second_name', 'third_name', 'last_name',
                'father_phone', 'mother_phone', 'guardian_job',
                'governorate_id', 'city_id', 'school_id', 'grade_level_id', 'academic_track_id',
                'gender', 'birth_date', 'profile_image', 'is_verified',
            ]);
        });
    }
};
