<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\CenterExam;
use App\Models\CenterGrade;
use App\Models\GradeLevel;
use App\Models\Group;
use App\Models\Student;
use App\Services\NotificationService;
use App\Services\RankingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CenterStaffController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    // ─── Academic Years ───
    public function academicYears(): JsonResponse
    {
        $years = AcademicYear::orderByDesc('created_at')->get();
        return response()->json(['data' => $years]);
    }

    public function storeAcademicYear(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $year = AcademicYear::create($validated);
        return response()->json(['message' => 'تم إضافة السنة الدراسية بنجاح.', 'data' => $year], 201);
    }

    public function updateAcademicYear(Request $request, string $id): JsonResponse
    {
        $year = AcademicYear::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $year->update($validated);
        return response()->json(['message' => 'تم تحديث السنة الدراسية بنجاح.', 'data' => $year]);
    }

    // ─── Groups ───
    public function groups(Request $request): JsonResponse
    {
        $query = Group::withCount('students');

        if ($request->has('academic_year')) {
            $query->where('academic_year', $request->query('academic_year'));
        }

        $groups = $query->orderByDesc('created_at')->get();
        return response()->json(['data' => $groups]);
    }

    public function showGroup(string $id): JsonResponse
    {
        $group = Group::findOrFail($id);
        $students = Student::where('group_id', $id)->orderBy('first_name')->get();
        $sessions = AcademicSession::where('group_id', $id)->orderByDesc('date')->get();
        $exams = CenterExam::where('group_id', $id)->orderByDesc('date')->get();
        $rankings = RankingService::getGroupRankings($id);

        return response()->json([
            'group' => $group,
            'students' => $students,
            'sessions' => $sessions,
            'exams' => $exams,
            'rankings' => $rankings,
        ]);
    }

    public function storeGroup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'academic_year' => 'required|string|in:prep_1,prep_2,prep_3,sec_1,sec_2,sec_3',
            'academic_year_id' => 'nullable|uuid|exists:academic_years,id',
            'capacity' => 'nullable|integer|min:0',
            'schedule' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $group = Group::create($validated);
        return response()->json(['message' => 'تم إضافة المجموعة بنجاح.', 'data' => $group], 201);
    }

    public function updateGroup(Request $request, string $id): JsonResponse
    {
        $group = Group::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'academic_year' => 'sometimes|string|in:prep_1,prep_2,prep_3,sec_1,sec_2,sec_3',
            'academic_year_id' => 'nullable|uuid|exists:academic_years,id',
            'capacity' => 'nullable|integer|min:0',
            'schedule' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $group->update($validated);
        return response()->json(['message' => 'تم تحديث المجموعة بنجاح.', 'data' => $group]);
    }

    // ─── Academic Sessions ───
    public function sessions(Request $request): JsonResponse
    {
        $query = AcademicSession::with('group');

        if ($request->has('group_id')) {
            $query->where('group_id', $request->query('group_id'));
        }

        $sessions = $query->orderByDesc('date')->orderByDesc('created_at')->get();
        return response()->json(['data' => $sessions]);
    }

    public function storeSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'group_id' => 'nullable|uuid|exists:groups,id',
            'academic_year' => 'nullable|string|in:prep_1,prep_2,prep_3,sec_1,sec_2,sec_3',
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'uuid|exists:groups,id',
            'date' => 'required|date',
            'topic' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $createdSessions = [];
        $userId = $request->user()->id;

        if (!empty($validated['group_ids'])) {
            $groupIds = $validated['group_ids'];
        } elseif (!empty($validated['academic_year'])) {
            $groupIds = Group::where('academic_year', $validated['academic_year'])->pluck('id')->toArray();
        } elseif (!empty($validated['group_id'])) {
            $groupIds = [$validated['group_id']];
        } else {
            return response()->json(['message' => 'يرجى اختيار مجموعة أو صف دراسي.'], 422);
        }

        foreach ($groupIds as $gid) {
            $session = AcademicSession::create([
                'group_id' => $gid,
                'date' => $validated['date'],
                'topic' => $validated['topic'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => $userId,
            ]);
            $createdSessions[] = $session->load('group');
        }

        return response()->json([
            'message' => count($createdSessions) > 1
                ? "تم إضافة الحصة وتعميمها بنجاح على " . count($createdSessions) . " مجموعات لهذا الصف الدراسي! 🌟"
                : "تم إنشاء الحصة بنجاح.",
            'data' => $createdSessions[0] ?? null,
            'sessions' => $createdSessions,
        ], 201);
    }

    public function getSessionAttendance(string $sessionId): JsonResponse
    {
        $session = AcademicSession::with('group')->findOrFail($sessionId);

        // Get all students enrolled in the group
        $groupStudents = Student::where('group_id', $session->group_id)->get();

        // Get existing attendance records for this session
        $attendanceMap = Attendance::where('session_id', $sessionId)
            ->get()
            ->keyBy('student_id');

        // Check cross-group attendance for the same topic
        $sameTopicSessions = AcademicSession::where('topic', $session->topic)
            ->where('id', '!=', $sessionId)
            ->pluck('id');

        $crossGroupAttendances = Attendance::with('session.group')
            ->whereIn('session_id', $sameTopicSessions)
            ->get()
            ->keyBy('student_id');

        $result = $groupStudents->map(function ($student) use ($attendanceMap, $crossGroupAttendances) {
            $att = $attendanceMap->get($student->id);
            $crossAtt = $crossGroupAttendances->get($student->id);

            if ($att) {
                $status = $att->status;
                $isGuest = $att->is_guest;
                $otherGroupNote = null;
            } elseif ($crossAtt && in_array($crossAtt->status, ['present', 'late', 'guest'])) {
                $status = 'present';
                $isGuest = true;
                $otherGroupName = $crossAtt->session && $crossAtt->session->group ? $crossAtt->session->group->name : 'مجموعة أخرى';
                $otherGroupNote = "حضر الحصة في ({$otherGroupName})";
            } else {
                $status = 'absent';
                $isGuest = false;
                $otherGroupNote = null;
            }

            return [
                'student_id' => $student->id,
                'student_code' => $student->student_code,
                'full_name' => "{$student->first_name} {$student->second_name} {$student->third_name} {$student->last_name}",
                'phone' => $student->phone,
                'father_phone' => $student->father_phone,
                'status' => $status,
                'is_guest' => $isGuest,
                'other_group_note' => $otherGroupNote,
                'attendance_id' => $att ? $att->id : null,
            ];
        });

        // Add guest students from other groups who attended THIS session
        $guestAttendances = Attendance::with('student')
            ->where('session_id', $sessionId)
            ->where('is_guest', true)
            ->get();

        foreach ($guestAttendances as $guest) {
            if ($guest->student && !$result->pluck('student_id')->contains($guest->student_id)) {
                $result->push([
                    'student_id' => $guest->student->id,
                    'student_code' => $guest->student->student_code,
                    'full_name' => "{$guest->student->first_name} {$guest->student->second_name} {$guest->student->third_name} {$guest->student->last_name}",
                    'phone' => $guest->student->phone,
                    'father_phone' => $guest->student->father_phone,
                    'status' => 'guest',
                    'is_guest' => true,
                    'other_group_note' => 'طالب ضيف من مجموعة أخرى',
                    'attendance_id' => $guest->id,
                ]);
            }
        }

        return response()->json([
            'session' => $session,
            'attendance' => $result,
        ]);
    }

    public function updateAttendance(Request $request, string $sessionId): JsonResponse
    {
        $session = AcademicSession::findOrFail($sessionId);

        $validated = $request->validate([
            'records' => 'required|array',
            'records.*.student_id' => 'required|uuid|exists:students,id',
            'records.*.status' => 'required|in:present,absent,late,guest',
        ]);

        foreach ($validated['records'] as $item) {
            $student = Student::find($item['student_id']);
            if (!$student) continue;

            $isGuest = $student->group_id !== $session->group_id;

            $att = Attendance::updateOrCreate(
                [
                    'session_id' => $sessionId,
                    'student_id' => $item['student_id'],
                ],
                [
                    'status' => $item['status'],
                    'is_guest' => $isGuest,
                    'original_group_id' => $isGuest ? $student->group_id : null,
                ]
            );

            // Notify parent
            try {
                $this->notificationService->notifyAttendance(
                    $student,
                    $item['status'],
                    $session->topic,
                    $session->date->format('Y-m-d')
                );
            } catch (\Exception $e) {
                // Ignore notification error
            }
        }

        return response()->json(['message' => 'تم حفظ سجل الحضور والغياب بنجاح.']);
    }

    // ─── Live Camera / Code Scanner Endpoint ───
    public function scanAttendance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|uuid|exists:academic_sessions,id',
            'code' => 'required|string',
            'status' => 'nullable|in:present,late,guest,absent',
        ]);

        $session = AcademicSession::with('group')->findOrFail($validated['session_id']);
        $code = trim($validated['code']);

        // Search student by student_code or phone
        $student = Student::where('student_code', $code)
            ->orWhere('phone', $code)
            ->orWhereHas('user', function ($q) use ($code) {
                $q->where('phone', $code)->orWhere('email', $code);
            })
            ->first();

        if (!$student) {
            return response()->json([
                'status' => 'error',
                'message' => "لم يتم العثور على طالب بالكود أو الرقم ({$code}).",
            ], 444);
        }

        $isGuest = $student->group_id !== $session->group_id;
        $status = $validated['status'] ?? ($isGuest ? 'guest' : 'present');

        $att = Attendance::updateOrCreate(
            [
                'session_id' => $session->id,
                'student_id' => $student->id,
            ],
            [
                'status' => $status,
                'is_guest' => $isGuest,
                'original_group_id' => $isGuest ? $student->group_id : null,
            ]
        );

        // Notify parent
        try {
            $this->notificationService->notifyAttendance(
                $student,
                $status,
                $session->topic,
                $session->date->format('Y-m-d')
            );
        } catch (\Exception $e) {}

        return response()->json([
            'status' => 'success',
            'message' => "تم تسجيل " . ($status === 'present' ? 'حضور' : ($status === 'late' ? 'تأخير' : 'حضور كضيف')) . " الطالب بنجاح! 🟢",
            'student' => [
                'id' => $student->id,
                'code' => $student->student_code,
                'name' => "{$student->first_name} {$student->second_name} {$student->last_name}",
                'phone' => $student->phone,
                'father_phone' => $student->father_phone,
                'status' => $status,
                'is_guest' => $isGuest,
                'original_group' => $student->group ? $student->group->name : null,
            ]
        ]);
    }

    // ─── Center Exams & Grades ───
    public function exams(Request $request): JsonResponse
    {
        $query = CenterExam::with(['group', 'semester']);

        if ($request->has('group_id')) {
            $query->where('group_id', $request->query('group_id'));
        }

        $exams = $query->orderByDesc('date')->orderByDesc('created_at')->get();
        return response()->json(['data' => $exams]);
    }

    public function storeExam(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'total_marks' => 'required|numeric|min:1',
            'date' => 'required|date',
            'group_id' => 'nullable|uuid|exists:groups,id',
            'semester_id' => 'nullable|uuid|exists:semesters,id',
            'academic_year_id' => 'nullable|uuid|exists:academic_years,id',
        ]);

        $validated['created_by'] = $request->user()->id;
        $exam = CenterExam::create($validated);

        return response()->json(['message' => 'تم إضافة الامتحان بنجاح.', 'data' => $exam->load('group')], 201);
    }

    public function getExamGrades(string $examId): JsonResponse
    {
        $exam = CenterExam::with('group')->findOrFail($examId);

        $students = $exam->group_id
            ? Student::where('group_id', $exam->group_id)->get()
            : Student::all();

        $gradesMap = CenterGrade::where('center_exam_id', $examId)
            ->get()
            ->keyBy('student_id');

        $result = $students->map(function ($student) use ($gradesMap) {
            $g = $gradesMap->get($student->id);
            return [
                'student_id' => $student->id,
                'student_code' => $student->student_code,
                'full_name' => "{$student->first_name} {$student->second_name} {$student->third_name} {$student->last_name}",
                'phone' => $student->phone,
                'father_phone' => $student->father_phone,
                'score' => $g ? (float) $g->score : 0,
                'notes' => $g ? $g->notes : '',
                'grade_id' => $g ? $g->id : null,
            ];
        });

        return response()->json([
            'exam' => $exam,
            'grades' => $result,
        ]);
    }

    public function saveExamGrades(Request $request, string $examId): JsonResponse
    {
        $exam = CenterExam::findOrFail($examId);

        $validated = $request->validate([
            'grades' => 'required|array',
            'grades.*.student_id' => 'required|uuid|exists:students,id',
            'grades.*.score' => 'required|numeric|min:0',
            'grades.*.notes' => 'nullable|string',
        ]);

        foreach ($validated['grades'] as $item) {
            $student = Student::find($item['student_id']);
            if (!$student) continue;

            CenterGrade::updateOrCreate(
                [
                    'center_exam_id' => $examId,
                    'student_id' => $item['student_id'],
                ],
                [
                    'score' => $item['score'],
                    'notes' => $item['notes'] ?? null,
                ]
            );

            // Notify parent
            try {
                $this->notificationService->notifyCenterGrade(
                    $student,
                    $exam->name,
                    (float) $item['score'],
                    (float) $exam->total_marks
                );
            } catch (\Exception $e) {}
        }

        return response()->json(['message' => 'تم حفظ درجات الامتحان بنجاح وصدرت الإشعارات لأولياء الأمور.']);
    }

    // ─── Rankings ───
    public function rankings(Request $request): JsonResponse
    {
        $groupId = $request->query('group_id');
        $academicYear = $request->query('academic_year');

        if ($groupId) {
            $rankings = RankingService::getGroupRankings($groupId);
        } elseif ($academicYear) {
            $rankings = RankingService::getAcademicYearRankings($academicYear);
        } else {
            $firstGroup = Group::where('is_active', true)->first();
            $rankings = $firstGroup ? RankingService::getGroupRankings($firstGroup->id) : collect();
        }

        return response()->json(['data' => $rankings]);
    }

    // ─── Students List & Detail Report ───
    public function students(Request $request): JsonResponse
    {
        $query = Student::with(['group', 'user']);

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('second_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('student_code', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('group_id')) {
            $query->where('group_id', $request->query('group_id'));
        }

        $students = $query->orderBy('first_name')->paginate(20);
        return response()->json($students);
    }

    public function studentReport(string $studentId): JsonResponse
    {
        $student = Student::with(['group', 'governorate', 'city', 'user'])->findOrFail($studentId);

        $attendances = Attendance::with('session')
            ->where('student_id', $studentId)
            ->orderByDesc('created_at')
            ->get();

        $grades = CenterGrade::with('exam')
            ->where('student_id', $studentId)
            ->orderByDesc('created_at')
            ->get();

        $totalScore = $grades->sum('score');
        $maxScore = $grades->sum(fn ($g) => $g->exam ? $g->exam->total_marks : 0);
        $percentage = $maxScore > 0 ? round(($totalScore / $maxScore) * 100, 1) : 0;

        return response()->json([
            'student' => $student,
            'attendances' => $attendances,
            'grades' => $grades,
            'stats' => [
                'total_sessions' => $attendances->count(),
                'present_count' => $attendances->where('status', 'present')->count(),
                'absent_count' => $attendances->where('status', 'absent')->count(),
                'late_count' => $attendances->where('status', 'late')->count(),
                'total_exams' => $grades->count(),
                'percentage' => $percentage,
            ]
        ]);
    }

    public function updateStudentGroup(Request $request, string $studentId): JsonResponse
    {
        $student = Student::findOrFail($studentId);
        $validated = $request->validate([
            'group_id' => 'required|uuid|exists:groups,id',
        ]);

        $student->update(['group_id' => $validated['group_id']]);
        return response()->json(['message' => 'تم نقل الطالب للمجموعة بنجاح.', 'student' => $student->load('group')]);
    }

    public function storeStudent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'second_name' => 'nullable|string|max:255',
            'third_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'father_phone' => 'nullable|string|max:20',
            'academic_year' => 'required|string|in:prep_1,prep_2,prep_3,sec_1,sec_2,sec_3',
            'group_id' => 'nullable|uuid|exists:groups,id',
        ]);

        $studentCode = 'ST' . date('Y') . rand(1000, 9999);

        $user = \App\Models\User::create([
            'name' => "{$validated['first_name']} {$validated['last_name']}",
            'email' => strtolower($studentCode) . '@student.local',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'phone' => $validated['phone'] ?? null,
            'status' => \App\Enums\UserStatus::Active,
        ]);
        $user->assignRole('student');

        $student = Student::create([
            'user_id' => $user->id,
            'student_code' => $studentCode,
            'first_name' => $validated['first_name'],
            'second_name' => $validated['second_name'] ?? '',
            'third_name' => $validated['third_name'] ?? '',
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'] ?? null,
            'father_phone' => $validated['father_phone'] ?? null,
            'academic_year' => $validated['academic_year'],
            'group_id' => $validated['group_id'] ?? null,
        ]);

        return response()->json([
            'message' => "تم إضافة الطالب بنجاح! كود الطالب: {$studentCode}",
            'student' => $student->load(['group'])
        ], 201);
    }
}
