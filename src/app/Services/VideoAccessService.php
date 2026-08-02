<?php

namespace App\Services;

use App\Models\Entitlement;
use App\Models\Lecture;
use App\Models\Student;
use App\Models\User;

class VideoAccessService
{
    protected array $studentCache = [];
    protected array $courseBlockingExams = [];
    protected array $studentPassedExams = [];
    protected array $courseAssistants = [];
    protected array $enrollmentsCache = [];
    protected array $entitlementsCache = [];

    protected function getStudent(User $user): ?Student
    {
        if (app()->runningUnitTests() || !array_key_exists($user->id, $this->studentCache)) {
            $this->studentCache[$user->id] = Student::where('user_id', $user->id)->first();
        }
        return $this->studentCache[$user->id];
    }

    protected function isAssistantForCourse(User $user, string $courseId): bool
    {
        $cacheKey = $user->id . '-' . $courseId;
        if (app()->runningUnitTests() || !isset($this->courseAssistants[$cacheKey])) {
            $this->courseAssistants[$cacheKey] = \App\Models\CourseAssistant::where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->exists();
        }
        return $this->courseAssistants[$cacheKey];
    }

    protected function getBlockingExams(string $courseId): \Illuminate\Database\Eloquent\Collection
    {
        if (app()->runningUnitTests() || !isset($this->courseBlockingExams[$courseId])) {
            $this->courseBlockingExams[$courseId] = \App\Models\Exam::where('is_blocking', true)
                ->whereHas('lecture.section', function ($q) use ($courseId) {
                    $q->where('course_id', $courseId);
                })
                ->with(['lecture.section'])
                ->get();
        }
        return $this->courseBlockingExams[$courseId];
    }

    protected function loadStudentPassedExams(Student $student, string $courseId): void
    {
        if (app()->runningUnitTests() || !isset($this->studentPassedExams[$student->id])) {
            $this->studentPassedExams[$student->id] = \App\Models\ExamAttempt::where('student_id', $student->id)
                ->whereNotNull('submitted_at')
                ->whereHas('exam.lecture.section', function ($q) use ($courseId) {
                    $q->where('course_id', $courseId);
                })
                ->get()
                ->filter(fn($attempt) => $attempt->score >= ($attempt->exam->pass_percentage ?? 0))
                ->pluck('exam_id')
                ->unique()
                ->toArray();
        }
    }

    protected function hasPassedExam(Student $student, string $examId): bool
    {
        return in_array($examId, $this->studentPassedExams[$student->id] ?? []);
    }

    protected function isEnrolled(Student $student, string $courseId): bool
    {
        $cacheKey = $student->id . '-' . $courseId;
        if (app()->runningUnitTests() || !isset($this->enrollmentsCache[$cacheKey])) {
            $this->enrollmentsCache[$cacheKey] = \App\Models\Enrollment::where('student_id', $student->id)
                ->where('course_id', $courseId)
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
                })
                ->exists();
        }
        return $this->enrollmentsCache[$cacheKey];
    }

    protected function hasEntitlement(Student $student, string $lectureId): bool
    {
        return Entitlement::where('student_id', $student->id)
            ->where('lecture_id', $lectureId)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Check if a user is entitled to watch a specific lecture.
     */
    public function canAccess(User $user, Lecture $lecture): bool
    {
        // 1. Super admin has full access
        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return true;
        }

        // 2. Instructor can access their own lectures (standalone or course)
        if ($user->hasRole('instructor')) {
            return $lecture->resolveInstructorId() === $user->id;
        }

        // 3. Assistants can access assigned course lectures
        if ($user->hasRole('assistant')) {
            if ($lecture->isStandalone()) {
                return false;
            }
            $lecture->loadMissing('section.course');
            $courseId = $lecture->section?->course?->id;
            return $courseId ? $this->isAssistantForCourse($user, $courseId) : false;
        }

        // 4. Students must have a valid Entitlement OR be enrolled in the course
        $student = $this->getStudent($user);
        if (!$student) {
            return false;
        }

        // Check strict entitlement
        if ($this->hasEntitlement($student, $lecture->id)) {
            // Entitled students must still pass blocking exams
            if ($this->isBlockedByExam($user, $lecture, 'video')) {
                return false;
            }
            return true;
        }

        // Check if student is enrolled in the course (free or paid)
        if (! $lecture->isStandalone()) {
            $lecture->loadMissing('section.course');
            $course = $lecture->section?->course;
            if ($course && $this->isEnrolled($student, $course->id)) {
                if ($this->isBlockedByExam($user, $lecture, 'video')) {
                    return false;
                }
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a content item is blocked by preceding exams.
     */
    public function isBlockedByExam(User $user, Lecture $lecture, string $itemType = 'video', ?string $itemId = null): bool
    {
        // Admins and instructors are never blocked
        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return false;
        }
        
        if ($lecture->isStandalone() || !$lecture->section) {
            return false;
        }

        $lecture->loadMissing('section.course');
        $course = $lecture->section?->course;
        if (!$course) {
            return false;
        }

        if ($user->hasRole('instructor') && $course->instructor_id === $user->id) {
            return false;
        }

        $student = $this->getStudent($user);
        if (!$student) {
            return true; // Block by default if student record not found
        }

        // Determine target sort order
        $targetSortOrder = 0; // Default for video/lecture_access
        if (($itemType === 'exam' || $itemType === 'assignment') && $itemId) {
            $currentExam = \App\Models\Exam::find($itemId);
            if ($currentExam) {
                $targetSortOrder = $currentExam->sort_order;
            }
        }

        // Get all blocking exams in the course
        $blockingExams = $this->getBlockingExams($course->id);

        // Pre-load all passed exams for this student in this course
        $this->loadStudentPassedExams($student, $course->id);

        foreach ($blockingExams as $exam) {
            // Don't block itself
            if (($itemType === 'exam' || $itemType === 'assignment') && $exam->id === $itemId) {
                continue;
            }

            $examLecture = $exam->lecture;
            $examSection = $examLecture->section;
            $currentSection = $lecture->section;

            if (!$examSection || !$currentSection) {
                continue;
            }

            // Determine if this exam precedes the target item
            $precedes = false;
            if ($examSection->sort_order < $currentSection->sort_order) {
                $precedes = true;
            } elseif ($examSection->sort_order === $currentSection->sort_order) {
                if ($examLecture->sort_order < $lecture->sort_order) {
                    $precedes = true;
                } elseif ($examLecture->sort_order === $lecture->sort_order) {
                    if ($itemType !== 'lecture_access') {
                        // For video (0), only exams with sort_order < 0 precede it.
                        // For other exams/assignments, lower sort_order precedes.
                        if ($exam->sort_order < $targetSortOrder) {
                            $precedes = true;
                        }
                    }
                }
            }

            if ($precedes) {
                // Check if student passed this exam
                if (!$this->hasPassedExam($student, $exam->id)) {
                    return true; // Blocked!
                }
            }
        }

        return false;
    }

    /**
     * Generate a signed security token for video playback streams.
     */
    public function generateSignedToken(User $user, Lecture $lecture, string $ip): string
    {
        $payload = [
            'user_id' => $user->id,
            'lecture_id' => $lecture->id,
            'ip' => $ip,
            'expires_at' => now()->addMinutes(5)->timestamp, // 5 minutes expiration
        ];

        return \Illuminate\Support\Facades\Crypt::encrypt($payload);
    }

    /**
     * Validate the playback stream security token.
     */
    public function validateToken(string $token, Lecture $lecture, string $ip): bool
    {
        try {
            $payload = \Illuminate\Support\Facades\Crypt::decrypt($token);

            if (!is_array($payload)) {
                return false;
            }

            if (!isset($payload['user_id']) || !isset($payload['lecture_id']) || !isset($payload['ip']) || !isset($payload['expires_at'])) {
                return false;
            }

            if ($payload['lecture_id'] !== $lecture->id) {
                return false;
            }

            if ($payload['ip'] !== $ip) {
                return false;
            }

            if (now()->timestamp > $payload['expires_at']) {
                return false;
            }

            $user = User::find($payload['user_id']);
            if (!$user) {
                return false;
            }

            // Check if user is still active and has access to the lecture
            if ($user->status !== \App\Enums\UserStatus::Active) {
                return false;
            }

            return $this->canAccess($user, $lecture);

        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return false;
        }
    }
}
