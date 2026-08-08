<?php

namespace App\Filament\Widgets;

use App\Models\AcademicSession;
use App\Models\Attendance;
use App\Models\CenterExam;
use App\Models\Group;
use App\Models\Student;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CenterStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $activeGroups = Group::where('is_active', true)->count();
        $totalStudentsInGroups = Student::whereNotNull('group_id')->count();

        $weekSessionsCount = AcademicSession::where('date', '>=', now()->startOfWeek())->count();
        $weekAttendances = Attendance::whereHas('session', function ($q) {
            $q->where('date', '>=', now()->startOfWeek());
        })->get();

        $presentCount = $weekAttendances->where('status', 'present')->count();
        $totalWeekAttendances = $weekAttendances->count();
        $attendanceRate = $totalWeekAttendances > 0
            ? round(($presentCount / $totalWeekAttendances) * 100, 1)
            : 100;

        $examsCount = CenterExam::count();

        // Students absent 3+ consecutive sessions
        $frequentAbsentees = Student::whereHas('attendances', function ($q) {
            $q->where('status', 'absent');
        }, '>=', 3)->count();

        return [
            Stat::make('مجموعات السنتر النشطة', $activeGroups)
                ->description("إجمالي طلاب السنتر المسجلين: {$totalStudentsInGroups}")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('نسبة الحضور هذا الأسبوع', "{$attendanceRate}%")
                ->description("حصص هذا الأسبوع: {$weekSessionsCount}")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($attendanceRate >= 80 ? 'success' : 'warning'),

            Stat::make('الامتحانات الورقية المسجلة', $examsCount)
                ->description('إجمالي الاختبارات الدورية والشهرية')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('info'),

            Stat::make('طلاب غائبون (3 حصص فأكثر)', $frequentAbsentees)
                ->description('يحتاجون متابعة وتواصل مع ولي الأمر')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($frequentAbsentees > 0 ? 'danger' : 'gray'),
        ];
    }
}
