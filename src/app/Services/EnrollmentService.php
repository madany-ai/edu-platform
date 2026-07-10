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
        return Enrollment::firstOrCreate(
            ['student_id' => $student->id, 'course_id' => $course->id],
            [
                'status' => EnrollmentStatus::Active->value,
                'source' => $source->value,
                'started_at' => now(),
            ]
        );
    }

    public function enrollByUserId(Course $course, int $userId, EnrollmentSource $source = EnrollmentSource::Manual): Enrollment
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

    public function isEnrolled(int $userId, int $courseId): bool
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

    public function getStudentEnrollments(int $userId): Collection
    {
        $student = Student::where('user_id', $userId)->first();

        if (! $student) {
            return collect();
        }

        return Enrollment::with(['course.instructor', 'course.sections'])
            ->where('student_id', $student->id)
            ->latest()
            ->get();
    }
}
