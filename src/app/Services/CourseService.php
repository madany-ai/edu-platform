<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Pagination\LengthAwarePaginator;

class CourseService
{
    public function listPublished(array $filters = []): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;
        $page = request()->get('page', 1);

        if (! empty($search)) {
            $query = Course::with(['instructor'])
                ->withCount(['sections', 'enrollments'])
                ->where('status', 'published');

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });

            return $query->latest()->paginate(12);
        }

        // Skip cache in local environment to prevent stale/empty results during development
        if (app()->environment('local')) {
            return Course::with(['instructor'])
                ->withCount(['sections', 'enrollments'])
                ->where('status', 'published')
                ->latest()
                ->paginate(12);
        }

        $cacheKey = 'published_courses_page_' . $page;

        return \Illuminate\Support\Facades\Cache::tags(['courses'])->remember($cacheKey, now()->addHours(2), function () {
            return Course::with(['instructor'])
                ->withCount(['sections', 'enrollments'])
                ->where('status', 'published')
                ->latest()
                ->paginate(12);
        });
    }

    public function findById(int $id): Course
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
