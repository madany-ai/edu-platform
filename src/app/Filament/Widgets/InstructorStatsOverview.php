<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InstructorStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = request()->user();
        $courseIds = $this->getUserCourseIds($user);

        $coursesCount = count($courseIds);
        $publishedCount = Course::whereIn('id', $courseIds)
            ->where('status', 'published')->count();
        $totalStudents = Enrollment::whereIn('course_id', $courseIds)->count();
        $recentEnrollments = Enrollment::whereIn('course_id', $courseIds)
            ->where('created_at', '>=', now()->subDays(7))->count();
        $totalRevenue = 0;
        
        $examsCount = 0;
        $assignmentsCount = 0;
        $attemptsCount = 0;
        $questionsCount = 0;

        if ($user->hasRole('super_admin')) {
            $totalRevenue = Order::where('status', 'completed')->sum('amount_cents') / 100;
            $examsCount = \App\Models\Exam::where('is_assignment', false)->count();
            $assignmentsCount = \App\Models\Exam::where('is_assignment', true)->count();
            $attemptsCount = \App\Models\ExamAttempt::count();
            $questionsCount = \App\Models\QuestionsPost::count();
        } else {
            $orders = Order::where('status', 'completed')->with('purchasable')->get();
            foreach ($orders as $order) {
                if ($order->purchasable && $order->purchasable->instructor_id === $user->id) {
                    $totalRevenue += ($order->amount_cents / 100);
                }
            }
            
            $lectureIds = \App\Models\Lecture::whereHas('section', function($q) use ($courseIds) {
                $q->whereIn('course_id', $courseIds);
            })->pluck('id')->toArray();
            
            $examsCount = \App\Models\Exam::where('is_assignment', false)->whereIn('lecture_id', $lectureIds)->count();
            $assignmentsCount = \App\Models\Exam::where('is_assignment', true)->whereIn('lecture_id', $lectureIds)->count();
            $attemptsCount = \App\Models\ExamAttempt::whereHas('exam', function($q) use ($lectureIds) {
                $q->whereIn('lecture_id', $lectureIds);
            })->count();
            $questionsCount = \App\Models\QuestionsPost::whereIn('lecture_id', $lectureIds)->count();
        }

        return [
            Stat::make('إجمالي الدورات', $coursesCount)
                ->description("{$publishedCount} منشور")
                ->descriptionIcon('heroicon-o-academic-cap')
                ->color('primary'),

            Stat::make('إجمالي الطلاب', $totalStudents)
                ->description("{$recentEnrollments} تسجيل جديد هذا الأسبوع")
                ->descriptionIcon('heroicon-o-users')
                ->color('success'),

            Stat::make('الإيرادات', number_format((float) $totalRevenue, 2) . ' ج.م')
                ->description('إجمالي مبيعات الدورات')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('warning'),

            Stat::make('المحاضرات', $this->getTotalLectures($courseIds))
                ->description('إجمالي المحاضرات في جميع الدورات')
                ->descriptionIcon('heroicon-o-play')
                ->color('info'),
                
            Stat::make('الامتحانات', $examsCount)
                ->description('إجمالي الامتحانات')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('gray'),
                
            Stat::make('الواجبات', $assignmentsCount)
                ->description('إجمالي الواجبات')
                ->descriptionIcon('heroicon-o-clipboard-document-list')
                ->color('gray'),
                
            Stat::make('إجابات الطلاب', $attemptsCount)
                ->description('إجمالي محاولات الامتحانات والواجبات')
                ->descriptionIcon('heroicon-o-pencil-square')
                ->color('gray'),
                
            Stat::make('الأسئلة والاستفسارات', $questionsCount)
                ->description('إجمالي الأسئلة في المحاضرات')
                ->descriptionIcon('heroicon-o-chat-bubble-left-right')
                ->color('gray'),
        ];
    }

    private function getUserCourseIds($user): array
    {
        if ($user->hasRole('super_admin')) {
            return Course::pluck('id')->toArray();
        }

        if ($user->hasRole('instructor')) {
            return Course::where('instructor_id', $user->id)->pluck('id')->toArray();
        }

        if ($user->hasRole('assistant')) {
            return Course::whereHas('assistants', fn ($q) => $q->where('user_id', $user->id))
                ->pluck('id')->toArray();
        }

        return [];
    }

    private function getTotalLectures(array $courseIds): int
    {
        return Course::whereIn('id', $courseIds)
            ->withCount('lectures')
            ->get()
            ->sum('lectures_count');
    }
}
