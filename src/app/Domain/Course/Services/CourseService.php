<?php

namespace App\Domain\Course\Services;

use App\Domain\Course\Models\Course;
use App\Domain\Course\Models\Enrollment;
use App\Domain\Course\Models\CourseReview;
use Illuminate\Pagination\LengthAwarePaginator;

class CourseService
{
    public function listPublished(array $filters = []): LengthAwarePaginator
    {
        $query = Course::with(['category', 'instructor'])
            ->withCount(['lessons', 'enrollments'])
            ->where('is_published', true);

        if (! empty($filters['category'])) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $filters['category']));
        }

        if (! empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        return $query->latest()->paginate(12);
    }

    public function findBySlug(string $slug): ?Course
    {
        return Course::with(['category', 'instructor', 'lessons'])
            ->withCount(['lessons', 'enrollments'])
            ->where('is_published', true)
            ->where('slug', $slug)
            ->first();
    }

    public function findById(int $id): ?Course
    {
        return Course::with(['category', 'instructor', 'lessons'])
            ->withCount(['lessons', 'enrollments'])
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

    public function enrollUser(Course $course, int $userId): Enrollment
    {
        return Enrollment::firstOrCreate([
            'user_id' => $userId,
            'course_id' => $course->id,
        ]);
    }

    public function updateProgress(Enrollment $enrollment, int $progress): Enrollment
    {
        $data = ['progress' => min(100, max(0, $progress))];

        if ($data['progress'] >= 100) {
            $data['completed_at'] = now();
        }

        $enrollment->update($data);
        return $enrollment->fresh();
    }

    public function getUserEnrollments(int $userId)
    {
        return Enrollment::with(['course' => function ($q) {
            $q->withCount(['lessons', 'enrollments']);
        }, 'course.category', 'course.instructor'])
            ->where('user_id', $userId)
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
            ->withCount(['lessons', 'enrollments'])
            ->where('instructor_id', $instructorId)
            ->latest()
            ->get();
    }
}
