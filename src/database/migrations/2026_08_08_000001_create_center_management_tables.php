<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('semesters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignUuid('grade_level_id')->nullable()->constrained('grade_levels')->nullOnDelete();
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignUuid('grade_level_id')->constrained('grade_levels')->cascadeOnDelete();
            $table->string('name');
            $table->json('schedule')->nullable();
            $table->integer('capacity')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->foreignUuid('group_id')->nullable()->constrained('groups')->nullOnDelete();
        });

        Schema::create('academic_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('group_id')->constrained('groups')->cascadeOnDelete();
            $table->date('date');
            $table->string('topic');
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('status', ['present', 'absent', 'late', 'guest'])->default('present');
            $table->boolean('is_guest')->default(false);
            $table->foreignUuid('original_group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->timestamps();

            $table->unique(['session_id', 'student_id']);
        });

        Schema::create('center_exams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('total_marks', 8, 2);
            $table->date('date');
            $table->foreignUuid('semester_id')->nullable()->constrained('semesters')->nullOnDelete();
            $table->foreignUuid('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->foreignUuid('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('center_grades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('center_exam_id')->constrained('center_exams')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->decimal('score', 8, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['center_exam_id', 'student_id']);
        });

        Schema::create('student_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignUuid('from_group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->foreignUuid('to_group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamp('transferred_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('communication_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('date');
            $table->string('contact_method');
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_logs');
        Schema::dropIfExists('student_transfers');
        Schema::dropIfExists('center_grades');
        Schema::dropIfExists('center_exams');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('academic_sessions');

        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });

        Schema::dropIfExists('groups');
        Schema::dropIfExists('semesters');
        Schema::dropIfExists('academic_years');
    }
};
