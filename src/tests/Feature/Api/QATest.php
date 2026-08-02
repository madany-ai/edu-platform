<?php

use App\Models\Course;
use App\Models\CourseAssistant;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lecture;
use App\Models\QuestionsPost;
use App\Models\QuestionReply;
use App\Models\Student;
use App\Models\User;

beforeEach(function () {
    $this->instructor = User::factory()->create(['status' => 'active']);
    $this->instructor->assignRole('instructor');

    $this->assistantUser = User::factory()->create(['status' => 'active']);
    $this->assistantUser->assignRole('assistant');

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
        'description' => 'Math',
        'price' => 0,
        'status' => 'published',
        'instructor_id' => $this->instructor->id,
    ]);

    $this->section = $this->course->sections()->create(['title' => 'Month 1', 'sort_order' => 1]);
    $this->lecture = $this->section->lectures()->create([
        'title' => 'Lecture 1',
        'description' => 'Content',
        'sort_order' => 1,
    ]);

    Enrollment::create([
        'student_id' => $this->student->id,
        'course_id' => $this->course->id,
        'status' => 'active',
        'source' => 'manual',
        'started_at' => now(),
    ]);

    CourseAssistant::create([
        'course_id' => $this->course->id,
        'user_id' => $this->assistantUser->id,
    ]);
});

describe('Student Posts Questions', function () {
    it('student posts a question under a lecture', function () {
        $response = $this->actingAs($this->studentUser)
            ->postJson("/api/lectures/{$this->lecture->id}/questions", [
                'body' => 'ما الفرق بين المعادلة التربيعية والخطية؟',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'تم نشر سؤالك بنجاح.',
            ])
            ->assertJsonStructure([
                'message',
                'question' => [
                    'id',
                    'body',
                    'student' => ['id', 'name'],
                    'lecture' => ['id', 'title', 'course'],
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('questions_posts', [
            'lecture_id' => $this->lecture->id,
            'student_id' => $this->student->id,
            'body' => 'ما الفرق بين المعادلة التربيعية والخطية؟',
        ]);
    });

    it('student cannot post empty question', function () {
        $response = $this->actingAs($this->studentUser)
            ->postJson("/api/lectures/{$this->lecture->id}/questions", [
                'body' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    });

    it('student cannot post question without body field', function () {
        $response = $this->actingAs($this->studentUser)
            ->postJson("/api/lectures/{$this->lecture->id}/questions", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    });

    it('student cannot post question exceeding max length', function () {
        $response = $this->actingAs($this->studentUser)
            ->postJson("/api/lectures/{$this->lecture->id}/questions", [
                'body' => str_repeat('a', 5001),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    });

    it('unauthenticated user cannot post question', function () {
        $response = $this->postJson("/api/lectures/{$this->lecture->id}/questions", [
            'body' => 'سؤال؟',
        ]);

        $response->assertStatus(401);
    });

    it('notification is sent to instructor when question is posted', function () {
        $this->actingAs($this->studentUser)
            ->postJson("/api/lectures/{$this->lecture->id}/questions", [
                'body' => 'سؤال جديد؟',
            ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->instructor->id,
            'title' => 'سؤال جديد',
        ]);
    });

    it('notification is sent to assistant when question is posted', function () {
        $this->actingAs($this->studentUser)
            ->postJson("/api/lectures/{$this->lecture->id}/questions", [
                'body' => 'سؤال للمساعد؟',
            ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->assistantUser->id,
            'title' => 'سؤال جديد',
        ]);
    });
});

describe('List Questions for Lecture', function () {
    it('student sees questions they posted for a lecture', function () {
        QuestionsPost::create([
            'lecture_id' => $this->lecture->id,
            'student_id' => $this->student->id,
            'body' => 'سؤال 1',
        ]);

        $response = $this->actingAs($this->studentUser)
            ->getJson("/api/lectures/{$this->lecture->id}/questions");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });

    it('instructor sees all questions for their lecture', function () {
        $otherStudent = User::factory()->create(['status' => 'active']);
        $otherStudent->assignRole('student');
        $otherStd = Student::create([
            'user_id' => $otherStudent->id,
            'first_name' => 'Other',
            'second_name' => 'Student',
            'third_name' => '',
            'last_name' => 'Test',
            'phone' => '01011111111',
            'father_phone' => '01111111111',
            'mother_phone' => '01211111111',
            'guardian_job' => 'Engineer',
            'gender' => 'female',
            'birth_date' => '2006-01-01',
        ]);

        QuestionsPost::create([
            'lecture_id' => $this->lecture->id,
            'student_id' => $this->student->id,
            'body' => 'سؤال طالب 1',
        ]);
        QuestionsPost::create([
            'lecture_id' => $this->lecture->id,
            'student_id' => $otherStd->id,
            'body' => 'سؤال طالب 2',
        ]);

        $response = $this->actingAs($this->instructor)
            ->getJson("/api/lectures/{$this->lecture->id}/questions");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    });

    it('questions are paginated', function () {
        for ($i = 0; $i < 25; $i++) {
            QuestionsPost::create([
                'lecture_id' => $this->lecture->id,
                'student_id' => $this->student->id,
                'body' => "سؤال {$i}",
            ]);
        }

        $response = $this->actingAs($this->studentUser)
            ->getJson("/api/lectures/{$this->lecture->id}/questions?per_page=10");

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);
    });
});

describe('View Single Question with Replies', function () {
    it('returns question with replies', function () {
        $question = QuestionsPost::create([
            'lecture_id' => $this->lecture->id,
            'student_id' => $this->student->id,
            'body' => 'سؤال مهم',
        ]);

        QuestionReply::create([
            'question_id' => $question->id,
            'user_id' => $this->instructor->id,
            'body' => 'إجابة المدرس',
        ]);

        $response = $this->actingAs($this->studentUser)
            ->getJson("/api/questions/{$question->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'body',
                    'student',
                    'lecture',
                    'replies' => [
                        '*' => ['id', 'body', 'user', 'created_at'],
                    ],
                ],
            ]);
    });
});

describe('Reply to Question', function () {
    it('instructor can reply to a question', function () {
        $question = QuestionsPost::create([
            'lecture_id' => $this->lecture->id,
            'student_id' => $this->student->id,
            'body' => 'سؤال؟',
        ]);

        $response = $this->actingAs($this->instructor)
            ->postJson("/api/questions/{$question->id}/replies", [
                'body' => 'إجابة المدرس',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'تم إضافة الرد بنجاح.',
            ]);

        $this->assertDatabaseHas('question_replies', [
            'question_id' => $question->id,
            'user_id' => $this->instructor->id,
            'body' => 'إجابة المدرس',
        ]);
    });

    it('assistant can reply to a question', function () {
        $question = QuestionsPost::create([
            'lecture_id' => $this->lecture->id,
            'student_id' => $this->student->id,
            'body' => 'سؤال؟',
        ]);

        $response = $this->actingAs($this->assistantUser)
            ->postJson("/api/questions/{$question->id}/replies", [
                'body' => 'إجابة المساعد',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('question_replies', [
            'question_id' => $question->id,
            'user_id' => $this->assistantUser->id,
            'body' => 'إجابة المساعد',
        ]);
    });

    it('student can reply to a question', function () {
        $question = QuestionsPost::create([
            'lecture_id' => $this->lecture->id,
            'student_id' => $this->student->id,
            'body' => 'سؤال؟',
        ]);

        $response = $this->actingAs($this->studentUser)
            ->postJson("/api/questions/{$question->id}/replies", [
                'body' => 'توضيح من الطالب',
            ]);

        $response->assertStatus(201);
    });

    it('cannot reply with empty body', function () {
        $question = QuestionsPost::create([
            'lecture_id' => $this->lecture->id,
            'student_id' => $this->student->id,
            'body' => 'سؤال؟',
        ]);

        $response = $this->actingAs($this->instructor)
            ->postJson("/api/questions/{$question->id}/replies", [
                'body' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    });

    it('notification is sent to question author when replied', function () {
        $question = QuestionsPost::create([
            'lecture_id' => $this->lecture->id,
            'student_id' => $this->student->id,
            'body' => 'سؤال؟',
        ]);

        $this->actingAs($this->instructor)
            ->postJson("/api/questions/{$question->id}/replies", [
                'body' => 'رد المدرس',
            ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->studentUser->id,
            'title' => 'رد على سؤالك',
        ]);
    });
});

describe('My Questions', function () {
    it('student can see their own questions', function () {
        QuestionsPost::create([
            'lecture_id' => $this->lecture->id,
            'student_id' => $this->student->id,
            'body' => 'سؤالي 1',
        ]);
        QuestionsPost::create([
            'lecture_id' => $this->lecture->id,
            'student_id' => $this->student->id,
            'body' => 'سؤالي 2',
        ]);

        $response = $this->actingAs($this->studentUser)
            ->getJson('/api/my-questions');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    });

    it('student does not see other students questions in my-questions', function () {
        $otherStudent = User::factory()->create(['status' => 'active']);
        $otherStudent->assignRole('student');
        $otherStd = Student::create([
            'user_id' => $otherStudent->id,
            'first_name' => 'Other',
            'second_name' => 'Student',
            'third_name' => '',
            'last_name' => 'Test',
            'phone' => '01022222222',
            'father_phone' => '01122222222',
            'mother_phone' => '01222222222',
            'guardian_job' => 'Doctor',
            'gender' => 'female',
            'birth_date' => '2006-01-01',
        ]);

        QuestionsPost::create([
            'lecture_id' => $this->lecture->id,
            'student_id' => $this->student->id,
            'body' => 'سؤالي',
        ]);
        QuestionsPost::create([
            'lecture_id' => $this->lecture->id,
            'student_id' => $otherStd->id,
            'body' => 'سؤال غيري',
        ]);

        $response = $this->actingAs($this->studentUser)
            ->getJson('/api/my-questions');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });
});

describe('Delete Question', function () {
    it('student can delete their own question', function () {
        $question = QuestionsPost::create([
            'lecture_id' => $this->lecture->id,
            'student_id' => $this->student->id,
            'body' => 'سؤال للحذف',
        ]);

        $response = $this->actingAs($this->studentUser)
            ->deleteJson("/api/questions/{$question->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'تم حذف السؤال بنجاح.']);

        $this->assertDatabaseMissing('questions_posts', ['id' => $question->id]);
    });

    it('student cannot delete another student question', function () {
        $otherStudent = User::factory()->create(['status' => 'active']);
        $otherStudent->assignRole('student');
        $otherStd = Student::create([
            'user_id' => $otherStudent->id,
            'first_name' => 'Other',
            'second_name' => 'Student',
            'third_name' => '',
            'last_name' => 'Test',
            'phone' => '01033333333',
            'father_phone' => '01133333333',
            'mother_phone' => '01233333333',
            'guardian_job' => 'Engineer',
            'gender' => 'male',
            'birth_date' => '2006-01-01',
        ]);

        $question = QuestionsPost::create([
            'lecture_id' => $this->lecture->id,
            'student_id' => $otherStd->id,
            'body' => 'سؤال غيري',
        ]);

        $response = $this->actingAs($this->studentUser)
            ->deleteJson("/api/questions/{$question->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('questions_posts', ['id' => $question->id]);
    });

    it('deleting a question cascades to its replies', function () {
        $question = QuestionsPost::create([
            'lecture_id' => $this->lecture->id,
            'student_id' => $this->student->id,
            'body' => 'سؤال مع ردود',
        ]);

        QuestionReply::create([
            'question_id' => $question->id,
            'user_id' => $this->instructor->id,
            'body' => 'رد 1',
        ]);

        $this->actingAs($this->studentUser)
            ->deleteJson("/api/questions/{$question->id}");

        $this->assertDatabaseMissing('question_replies', ['question_id' => $question->id]);
    });
});

describe('Delete Reply', function () {
    it('user can delete their own reply', function () {
        $question = QuestionsPost::create([
            'lecture_id' => $this->lecture->id,
            'student_id' => $this->student->id,
            'body' => 'سؤال؟',
        ]);

        $reply = QuestionReply::create([
            'question_id' => $question->id,
            'user_id' => $this->instructor->id,
            'body' => 'رد للحذف',
        ]);

        $response = $this->actingAs($this->instructor)
            ->deleteJson("/api/replies/{$reply->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'تم حذف الرد بنجاح.']);

        $this->assertDatabaseMissing('question_replies', ['id' => $reply->id]);
    });

    it('user cannot delete another users reply', function () {
        $question = QuestionsPost::create([
            'lecture_id' => $this->lecture->id,
            'student_id' => $this->student->id,
            'body' => 'سؤال؟',
        ]);

        $reply = QuestionReply::create([
            'question_id' => $question->id,
            'user_id' => $this->instructor->id,
            'body' => 'رد المدرس',
        ]);

        $response = $this->actingAs($this->studentUser)
            ->deleteJson("/api/replies/{$reply->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('question_replies', ['id' => $reply->id]);
    });
});

describe('Instructor and Assistant Questions Endpoint', function () {
    it('instructor can see questions for their courses', function () {
        QuestionsPost::create([
            'lecture_id' => $this->lecture->id,
            'student_id' => $this->student->id,
            'body' => 'سؤال للمدرس',
        ]);

        $response = $this->actingAs($this->instructor)
            ->getJson('/api/instructor/questions');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });

    it('instructor does not see questions from other instructors courses', function () {
        $otherInstructor = User::factory()->create(['status' => 'active']);
        $otherInstructor->assignRole('instructor');

        $otherCourse = Course::create([
            'title' => 'Other Course',
            'description' => 'Other',
            'price' => 0,
            'status' => 'published',
            'instructor_id' => $otherInstructor->id,
        ]);

        $otherSection = $otherCourse->sections()->create(['title' => 'S1', 'sort_order' => 1]);
        $otherLecture = $otherSection->lectures()->create([
            'title' => 'L1',
            'description' => 'D',
            'sort_order' => 1,
        ]);

        QuestionsPost::create([
            'lecture_id' => $this->lecture->id,
            'student_id' => $this->student->id,
            'body' => 'سؤال مدرسي',
        ]);
        QuestionsPost::create([
            'lecture_id' => $otherLecture->id,
            'student_id' => $this->student->id,
            'body' => 'سؤال مدرس آخر',
        ]);

        $response = $this->actingAs($this->instructor)
            ->getJson('/api/instructor/questions');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });

    it('assistant can see questions for assigned courses', function () {
        QuestionsPost::create([
            'lecture_id' => $this->lecture->id,
            'student_id' => $this->student->id,
            'body' => 'سؤال للمساعد',
        ]);

        $response = $this->actingAs($this->assistantUser)
            ->getJson('/api/assistant/questions');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });

    it('student cannot access instructor questions endpoint', function () {
        $response = $this->actingAs($this->studentUser)
            ->getJson('/api/instructor/questions');

        $response->assertStatus(403);
    });
});
