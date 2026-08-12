<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\CenterExam;
use App\Models\CenterGrade;
use App\Models\CommunicationLog;
use App\Models\Group;
use App\Models\Student;
use App\Models\StudentTransfer;
use App\Models\User;
use App\Services\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CenterManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Student $student;
    protected AcademicYear $academicYear;
    protected Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

        $this->user = User::factory()->create([
            'name' => 'طالب تجريبي',
            'email' => 'student@test.local',
            'status' => 'active',
        ]);
        $this->user->assignRole('student');

        $this->academicYear = AcademicYear::create([
            'name' => '2026 - 2027',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'is_active' => true,
        ]);

        $this->group = Group::create([
            'academic_year_id' => $this->academicYear->id,
            'academic_year' => 'prep_3',
            'name' => 'مجموعة الأحد والأربعاء 5 مساءً',
            'capacity' => 40,
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'user_id' => $this->user->id,
            'student_code' => 'ST2026001',
            'first_name' => 'أحمد',
            'second_name' => 'محمود',
            'third_name' => 'على',
            'last_name' => 'حسن',
            'phone' => '01000000001',
            'father_phone' => '01000000002',
            'mother_phone' => '01000000003',
            'guardian_job' => 'مهندس',
            'academic_year' => 'prep_3',
            'group_id' => $this->group->id,
            'gender' => 'male',
            'birth_date' => '2010-05-15',
            'is_verified' => true,
        ]);
    }

    public function test_can_create_academic_session_and_record_attendance(): void
    {
        $session = AcademicSession::create([
            'group_id' => $this->group->id,
            'date' => now()->format('Y-m-d'),
            'topic' => 'درس التفاعلات الكيميائية',
            'notes' => 'حصة هامة جداً',
        ]);

        $attendance = Attendance::create([
            'session_id' => $session->id,
            'student_id' => $this->student->id,
            'status' => 'present',
            'is_guest' => false,
        ]);

        $this->assertDatabaseHas('academic_sessions', [
            'id' => $session->id,
            'topic' => 'درس التفاعلات الكيميائية',
        ]);

        $this->assertDatabaseHas('attendances', [
            'session_id' => $session->id,
            'student_id' => $this->student->id,
            'status' => 'present',
        ]);
    }

    public function test_can_record_center_exam_grades(): void
    {
        $exam = CenterExam::create([
            'name' => 'امتحان شهر أكتوبر - العلوم',
            'total_marks' => 20,
            'date' => now()->format('Y-m-d'),
            'academic_year_id' => $this->academicYear->id,
            'group_id' => $this->group->id,
        ]);

        $grade = CenterGrade::create([
            'center_exam_id' => $exam->id,
            'student_id' => $this->student->id,
            'score' => 19.5,
        ]);

        $this->assertDatabaseHas('center_grades', [
            'center_exam_id' => $exam->id,
            'student_id' => $this->student->id,
            'score' => 19.5,
        ]);
    }

    public function test_can_transfer_student_to_another_group(): void
    {
        $newGroup = Group::create([
            'academic_year_id' => $this->academicYear->id,
            'academic_year' => 'prep_3',
            'name' => 'مجموعة السبت والثلاثاء 4 عصراً',
            'capacity' => 30,
            'is_active' => true,
        ]);

        StudentTransfer::create([
            'student_id' => $this->student->id,
            'from_group_id' => $this->group->id,
            'to_group_id' => $newGroup->id,
            'reason' => 'تغيير الموعد بناءً على طلب الطالب',
            'transferred_at' => now(),
        ]);

        $this->student->update(['group_id' => $newGroup->id]);

        $this->assertEquals($newGroup->id, $this->student->fresh()->group_id);
        $this->assertDatabaseHas('student_transfers', [
            'student_id' => $this->student->id,
            'from_group_id' => $this->group->id,
            'to_group_id' => $newGroup->id,
        ]);
    }

    public function test_can_record_communication_log(): void
    {
        $log = CommunicationLog::create([
            'student_id' => $this->student->id,
            'date' => now()->format('Y-m-d'),
            'contact_method' => 'اتصال هاتف',
            'reason' => 'متابعة المستوى الأكاديمي',
            'notes' => 'ولي الأمر متعاون جداً',
        ]);

        $this->assertDatabaseHas('communication_logs', [
            'student_id' => $this->student->id,
            'contact_method' => 'اتصال هاتف',
        ]);
    }

    public function test_student_center_api_endpoints(): void
    {
        $session = AcademicSession::create([
            'group_id' => $this->group->id,
            'date' => now()->format('Y-m-d'),
            'topic' => 'درس الحركة في اتجاه واحد',
        ]);

        Attendance::create([
            'session_id' => $session->id,
            'student_id' => $this->student->id,
            'status' => 'present',
        ]);

        $exam = CenterExam::create([
            'name' => 'اختبار منتصف الفصل',
            'total_marks' => 30,
            'date' => now()->format('Y-m-d'),
            'group_id' => $this->group->id,
        ]);

        CenterGrade::create([
            'center_exam_id' => $exam->id,
            'student_id' => $this->student->id,
            'score' => 28.5,
        ]);

        $token = $this->user->createToken('test')->plainTextToken;

        // Test my-attendance endpoint
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/center/my-attendance');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.stats.total', 1)
            ->assertJsonPath('data.stats.present', 1);

        // Test my-grades endpoint
        $responseGrades = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/center/my-grades');

        $responseGrades->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.0.score', 28.5);

        // Test my-group endpoint
        $responseGroup = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/center/my-group');

        $responseGroup->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'مجموعة الأحد والأربعاء 5 مساءً');

        // Test my-report endpoint
        $responseReport = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/center/my-report');

        $responseReport->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.student.code', 'ST2026001')
            ->assertJsonPath('data.attendance_summary.percentage', 100);
    }

    public function test_ranking_service_calculations(): void
    {
        $exam = CenterExam::create([
            'name' => 'امتحان عام',
            'total_marks' => 50,
            'date' => now()->format('Y-m-d'),
            'group_id' => $this->group->id,
        ]);

        CenterGrade::create([
            'center_exam_id' => $exam->id,
            'student_id' => $this->student->id,
            'score' => 45,
        ]);

        $rankings = RankingService::getGroupRankings($this->group->id);

        $this->assertCount(1, $rankings);
        $this->assertEquals(45, $rankings->first()->total_score);
        $this->assertEquals(90, $rankings->first()->percentage);
    }
}
