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

    public function getStudentEnrollments(string $userId): Collection
    {
        $student = Student::where('user_id', $userId)->first();

        if (! $student) {
            return collect();
        }

        $enrollments = Enrollment::with(['course.instructor', 'course.sections'])
            ->where('student_id', $student->id)
            ->latest()
            ->get();

        $entitlementCourseIds = \App\Models\Entitlement::where('student_id', $student->id)
            ->with('lecture.section')
            ->get()
            ->map(fn($e) => $e->lecture->section->course_id ?? null)
            ->filter()
            ->unique();

        $enrolledCourseIds = $enrollments->pluck('course_id');
        $missingCourseIds = $entitlementCourseIds->diff($enrolledCourseIds);

        if ($missingCourseIds->isNotEmpty()) {
            $courses = \App\Models\Course::with(['instructor', 'sections'])
                ->whereIn('id', $missingCourseIds)
                ->get();

            foreach ($courses as $course) {
                $fakeEnrollment = new Enrollment([
                    'course_id' => $course->id,
                    'student_id' => $student->id,
                    'status' => 'active',
                    'source' => 'purchase',
                ]);
                // Ensure it has an ID for the frontend key (we can use course id)
                $fakeEnrollment->id = 'entitlement-fake-' . $course->id;
                $fakeEnrollment->started_at = now();
                $fakeEnrollment->created_at = now();
                $fakeEnrollment->setRelation('course', $course);
                $enrollments->push($fakeEnrollment);
            }
        }

        return $enrollments;
    }

    public function getStudentEntitlements(string $userId): \Illuminate\Support\Collection
    {
        $student = Student::where('user_id', $userId)->first();
        if (!$student) {
            return collect();
        }

        return \App\Models\Entitlement::where('student_id', $student->id)->get();
    }
}
