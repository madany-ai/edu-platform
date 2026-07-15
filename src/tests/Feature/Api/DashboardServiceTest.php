<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Notification;
use App\Models\Student;
use App\Models\StudentActivity;
use App\Models\StudentStatistic;
use App\Models\User;

beforeEach(function () {
    $this->instructor = User::factory()->create(['status' => 'active']);
    $this->instructor->assignRole('instructor');

    $this->studentUser = User::factory()->create(['status' => 'active']);
    $this->studentUser->assignRole('student');

    $this->student = Student::create([
        'user_id' => $this->studentUser->id,
        'first_name' => 'Test',
        'second_name' => 'Mid',
        'third_name' => 'Mid2',
        'last_name' => 'Student',
        'phone' => '01000000000',
        'father_phone' => '01100000000',
        'mother_phone' => '01200000000',
        'guardian_job' => 'Teacher',
        'gender' => 'male',
        'birth_date' => '2005-01-01',
    ]);

    $this->course = Course::create([
        'title' => 'Math Course',
        'description' => 'Advanced Math',
        'price' => 200,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $this->service = app(\App\Services\DashboardService::class);
});

it('getStudentStats returns correct completed courses count', function () {
    $section = $this->course->sections()->create(['title' => 'S1', 'sort_order' => 1]);
    $lecture = $section->lectures()->create(['title' => 'L1', 'sort_order' => 1]);

    Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);

    StudentActivity::create([
        'student_id' => $this->student->id,
        'type' => 'video_completed',
        'entity_type' => \App\Models\Lecture::class,
        'entity_id' => $lecture->id,
        'metadata' => ['duration' => 120],
    ]);

    $stats = $this->service->getStudentStats($this->student->id);

    expect($stats['completed_courses'])->toBe(1);
});

it('getStudentStats returns zero completed courses when lectures not all completed', function () {
    $section = $this->course->sections()->create(['title' => 'S1', 'sort_order' => 1]);
    $lecture1 = $section->lectures()->create(['title' => 'L1', 'sort_order' => 1]);
    $lecture2 = $section->lectures()->create(['title' => 'L2', 'sort_order' => 2]);

    Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);

    StudentActivity::create([
        'student_id' => $this->student->id,
        'type' => 'video_completed',
        'entity_type' => \App\Models\Lecture::class,
        'entity_id' => $lecture1->id,
        'metadata' => ['duration' => 120],
    ]);

    $stats = $this->service->getStudentStats($this->student->id);

    expect($stats['completed_courses'])->toBe(0);
});

it('getStudentStats returns values from student_statistics table', function () {
    StudentStatistic::create([
        'student_id' => $this->student->id,
        'completed_lectures' => 15,
        'total_watch_minutes' => 420,
        'average_exam_score' => 85.5,
    ]);

    $stats = $this->service->getStudentStats($this->student->id);

    expect($stats['completed_lectures'])->toBe(15)
        ->and($stats['total_watch_minutes'])->toBe(420)
        ->and($stats['average_exam_score'])->toBe(85.5);
});

it('getStudentStats defaults to zero when no student_statistics row', function () {
    $stats = $this->service->getStudentStats($this->student->id);

    expect($stats['completed_lectures'])->toBe(0)
        ->and($stats['total_watch_minutes'])->toBe(0)
        ->and($stats['average_exam_score'])->toBe(0);
});

it('getInstructorStats returns correct draft count', function () {
    Course::create([
        'title' => 'Draft Course',
        'description' => 'Draft',
        'price' => 0,
        'status' => 'draft',
        'instructor_id' => $this->instructor->id,
    ]);

    $stats = $this->service->getInstructorStats($this->instructor->id);

    expect($stats['courses']['draft'])->toBe(1)
        ->and($stats['courses']['total'])->toBe(2)
        ->and($stats['courses']['published'])->toBe(1);
});

it('getInstructorStats returns correct revenue', function () {
    $stats = $this->service->getInstructorStats($this->instructor->id);

    expect($stats['revenue']['total'])->toBe(200.0);
});

it('getInstructorStats returns correct pending enrollments', function () {
    $student2 = User::factory()->create(['status' => 'active']);
    $student2->assignRole('student');
    $studentRec2 = Student::create([
        'user_id' => $student2->id,
        'first_name' => 'S2',
        'second_name' => 'M',
        'third_name' => 'M2',
        'last_name' => 'L',
        'phone' => '01000000001',
        'father_phone' => '01100000001',
        'mother_phone' => '01200000001',
        'guardian_job' => 'Engineer',
        'gender' => 'female',
        'birth_date' => '2005-01-01',
    ]);

    Enrollment::create([
        'student_id' => $studentRec2->id,
        'course_id' => $this->course->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);

    $stats = $this->service->getInstructorStats($this->instructor->id);

    expect($stats['pending_enrollments'])->toBe(1)
        ->and($stats['students']['total'])->toBe(1);
});

it('getInstructorStats returns correct total lectures count', function () {
    $section = $this->course->sections()->create(['title' => 'S1', 'sort_order' => 1]);
    $section->lectures()->create(['title' => 'L1', 'sort_order' => 1]);
    $section->lectures()->create(['title' => 'L2', 'sort_order' => 2]);

    $stats = $this->service->getInstructorStats($this->instructor->id);

    expect($stats['content']['total_lectures'])->toBe(2);
});

it('getInstructorStats returns correct recent enrollments count', function () {
    $student2 = User::factory()->create(['status' => 'active']);
    $student2->assignRole('student');
    $studentRec2 = Student::create([
        'user_id' => $student2->id,
        'first_name' => 'S2',
        'second_name' => 'M',
        'third_name' => 'M2',
        'last_name' => 'L',
        'phone' => '01000000001',
        'father_phone' => '01100000001',
        'mother_phone' => '01200000001',
        'guardian_job' => 'Engineer',
        'gender' => 'female',
        'birth_date' => '2005-01-01',
    ]);

    Enrollment::create([
        'student_id' => $studentRec2->id,
        'course_id' => $this->course->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);

    $stats = $this->service->getInstructorStats($this->instructor->id);

    expect($stats['students']['recent_enrollments'])->toBe(1);
});

it('getInstructorStats returns zero stats for instructor with no courses', function () {
    $newInstructor = User::factory()->create(['status' => 'active']);
    $newInstructor->assignRole('instructor');

    $stats = $this->service->getInstructorStats($newInstructor->id);

    expect($stats['courses']['total'])->toBe(0)
        ->and($stats['courses']['published'])->toBe(0)
        ->and($stats['courses']['draft'])->toBe(0)
        ->and($stats['students']['total'])->toBe(0)
        ->and($stats['revenue']['total'])->toBe(0.0)
        ->and($stats['content']['total_lectures'])->toBe(0)
        ->and($stats['pending_enrollments'])->toBe(0);
});

it('getStudentRecentEnrollments returns enrollments', function () {
    Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);

    $enrollments = $this->service->getStudentRecentEnrollments($this->student->id);

    expect($enrollments)->toHaveCount(1)
        ->and($enrollments->first()->course_id)->toBe($this->course->id);
});

it('getStudentRecentEnrollments respects limit parameter', function () {
    $course2 = Course::create([
        'title' => 'Course 2',
        'description' => 'Desc',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now()->subDay(),
    ]);

    Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $course2->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);

    $enrollments = $this->service->getStudentRecentEnrollments($this->student->id, 1);

    expect($enrollments)->toHaveCount(1);
});
