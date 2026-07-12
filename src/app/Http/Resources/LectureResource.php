<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LectureResource extends JsonResource
{
    private ?array $progressMap = null;

    public function setProgressMap(?array $map): self
    {
        $this->progressMap = $map;
        return $this;
    }

    public function toArray(Request $request): array
    {
        $videoData = null;
        if ($this->relationLoaded('video') && $this->video) {
            $videoPath = $this->video->video_path;
            $status = $this->video->status;
            
            // YouTube videos are always completed immediately
            if ($videoPath && (str_contains($videoPath, 'youtube.com') || str_contains($videoPath, 'youtu.be'))) {
                $status = 'completed';
            }

            $videoData = [
                'id' => $this->video->id,
                'video_path' => $videoPath,
                'status' => $status,
                'bunny_video_id' => $this->video->bunny_video_id,
                'duration' => $this->video->duration,
            ];
            
            if ($status === 'completed' && $videoPath) {
                if (str_contains($videoPath, 'youtube.com') || str_contains($videoPath, 'youtu.be')) {
                    $videoData['stream_url'] = $videoPath;
                    $videoData['stream_type'] = 'video/youtube';
                } else if (str_ends_with(strtolower($videoPath), '.mp4')) {
                    $url = \Illuminate\Support\Facades\Storage::disk('minio')
                        ->temporaryUrl($videoPath, now()->addHours(2));
                    $videoData['stream_url'] = str_replace('http://minio:9000', 'http://localhost:9000', $url);
                    $videoData['stream_type'] = 'video/mp4';
                } else {
                    $videoData['stream_url'] = route('lectures.stream', ['lecture' => $this->id]);
                    $videoData['stream_type'] = 'application/x-mpegURL';
                }
            }
        }

        $user = auth('sanctum')->user();
        $student = $user ? \App\Models\Student::where('user_id', $user->id)->first() : null;
        $accessService = app(\App\Services\VideoAccessService::class);

        $examsFormatted = [];
        if ($this->relationLoaded('exams')) {
            $examsFormatted = $this->exams->map(function ($exam) use ($student) {
                $latestAttempt = $student ? \App\Models\ExamAttempt::where('exam_id', $exam->id)
                    ->where('student_id', $student->id)
                    ->whereNotNull('submitted_at')
                    ->latest('submitted_at')
                    ->first() : null;

                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'sort_order' => $exam->sort_order,
                    'is_blocking' => $exam->is_blocking,
                    'pass_percentage' => $exam->pass_percentage,
                    'duration' => $exam->duration,
                    'latest_attempt' => $latestAttempt ? [
                        'id' => $latestAttempt->id,
                        'score' => $latestAttempt->score,
                        'submitted_at' => $latestAttempt->submitted_at,
                    ] : null,
                    'passed' => $latestAttempt ? ($latestAttempt->score >= $exam->pass_percentage) : false,
                ];
            });
        }

        $assignmentsFormatted = [];
        if ($this->relationLoaded('assignments')) {
            $assignmentsFormatted = $this->assignments->map(function ($assignment) use ($student) {
                $latestAttempt = $student ? \App\Models\ExamAttempt::where('exam_id', $assignment->id)
                    ->where('student_id', $student->id)
                    ->whereNotNull('submitted_at')
                    ->latest('submitted_at')
                    ->first() : null;

                return [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                    'sort_order' => $assignment->sort_order,
                    'is_blocking' => $assignment->is_blocking,
                    'pass_percentage' => $assignment->pass_percentage,
                    'duration' => $assignment->duration,
                    'latest_attempt' => $latestAttempt ? [
                        'id' => $latestAttempt->id,
                        'score' => $latestAttempt->score,
                        'submitted_at' => $latestAttempt->submitted_at,
                    ] : null,
                    'passed' => $latestAttempt ? ($latestAttempt->score >= $assignment->pass_percentage) : false,
                ];
            });
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'duration' => $this->duration,
            'sort_order' => $this->sort_order,
            'video' => $videoData,
            'files' => $this->whenLoaded('files'),
            'progress' => ($this->progressMap && array_key_exists($this->id, $this->progressMap))
                ? $this->progressMap[$this->id]
                : $this->progress,
            'has_exam' => $this->relationLoaded('exams') ? $this->exams->isNotEmpty() : \App\Models\Exam::where('lecture_id', $this->id)->where('is_assignment', false)->exists(),
            'has_assignment' => $this->relationLoaded('assignments') ? $this->assignments->isNotEmpty() : \App\Models\Exam::where('lecture_id', $this->id)->where('is_assignment', true)->exists(),
            'exams' => $examsFormatted,
            'assignments' => $assignmentsFormatted,
            'is_locked' => $user ? $accessService->isBlockedByExam($user, $this->resource, 'lecture_access') : false,
            'video_locked' => $user ? $accessService->isBlockedByExam($user, $this->resource, 'video') : false,
        ];
    }
}
