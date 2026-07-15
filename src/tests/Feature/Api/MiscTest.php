<?php

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lecture;
use App\Models\Student;
use App\Models\User;
use App\Services\CodeGeneratorService;
use App\Services\VideoAccessService;

beforeEach(function () {
    $this->instructor = User::factory()->create(['status' => 'active']);
    $this->instructor->assignRole('instructor');
});

it('returns governorates list', function () {
    \App\Models\Governorate::create(['name' => 'القاهرة']);
    \App\Models\Governorate::create(['name' => 'الإسكندرية']);

    $response = $this->getJson('/api/governorates');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('status', 'success');
});

it('returns grade levels list sorted by sort_order', function () {
    \App\Models\GradeLevel::create(['name' => 'الثالث', 'sort_order' => 3]);
    \App\Models\GradeLevel::create(['name' => 'الأول', 'sort_order' => 1]);

    $response = $this->getJson('/api/grade-levels');

    $response->assertOk()
        ->assertJsonCount(2, 'data');

    $data = $response->json('data');
    expect($data[0]['name'])->toBe('الأول');
    expect($data[1]['name'])->toBe('الثالث');
});

it('code generator produces unique student codes', function () {
    $service = new CodeGeneratorService();

    $student1 = Student::create([
        'user_id' => User::factory()->create()->id,
        'first_name' => 'A',
        'second_name' => 'B',
        'third_name' => 'C',
        'last_name' => 'D',
        'phone' => '01000000001',
        'father_phone' => '01100000001',
        'mother_phone' => '01200000001',
        'guardian_job' => 'Engineer',
        'gender' => 'male',
        'birth_date' => '2005-01-01',
    ]);

    $student2 = Student::create([
        'user_id' => User::factory()->create()->id,
        'first_name' => 'E',
        'second_name' => 'F',
        'third_name' => 'G',
        'last_name' => 'H',
        'phone' => '01000000002',
        'father_phone' => '01100000002',
        'mother_phone' => '01200000002',
        'guardian_job' => 'Doctor',
        'gender' => 'female',
        'birth_date' => '2006-01-01',
    ]);

    expect($student1->student_code)->not->toBe($student2->student_code);
    expect($student1->student_code)->toStartWith('ST');
    expect($student2->student_code)->toStartWith('ST');
});

it('code generator produces unique course codes', function () {
    $code1 = (new CodeGeneratorService())->generateCourseCode();
    $code2 = (new CodeGeneratorService())->generateCourseCode();

    expect($code1)->not->toBe($code2);
    expect($code1)->toStartWith('CR');
    expect($code2)->toStartWith('CR');
});

it('code generator produces unique assistant codes', function () {
    $code1 = (new CodeGeneratorService())->generateAssistantCode();
    $code2 = (new CodeGeneratorService())->generateAssistantCode();

    expect($code1)->not->toBe($code2);
    expect($code1)->toStartWith('TA');
    expect($code2)->toStartWith('TA');
});

it('video access service grants access to super admin', function () {
    $admin = User::factory()->create(['status' => 'active']);
    $admin->assignRole('super_admin');

    $course = Course::create([
        'title' => 'Course',
        'description' => 'Desc',
        'price' => 100,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);
    $section = $course->sections()->create(['title' => 'S1', 'sort_order' => 1]);
    $lecture = $section->lectures()->create(['title' => 'L1', 'sort_order' => 1]);

    $service = app(VideoAccessService::class);
    expect($service->canAccess($admin, $lecture))->toBeTrue();
});

it('video access service grants access to course instructor', function () {
    $course = Course::create([
        'title' => 'Course',
        'description' => 'Desc',
        'price' => 100,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);
    $section = $course->sections()->create(['title' => 'S1', 'sort_order' => 1]);
    $lecture = $section->lectures()->create(['title' => 'L1', 'sort_order' => 1]);

    $service = app(VideoAccessService::class);
    expect($service->canAccess($this->instructor, $lecture))->toBeTrue();
});

it('video access service denies student without entitlement or enrollment', function () {
    $studentUser = User::factory()->create(['status' => 'active']);
    $studentUser->assignRole('student');

    $course = Course::create([
        'title' => 'Course',
        'description' => 'Desc',
        'price' => 100,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);
    $section = $course->sections()->create(['title' => 'S1', 'sort_order' => 1]);
    $lecture = $section->lectures()->create(['title' => 'L1', 'sort_order' => 1]);

    $service = app(VideoAccessService::class);
    expect($service->canAccess($studentUser, $lecture))->toBeFalse();
});

it('governorates returns empty when none exist', function () {
    $response = $this->getJson('/api/governorates');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

it('grade levels returns empty when none exist', function () {
    $response = $this->getJson('/api/grade-levels');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});
