<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);
Route::get('courses', [CourseController::class, 'index']);
Route::get('courses/{course}', [CourseController::class, 'show']);

// Authenticated routes
Route::middleware(['auth:sanctum', 'user.active'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    // Dashboard
    Route::get('dashboard/student', [DashboardController::class, 'student']);
    Route::get('dashboard/instructor', [DashboardController::class, 'instructor']);

    // Courses (instructor)
    Route::get('instructor/courses', [CourseController::class, 'instructorCourses']);
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

    // Enrollments
    Route::get('my-enrollments', [EnrollmentController::class, 'myEnrollments']);
    Route::post('courses/{course}/enroll', [EnrollmentController::class, 'enroll']);
    Route::post('courses/{course}/purchase', [EnrollmentController::class, 'purchase']);
    Route::get('courses/{course}/enrollments', [EnrollmentController::class, 'courseEnrollments'])
        ->middleware('role:instructor');
    Route::delete('courses/{course}/enrollments/{student}', [EnrollmentController::class, 'revoke'])
        ->middleware('role:instructor');

    // Students (instructor)
    Route::get('instructor/students', [DashboardController::class, 'instructorStudents'])
        ->middleware('role:instructor');
});
