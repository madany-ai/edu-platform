<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (!Schema::hasIndex('students', 'students_user_id_index')) {
                    $table->index('user_id');
                }
            });
        }

        if (Schema::hasTable('exams')) {
            Schema::table('exams', function (Blueprint $table) {
                if (!Schema::hasIndex('exams', 'exams_lecture_id_index')) {
                    $table->index('lecture_id');
                }
                try {
                    $table->unique(['lecture_id', 'is_assignment']);
                } catch (\Exception $e) {
                    // index may already exist
                }
            });
        }

        if (Schema::hasTable('questions')) {
            Schema::table('questions', function (Blueprint $table) {
                if (!Schema::hasIndex('questions', 'questions_exam_id_index')) {
                    $table->index('exam_id');
                }
            });
        }

        if (Schema::hasTable('choices')) {
            Schema::table('choices', function (Blueprint $table) {
                if (!Schema::hasIndex('choices', 'choices_question_id_index')) {
                    $table->index('question_id');
                }
            });
        }

        if (Schema::hasTable('questions_posts')) {
            Schema::table('questions_posts', function (Blueprint $table) {
                if (!Schema::hasIndex('questions_posts', 'questions_posts_lecture_id_index')) {
                    $table->index('lecture_id');
                }
                if (!Schema::hasIndex('questions_posts', 'questions_posts_student_id_index')) {
                    $table->index('student_id');
                }
            });
        }

        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (!Schema::hasIndex('notifications', 'notifications_user_id_index')) {
                    $table->index('user_id');
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasIndex('users', 'users_status_index')) {
                    $table->index('status');
                }
            });
        }

        if (Schema::hasTable('courses')) {
            Schema::table('courses', function (Blueprint $table) {
                if (!Schema::hasIndex('courses', 'courses_status_index')) {
                    $table->index('status');
                }
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasIndex('products', 'products_is_active_index')) {
                    $table->index('is_active');
                }
            });
        }
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
