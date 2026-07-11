<?php

namespace App\Filament\Resources\Lectures\Pages;

use App\Filament\Resources\Lectures\LectureResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLecture extends EditRecord
{
    protected static string $resource = LectureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (!empty($data['video_path']) && (str_contains($data['video_path'], 'youtube.com') || str_contains($data['video_path'], 'youtu.be'))) {
            $data['youtube_url'] = $data['video_path'];
            $data['video_path'] = null;
        }
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!empty($data['youtube_url'])) {
            $data['video_path'] = $data['youtube_url'];
        }
        unset($data['youtube_url']);
        return $data;
    }

    protected function afterSave(): void
    {
        $lecture = $this->record;
        if ($lecture->video_path && (str_contains($lecture->video_path, 'youtube.com') || str_contains($lecture->video_path, 'youtu.be'))) {
            $lecture->video()->updateOrCreate(
                ['lecture_id' => $lecture->id],
                [
                    'video_path' => $lecture->video_path,
                    'status' => 'completed',
                    'bunny_video_id' => 'youtube',
                    'duration' => $lecture->duration ?? 0,
                ]
            );
        }
    }
}
