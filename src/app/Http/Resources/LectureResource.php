<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LectureResource extends JsonResource
{
    private ?array $progressMap = null;
    private ?array $attemptsMap = null;
    private $student = null;
    private bool $hasStudentBeenSet = false;

    public function setProgressMap(?array $map): self
    {
        $this->progressMap = $map;
        return $this;
    }

    public function setAttemptsMap(?array $map): self
    {
        $this->attemptsMap = $map;
        return $this;
    }

    public function setStudent($student): self
    {
        $this->student = $student;
        $this->hasStudentBeenSet = true;
        return $this;
    }

    public function toArray(Request $request): array
    {
        $user = auth('sanctum')->user();
        
        if ($this->hasStudentBeenSet) {
            $student = $this->student;
        } else {
            $student = $user ? \App\Models\Student::where('user_id', $user->id)->first() : null;
        }
        
        $accessService = app(\App\Services\VideoAccessService::class);
        $hasAccess = $user ? $accessService->canAccess($user, $this->resource) : false;

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
                'status' => $status,
                'duration' => $this->video->duration,
            ];

            if ($hasAccess) {
                $videoData['video_path'] = $videoPath;
                $videoData['bunny_video_id'] = $this->video->bunny_video_id;

                if ($status === 'completed') {
                    if ($videoPath && (str_contains($videoPath, 'youtube.com') || str_contains($videoPath, 'youtu.be'))) {
                        // YouTube videos
                        $videoData['stream_url'] = $videoPath;
                        $videoData['stream_type'] = 'video/youtube';
                    } else if ($this->video->bunny_video_id) {
                        // Bunny Stream — signed embed URL
                        $bunnyService = app(\App\Services\BunnyStreamService::class);
                        $videoData['stream_url'] = $bunnyService->getSignedPlaybackUrl($this->video->bunny_video_id);
                        $videoData['stream_type'] = 'application/x-mpegURL';
                    } else if ($videoPath && str_ends_with(strtolower($videoPath), '.mp4')) {
                        // Legacy MP4 files — direct MinIO temporary URL
                        $url = \Illuminate\Support\Facades\Storage::disk('minio')
                            ->temporaryUrl($videoPath, now()->addHours(2));
                        $minioEndpoint = rtrim(config('filesystems.disks.minio.endpoint', 'http://minio:9000'), '/');
                        $publicUrl = config('filesystems.disks.minio.url', 'http://localhost:9000/lms-videos');
                        $parsed = parse_url($publicUrl);
                        $publicHost = ($parsed['scheme'] ?? 'http') . '://' . ($parsed['host'] ?? 'localhost') . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
                        $videoData['stream_url'] = str_replace($minioEndpoint, $publicHost, $url);
                        $videoData['stream_type'] = 'video/mp4';
                    }
                }
            }
        }

        $examsFormatted = [];
        if ($this->relationLoaded('exams')) {
            $examsFormatted = $this->exams->map(function ($exam) use ($student) {
                if ($this->attemptsMap && array_key_exists($exam->id, $this->attemptsMap)) {
                    $latestAttempt = $this->attemptsMap[$exam->id];
                } else {
                    $latestAttempt = $student ? \App\Models\ExamAttempt::where('exam_id', $exam->id)
                        ->where('student_id', $student->id)
                        ->whereNotNull('submitted_at')
                        ->latest('submitted_at')
                        ->first() : null;
                }

                $score = $latestAttempt ? (is_array($latestAttempt) ? ($latestAttempt['score'] ?? 0) : $latestAttempt->score) : 0;

                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'sort_order' => $exam->sort_order,
                    'is_blocking' => $exam->is_blocking,
                    'pass_percentage' => $exam->pass_percentage,
                    'duration' => $exam->duration,
                    'latest_attempt' => $latestAttempt ? [
                        'id' => is_array($latestAttempt) ? ($latestAttempt['id'] ?? null) : $latestAttempt->id,
                        'score' => $score,
                        'submitted_at' => is_array($latestAttempt) ? ($latestAttempt['submitted_at'] ?? null) : $latestAttempt->submitted_at,
                    ] : null,
                    'passed' => $latestAttempt ? ($score >= $exam->pass_percentage) : false,
                ];
            });
        }

        $assignmentsFormatted = [];
        if ($this->relationLoaded('assignments')) {
            $assignmentsFormatted = $this->assignments->map(function ($assignment) use ($student) {
                if ($this->attemptsMap && array_key_exists($assignment->id, $this->attemptsMap)) {
                    $latestAttempt = $this->attemptsMap[$assignment->id];
                } else {
                    $latestAttempt = $student ? \App\Models\ExamAttempt::where('exam_id', $assignment->id)
                        ->where('student_id', $student->id)
                        ->whereNotNull('submitted_at')
                        ->latest('submitted_at')
                        ->first() : null;
                }

                $score = $latestAttempt ? (is_array($latestAttempt) ? ($latestAttempt['score'] ?? 0) : $latestAttempt->score) : 0;

                return [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                    'sort_order' => $assignment->sort_order,
                    'is_blocking' => $assignment->is_blocking,
                    'pass_percentage' => $assignment->pass_percentage,
                    'duration' => $assignment->duration,
                    'latest_attempt' => $latestAttempt ? [
                        'id' => is_array($latestAttempt) ? ($latestAttempt['id'] ?? null) : $latestAttempt->id,
                        'score' => $score,
                        'submitted_at' => is_array($latestAttempt) ? ($latestAttempt['submitted_at'] ?? null) : $latestAttempt->submitted_at,
                    ] : null,
                    'passed' => $latestAttempt ? ($score >= $assignment->pass_percentage) : false,
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
            'files' => $hasAccess ? $this->whenLoaded('files') : [],
            'progress' => ($this->progressMap && array_key_exists($this->id, $this->progressMap))
                ? $this->progressMap[$this->id]
                : $this->progress,
            'has_exam' => $this->relationLoaded('exams') ? $this->exams->isNotEmpty() : \App\Models\Exam::where('lecture_id', $this->id)->where('is_assignment', false)->exists(),
            'has_assignment' => $this->relationLoaded('assignments') ? $this->assignments->isNotEmpty() : \App\Models\Exam::where('lecture_id', $this->id)->where('is_assignment', true)->exists(),
            'exams' => $examsFormatted,
            'assignments' => $assignmentsFormatted,
            'is_locked' => $user ? $accessService->isBlockedByExam($user, $this->resource, 'lecture_access') : false,
            'video_locked' => $user ? $accessService->isBlockedByExam($user, $this->resource, 'video') : false,
            'has_access' => $hasAccess,
            'section' => $this->whenLoaded('section', function () {
                return [
                    'id' => $this->section->id,
                    'title' => $this->section->title,
                    'course' => $this->section->relationLoaded('course') && $this->section->course ? [
                        'id' => $this->section->course->id,
                        'title' => $this->section->course->title,
                    ] : null,
                ];
            }),
            'instructor' => $this->whenLoaded('instructor', function () {
                return [
                    'id' => $this->instructor->id,
                    'name' => $this->instructor->name,
                ];
            }),
        ];
    }
}
