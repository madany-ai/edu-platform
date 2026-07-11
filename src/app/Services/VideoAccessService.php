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

        // 4. Students must have a valid Entitlement
        $student = Student::where('user_id', $user->id)->first();
        if (!$student) {
            return false;
        }

        return Entitlement::where('student_id', $student->id)
            ->where('lecture_id', $lecture->id)
            ->exists();
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
            if (!$user || $user->status !== \App\Enums\UserStatus::Active) {
                return false;
            }

            // Check entitlement access
            return $this->canAccess($user, $lecture);

        } catch (\Exception $e) {
            return false;
        }
    }
}
