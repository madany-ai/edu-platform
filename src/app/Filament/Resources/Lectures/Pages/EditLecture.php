<?php

namespace App\Filament\Resources\Lectures\Pages;

use App\Filament\Resources\Lectures\LectureResource;
use App\Models\Lecture;
use App\Models\Product;
use App\Models\Bundle;
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
        /** @var Lecture $lecture */
        $lecture = $this->record;

        if (!empty($data['video_path']) && (str_contains($data['video_path'], 'youtube.com') || str_contains($data['video_path'], 'youtu.be'))) {
            $data['youtube_url'] = $data['video_path'];
            $data['video_path'] = null;
        }

        $product = Product::where('sellable_id', $lecture->id)
            ->where('sellable_type', Lecture::class)
            ->first();

        if ($product) {
            $data['is_standalone'] = (bool) $product->is_active;
            $data['price'] = $product->price;
            $data['access_duration_days'] = $product->access_duration_days;
            $data['bundles'] = $product->bundles()->pluck('bundles.id')->toArray();
        } else {
            $data['is_standalone'] = false;
            $data['price'] = 0;
            $data['access_duration_days'] = null;
            $data['bundles'] = [];
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
        /** @var Lecture $lecture */
        $lecture = $this->record;
        $data = $this->form->getRawState();

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

        $isStandalone = !empty($data['is_standalone']);
        $hasBundles = !empty($data['bundles']) && is_array($data['bundles']);
        $product = Product::where('sellable_id', $lecture->id)
            ->where('sellable_type', Lecture::class)
            ->first();

        if ($isStandalone || $hasBundles) {
            $instructorId = $lecture->resolveInstructorId() ?? auth()->id();
            $product = Product::updateOrCreate(
                [
                    'sellable_id' => $lecture->id,
                    'sellable_type' => Lecture::class,
                ],
                [
                    'instructor_id' => $instructorId,
                    'name' => 'محاضرة: ' . $lecture->title,
                    'price' => $data['price'] ?? $lecture->price ?? 0,
                    'access_duration_days' => $data['access_duration_days'] ?? null,
                    'is_active' => true,
                ]
            );

            if (isset($data['bundles']) && is_array($data['bundles'])) {
                $product->bundles()->sync($data['bundles']);
            }
        } elseif ($product && !$hasBundles && !$isStandalone) {
            $product->update(['is_active' => false]);
        }
    }
}
