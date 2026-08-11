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
Route::get('standalone-lectures', [CourseController::class, 'standaloneIndex']);

// ─── Payment Webhooks ───
Route::post('webhooks/{gateway}', [\App\Http\Controllers\Api\PaymentWebhookController::class, 'handle'])
    ->where('gateway', 'paymob|fawry');
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
    Route::put('auth/profile', [AuthController::class, 'updateProfile']);
    Route::put('auth/change-password', [AuthController::class, 'changePassword']);

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
    Route::get('orders', [OrderController::class, 'index']);
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
    // Video stream key (token-based auth — rate-limited)
    Route::get('lectures/{lecture}/key', [\App\Http\Controllers\Api\CourseController::class, 'streamKey'])
        ->middleware('throttle:10,1');

    // ─── Center Management (Student) ───
    Route::get('center/my-attendance', [\App\Http\Controllers\Api\CenterStudentController::class, 'myAttendance']);
    Route::get('center/my-grades', [\App\Http\Controllers\Api\CenterStudentController::class, 'myGrades']);
    Route::get('center/my-group', [\App\Http\Controllers\Api\CenterStudentController::class, 'myGroup']);
    Route::get('center/my-report', [\App\Http\Controllers\Api\CenterStudentController::class, 'myReport']);

    // ─── Center Management (Staff: Instructor & Assistant) ───
    Route::middleware('role:instructor|assistant')->prefix('center/staff')->group(function () {
        Route::get('academic-years', [\App\Http\Controllers\Api\CenterStaffController::class, 'academicYears']);
        Route::post('academic-years', [\App\Http\Controllers\Api\CenterStaffController::class, 'storeAcademicYear']);
        Route::put('academic-years/{id}', [\App\Http\Controllers\Api\CenterStaffController::class, 'updateAcademicYear']);

        Route::get('groups', [\App\Http\Controllers\Api\CenterStaffController::class, 'groups']);
        Route::get('groups/{id}', [\App\Http\Controllers\Api\CenterStaffController::class, 'showGroup']);
        Route::post('groups', [\App\Http\Controllers\Api\CenterStaffController::class, 'storeGroup']);
        Route::put('groups/{id}', [\App\Http\Controllers\Api\CenterStaffController::class, 'updateGroup']);

        Route::get('sessions', [\App\Http\Controllers\Api\CenterStaffController::class, 'sessions']);
        Route::post('sessions', [\App\Http\Controllers\Api\CenterStaffController::class, 'storeSession']);
        Route::get('sessions/{id}/attendance', [\App\Http\Controllers\Api\CenterStaffController::class, 'getSessionAttendance']);
        Route::post('sessions/{id}/attendance', [\App\Http\Controllers\Api\CenterStaffController::class, 'updateAttendance']);
        Route::post('attendance/scan', [\App\Http\Controllers\Api\CenterStaffController::class, 'scanAttendance']);

        Route::get('exams', [\App\Http\Controllers\Api\CenterStaffController::class, 'exams']);
        Route::post('exams', [\App\Http\Controllers\Api\CenterStaffController::class, 'storeExam']);
        Route::get('exams/{id}/grades', [\App\Http\Controllers\Api\CenterStaffController::class, 'getExamGrades']);
        Route::post('exams/{id}/grades', [\App\Http\Controllers\Api\CenterStaffController::class, 'saveExamGrades']);

        Route::get('rankings', [\App\Http\Controllers\Api\CenterStaffController::class, 'rankings']);
        Route::get('students', [\App\Http\Controllers\Api\CenterStaffController::class, 'students']);
        Route::post('students', [\App\Http\Controllers\Api\CenterStaffController::class, 'storeStudent']);
        Route::put('students/{id}/group', [\App\Http\Controllers\Api\CenterStaffController::class, 'updateStudentGroup']);
        Route::get('students/{id}/report', [\App\Http\Controllers\Api\CenterStaffController::class, 'studentReport']);
    });
});
