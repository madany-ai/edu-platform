<?php

namespace App\Filament\Resources\Lectures\Pages;

use App\Filament\Resources\Lectures\LectureResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLecture extends CreateRecord
{
    protected static string $resource = LectureResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!empty($data['youtube_url'])) {
            $data['video_path'] = $data['youtube_url'];
        }
        unset($data['youtube_url']);
        return $data;
    }

    protected function afterCreate(): void
    {
        $lecture = $this->record;
        if ($lecture->video_path && (str_contains($lecture->video_path, 'youtube.com') || str_contains($lecture->video_path, 'youtu.be'))) {
            $lecture->video()->create([
                'video_path' => $lecture->video_path,
                'status' => 'completed',
                'bunny_video_id' => 'youtube',
                'duration' => $lecture->duration ?? 0,
            ]);
        }
    }
}
