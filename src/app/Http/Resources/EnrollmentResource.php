<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'course' => $this->whenLoaded('course', fn () => $this->course ? [
                'id' => $this->course->id,
                'title' => $this->course->title,
                'price' => (float) $this->course->price,
                'status' => $this->course->status,
                'instructor' => $this->course->instructor ? [
                    'id' => $this->course->instructor->id,
                    'name' => $this->course->instructor->name,
                ] : null,
            ] : null),
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'user' => $this->student->user ? [
                    'name' => $this->student->user->name,
                ] : null,
            ]),
            'status' => $this->status,
            'source' => $this->source,
            'started_at' => $this->started_at,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
        ];
    }
}
