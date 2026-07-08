<?php

namespace App\Domain\Course\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'price' => (float) $this->price,
            'thumbnail' => $this->thumbnail,
            'level' => $this->level,
            'duration_minutes' => $this->duration_minutes,
            'language' => $this->language,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'instructor' => $this->whenLoaded('instructor', fn () => [
                'id' => $this->instructor->id,
                'name' => $this->instructor->name,
                'email' => $this->instructor->email,
            ]),
            'lessons_count' => $this->whenCounted('lessons'),
            'students_count' => $this->whenCounted('enrollments'),
            'lessons' => CourseLessonResource::collection($this->whenLoaded('lessons')),
            'is_published' => $this->is_published,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
