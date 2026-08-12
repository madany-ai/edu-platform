<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function send(User $user, string $title, string $body): Notification
    {
        $notification = Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
        ]);

        $this->dispatchWebhook([
            'event' => 'user_notification',
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
        ]);

        return $notification;
    }

    public function notifyAttendance(Student $student, string $status, string $topic, string $date): ?Notification
    {
        if (! $student->user) {
            return null;
        }

        $title = 'تحديث حالة الحضور';
        $body = match ($status) {
            'absent' => "⚠️ تنبيه غياب: تم تسجيل غيابك في حصة ({$topic}) بتاريخ {$date}.",
            'late' => "⏳ تنبيه تأخير: تم تسجيل تأخيرك في حصة ({$topic}) بتاريخ {$date}.",
            'guest' => "👤 تم تسجيل حضورك كضيف في حصة ({$topic}) بتاريخ {$date}.",
            default => "✅ تم تسجيل حضورك بنجاح في حصة ({$topic}) بتاريخ {$date}.",
        };

        $notification = Notification::create([
            'user_id' => $student->user->id,
            'title' => $title,
            'body' => $body,
        ]);

        $this->dispatchWebhook([
            'event' => 'attendance_recorded',
            'student_code' => $student->student_code,
            'status' => $status,
            'topic' => $topic,
            'date' => $date,
        ]);

        return $notification;
    }

    public function notifyCenterGrade(Student $student, string $examName, float $score, float $totalMarks): ?Notification
    {
        if (! $student->user) {
            return null;
        }

        $percentage = $totalMarks > 0 ? round(($score / $totalMarks) * 100, 1) : 0;
        $title = 'نتيجة امتحان ورقي جديد';
        $body = "📊 تم رصد درجتك في امتحان ({$examName}): {$score} من {$totalMarks} (النسبة: {$percentage}%).";

        $notification = Notification::create([
            'user_id' => $student->user->id,
            'title' => $title,
            'body' => $body,
        ]);

        $this->dispatchWebhook([
            'event' => 'grade_recorded',
            'student_code' => $student->student_code,
            'exam_name' => $examName,
            'score' => $score,
            'total_marks' => $totalMarks,
            'percentage' => $percentage,
        ]);

        return $notification;
    }

    private function dispatchWebhook(array $payload): void
    {
        $webhookUrl = config('services.n8n.webhook_url') ?? env('N8N_WEBHOOK_URL');
        if (! $webhookUrl) {
            return;
        }

        try {
            Http::timeout(3)->post($webhookUrl, $payload);
        } catch (\Throwable $e) {
            Log::warning("Failed to dispatch n8n webhook: " . $e->getMessage());
        }
    }
}
