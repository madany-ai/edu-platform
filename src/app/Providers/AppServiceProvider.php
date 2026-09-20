<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\CenterExam;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Group;
use App\Models\Lecture;
use App\Models\Order;
use App\Models\PersonalAccessToken;
use App\Models\Product;
use App\Models\Student;
use App\Policies\AttendancePolicy;
use App\Policies\CenterExamPolicy;
use App\Policies\CoursePolicy;
use App\Policies\ExamAttemptPolicy;
use App\Policies\ExamPolicy;
use App\Policies\GroupPolicy;
use App\Policies\LecturePolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\SectionPolicy;
use App\Policies\StudentPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        LogViewer::auth(function ($request) {
            return $request->user() && $request->user()->hasAnyRole(['super_admin', 'admin']);
        });

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(Lecture::class, LecturePolicy::class);
        Gate::policy(CourseSection::class, SectionPolicy::class);
        Gate::policy(Exam::class, ExamPolicy::class);
        Gate::policy(ExamAttempt::class, ExamAttemptPolicy::class);
        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Group::class, GroupPolicy::class);
        Gate::policy(Attendance::class, AttendancePolicy::class);
        Gate::policy(CenterExam::class, CenterExamPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('video', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });
    }
}
