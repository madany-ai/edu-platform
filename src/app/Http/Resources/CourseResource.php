<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\LectureResource;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'price' => (float) $this->price,
            'thumbnail' => $this->thumbnail ? asset('storage/' . $this->thumbnail) : null,
            'status' => $this->status,
            'instructor' => $this->whenLoaded('instructor', fn () => [
                'id' => $this->instructor->id,
                'name' => $this->instructor->name,
                'email' => $this->instructor->email,
            ]),
            'sections_count' => $this->whenCounted('sections'),
            'students_count' => $this->whenCounted('enrollments'),
            'sections' => $this->whenLoaded('sections', function() {
                $progressMap = $this->resource->getAttribute('progress_map') ?? [];
                return $this->sections->map(function ($section) use ($progressMap) {
                    return [
                        'id' => $section->id,
                        'title' => $section->title,
                        'sort_order' => $section->sort_order,
                        'lectures' => $section->lectures->map(function ($lecture) use ($progressMap) {
                            return (new LectureResource($lecture))->setProgressMap($progressMap);
                        }),
                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
