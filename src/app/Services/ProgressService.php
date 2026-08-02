<?php

namespace App\Services;

use App\Models\Lecture;
use App\Models\Student;
use App\Models\StudentActivity;
use App\Models\StudentStatistic;
use Illuminate\Support\Facades\DB;

class ProgressService
{
    public function updateLectureProgress(Student $student, Lecture $lecture, array $data): array
    {
        return DB::transaction(function () use ($student, $lecture, $data) {
            $lecture->loadMissing('video');
            $videoDuration = $lecture->video?->duration ?? $lecture->duration ?? 0;

            $currentTime = (int) ($data['current_time'] ?? 0);
            if ($videoDuration > 0 && $currentTime > $videoDuration) {
                $currentTime = $videoDuration;
            }

            $isCompleted = (bool) ($data['is_completed'] ?? false);
            if ($videoDuration > 0 && $currentTime < ($videoDuration * 0.8)) {
                $isCompleted = false;
            }

            $activity = StudentActivity::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'type' => 'video_progress',
                    'entity_type' => Lecture::class,
                    'entity_id' => $lecture->id,
                ],
                [
                    'metadata' => [
                        'current_time' => $currentTime,
                        'is_completed' => $isCompleted,
                    ]
                ]
            );

            $stats = StudentStatistic::firstOrCreate(['student_id' => $student->id]);

            $wasCompleted = StudentActivity::where('student_id', $student->id)
                ->where('type', 'video_completed')
                ->where('entity_type', Lecture::class)
                ->where('entity_id', $lecture->id)
                ->exists();

            if ($isCompleted && !$wasCompleted) {
                StudentActivity::create([
                    'student_id' => $student->id,
                    'type' => 'video_completed',
                    'entity_type' => Lecture::class,
                    'entity_id' => $lecture->id,
                    'metadata' => ['completed_at' => now()->toDateTimeString()],
                ]);
                $stats->completed_lectures = ($stats->completed_lectures ?? 0) + 1;
                
                $durationMinutes = $lecture && $lecture->duration ? (int)$lecture->duration : 10;
                $stats->total_watch_minutes = ($stats->total_watch_minutes ?? 0) + $durationMinutes;
            }

            $stats->last_activity_at = now();
            $stats->save();

            return [
                'progress' => $activity->metadata,
            ];
        });
    }
}
