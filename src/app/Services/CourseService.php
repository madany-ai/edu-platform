<?php

namespace App\Services;

use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
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

    public function getInstructorCourses(int $instructorId)
    {
        return Course::withCount(['sections', 'enrollments'])
            ->where('instructor_id', $instructorId)
            ->latest()
            ->get();
    }
}
