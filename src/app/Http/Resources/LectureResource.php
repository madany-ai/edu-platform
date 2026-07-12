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
            $videoData = [
                'id' => $this->video->id,
                'video_path' => $this->video->video_path,
                'status' => $this->video->status,
                'bunny_video_id' => $this->video->bunny_video_id,
                'duration' => $this->video->duration,
            ];
            
            if ($this->video->status === 'completed' && $this->video->video_path) {
                $videoPath = $this->video->video_path;
                if (str_contains($videoPath, 'youtube.com') || str_contains($videoPath, 'youtu.be')) {
                    $videoData['stream_url'] = $videoPath;
                    $videoData['stream_type'] = 'video/youtube';
                } else if (str_ends_with(strtolower($videoPath), '.mp4')) {
                    $videoData['stream_url'] = \Illuminate\Support\Facades\Storage::disk('minio')
                        ->temporaryUrl($videoPath, now()->addHours(2));
                    $videoData['stream_type'] = 'video/mp4';
                } else {
                    $videoData['stream_url'] = route('lectures.stream', ['lecture' => $this->id]);
                    $videoData['stream_type'] = 'application/x-mpegURL';
                }
            }
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
        ];
    }
}
