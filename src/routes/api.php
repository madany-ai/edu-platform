<?php

use App\Domain\Auth\Controllers\AuthController;
use App\Domain\Course\Controllers\CategoryController;
use App\Domain\Course\Controllers\CourseController;
use App\Domain\Dashboard\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');

    Route::get('dashboard/student', [DashboardController::class, 'student']);
    Route::get('dashboard/instructor', [DashboardController::class, 'instructor']);

    Route::get('courses/my-enrollments', [CourseController::class, 'myEnrollments']);
    Route::post('courses/{course}/enroll', [CourseController::class, 'enroll']);
    Route::post('courses/{course}/review', [CourseController::class, 'review']);

    Route::get('instructor/courses', [CourseController::class, 'instructorCourses']);
    Route::post('courses', [CourseController::class, 'store']);
    Route::put('courses/{course}', [CourseController::class, 'update']);
    Route::delete('courses/{course}', [CourseController::class, 'destroy']);
});

Route::get('categories', [CategoryController::class, 'index']);
Route::get('courses', [CourseController::class, 'index']);
Route::get('courses/{course}', [CourseController::class, 'show']);
