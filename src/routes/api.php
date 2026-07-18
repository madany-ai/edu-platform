<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\QAController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::middleware('throttle:login')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
});
Route::post('auth/forgot-password', [PasswordResetController::class, 'forgotPassword'])
    ->middleware('throttle:login');
Route::post('auth/reset-password', [PasswordResetController::class, 'resetPassword'])
    ->middleware('throttle:login');
Route::get('courses', [CourseController::class, 'index']);
Route::get('courses/{course}', [CourseController::class, 'show']);
Route::get('governorates', [\App\Http\Controllers\Api\MiscController::class, 'governorates']);
Route::get('grade-levels', [\App\Http\Controllers\Api\MiscController::class, 'gradeLevels']);

// ─── Video Stream Proxy (token-based auth — no Sanctum session needed) ───
Route::get('video/{videoId}/playlist', [\App\Http\Controllers\Api\VideoStreamController::class, 'playlist'])
    ->name('video.playlist')
    ->middleware('throttle:120,1');
Route::get('video/{videoId}/segment', [\App\Http\Controllers\Api\VideoStreamController::class, 'segment'])
    ->name('video.segment')
    ->middleware('throttle:600,1');



// Authenticated routes
Route::middleware(['auth:sanctum', 'user.active'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    // Dashboard
    Route::get('dashboard/student', [DashboardController::class, 'student']);
    Route::get('notifications', [DashboardController::class, 'myNotifications']);

    // Instructor Dashboard
    Route::middleware('role:instructor')->group(function () {
        Route::get('dashboard/instructor', [DashboardController::class, 'instructor']);
        Route::get('dashboard/instructor/courses', [DashboardController::class, 'instructorCourses']);
        Route::get('dashboard/instructor/recent-enrollments', [DashboardController::class, 'instructorRecentEnrollments']);
        Route::get('dashboard/instructor/course-performance', [DashboardController::class, 'instructorCoursePerformance']);
        Route::get('dashboard/instructor/notifications', [DashboardController::class, 'instructorNotifications']);
    });

    // Courses (instructor)
    Route::post('courses', [CourseController::class, 'store']);
    Route::put('courses/{course}', [CourseController::class, 'update']);
    Route::delete('courses/{course}', [CourseController::class, 'destroy']);

    // Sections & Lectures (instructor only)
    Route::middleware('role:instructor')->group(function () {
        Route::post('courses/{course}/sections', [CourseController::class, 'storeSection']);
        Route::put('courses/{course}/sections/{section}', [CourseController::class, 'updateSection']);
        Route::delete('courses/{course}/sections/{section}', [CourseController::class, 'destroySection']);
        Route::post('sections/{section}/lectures', [CourseController::class, 'storeLecture']);
        Route::put('sections/{section}/lectures/{lecture}', [CourseController::class, 'updateLecture']);
        Route::delete('sections/{section}/lectures/{lecture}', [CourseController::class, 'destroyLecture']);
    });

    Route::get('lectures/{lecture}', [CourseController::class, 'showLecture'])
        ->middleware(\App\Http\Middleware\CheckEnrollment::class);
    Route::get('lectures/{lecture}/files/{file}', [CourseController::class, 'downloadFile'])
        ->middleware(\App\Http\Middleware\CheckEnrollment::class)
        ->name('lectures.downloadFile');
    Route::post('lectures/{lecture}/progress', [CourseController::class, 'updateProgress'])
        ->middleware(\App\Http\Middleware\CheckEnrollment::class);
    Route::get('lectures/{lecture}/assignment', [ExamController::class, 'show'])
        ->middleware(\App\Http\Middleware\CheckEnrollment::class);

    // Enrollments
    Route::get('my-enrollments', [EnrollmentController::class, 'myEnrollments']);
    Route::get('my-entitlements', [EnrollmentController::class, 'myEntitlements']);
    Route::post('courses/{course}/enroll', [EnrollmentController::class, 'enroll']);
    Route::post('courses/{course}/purchase', [EnrollmentController::class, 'purchase']);
    Route::get('courses/{course}/enrollments', [EnrollmentController::class, 'courseEnrollments'])
        ->middleware('role:instructor');
    Route::delete('courses/{course}/enrollments/{student}', [EnrollmentController::class, 'revoke'])
        ->middleware('role:instructor');

    // Students (instructor)
    Route::get('instructor/students', [DashboardController::class, 'instructorStudents'])
        ->middleware('role:instructor');

    // Exams (student)
    Route::get('my-attempts', [ExamController::class, 'myAttempts']);
    Route::get('lectures/{lecture}/exam', [ExamController::class, 'show']);
    Route::post('exams/{exam}/start', [ExamController::class, 'startAttempt']);
    Route::post('attempts/{attempt}/submit', [ExamController::class, 'submitAttempt']);
    Route::get('attempts/{attempt}/result', [ExamController::class, 'result']);

    // Exams (instructor)
    Route::post('lectures/{lecture}/exam', [ExamController::class, 'store'])
        ->middleware('role:instructor');
    Route::put('exams/{exam}', [ExamController::class, 'update'])
        ->middleware('role:instructor');
    Route::delete('exams/{exam}', [ExamController::class, 'destroy'])
        ->middleware('role:instructor');

    // Products & Bundles
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{product}', [ProductController::class, 'show']);
    Route::get('bundles', [ProductController::class, 'bundles']);
    Route::get('bundles/{bundle}', [ProductController::class, 'showBundle']);

    // Orders & Purchases
    Route::post('orders', [OrderController::class, 'store']);

    // Q&A — Student
    Route::post('lectures/{lecture}/questions', [QAController::class, 'store'])
        ->middleware(\App\Http\Middleware\CheckEnrollment::class);
    Route::get('lectures/{lecture}/questions', [QAController::class, 'index'])
        ->middleware(\App\Http\Middleware\CheckEnrollment::class);
    Route::get('questions/{question}', [QAController::class, 'show']);
    Route::post('questions/{question}/replies', [QAController::class, 'reply']);
    Route::get('my-questions', [QAController::class, 'myQuestions']);
    Route::delete('questions/{question}', [QAController::class, 'destroyQuestion']);
    Route::delete('replies/{reply}', [QAController::class, 'destroyReply']);

    // Q&A — Instructor
    Route::get('instructor/questions', [QAController::class, 'staffQuestions'])
        ->middleware('role:instructor');

    // Q&A — Assistant
    Route::get('assistant/questions', [QAController::class, 'staffQuestions'])
        ->middleware('role:assistant');
});

Route::get('lectures/{lecture}/key', [\App\Http\Controllers\Api\CourseController::class, 'streamKey']);
