<?php

namespace App\Filament\Resources\Lectures\Pages;

use App\Filament\Resources\Lectures\LectureResource;
use App\Models\Lecture;
use App\Models\Product;
use App\Models\Bundle;
use Filament\Resources\Pages\CreateRecord;

class CreateLecture extends CreateRecord
{
    protected static string $resource = LectureResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        if ($user && $user->hasRole('instructor')) {
            $data['instructor_id'] = $user->id;
        }

        if (!empty($data['youtube_url'])) {
            $data['video_path'] = $data['youtube_url'];
        }
        unset($data['youtube_url']);

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Lecture $lecture */
        $lecture = $this->record;
        $data = $this->form->getRawState();

        if ($lecture->video_path && (str_contains($lecture->video_path, 'youtube.com') || str_contains($lecture->video_path, 'youtu.be'))) {
            $lecture->video()->create([
                'video_path' => $lecture->video_path,
                'status' => 'completed',
                'bunny_video_id' => 'youtube',
                'duration' => $lecture->duration ?? 0,
            ]);
        }

        // Handle Product & Bundle creation
        $isStandalone = !empty($data['is_standalone']);
        $hasBundles = !empty($data['bundles']) && is_array($data['bundles']);

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

            if ($hasBundles) {
                foreach ($data['bundles'] as $bundleId) {
                    $bundle = Bundle::find($bundleId);
                    if ($bundle) {
                        $bundle->products()->syncWithoutDetaching([$product->id]);
                    }
                }
            }
        }
    }
}
