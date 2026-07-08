<?php

namespace App\Domain\Course\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseLessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'duration_minutes' => $this->duration_minutes,
            'sort_order' => $this->sort_order,
        ];
    }
}
