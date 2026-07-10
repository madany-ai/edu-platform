<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Pagination\LengthAwarePaginator;

class CourseService
{
    public function listPublished(array $filters = []): LengthAwarePaginator
    {
        $query = Course::with(['instructor'])
            ->withCount(['sections', 'enrollments'])
            ->where('status', 'published');

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
        return Course::with(['instructor', 'sections.lectures'])
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

    public function getInstructorCourses(int $instructorId)
    {
        return Course::withCount(['sections', 'enrollments'])
            ->where('instructor_id', $instructorId)
            ->latest()
            ->get();
    }
}
