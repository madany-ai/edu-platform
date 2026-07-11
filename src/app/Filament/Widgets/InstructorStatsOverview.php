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
        if ($user->hasRole('super_admin')) {
            $totalRevenue = Order::where('status', 'completed')->sum('amount_cents') / 100;
        } else {
            $orders = Order::where('status', 'completed')->with('purchasable')->get();
            foreach ($orders as $order) {
                if ($order->purchasable && $order->purchasable->instructor_id === $user->id) {
                    $totalRevenue += ($order->amount_cents / 100);
                }
            }
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
