<?php

namespace App\Services;

use App\Models\Entitlement;
use App\Models\Lecture;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

class VideoAccessService
{
    /**
     * Check if a user is entitled to watch a specific lecture.
     */
    public function canAccess(User $user, Lecture $lecture): bool
    {
        // 1. Super admin has full access
        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return true;
        }

        // 2. Instructor can access their own course lectures
        if ($user->hasRole('instructor')) {
            $lecture->loadMissing('section.course');
            return $lecture->section->course->instructor_id === $user->id;
        }

        // 3. Assistants can access assigned course lectures
        if ($user->hasRole('assistant')) {
            $lecture->loadMissing('section.course');
            $courseId = $lecture->section->course->id;
            return \App\Models\CourseAssistant::where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->exists();
        }

        // 4. Students must have a valid Entitlement OR be enrolled in a Free course
        $student = Student::where('user_id', $user->id)->first();
        if (!$student) {
            return false;
        }

        // Check strict entitlement
        $hasEntitlement = Entitlement::where('student_id', $student->id)
            ->where('lecture_id', $lecture->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->exists();
        if ($hasEntitlement) {
            // Entitled students must still pass blocking exams
            if ($this->isBlockedByExam($user, $lecture, 'video')) {
                return false;
            }
            return true;
        }

        // Check if course is free and student is enrolled
        $lecture->loadMissing('section.course');
        $course = $lecture->section->course;
        if ($course && floatval($course->price) == 0) {
            $isEnrolled = \App\Models\Enrollment::where('student_id', $student->id)
                ->where('course_id', $course->id)
                ->where('status', 'active')
                ->exists();
            
            if ($isEnrolled) {
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
        
        $lecture->loadMissing('section.course');
        $course = $lecture->section->course;
        if (!$course) {
            return false;
        }

        if ($user->hasRole('instructor') && $course->instructor_id === $user->id) {
            return false;
        }

        $student = Student::where('user_id', $user->id)->first();
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
        $blockingExams = \App\Models\Exam::where('is_blocking', true)
            ->whereHas('lecture.section', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->with(['lecture.section'])
            ->get();

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
                $passed = \App\Models\ExamAttempt::where('exam_id', $exam->id)
                    ->where('student_id', $student->id)
                    ->whereNotNull('submitted_at')
                    ->where('score', '>=', $exam->pass_percentage)
                    ->exists();

                if (!$passed) {
                    return true; // Blocked!
                }
            }
        }

        return false;
    }

    /**
     * Generate a short-lived token bound to user, lecture, IP and expiration.
     */
    public function generateSignedToken(User $user, Lecture $lecture, string $ipAddress): string
    {
        $payload = [
            'user_id' => $user->id,
            'lecture_id' => $lecture->id,
            'ip' => $ipAddress,
            'expires_at' => now()->addMinutes(5)->timestamp,
        ];

        return Crypt::encrypt($payload);
    }

    /**
     * Validate the signed token.
     */
    public function validateToken(string $token, Lecture $lecture, string $ipAddress): bool
    {
        try {
            $payload = Crypt::decrypt($token);

            if (!is_array($payload)) {
                return false;
            }

            // Validate expiration
            if (($payload['expires_at'] ?? 0) < now()->timestamp) {
                return false;
            }

            // Validate lecture binding
            if (($payload['lecture_id'] ?? '') !== $lecture->id) {
                return false;
            }

            // Validate IP binding
            if (($payload['ip'] ?? '') !== $ipAddress) {
                return false;
            }

            // Double check if user still exists and is active
            $user = User::find($payload['user_id'] ?? null);
            if (!$user || $user->status !== \App\Enums\UserStatus::Active->value) {
                return false;
            }

            // Check entitlement access
            return $this->canAccess($user, $lecture);

        } catch (\Exception $e) {
            return false;
        }
    }
}
