<?php

namespace App\Services;

use App\Models\CenterGrade;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RankingService
{
    public static function getGroupRankings(string $groupId, int $limit = 50): Collection
    {
        return DB::table('students')
            ->join('center_grades', 'students.id', '=', 'center_grades.student_id')
            ->join('center_exams', 'center_grades.center_exam_id', '=', 'center_exams.id')
            ->where('students.group_id', $groupId)
            ->select(
                'students.id as student_id',
                'students.first_name',
                'students.second_name',
                'students.third_name',
                'students.last_name',
                'students.student_code',
                DB::raw('SUM(center_grades.score) as total_score'),
                DB::raw('SUM(center_exams.total_marks) as max_score'),
                DB::raw('COUNT(center_exams.id) as exams_count'),
                DB::raw('ROUND(CAST((SUM(center_grades.score) / NULLIF(SUM(center_exams.total_marks), 0)) * 100 AS numeric), 1) as percentage')
            )
            ->groupBy('students.id', 'students.first_name', 'students.second_name', 'students.third_name', 'students.last_name', 'students.student_code')
            ->orderByDesc('total_score')
            ->limit($limit)
            ->get();
    }

    public static function getAcademicYearRankings(string $academicYear, int $limit = 50): Collection
    {
        return DB::table('students')
            ->join('center_grades', 'students.id', '=', 'center_grades.student_id')
            ->join('center_exams', 'center_grades.center_exam_id', '=', 'center_exams.id')
            ->where('students.academic_year', $academicYear)
            ->select(
                'students.id as student_id',
                'students.first_name',
                'students.second_name',
                'students.third_name',
                'students.last_name',
                'students.student_code',
                DB::raw('SUM(center_grades.score) as total_score'),
                DB::raw('SUM(center_exams.total_marks) as max_score'),
                DB::raw('COUNT(center_exams.id) as exams_count'),
                DB::raw('ROUND(CAST((SUM(center_grades.score) / NULLIF(SUM(center_exams.total_marks), 0)) * 100 AS numeric), 1) as percentage')
            )
            ->groupBy('students.id', 'students.first_name', 'students.second_name', 'students.third_name', 'students.last_name', 'students.student_code')
            ->orderByDesc('total_score')
            ->limit($limit)
            ->get();
    }
}
