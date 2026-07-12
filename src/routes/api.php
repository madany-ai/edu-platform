<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::middleware('throttle:login')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
});
Route::get('courses', [CourseController::class, 'index']);
Route::get('courses/{course}', [CourseController::class, 'show']);
Route::get('lectures/{lecture}/key', [CourseController::class, 'streamKey'])
    ->middleware('throttle:video')
    ->name('lectures.key');
Route::get('governorates', [\App\Http\Controllers\Api\MiscController::class, 'governorates']);
Route::get('grade-levels', [\App\Http\Controllers\Api\MiscController::class, 'gradeLevels']);

// Authenticated routes
Route::middleware(['auth:sanctum', 'user.active'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    // Dashboard
    Route::get('dashboard/student', [DashboardController::class, 'student']);

    // Instructor Dashboard
    Route::get('dashboard/instructor', [DashboardController::class, 'instructor']);
    Route::get('dashboard/instructor/courses', [DashboardController::class, 'instructorCourses']);
    Route::get('dashboard/instructor/recent-enrollments', [DashboardController::class, 'instructorRecentEnrollments']);
    Route::get('dashboard/instructor/course-performance', [DashboardController::class, 'instructorCoursePerformance']);
    Route::get('dashboard/instructor/notifications', [DashboardController::class, 'instructorNotifications']);

    // Courses (instructor)
    Route::post('courses', [CourseController::class, 'store']);
    Route::put('courses/{course}', [CourseController::class, 'update']);
    Route::delete('courses/{course}', [CourseController::class, 'destroy']);

    // Sections & Lectures
    Route::post('courses/{course}/sections', [CourseController::class, 'storeSection']);
    Route::put('courses/{course}/sections/{section}', [CourseController::class, 'updateSection']);
    Route::delete('courses/{course}/sections/{section}', [CourseController::class, 'destroySection']);
    Route::post('sections/{section}/lectures', [CourseController::class, 'storeLecture']);
    Route::put('sections/{section}/lectures/{lecture}', [CourseController::class, 'updateLecture']);
    Route::delete('sections/{section}/lectures/{lecture}', [CourseController::class, 'destroyLecture']);

    Route::get('lectures/{lecture}', [CourseController::class, 'showLecture'])
        ->middleware(\App\Http\Middleware\CheckEnrollment::class);
    Route::get('lectures/{lecture}/stream', [CourseController::class, 'streamLecture'])
        ->middleware('throttle:video')
        ->name('lectures.stream');
    Route::post('lectures/{lecture}/progress', [CourseController::class, 'updateProgress'])
        ->middleware(\App\Http\Middleware\CheckEnrollment::class);
    Route::get('lectures/{lecture}/assignment', [ExamController::class, 'showAssignment'])
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

    // Orders & Purchases
    Route::post('orders', [OrderController::class, 'store']);
});
