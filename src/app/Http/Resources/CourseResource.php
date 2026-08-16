<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\LectureResource;

class CourseResource extends JsonResource
{
    use \App\Traits\ResolvesMinioUrls;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'price' => (float) $this->price,
            'thumbnail' => $this->resolveMinioUrl($this->thumbnail),
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
                $attemptsMap = $this->resource->getAttribute('attempts_map') ?? [];
                $attemptsCountMap = $this->resource->getAttribute('attempts_count_map') ?? [];

                $user = auth('sanctum')->user();
                $student = $user ? \App\Models\Student::where('user_id', $user->id)->first() : null;

                return $this->sections->map(function ($section) use ($progressMap, $attemptsMap, $attemptsCountMap, $student) {
                    return [
                        'id' => $section->id,
                        'title' => $section->title,
                        'sort_order' => $section->sort_order,
                        'lectures' => $section->lectures->map(function ($lecture) use ($progressMap, $attemptsMap, $attemptsCountMap, $student) {
                            return (new LectureResource($lecture))
                                ->setProgressMap($progressMap)
                                ->setAttemptsMap($attemptsMap)
                                ->setAttemptsCountMap($attemptsCountMap)
                                ->setStudent($student);
                        }),
                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
