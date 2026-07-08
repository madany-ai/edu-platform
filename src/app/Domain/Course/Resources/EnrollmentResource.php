<?php

namespace App\Domain\Course\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course' => new CourseResource($this->whenLoaded('course')),
            'progress' => $this->progress,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
        ];
    }
}
