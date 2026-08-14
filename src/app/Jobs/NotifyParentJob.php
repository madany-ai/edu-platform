<?php

namespace App\Jobs;

use App\Models\Student;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyParentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $student;
    public $examName;
    public $score;
    public $totalMarks;

    /**
     * Create a new job instance.
     */
    public function __construct(Student $student, string $examName, float $score, float $totalMarks)
    {
        $this->student = $student;
        $this->examName = $examName;
        $this->score = $score;
        $this->totalMarks = $totalMarks;
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        $notificationService->notifyCenterGrade(
            $this->student,
            $this->examName,
            $this->score,
            $this->totalMarks
        );
    }
}
