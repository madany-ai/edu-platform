<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\CenterGrade;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CenterStudentController extends Controller
{
    public function myAttendance(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = Student::where('user_id', $user->id)->first();

        if (! $student) {
            return response()->json([
                'status' => 'error',
                'message' => 'بيانات الطالب غير موجودة',
            ], 404);
        }

        $attendances = Attendance::with(['session.group'])
            ->where('student_id', $student->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'guest' => $attendances->where('status', 'guest')->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'stats' => $stats,
                'attendances' => $attendances->map(fn ($a) => [
                    'id' => $a->id,
                    'date' => $a->session?->date?->format('Y-m-d'),
                    'topic' => $a->session?->topic,
                    'group_name' => $a->session?->group?->name,
                    'status' => $a->status,
                    'is_guest' => $a->is_guest,
                ]),
            ],
        ]);
    }

    public function myGrades(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = Student::where('user_id', $user->id)->first();

        if (! $student) {
            return response()->json([
                'status' => 'error',
                'message' => 'بيانات الطالب غير موجودة',
            ], 404);
        }

        $grades = CenterGrade::with(['centerExam.group'])
            ->where('student_id', $student->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $grades->map(fn ($g) => [
                'id' => $g->id,
                'exam_name' => $g->centerExam?->name,
                'total_marks' => (float) ($g->centerExam?->total_marks ?? 0),
                'score' => (float) $g->score,
                'percentage' => $g->centerExam?->total_marks > 0
                    ? round(($g->score / $g->centerExam->total_marks) * 100, 1)
                    : 0,
                'date' => $g->centerExam?->date?->format('Y-m-d'),
                'notes' => $g->notes,
            ]),
        ]);
    }

    public function myGroup(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = Student::where('user_id', $user->id)->with(['group.gradeLevel', 'group.academicYear'])->first();

        if (! $student || ! $student->group) {
            return response()->json([
                'status' => 'success',
                'data' => null,
            ]);
        }

        $group = $student->group;

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $group->id,
                'name' => $group->name,
                'grade_level' => $group->gradeLevel?->name,
                'academic_year' => $group->academicYear?->name,
                'schedule' => $group->schedule,
                'capacity' => $group->capacity,
            ],
        ]);
    }

    public function myReport(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = Student::where('user_id', $user->id)
            ->with(['gradeLevel', 'group', 'attendances.session', 'centerGrades.centerExam'])
            ->first();

        if (! $student) {
            return response()->json([
                'status' => 'error',
                'message' => 'بيانات الطالب غير موجودة',
            ], 404);
        }

        $attendancesCount = $student->attendances->count();
        $presentCount = $student->attendances->where('status', 'present')->count();
        $attendancePercentage = $attendancesCount > 0
            ? round(($presentCount / $attendancesCount) * 100, 1)
            : 100;

        $totalScore = $student->centerGrades->sum('score');
        $maxScore = $student->centerGrades->sum(fn ($g) => $g->centerExam?->total_marks ?? 0);
        $gradePercentage = $maxScore > 0 ? round(($totalScore / $maxScore) * 100, 1) : 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'student' => [
                    'code' => $student->student_code,
                    'full_name' => $student->full_name,
                    'grade_level' => $student->gradeLevel?->name,
                    'group' => $student->group?->name,
                ],
                'attendance_summary' => [
                    'total_sessions' => $attendancesCount,
                    'present' => $presentCount,
                    'absent' => $student->attendances->where('status', 'absent')->count(),
                    'late' => $student->attendances->where('status', 'late')->count(),
                    'percentage' => $attendancePercentage,
                ],
                'academic_summary' => [
                    'exams_count' => $student->centerGrades->count(),
                    'total_score' => (float) $totalScore,
                    'max_score' => (float) $maxScore,
                    'percentage' => $gradePercentage,
                ],
            ],
        ]);
    }
}
