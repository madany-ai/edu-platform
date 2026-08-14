<?php

namespace App\Services;

use App\Enums\EnrollmentSource;
use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class EnrollmentService
{
    public function enrollStudent(Course $course, Student $student, EnrollmentSource $source = EnrollmentSource::Manual): Enrollment
    {
        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->first();

        if ($enrollment) {
            $enrollment->update([
                'status' => EnrollmentStatus::Active->value,
                'source' => $source->value,
                'started_at' => now(),
            ]);
            return $enrollment;
        }

        return Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => EnrollmentStatus::Active->value,
            'source' => $source->value,
            'started_at' => now(),
        ]);
    }

    public function enrollByUserId(Course $course, string $userId, EnrollmentSource $source = EnrollmentSource::Manual): Enrollment
    {
        $student = Student::where('user_id', $userId)->firstOrFail();
        return $this->enrollStudent($course, $student, $source);
    }

    public function revokeEnrollment(Course $course, Student $student): bool
    {
        return Enrollment::where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->update(['status' => EnrollmentStatus::Suspended->value]) > 0;
    }

    public function isEnrolled(string $userId, string $courseId): bool
    {
        $student = Student::where('user_id', $userId)->first();
        if (! $student) {
            return false;
        }

        return Enrollment::where('student_id', $student->id)
            ->where('course_id', $courseId)
            ->where('status', EnrollmentStatus::Active->value)
            ->exists();
    }

    public function getCourseEnrollments(Course $course): Collection
    {
        return Enrollment::with(['student.user'])
            ->where('course_id', $course->id)
            ->latest()
            ->get();
    }

    public function getStudentEnrollments(string $userId): \Illuminate\Support\Collection
    {
        $student = Student::where('user_id', $userId)->first();

        if (! $student) {
            return collect();
        }


        return Enrollment::with(['course' => function ($query) {
            $query->withoutGlobalScope(\App\Models\Scopes\AcademicYearScope::class)->with(['instructor', 'sections']);
        }])
            ->where('student_id', $student->id)
            ->latest()
            ->get();
    }

    public function getStudentEntitlements(string $userId): \Illuminate\Support\Collection
    {
        $student = Student::where('user_id', $userId)->first();
        if (!$student) {
            return collect();
        }

        return \App\Models\Entitlement::with(['lecture.instructor', 'lecture.video', 'lecture.section.course'])
            ->where('student_id', $student->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->get();
    }
}
