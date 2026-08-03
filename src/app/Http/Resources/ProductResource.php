<?php

namespace App\Http\Resources;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lecture;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sellableType = match ($this->sellable_type) {
            Course::class => 'course',
            CourseSection::class => 'section',
            Lecture::class => 'lecture',
            default => 'unknown',
        };

        $sellableSummary = null;
        if ($this->relationLoaded('sellable') && $this->sellable) {
            $sellableSummary = [
                'id' => $this->sellable->id,
                'title' => $this->sellable->title ?? $this->sellable->name ?? null,
                'type' => $sellableType,
            ];

            if ($this->sellable instanceof CourseSection && $this->sellable->relationLoaded('lectures')) {
                $sellableSummary['lectures'] = $this->sellable->lectures->map(fn ($l) => [
                    'id' => $l->id,
                    'title' => $l->title,
                    'duration' => $l->duration,
                ]);
            } elseif ($this->sellable instanceof Course && $this->sellable->relationLoaded('sections')) {
                $sellableSummary['instructor'] = $this->sellable->relationLoaded('instructor') && $this->sellable->instructor ? [
                    'name' => $this->sellable->instructor->name,
                ] : null;
                $sellableSummary['sections'] = $this->sellable->sections->map(fn ($s) => [
                    'id' => $s->id,
                    'title' => $s->title,
                    'lectures' => $s->relationLoaded('lectures') ? $s->lectures->map(fn ($l) => [
                        'id' => $l->id,
                        'title' => $l->title,
                        'duration' => $l->duration,
                    ]) : [],
                    'lectures_count' => $s->relationLoaded('lectures') ? $s->lectures->count() : null,
                ]);
            } elseif ($this->sellable instanceof Lecture) {
                $sellableSummary['description'] = $this->sellable->description;
                $sellableSummary['duration'] = $this->sellable->relationLoaded('video') && $this->sellable->video 
                    ? $this->sellable->video->duration 
                    : $this->sellable->duration;
                $sellableSummary['instructor'] = $this->sellable->relationLoaded('instructor') && $this->sellable->instructor ? [
                    'name' => $this->sellable->instructor->name,
                ] : null;
                $sellableSummary['course'] = $this->sellable->relationLoaded('section') && $this->sellable->section && $this->sellable->section->relationLoaded('course') ? [
                    'title' => $this->sellable->section->course->title,
                ] : null;
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => (float) $this->price,
            'access_duration_days' => $this->access_duration_days,
            'is_active' => (bool) $this->is_active,
            'sellable_type' => $sellableType,
            'sellable' => $sellableSummary,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
