<?php

namespace App\Services;

use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\CourseReview;
use App\Models\Student;
use Illuminate\Pagination\LengthAwarePaginator;

class CourseService
{
    public function listPublished(array $filters = []): LengthAwarePaginator
    {
        $query = Course::with(['category', 'instructor'])
            ->withCount(['sections', 'enrollments'])
            ->where('status', 'published');

        if (! empty($filters['category'])) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $filters['category']));
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        return $query->latest()->paginate(12);
    }

    public function findById(int $id): ?Course
    {
        return Course::with(['category', 'instructor', 'sections.lectures'])
            ->withCount(['sections', 'enrollments'])
            ->findOrFail($id);
    }

    public function create(array $data): Course
    {
        return Course::create($data);
    }

    public function update(Course $course, array $data): Course
    {
        $course->update($data);
        return $course->fresh();
    }

    public function delete(Course $course): bool
    {
        return $course->delete();
    }

    public function enrollStudent(Course $course, User $user): Enrollment
    {
        $student = Student::where('user_id', $user->id)->firstOrFail();

        return Enrollment::firstOrCreate([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ], [
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    public function getUserEnrollments(User $user)
    {
        $student = Student::where('user_id', $user->id)->first();

        if (! $student) {
            return collect();
        }

        return Enrollment::with(['course' => function ($q) {
            $q->withCount(['sections', 'enrollments']);
        }, 'course.category', 'course.instructor'])
            ->where('student_id', $student->id)
            ->latest()
            ->get();
    }

    public function createReview(Course $course, int $userId, int $rating, ?string $review): CourseReview
    {
        return CourseReview::updateOrCreate(
            ['user_id' => $userId, 'course_id' => $course->id],
            ['rating' => $rating, 'review' => $review]
        );
    }

    public function getInstructorCourses(int $instructorId)
    {
        return Course::with(['category'])
            ->withCount(['sections', 'enrollments'])
            ->where('instructor_id', $instructorId)
            ->latest()
            ->get();
    }
}
