<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CenterStaffController;
use App\Http\Controllers\Api\CenterStudentController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\MiscController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\QAController;
use App\Http\Controllers\Api\VideoStreamController;
use App\Http\Middleware\CheckEnrollment;
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
Route::get('governorates', [MiscController::class, 'governorates']);
Route::get('grade-levels', [MiscController::class, 'gradeLevels']);
Route::get('standalone-lectures', [CourseController::class, 'standaloneIndex']);

// ─── Payment Webhooks ───
Route::post('webhooks/{gateway}', [PaymentWebhookController::class, 'handle'])
    ->where('gateway', 'paymob|fawry');
Route::get('video/{videoId}/playlist', [VideoStreamController::class, 'playlist'])
    ->name('video.playlist')
    ->middleware('throttle:120,1');
Route::get('video/{videoId}/segment', [VideoStreamController::class, 'segment'])
    ->name('video.segment')
    ->middleware('throttle:600,1');

// Authenticated routes
Route::middleware(['auth:sanctum', 'user.active'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/refresh-token', [AuthController::class, 'refreshToken']);
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
        ->middleware(CheckEnrollment::class);
    Route::get('lectures/{lecture}/files/{file}', [CourseController::class, 'downloadFile'])
        ->middleware(CheckEnrollment::class)
        ->name('lectures.downloadFile');
    Route::post('lectures/{lecture}/progress', [CourseController::class, 'updateProgress'])
        ->middleware(CheckEnrollment::class);
    Route::get('lectures/{lecture}/assignment', [ExamController::class, 'show'])
        ->middleware(CheckEnrollment::class);

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
        ->middleware(CheckEnrollment::class);
    Route::get('lectures/{lecture}/questions', [QAController::class, 'index'])
        ->middleware(CheckEnrollment::class);
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
    Route::get('lectures/{lecture}/key', [CourseController::class, 'streamKey'])
        ->middleware('throttle:10,1');

    // ─── Center Management (Student) ───
    Route::get('center/my-attendance', [CenterStudentController::class, 'myAttendance']);
    Route::get('center/my-grades', [CenterStudentController::class, 'myGrades']);
    Route::get('center/my-group', [CenterStudentController::class, 'myGroup']);
    Route::get('center/my-report', [CenterStudentController::class, 'myReport']);

    // ─── Center Management (Staff: Instructor & Assistant) ───
    Route::middleware('role:instructor|assistant|admin|super_admin')->prefix('center/staff')->group(function () {
        Route::get('stats', [CenterStaffController::class, 'stats']);
        Route::get('academic-years', [CenterStaffController::class, 'academicYears']);
        Route::post('academic-years', [CenterStaffController::class, 'storeAcademicYear']);
        Route::put('academic-years/{id}', [CenterStaffController::class, 'updateAcademicYear']);

        Route::get('groups', [CenterStaffController::class, 'groups']);
        Route::get('groups/{id}', [CenterStaffController::class, 'showGroup']);
        Route::post('groups', [CenterStaffController::class, 'storeGroup']);
        Route::put('groups/{id}', [CenterStaffController::class, 'updateGroup']);
        Route::delete('groups/{id}', [CenterStaffController::class, 'destroyGroup']);

        Route::get('sessions', [CenterStaffController::class, 'sessions']);
        Route::post('sessions', [CenterStaffController::class, 'storeSession']);
        Route::put('sessions/{id}', [CenterStaffController::class, 'updateSession']);
        Route::get('sessions/{id}/attendance', [CenterStaffController::class, 'getSessionAttendance']);
        Route::post('sessions/{id}/attendance', [CenterStaffController::class, 'updateAttendance']);
        Route::post('attendance/scan', [CenterStaffController::class, 'scanAttendance']);

        Route::get('exams', [CenterStaffController::class, 'exams']);
        Route::post('exams', [CenterStaffController::class, 'storeExam']);
        Route::get('exams/{id}/grades', [CenterStaffController::class, 'getExamGrades']);
        Route::post('exams/{id}/grades', [CenterStaffController::class, 'saveExamGrades']);

        Route::get('rankings', [CenterStaffController::class, 'rankings']);
        Route::get('students', [CenterStaffController::class, 'students']);
        Route::post('students', [CenterStaffController::class, 'storeStudent']);
        Route::put('students/bulk-group', [CenterStaffController::class, 'bulkUpdateStudentGroup']);
        Route::put('students/{id}/group', [CenterStaffController::class, 'updateStudentGroup']);
        Route::put('students/{id}/approve', [CenterStaffController::class, 'approveStudent']);
        Route::put('students/{id}/reject', [CenterStaffController::class, 'rejectStudent']);
        Route::get('students/{id}/report', [CenterStaffController::class, 'studentReport']);
    });
});
