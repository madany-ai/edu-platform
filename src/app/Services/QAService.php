<?php

namespace App\Services;

use App\Models\QuestionsPost;
use App\Models\QuestionReply;
use App\Models\Lecture;
use App\Models\Student;
use App\Models\User;
use App\Models\CourseAssistant;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class QAService
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function postQuestion(Lecture $lecture, ?Student $student, User $user, array $data): QuestionsPost
    {
        $question = QuestionsPost::create([
            'lecture_id' => $lecture->id,
            'student_id' => $student?->id,
            'user_id' => $user->id,
            'body' => $data['body'],
        ]);

        $question->load('student.user', 'user', 'lecture');

        $this->notifyInstructorAndAssistants($question);

        return $question;
    }

    public function getLectureQuestions(Lecture $lecture, ?Student $student, int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        $query = QuestionsPost::with(['student.user', 'user', 'replies.user'])
            ->withCount('replies')
            ->where('lecture_id', $lecture->id)
            ->latest();

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function getQuestion(QuestionsPost $question): QuestionsPost
    {
        return $question->load(['student.user', 'user', 'replies.user', 'lecture.section.course']);
    }

    public function replyToQuestion(QuestionsPost $question, User $user, array $data): QuestionReply
    {
        $reply = $question->replies()->create([
            'user_id' => $user->id,
            'body' => $data['body'],
        ]);

        $reply->load('user');

        $this->notifyQuestionAuthor($question, $user);

        return $reply;
    }

    public function getMyQuestions(Student $student, int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        return QuestionsPost::with(['lecture.section.course', 'user', 'replies.user'])
            ->withCount('replies')
            ->where('student_id', $student->id)
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function deleteQuestion(QuestionsPost $question, Student $student): bool
    {
        if ($question->student_id !== $student->id) {
            return false;
        }

        return $question->delete();
    }

    public function deleteReply(QuestionReply $reply, User $user): bool
    {
        if ($reply->user_id !== $user->id) {
            return false;
        }

        return $reply->delete();
    }

    public function getInstructorQuestions(User $instructor, int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        return QuestionsPost::with(['student.user', 'user', 'lecture.section.course', 'replies.user'])
            ->withCount('replies')
            ->whereHas('lecture.section.course', function ($q) use ($instructor) {
                $q->where('instructor_id', $instructor->id);
            })
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function getAssistantQuestions(User $assistant, int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        $courseIds = $assistant->assistedCourses()->pluck('courses.id');

        return QuestionsPost::with(['student.user', 'user', 'lecture.section.course', 'replies.user'])
            ->withCount('replies')
            ->whereHas('lecture.section.course', function ($q) use ($courseIds) {
                $q->whereIn('courses.id', $courseIds);
            })
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);
    }

    private function notifyInstructorAndAssistants(QuestionsPost $question): void
    {
        $course = $question->lecture->section->course;

        if ($course->instructor) {
            $this->notificationService->send(
                $course->instructor,
                'سؤال جديد',
                "طالب جديد سأل سؤالاً في محاضرة \"{$question->lecture->title}\""
            );
        }

        $assistantIds = CourseAssistant::where('course_id', $course->id)->pluck('user_id');

        $assistants = User::whereIn('id', $assistantIds)->get();

        foreach ($assistants as $assistant) {
            $this->notificationService->send(
                $assistant,
                'سؤال جديد',
                "طالب جديد سأل سؤالاً في محاضرة \"{$question->lecture->title}\""
            );
        }
    }

    private function notifyQuestionAuthor(QuestionsPost $question, User $responder): void
    {
        $student = $question->student;
        if ($student && $student->user) {
            $responderName = $responder->name;
            $this->notificationService->send(
                $student->user,
                'رد على سؤالك',
                "{$responderName} رد على سؤالك في محاضرة \"{$question->lecture->title}\""
            );
        }
    }
}
