<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use App\Models\Enrollment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InstructorStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = request()->user();
        $courseIds = Course::where('instructor_id', $user->id)->pluck('id');

        $coursesCount = Course::where('instructor_id', $user->id)->count();
        $publishedCount = Course::where('instructor_id', $user->id)
            ->where('status', 'published')->count();
        $totalStudents = Enrollment::whereIn('course_id', $courseIds)->count();
        $recentEnrollments = Enrollment::whereIn('course_id', $courseIds)
            ->where('created_at', '>=', now()->subDays(7))->count();
        $totalRevenue = Course::where('instructor_id', $user->id)->sum('price');

        return [
            Stat::make('إجمالي الدورات', $coursesCount)
                ->description("{$publishedCount} منشور")
                ->descriptionIcon('heroicon-o-academic-cap')
                ->color('primary'),

            Stat::make('إجمالي الطلاب', $totalStudents)
                ->description("{$recentEnrollments} تسجيل جديد هذا الأسبوع")
                ->descriptionIcon('heroicon-o-users')
                ->color('success'),

            Stat::make('الإيرادات', number_format((float) $totalRevenue, 2) . ' د.م')
                ->description('إجمالي أسعار الدورات')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('warning'),

            Stat::make('المحاضرات', $this->getTotalLectures($user->id))
                ->description('إجمالي المحاضرات في جميع الدورات')
                ->descriptionIcon('heroicon-o-play')
                ->color('info'),
        ];
    }

    private function getTotalLectures(int $instructorId): int
    {
        return Course::where('instructor_id', $instructorId)
            ->withCount('lectures')
            ->get()
            ->sum('lectures_count');
    }
}
