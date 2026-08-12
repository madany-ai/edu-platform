<?php

namespace App\Providers;

use App\Models\Course;
use App\Policies\CoursePolicy;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(\App\Models\Lecture::class, \App\Policies\LecturePolicy::class);
        Gate::policy(\App\Models\CourseSection::class, \App\Policies\SectionPolicy::class);
        Gate::policy(\App\Models\Exam::class, \App\Policies\ExamPolicy::class);
        Gate::policy(\App\Models\ExamAttempt::class, \App\Policies\ExamAttemptPolicy::class);
        Gate::policy(\App\Models\Student::class, \App\Policies\StudentPolicy::class);
        Gate::policy(\App\Models\Order::class, \App\Policies\OrderPolicy::class);
        Gate::policy(\App\Models\Group::class, \App\Policies\GroupPolicy::class);
        Gate::policy(\App\Models\Attendance::class, \App\Policies\AttendancePolicy::class);
        Gate::policy(\App\Models\CenterExam::class, \App\Policies\CenterExamPolicy::class);
        Gate::policy(\App\Models\Product::class, \App\Policies\ProductPolicy::class);
        \Laravel\Sanctum\Sanctum::usePersonalAccessTokenModel(\App\Models\PersonalAccessToken::class);

        \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('login', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by($request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('video', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });
    }
}
